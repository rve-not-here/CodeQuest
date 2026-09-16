<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_overview_requires_authentication(): void
    {
        $this->get(route('progress'))->assertRedirect(route('login'));
    }

    public function test_attempts_to_view_another_students_progress_fail_via_any_parameter(): void
    {
        $attacker = User::factory()->create();
        $victim = User::factory()->create();

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $parameter) {
            $this->actingAs($attacker)
                ->get(route('progress', [$parameter => $victim->id]))
                ->assertForbidden();
        }
    }

    public function test_progress_overview_empty_when_no_courses(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('progress'))
            ->assertOk()
            ->assertSee('NO COURSES');
    }

    public function test_progress_shows_untouched_active_course_as_in_progress(): void
    {
        $user = User::factory()->create();
        $this->createCourseWithMissions(1, missionCount: 2);

        $this->actingAs($user)
            ->get(route('progress'))
            ->assertOk()
            ->assertSee('0/2')
            ->assertSee('IN PROGRESS');
    }

    public function test_progress_counts_completed_missions(): void
    {
        $user = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, missionCount: 2);

        $this->complete($user, $missions[0]);

        $this->actingAs($user)
            ->get(route('progress'))
            ->assertOk()
            ->assertSee('1/2')
            ->assertSee('IN PROGRESS');
    }

    public function test_progress_marks_all_missions_done_as_ready(): void
    {
        $user = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, missionCount: 2);
        Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $missions[0]);
        $this->complete($user, $missions[1]);

        $this->actingAs($user)
            ->get(route('progress'))
            ->assertOk()
            ->assertSee('2/2')
            ->assertSee('READY');
    }

    public function test_progress_marks_passed_challenge_as_completed(): void
    {
        $user = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);
        $this->complete($user, $missions[0]);

        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'passed',
            'passed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('progress'))
            ->assertOk()
            ->assertSee('1/1')
            ->assertSee('COMPLETED');
    }

    public function test_progress_keeps_completed_even_after_a_failed_retry(): void
    {
        $user = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);
        $this->complete($user, $missions[0]);
        $this->attempt($user, $assessment, 'passed');
        $this->attempt($user, $assessment, 'failed');

        $this->actingAs($user)
            ->get(route('progress'))
            ->assertOk()
            ->assertSee('COMPLETED');
    }

    public function test_progress_marks_ahead_course_as_locked(): void
    {
        $user = User::factory()->create();
        [$firstCourse, $firstMissions] = $this->createCourseWithMissions(1, missionCount: 2);
        $firstAssessment = Assessment::factory()->create(['course_id' => $firstCourse->id]);
        $this->complete($user, $firstMissions[0]);
        $this->complete($user, $firstMissions[1]);
        $this->attempt($user, $firstAssessment, 'passed');

        $this->createCourseWithMissions(2, missionCount: 2);
        [$aheadCourse] = $this->createCourseWithMissions(3, missionCount: 1);

        $this->actingAs($user)
            ->get(route('progress'))
            ->assertOk()
            ->assertSee('COMPLETED')
            ->assertSee('IN PROGRESS')
            ->assertSee($aheadCourse->name)
            ->assertSee('0/1')
            ->assertSee('LOCKED');
    }

    public function test_progress_marks_locked_course_record_as_locked(): void
    {
        $user = User::factory()->create();
        $this->createCourseWithMissions(1, status: 'locked');

        $this->actingAs($user)
            ->get(route('progress'))
            ->assertOk()
            ->assertSee('LOCKED');
    }

    public function test_progress_marks_zero_mission_active_course_as_locked(): void
    {
        $user = User::factory()->create();
        Course::factory()->create(['status' => 'active', 'order_num' => 1]);

        $this->actingAs($user)
            ->get(route('progress'))
            ->assertOk()
            ->assertSee('0/0')
            ->assertSee('LOCKED');
    }

    /**
     * @return array{0: Course, 1: array<int, Mission>}
     */
    private function createCourseWithMissions(int $orderNum, string $status = 'active', int $missionCount = 1): array
    {
        $course = Course::factory()->create(['status' => $status, 'order_num' => $orderNum]);
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1]);

        $missions = [];
        foreach (range(1, $missionCount) as $order) {
            $missions[] = Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $order,
            ]);
        }

        return [$course, $missions];
    }

    private function complete(User $user, Mission $mission): void
    {
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => now(),
        ]);
    }

    private function attempt(User $user, Assessment $assessment, string $status): void
    {
        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => $status,
            'passed_at' => $status === 'passed' ? now() : null,
        ]);
    }
}
