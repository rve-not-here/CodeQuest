<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\AssessmentService;
use App\Services\ChallengeAnalyticsService;
use App\Support\ReportFilters;
use App\Support\SchemaAvailability;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A deployed database can lag behind the code: the schema is applied on its
 * own, explicitly approved step. Optional columns and tables must degrade,
 * never crash.
 *
 * Before this tolerance existed, a missing `the404_activity.mission_id`
 * turned every Activity write into a 500 (a logout could not be recorded)
 * and a missing `the404_assessment_behavior_tests` turned every Boss
 * Challenge submission into a 500.
 *
 * The lag is real here, not faked: the columns and the table are actually
 * dropped from the test schema for the duration of each test, and the
 * surrounding transaction rolls the schema back.
 */
class SchemaLagToleranceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SchemaAvailability::reset();
    }

    protected function tearDown(): void
    {
        SchemaAvailability::reset();

        parent::tearDown();
    }

    public function test_activity_writes_succeed_while_the_attribution_column_is_pending(): void
    {
        Schema::table('the404_activity', function ($table): void {
            $table->dropIndex(['mission_id', 'type']);
            $table->dropColumn('mission_id');
        });

        $user = User::factory()->create();

        $activity = app(ActivityService::class)->record($user, [
            'type' => 'logout',
            'message' => 'Operator '.$user->username.' logged out',
        ]);

        $this->assertSame('logout', $activity->type);
        $this->assertDatabaseHas('the404_activity', [
            'user_id' => $user->id,
            'type' => 'logout',
        ]);
        $this->assertNull($activity->fresh()->mission_id);
    }

    public function test_mission_scoped_activity_keeps_working_while_the_attribution_column_is_pending(): void
    {
        Schema::table('the404_activity', function ($table): void {
            $table->dropIndex(['mission_id', 'type']);
            $table->dropColumn('mission_id');
        });

        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $activity = app(ActivityService::class)->record($user, [
            'type' => 'mission_completed',
            'message' => 'Completed a challenge',
            'pts' => 25,
            'mission_id' => $mission->id,
        ]);

        $this->assertSame(25, $activity->pts);
        $this->assertNull($activity->fresh()->mission_id);
    }

    public function test_boss_grading_keeps_the_legacy_path_while_the_hidden_test_table_is_pending(): void
    {
        Http::preventStrayRequests();
        $this->seed(AchievementSeeder::class);
        Schema::drop('the404_assessment_behavior_tests');
        Schema::table('the404_assessment_attempts', function ($table): void {
            $table->dropColumn(['tests_total', 'tests_passed', 'failure_feedback']);
        });

        $student = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        Progress::factory()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
        ]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'passing_score' => 70,
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'ALPHA']]),
        ]);

        $service = app(AssessmentService::class);
        $attempt = $service->beginAttempt($student, $course);
        $attempt = $service->submitAndEvaluateAttempt($student, $attempt, 'ALPHA');

        $this->assertSame('passed', $attempt->status);
        $this->assertSame(100, $attempt->score);
        $this->assertNull($attempt->tests_total);
        $this->assertNull($attempt->tests_passed);
        $this->assertNull($attempt->failure_feedback);
    }

    public function test_challenge_analytics_skip_ungradable_rows_while_the_attribution_column_is_pending(): void
    {
        Schema::table('the404_activity', function ($table): void {
            $table->dropIndex(['mission_id', 'type']);
            $table->dropColumn('mission_id');
        });

        $user = User::factory()->create();
        Activity::query()->create([
            'user_id' => $user->id,
            'type' => 'grading_unavailable',
            'message' => 'Grading infrastructure was unavailable.',
            'pts' => 0,
        ]);

        $summary = app(ChallengeAnalyticsService::class)->summarize(null, null, ReportFilters::fromArray([], []));

        $this->assertSame(0, $summary['attempts']);
        $this->assertSame([], $summary['by_challenge']);
    }
}
