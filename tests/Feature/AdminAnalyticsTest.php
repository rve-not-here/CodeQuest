<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AdminAnalyticsService;
use App\Services\CourseAnalyticsService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_page_renders_system_metrics_roles_and_xp_administration(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'chief_admin']);
        $teacher = User::factory()->teacher()->create(['username' => 'the_teacher']);
        $operator = User::factory()->create(['role' => 'operator', 'username' => 'the_operator']);
        User::factory()->deactivated()->create(['role' => 'teacher', 'username' => 'off_teacher']);

        $student = User::factory()->create(['username' => 'analytics_cadet']);
        $course = $this->course('Alpha', 1);
        $mission = $course->missions->first();

        $this->xp($student, XpService::TYPE_MISSION_COMPLETED, 50, $mission);
        $this->xp($student, XpService::TYPE_ASSESSMENT_COMPLETED, 100, null, $course->assessment);
        $this->xp($student, XpService::TYPE_HINT_USED, -5, $mission);
        $this->xp($student, XpService::TYPE_WRONG_SUBMISSION, -10, $mission);

        $response = $this->actingAs($admin)->get(route('admin.analytics'));

        $response->assertOk()
            ->assertSee('System Analytics')
            ->assertSee('XP Administration')
            ->assertSee('Fleet by Role')
            ->assertSee('Users')
            ->assertSee('Curriculum')
            ->assertSee('Learning')
            ->assertSee('Per-Course Analysis')
            ->assertSee('VIEW ONLY');

        $content = $response->getContent();

        $this->assertMetric($content, 'xp_awarded', '150');
        $this->assertMetric($content, 'xp_spent', '5');
        $this->assertMetric($content, 'xp_deducted', '10');
        $this->assertMetric($content, 'xp_outstanding', '135');
        $this->assertMetric($content, 'xp_accounts', '1');

        $this->assertMetric($content, 'role_student', '1');
        $this->assertMetric($content, 'role_teacher', '2');
        $this->assertMetric($content, 'role_admin', '1');
        $this->assertMetric($content, 'role_operator', '1');

        $this->assertMetric($content, 'users_total', '5');
        $this->assertMetric($content, 'users_active', '4');
        $this->assertMetric($content, 'users_inactive', '1');
        $this->assertMetric($content, 'courses', '1');
        $this->assertMetric($content, 'sections', '1');
        $this->assertMetric($content, 'challenges', '1');
        $this->assertMetric($content, 'boss_challenges', '1');
        $this->assertMetric($content, 'active_students', '1');
        $this->assertMetric($content, 'course_completions', '0');
        $this->assertMetric($content, 'completed_challenges', '0');
        $this->assertMetric($content, 'attempts', '0');
        $this->assertMetric($content, 'passed_attempts', '0');

        $this->assertMetric($content, 'xp_entries_'.XpService::TYPE_MISSION_COMPLETED, '1');
        $this->assertMetric($content, 'xp_total_'.XpService::TYPE_MISSION_COMPLETED, '50');
        $this->assertMetric($content, 'xp_total_'.XpService::TYPE_HINT_USED, '5');
        $this->assertMetric($content, 'xp_total_'.XpService::TYPE_WRONG_SUBMISSION, '10');
    }

    public function test_fleet_completed_challenges_counts_progress_rows_not_course_passes(): void
    {
        $admin = User::factory()->admin()->create();
        $course = $this->course('Delta', 1);

        $first = User::factory()->create(['username' => 'delta_first']);
        $second = User::factory()->create(['username' => 'delta_second']);
        $this->progress($first, $course->missions->first());
        $this->progress($second, $course->missions->first());

        $metrics = app(AdminAnalyticsService::class)->overview()['system'];

        $this->assertSame(2, $metrics['completed_challenges']);
        $this->assertSame(0, $metrics['course_completions']);
        $this->assertSame(0, $metrics['attempts']);
        $this->assertNull($metrics['pass_rate']);
    }

    public function test_fleet_pass_rate_uses_distinct_students_with_terminal_attempts(): void
    {
        $admin = User::factory()->admin()->create();
        $course = $this->course('Echo', 1);

        // One pass and one terminal fail give 50%; an attempt that is neither
        // (submitted, no verdict) never counts toward either side.
        $passer = User::factory()->create(['username' => 'echo_passer']);
        $failing = User::factory()->create(['username' => 'echo_failing']);
        $this->attempt($passer, $course->assessment, 'passed', 80, now()->subDay());
        $this->attempt($failing, $course->assessment, 'failed', 30, now()->subHour());
        $this->attempt($passer, $course->assessment, 'submitted', 0, now()->subMinutes(5));

        $metrics = app(AdminAnalyticsService::class)->overview()['system'];

        $this->assertSame(50, $metrics['pass_rate']);
        $this->assertSame(3, $metrics['attempts']);
        $this->assertSame(1, $metrics['passed_attempts']);
        $this->assertSame(1, $metrics['course_completions']);
        $this->assertSame(0, $metrics['completed_challenges']);
    }

    public function test_fleet_pass_rate_is_null_when_no_terminal_attempt_exists(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['username' => 'no_terminal_cadet']);
        $course = $this->course('Foxtrot', 1);

        $this->attempt($student, $course->assessment, 'submitted', 0, now()->subDay());

        $this->assertNull(app(AdminAnalyticsService::class)->overview()['system']['pass_rate']);
    }

    public function test_xp_summary_classifies_by_type_intent_not_amount_sign(): void
    {
        $alice = User::factory()->create(['username' => 'xp_alice']);
        $bob = User::factory()->create(['username' => 'xp_bob']);
        $clamped = User::factory()->create(['username' => 'xp_clamped']);

        $course = $this->course('Bravo', 1);
        $mission = $course->missions->first();

        $this->xp($alice, XpService::TYPE_MISSION_COMPLETED, 50, $mission);
        $this->xp($alice, XpService::TYPE_ASSESSMENT_COMPLETED, 100, null, $course->assessment);
        $this->xp($alice, XpService::TYPE_HINT_USED, -5, $mission);
        $this->xp($bob, XpService::TYPE_SOLUTION_REVEALED, -30, $mission);
        $this->xp($bob, XpService::TYPE_WRONG_SUBMISSION, -10, $mission);
        // A penalty clamped to a 0-amount write (balance already zero) still
        // counts as a deduction on intent, never as a credit.
        $this->xp($clamped, XpService::TYPE_WRONG_SUBMISSION, 0, $mission);

        $summary = app(XpService::class)->fleetSummary();

        $this->assertSame(150, $summary['awarded']);
        $this->assertSame(35, $summary['spent']);
        $this->assertSame(10, $summary['deducted']);
        $this->assertSame(105, $summary['outstanding']);
        $this->assertSame(3, $summary['accounts']);

        $types = array_column($summary['by_type'], 'type');
        $this->assertSame(
            [XpService::TYPE_MISSION_COMPLETED, XpService::TYPE_ASSESSMENT_COMPLETED, XpService::TYPE_HINT_USED, XpService::TYPE_SOLUTION_REVEALED, XpService::TYPE_WRONG_SUBMISSION],
            $types,
        );

        $wrong = $summary['by_type'][4];
        $this->assertSame('deduct', $wrong['direction']);
        $this->assertSame(2, $wrong['entries']);
        $this->assertSame(10, $wrong['total']);
    }

    public function test_analytics_page_reuses_the_course_analytics_aggregates(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['username' => 'analytics_reuse_cadet']);
        $course = $this->course('Reuse', 1);
        $this->attempt($student, $course->assessment, 'passed', 80, now()->subDay());

        $response = $this->actingAs($admin)->get(route('admin.analytics'));

        $response->assertOk()->assertSee('REUSE');

        $serviceRows = app(CourseAnalyticsService::class)->overview();

        $this->assertCount(1, $serviceRows);
        $this->assertSame(1, $serviceRows->first()['buckets']['completed']);

        $content = $response->getContent();
        $this->assertSame('1', $this->courseMetricValue($content, 'completed'));
    }

    public function test_analytics_page_is_read_only_and_the_route_is_get_only(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['username' => 'xp_read_only_cadet']);
        $course = $this->course('ReadOnly', 1);
        $mission = $course->missions->first();

        $this->xp($student, XpService::TYPE_MISSION_COMPLETED, 50, $mission);

        $before = XpTransaction::query()->count();
        $sumBefore = (int) XpTransaction::query()->sum('amount');

        $this->actingAs($admin)->get(route('admin.analytics'))->assertOk();

        $this->assertSame($before, XpTransaction::query()->count());
        $this->assertSame($sumBefore, (int) XpTransaction::query()->sum('amount'));

        $methods = Route::getRoutes()->getByName('admin.analytics')->methods();
        $this->assertContains('GET', $methods);
        $this->assertNotContains('POST', $methods);
    }

    public function test_analytics_rejects_user_scoping_probes(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $parameter) {
            $this->actingAs($admin)
                ->get(route('admin.analytics').'?'.$parameter.'=1')
                ->assertForbidden();
        }
    }

    public function test_analytics_shows_empty_states_when_there_is_nothing_to_report(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.analytics'));

        $response->assertOk()
            ->assertSee('Empty ledger')
            ->assertSee('No XP transactions have been recorded yet.')
            ->assertSee('No courses')
            ->assertSee('No active course with missions is available to summarize.');

        $content = $response->getContent();

        $this->assertMetric($content, 'xp_awarded', '0');
        $this->assertMetric($content, 'xp_spent', '0');
        $this->assertMetric($content, 'xp_deducted', '0');
        $this->assertMetric($content, 'xp_outstanding', '0');
        $this->assertMetric($content, 'xp_accounts', '0');
        $this->assertMetric($content, 'role_admin', '1');
        $this->assertMetric($content, 'role_operator', '0');
    }

    public function test_analytics_service_normalizes_roles_to_the_full_set(): void
    {
        User::factory()->admin()->create();
        User::factory()->create(['role' => 'operator', 'username' => 'solo_operator']);

        $overview = app(AdminAnalyticsService::class)->overview();

        $this->assertSame(['student' => 0, 'teacher' => 0, 'admin' => 1, 'operator' => 1], $overview['roles']);
    }

    private function metricValue(string $content, string $metric): ?string
    {
        if (preg_match('/data-metric="'.preg_quote($metric, '/').'"[^>]*>(\d+)</', $content, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    private function assertMetric(string $content, string $metric, string $expected): void
    {
        $this->assertSame($expected, $this->metricValue($content, $metric), "metric {$metric}");
    }

    private function courseMetricValue(string $content, string $metric): ?string
    {
        if (preg_match('/data-course-metric="'.preg_quote($metric, '/').'"[^>]*>(\d+)</', $content, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /**
     * An active course with one section and one mission, so the analytics
     * universe includes it.
     */
    private function course(string $name, int $order): Course
    {
        $course = Course::factory()->create([
            'slug' => Str::slug($name).'-'.$order,
            'name' => $name,
            'order_num' => $order,
            'status' => 'active',
        ]);

        $section = Section::factory()->create([
            'course_id' => $course->id,
            'order_num' => 1,
        ]);

        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'title' => 'Mission '.$name.' 1',
        ]);

        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'title' => $name.' Boss Challenge',
            'passing_score' => 70,
            'status' => 'active',
        ]);

        return $course->load('assessment', 'missions');
    }

    private function attempt(User $user, ?Assessment $assessment, string $status, int $score, Carbon $at): void
    {
        $this->assertNotNull($assessment);

        AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => $status,
            'score' => $score,
            'passed_at' => $status === 'passed' ? $at : null,
            'submitted_at' => $at,
            'created_at' => $at,
        ]);
    }

    private function xp(User $user, string $type, int $amount, ?Mission $mission = null, ?Assessment $assessment = null): void
    {
        XpTransaction::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission?->id,
            'assessment_id' => $assessment?->id,
            'amount' => $amount,
            'type' => $type,
        ]);
    }

    private function progress(User $user, Mission $mission): void
    {
        Progress::factory()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);
    }
}
