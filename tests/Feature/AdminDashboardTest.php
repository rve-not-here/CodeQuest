<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_metrics_are_derived_from_live_records(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->teacher()->create(['username' => 'the_teacher']);
        User::factory()->create(['role' => 'operator', 'username' => 'the_operator']);
        User::factory()->deactivated()->create(['role' => 'teacher', 'username' => 'off_teacher']);

        $student = User::factory()->create(['username' => 'dashboard_cadet']);
        $course = $this->course('Alpha', 1);
        $this->attempt($student, $course->assessment, 'passed', 80, now()->subDay());
        $this->attempt($student, $course->assessment, 'failed', 40, now()->subDays(2));

        $metrics = app(AdminDashboardService::class)->overview()['metrics'];

        $this->assertSame(5, $metrics['users_total']);
        $this->assertSame(4, $metrics['users_active']);
        $this->assertSame(1, $metrics['users_inactive']);
        $this->assertSame(1, $metrics['courses']);
        $this->assertSame(1, $metrics['sections']);
        $this->assertSame(1, $metrics['challenges']);
        $this->assertSame(1, $metrics['boss_challenges']);
        $this->assertSame(2, $metrics['attempts']);
        $this->assertSame(1, $metrics['passed_attempts']);
        $this->assertSame(1, $metrics['active_students']);
        $this->assertSame(1, $metrics['course_completions']);
    }

    public function test_dashboard_page_renders_stat_panels_and_recent_system_activity(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'chief_admin']);
        $student = User::factory()->create(['username' => 'dashboard_cadet']);
        $course = $this->course('Alpha', 1);
        $this->attempt($student, $course->assessment, 'passed', 80, now()->subDay());

        $this->audit($admin, 'Deactivated operator off_duty', now()->subDays(2));
        $this->audit($admin, 'Promoted cadet to teacher', now()->subDay());

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Admin Console')
            ->assertSee('Accounts')
            ->assertSee('Curriculum')
            ->assertSee('Assessments')
            ->assertSee('Learning')
            ->assertSee('Recent System Activity')
            ->assertSee('Deactivated operator off_duty')
            ->assertSee('Promoted cadet to teacher');

        $content = $response->getContent();

        $this->assertMetric($content, 'users_total', '2');
        $this->assertMetric($content, 'users_active', '2');
        $this->assertMetric($content, 'users_inactive', '0');
        $this->assertMetric($content, 'courses', '1');
        $this->assertMetric($content, 'sections', '1');
        $this->assertMetric($content, 'challenges', '1');
        $this->assertMetric($content, 'boss_challenges', '1');
        $this->assertMetric($content, 'attempts', '1');
        $this->assertMetric($content, 'passed_attempts', '1');
        $this->assertMetric($content, 'active_students', '1');
        $this->assertMetric($content, 'course_completions', '1');

        // Newest audit row renders first (created_at desc, id desc tiebreak).
        $this->assertLessThan(
            strpos($content, 'Deactivated operator off_duty'),
            strpos($content, 'Promoted cadet to teacher'),
        );
    }

    public function test_dashboard_shows_an_empty_state_when_no_audit_rows_exist(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['username' => 'audit_is_empty_cadet']);

        // Timeline-source rows that are NOT admin-audit rows. If recent() was
        // ever rewired to a timeline source, this row would surface as activity
        // and the test would fail instead of passing on a silently broken query.
        $student->activities()->create([
            'type' => 'mission_completed',
            'message' => 'Audited student completed a hidden mission',
            'pts' => 100,
        ]);

        $this->assertTrue(app(AdminAuditService::class)->recent()->isEmpty());

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('No administrative actions have been recorded yet.')
            ->assertDontSee('Audited student completed a hidden mission');
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

    /**
     * An active course with one section and one mission, so the analytics
     * universe includes it for the course-completions aggregate.
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

        return $course->load('assessment');
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

    private function audit(User $admin, string $summary, Carbon $at): void
    {
        (new AdminAudit)->forceFill([
            'admin_user_id' => $admin->id,
            'admin_username' => $admin->username,
            'action' => 'user.deactivate',
            'target_type' => 'user',
            'target_id' => 1,
            'summary' => $summary,
            'result' => 'success',
            'created_at' => $at,
        ])->save();
    }
}
