<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Services\CourseAnalyticsService;
use App\Services\TeacherDashboardService;
use App\Services\TimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

class TeacherDashboardTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_dashboard_metrics_are_composed_from_the_roster_analytics_and_attention_services(): void
    {
        $first = $this->course('Alpha', 1, 2, 100);
        $second = $this->course('Beta', 2, 2, 70);

        $passed = $this->student('passed_course');
        $this->completeAll($passed, $this->missions($first));
        $this->attempt($passed, $first->assessment, 'passed', 80);

        $failing = $this->student('failing_course');
        $this->completeAll($failing, $this->missions($first));
        $this->attempt($failing, $first->assessment, 'passed', 80);
        $this->completeAll($failing, $this->missions($second));
        $this->attempt($failing, $second->assessment, 'failed', 40);
        $this->attempt($failing, $second->assessment, 'failed', 40);

        $this->student('quiet');

        $metrics = $this->overview()['metrics'];

        $this->assertEquals(3, $metrics['total_students']);
        $this->assertEquals(2, $metrics['active_students']);
        $this->assertEquals(1, $metrics['courses_in_progress']);
        $this->assertEquals(2, $metrics['assessments_passed']);
        $this->assertEquals(1, $metrics['needs_attention']);
    }

    public function test_dashboard_metrics_agree_with_the_component_services(): void
    {
        $first = $this->course('Alpha', 1, 2, 100);
        $second = $this->course('Beta', 2, 2, 70);

        $passed = $this->student('passed_course');
        $this->completeAll($passed, $this->missions($first));
        $this->attempt($passed, $first->assessment, 'passed', 80);

        $failing = $this->student('failing_course');
        $this->completeAll($failing, $this->missions($first));
        $this->attempt($failing, $first->assessment, 'passed', 80);
        $this->completeAll($failing, $this->missions($second));
        $this->attempt($failing, $second->assessment, 'failed', 40);
        $this->attempt($failing, $second->assessment, 'failed', 40);

        $overview = $this->overview();
        $summary = app(CourseAnalyticsService::class)->overview();

        $this->assertSame($summary->pluck('course.id')->all(), $overview['assessment_summary']->pluck('course.id')->all());
        $this->assertEquals(
            $summary->filter(
                fn (array $row): bool => $row['buckets']['in_progress'] > 0 || $row['buckets']['assessment_ready'] > 0,
            )->count(),
            $overview['metrics']['courses_in_progress'],
        );
        $this->assertSame(
            (int) AssessmentAttempt::query()->where('status', 'passed')->count(),
            $overview['metrics']['assessments_passed'],
        );
    }

    public function test_active_students_respects_the_fourteen_day_window(): void
    {
        $course = $this->course('Window', 1, 2, 100);

        $fresh = $this->student('fresh_today');
        $this->complete($fresh, $this->missions($course)[0], now());

        $thirteen = $this->student('edge_thirteen');
        $this->complete($thirteen, $this->missions($course)[0], $this->daysAgo(13));

        $fourteen = $this->student('edge_fourteen');
        $this->complete($fourteen, $this->missions($course)[0], $this->daysAgo(14));

        $fifteen = $this->student('edge_fifteen');
        $this->complete($fifteen, $this->missions($course)[0], $this->daysAgo(15));

        $this->student('never_learnt');

        $this->assertEquals(3, $this->overview()['metrics']['active_students']);
    }

    public function test_dashboard_page_renders_metrics_recent_activity_and_assessment_summary(): void
    {
        $course = $this->course('Alpha', 1, 2, 100);
        $student = $this->student('dashboard_cadet');

        $this->complete($student, $this->missions($course)[0], $this->daysAgo(2));
        $this->attempt($student, $course->assessment, 'failed', 40, $this->daysAgo(1));

        $teacher = $this->teacher();
        $this->classroomFor($teacher, [$student], [$course]);

        $this->actingAs($teacher)
            ->get(route('students'))
            ->assertOk()
            ->assertSee('Teacher Dashboard')
            ->assertSee('Total students')
            ->assertSee('Active (14d)')
            ->assertSee('Courses in progress')
            ->assertSee('Assessments passed')
            ->assertSee('Needs attention')
            ->assertSee('Recent Activity')
            ->assertSee('Assessment Summary')
            ->assertSee('VIEW ALL')
            ->assertSee('FULL ANALYTICS')
            ->assertSee('Student Roster')
            ->assertSee('dashboard_cadet')
            ->assertSee(route('needs-attention'))
            ->assertSee(route('activity'))
            ->assertSee(route('course-analytics'));
    }

    public function test_dashboard_shows_empty_states_for_a_fresh_fleet(): void
    {
        $this->actingAs($this->teacher())
            ->get(route('students'))
            ->assertOk()
            ->assertSee('Teacher Dashboard')
            ->assertSee('No learning activity in the last 14 days.')
            ->assertSee('No active course with missions to summarize.')
            ->assertSee('NO TRAINEES');
    }

    public function test_roster_last_activity_matches_the_activity_strip_beat_vocabulary(): void
    {
        $course = $this->course('Alpha', 1, 2, 100);
        $student = $this->student('pass_cadet');

        $missions = $this->missions($course);
        $this->complete($student, $missions[0], $this->daysAgo(3));
        $this->complete($student, $missions[1], $this->daysAgo(3));
        $this->attempt($student, $course->assessment, 'passed', 100, now());

        $newest = app(TimelineService::class)->events($student, 1)->first();

        $this->assertNotNull($newest);
        $this->assertSame('Boss Challenge passed: Alpha Boss Challenge (score 100)', $newest['label']);

        $teacher = $this->teacher();
        $this->classroomFor($teacher, [$student], [$course]);

        $dashboard = $this->actingAs($teacher)->get(route('students'))->assertOk();
        $dashboard->assertSee($newest['label']);

        $roster = $this->rosterBody($dashboard->getContent());
        $this->assertStringContainsString($newest['label'], $roster);
    }

    /**
     * @return array<string, mixed>
     */
    private function overview(): array
    {
        return app(TeacherDashboardService::class)->overview();
    }

    private function daysAgo(int $days): Carbon
    {
        return now()->startOfDay()->subDays($days)->addHours(6);
    }

    /**
     * The /students page carries system-wide dashboard strips above the roster
     * (US-609). Roster assertions run against this slice so a label echoed by
     * the recent-activity or assessment-summary strips can never bleed into a
     * roster-column assertion.
     */
    private function rosterBody(string $content): string
    {
        $from = strpos($content, '<form method="GET"');

        return $from === false ? '' : substr($content, $from);
    }

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function student(string $username): User
    {
        return User::factory()->create(['username' => $username]);
    }

    private function course(string $name, int $order, int $missionCount, int $passingScore): Course
    {
        $course = Course::factory()->create([
            'slug' => Str::slug($name).'-'.$order,
            'name' => $name,
            'order_num' => $order,
            'status' => 'active',
        ]);

        for ($i = 1; $i <= $missionCount; $i++) {
            Mission::factory()->create([
                'course_id' => $course->id,
                'order_num' => $i,
                'title' => "Mission {$name} {$i}",
            ]);
        }

        Assessment::factory()->create([
            'course_id' => $course->id,
            'title' => "{$name} Boss Challenge",
            'passing_score' => $passingScore,
            'status' => 'active',
        ]);

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
            $this->complete($user, $mission, now());
        }
    }

    private function complete(User $user, Mission $mission, Carbon $at): void
    {
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => $at,
        ]);
    }

    private function attempt(User $user, ?Assessment $assessment, string $status, int $score, ?Carbon $at = null): void
    {
        $this->assertNotNull($assessment);

        AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => $status,
            'score' => $score,
            'passed_at' => $status === 'passed' ? ($at ?? now()) : null,
            'submitted_at' => $at ?? now(),
            'created_at' => $at ?? now(),
        ]);
    }
}
