<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\CourseAnalyticsService;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

class CourseAnalyticsTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_course_analytics_requires_authentication(): void
    {
        $this->get(route('course-analytics'))->assertRedirect(route('login'));
    }

    public function test_course_analytics_is_restricted_to_teachers(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('course-analytics'))
            ->assertForbidden();
    }

    public function test_course_analytics_rejects_user_scoping_probes(): void
    {
        $this->buildFleet();

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $parameter) {
            $this->actingAs($this->teacher())
                ->get(route('course-analytics', [$parameter => 1]))
                ->assertForbidden();
        }
    }

    public function test_all_metrics_derive_from_the_real_roster(): void
    {
        $this->buildFleet();

        $rows = app(CourseAnalyticsService::class)->overview()->keyBy(fn (array $row): string => $row['course']->name);

        $this->assertCount(3, $rows);

        $alpha = $rows['Alpha Analytics'];
        $this->assertSame(6, $alpha['fleet']);
        $this->assertSame(5, $alpha['engaged']);
        $this->assertSame(['completed' => 3, 'in_progress' => 1, 'assessment_ready' => 1, 'not_started' => 1], $alpha['buckets']);
        $this->assertSame(87, $alpha['avg_completion']);
        $this->assertSame(75, $alpha['pass_rate']);
        $this->assertSame([0, 1, 0, 0, 4], $alpha['distribution']);

        $bravo = $rows['Bravo Analytics'];
        $this->assertSame(6, $bravo['fleet']);
        $this->assertSame(2, $bravo['engaged']);
        $this->assertSame(['completed' => 1, 'in_progress' => 1, 'assessment_ready' => 0, 'not_started' => 4], $bravo['buckets']);
        $this->assertSame(75, $bravo['avg_completion']);
        $this->assertSame(100, $bravo['pass_rate']);
        $this->assertSame([0, 0, 1, 0, 1], $bravo['distribution']);

        $delta = $rows['Delta Analytics'];
        $this->assertSame(6, $delta['fleet']);
        $this->assertSame(1, $delta['engaged']);
        $this->assertSame(['completed' => 0, 'in_progress' => 1, 'assessment_ready' => 0, 'not_started' => 5], $delta['buckets']);
        $this->assertSame(100, $delta['avg_completion']);
        $this->assertNull($delta['pass_rate']);
        $this->assertSame([0, 0, 0, 0, 1], $delta['distribution']);
    }

    public function test_the_metrics_match_the_student_facing_state_services(): void
    {
        $this->buildFleet();

        $rows = app(CourseAnalyticsService::class)->overview();
        $students = User::query()->where('role', 'student')->get();
        $assessments = app(AssessmentService::class);
        $dashboard = app(DashboardService::class);

        foreach ($rows as $row) {
            $course = $row['course'];

            $expected = ['completed' => 0, 'in_progress' => 0, 'assessment_ready' => 0, 'not_started' => 0];
            $percents = [];

            foreach ($students as $student) {
                $progress = $dashboard->courseProgress($student, $course);
                $engaged = $progress['completed'] > 0 && $progress['total'] > 0;

                // Classify this student with the exact SAME predicates the
                // student-facing pages use (hasPassed for COMPLETED, isUnlocked
                // for READY, courseProgress for engagement), then assert the
                // SQL aggregation produced the same bucket counts (US-605's
                // no-duplicate-formula verdict applied to the whole fleet).
                if ($assessments->hasPassed($student, $course)) {
                    $expected['completed']++;
                } elseif ($assessments->isUnlocked($student, $course)) {
                    $expected['assessment_ready']++;
                } elseif ($engaged) {
                    $expected['in_progress']++;
                } else {
                    $expected['not_started']++;
                }

                if ($engaged) {
                    $percents[] = $progress['percent'];
                }
            }

            $this->assertSame($expected, $row['buckets'], "buckets for {$course->name}");
            $this->assertSame(
                count($percents) > 0 ? (int) round(array_sum($percents) / count($percents)) : null,
                $row['avg_completion'],
                "average completion for {$course->name}",
            );
        }
    }

    public function test_locked_draft_and_zero_mission_courses_are_excluded(): void
    {
        $this->buildFleet();

        $names = app(CourseAnalyticsService::class)
            ->overview()
            ->pluck('course.name');

        $this->assertTrue($names->contains('Alpha Analytics'));
        $this->assertFalse($names->contains('Zero Sim'));
        $this->assertFalse($names->contains('Locked Sim'));
    }

    public function test_the_page_renders_every_course_summary_from_real_values(): void
    {
        $this->buildFleet();

        $alpha = Course::query()->where('name', 'Alpha Analytics')->firstOrFail();
        $bravo = Course::query()->where('name', 'Bravo Analytics')->firstOrFail();
        $delta = Course::query()->where('name', 'Delta Analytics')->firstOrFail();
        $students = User::query()->where('role', 'student')->get()->all();

        $teacher = $this->teacher();
        $this->classroomFor($teacher, $students, [$alpha, $bravo, $delta]);

        $this->actingAs($teacher)
            ->get(route('course-analytics'))
            ->assertOk()
            ->assertSee('ALPHA ANALYTICS')
            ->assertSee('STUDENTS 5/6')
            ->assertSee('COMPLETED 3')
            ->assertSee('IN PROGRESS 1')
            ->assertSee('READY 1')
            ->assertSee('NOT STARTED 1')
            ->assertSee('Average challenge completion')
            ->assertSee('87%')
            ->assertSeeInOrder(['Boss Challenge pass rate', '75%'])
            ->assertSee('Completion distribution')
            ->assertSee('0-24%')
            ->assertSee('100%');
    }

    public function test_the_page_shows_the_empty_state_for_a_fresh_fleet(): void
    {
        $this->actingAs($this->teacher())
            ->get(route('course-analytics'))
            ->assertOk()
            ->assertSee('No courses');
    }

    /**
     * A realistic fixture: courses are sequential, so a student only has
     * progress on their current or already-passed courses. Six students:
     *  s1 nothing, s2 mid-course-A, s3 finished A (ready, one failed attempt),
     *  s4 passed A with a later failed retry, s5 passed A and mid-B,
     *  s6 passed A+B and finished D (whose challenge is still draft).
     */
    private function buildFleet(): void
    {
        $s1 = $this->student();
        $s2 = $this->student();
        $s3 = $this->student();
        $s4 = $this->student();
        $s5 = $this->student();
        $s6 = $this->student();

        $alpha = $this->course('Alpha Analytics', 1, 3);
        $bravo = $this->course('Bravo Analytics', 2, 2);
        $delta = $this->course('Delta Analytics', 3, 1, 'draft');
        $zero = $this->course('Zero Sim', 4, 0, null);
        $locked = $this->course('Locked Sim', 5, 1, null, 'locked');

        $alphaMissions = $this->missions($alpha);
        $bravoMissions = $this->missions($bravo);
        $deltaMissions = $this->missions($delta);

        $this->complete($s2, $alphaMissions[0]);

        $this->completeAll($s3, $alphaMissions);
        $this->attempt($s3, $alpha->assessment, 'failed', 20);

        $this->completeAll($s4, $alphaMissions);
        $this->attempt($s4, $alpha->assessment, 'passed', 80);
        $this->attempt($s4, $alpha->assessment, 'failed', 40);

        $this->completeAll($s5, $alphaMissions);
        $this->attempt($s5, $alpha->assessment, 'passed', 90);
        $this->complete($s5, $bravoMissions[0]);

        $this->completeAll($s6, $alphaMissions);
        $this->attempt($s6, $alpha->assessment, 'passed', 70);
        $this->completeAll($s6, $bravoMissions);
        $this->attempt($s6, $bravo->assessment, 'passed', 85);
        $this->completeAll($s6, $deltaMissions);
    }

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function student(): User
    {
        return User::factory()->create();
    }

    private function course(string $name, int $order, int $missionCount, ?string $assessmentStatus = 'active', string $status = 'active'): Course
    {
        $course = Course::factory()->create([
            'slug' => Str::slug($name).'-'.$order,
            'name' => $name,
            'order_num' => $order,
            'status' => $status,
        ]);

        for ($i = 1; $i <= $missionCount; $i++) {
            Mission::factory()->create([
                'course_id' => $course->id,
                'order_num' => $i,
                'title' => "Mission {$name} {$i}",
            ]);
        }

        if ($assessmentStatus !== null) {
            Assessment::factory()->create([
                'course_id' => $course->id,
                'title' => "{$name} Boss Challenge",
                'passing_score' => 60,
                'status' => $assessmentStatus,
            ]);
        }

        return $course->load('assessment');
    }

    /**
     * @return array<int, Mission>
     */
    private function missions(Course $course): array
    {
        return $course->missions()->orderBy('order_num')->get()->all();
    }

    /**
     * @param  array<int, Mission>  $missions
     */
    private function completeAll(User $user, array $missions): void
    {
        foreach ($missions as $mission) {
            $this->complete($user, $mission);
        }
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

    private function attempt(User $user, ?Assessment $assessment, string $status, int $score): void
    {
        $this->assertNotNull($assessment);

        AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => $status,
            'score' => $score,
            'passed_at' => $status === 'passed' ? now() : null,
            'submitted_at' => now(),
        ]);
    }
}
