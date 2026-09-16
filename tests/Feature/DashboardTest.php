<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_current_course_next_mission_and_xp(): void
    {
        $user = User::factory()->create(['username' => 'operator']);
        $course = $this->makeCourseWithMissions(3, ['order_num' => 1, 'status' => 'active']);

        // Completed only the first mission, so next is mission 2.
        $missionOne = $course->missions()->where('order_num', 1)->firstOrFail();
        Progress::factory()->create([
            'user_id' => $user->id,
            'mission_id' => $missionOne->id,
            'pts_earned' => 40,
        ]);

        $xp = app(XpService::class);
        $xp->awardCompletion($user, $missionOne);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($course->name)
            ->assertSee($course->missions()->where('order_num', 2)->firstOrFail()->title)
            ->assertSee('Continue Learning →')
            ->assertSee('XP '.$missionOne->points)
            ->assertSee('operator');
    }

    public function test_authenticated_user_sees_recent_activity(): void
    {
        $user = User::factory()->create();
        $this->makeCourseWithMissions(1, ['order_num' => 1, 'status' => 'active']);

        $user->activities()->create([
            'type' => 'login',
            'message' => 'Operator '.$user->username.' logged in',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Operator '.$user->username.' logged in');
    }

    public function test_fully_completed_student_sees_completion_message(): void
    {
        $user = User::factory()->create();
        $course = $this->makeCourseWithMissions(2, ['order_num' => 1, 'status' => 'active']);

        foreach ($course->missions as $mission) {
            Progress::factory()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'pts_earned' => 10,
            ]);
        }

        // Course completion is defined by the assessment gate (US-410): the
        // student must have passed the Boss Challenge, not merely finished
        // every mission. Finishing the missions unlocks it; passing it
        // completes the course.
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'passing_score' => 70,
        ]);
        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'passed',
            'score' => 100,
            'passed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Course complete');
    }

    public function test_continue_learning_links_to_the_resolved_mission_not_the_standby_shell(): void
    {
        $user = User::factory()->create();
        $course = $this->makeCourseWithMissions(3, ['order_num' => 1, 'status' => 'active']);

        $missionOne = $course->missions()->where('order_num', 1)->firstOrFail();
        Progress::factory()->create([
            'user_id' => $user->id,
            'mission_id' => $missionOne->id,
            'pts_earned' => 40,
        ]);
        $missionTwo = $course->missions()->where('order_num', 2)->firstOrFail();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('mission.show', $missionTwo))
            ->assertSee('Continue Learning →');
    }

    public function test_continue_learning_never_jumps_to_the_assessment_when_the_challenge_is_pending(): void
    {
        $user = User::factory()->create();
        $course = $this->makeCourseWithMissions(2, ['order_num' => 1, 'status' => 'active']);

        foreach ($course->missions as $mission) {
            Progress::factory()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'pts_earned' => 10,
            ]);
        }

        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'passing_score' => 70,
        ]);

        // Every mission is done but the Boss Challenge has not been attempted
        // yet (the US-414 edge). Continue Learning must resume at course level
        // and must NOT deep-link into the assessment.
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Boss Challenge ready')
            ->assertSee(route('learning-path'))
            ->assertDontSee(route('assessment.show', $assessment));
    }

    private function makeCourseWithMissions(int $missionCount, array $overrides = []): Course
    {
        $course = Course::factory()->create($overrides);

        foreach (range(1, $missionCount) as $order) {
            Mission::factory()->create([
                'course_id' => $course->id,
                'order_num' => $order,
                'title' => "Mission {$order}",
                'points' => $order * 10,
            ]);
        }

        return $course;
    }
}
