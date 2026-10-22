<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Assessment;
use App\Models\AssessmentBehaviorTest;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AssessmentService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Boss Challenge grading with hidden behavioural checks.
 *
 * The pattern rules stay the structural gate; hidden tests prove behaviour.
 * The grader transport is faked at the HTTP layer — the runner itself is
 * covered by grader/ unit suites. Every assertion here is about the
 * Laravel-side contract: a wrong-but-pattern-matching submission fails,
 * grader unavailability is never a student failure, and legacy pattern-only
 * assessments keep evaluating without touching the grader.
 */
class AssessmentBehavioralGradingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);

        config()->set('grader.url', 'http://grader.internal');
        config()->set('grader.token', 'test-token');
    }

    /**
     * A course whose single mission is complete, so the Boss Challenge is
     * unlocked. The assessment carries one passing pattern rule unless the
     * caller supplies its own.
     *
     * @param  list<string>  $ruleValues
     * @return array{student: User, course: Course, assessment: Assessment}
     */
    private function scenario(array $ruleValues = ['ALPHA'], int $passingScore = 70): array
    {
        $student = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
        ]);
        Progress::factory()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
        ]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'passing_score' => $passingScore,
            'grading_rule' => json_encode(array_map(
                fn (string $value): array => ['type' => 'contains', 'value' => $value],
                $ruleValues,
            )),
        ]);

        return compact('student', 'course', 'assessment');
    }

    /** @return array<string, mixed> */
    private function passedResult(int $total = 1): array
    {
        return [
            'protocol_version' => 2,
            'status' => 'passed',
            'tests_total' => $total,
            'tests_passed' => $total,
            'duration_ms' => 24,
            'error_type' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function failedResult(int $total = 1, int $passed = 0): array
    {
        return [
            'protocol_version' => 2,
            'status' => 'failed',
            'tests_total' => $total,
            'tests_passed' => $passed,
            'duration_ms' => 24,
            'error_type' => 'assertion',
        ];
    }

    public function test_legacy_pattern_only_assessment_evaluates_without_the_grader(): void
    {
        Http::preventStrayRequests();
        ['student' => $student, 'assessment' => $assessment] = $this->scenario();
        $service = app(AssessmentService::class);

        $attempt = $service->beginAttempt($student, $assessment->course);
        $attempt = $service->submitAndEvaluateAttempt($student, $attempt, 'ALPHA');

        $this->assertSame('passed', $attempt->status);
        $this->assertSame(100, $attempt->score);
        $this->assertNull($attempt->tests_total);
        $this->assertNull($attempt->tests_passed);
        $this->assertSame(0, AssessmentBehaviorTest::query()->count());
    }

    public function test_hidden_test_failure_fails_even_when_every_pattern_rule_passes(): void
    {
        Http::fake(['*' => Http::response($this->failedResult(), 200)]);
        ['student' => $student, 'assessment' => $assessment] = $this->scenario();
        AssessmentBehaviorTest::factory()->create(['assessment_id' => $assessment->id]);
        $this->assertSame(1, $assessment->behaviorTests()->count());
        $service = app(AssessmentService::class);

        $attempt = $service->beginAttempt($student, $assessment->course);
        $attempt = $service->submitAndEvaluateAttempt($student, $attempt, 'ALPHA');

        $this->assertSame('failed', $attempt->status);
        // One pattern rule passed, one hidden test failed: 50%.
        $this->assertSame(50, $attempt->score);
        $this->assertSame(1, $attempt->tests_total);
        $this->assertSame(0, $attempt->tests_passed);
        $this->assertNotNull($attempt->failure_feedback);
        $this->assertStringContainsString('hidden tests passed', (string) $attempt->failure_feedback);

        $this->assertSame(0, XpTransaction::query()
            ->where('user_id', $student->id)
            ->where('type', 'assessment_completed')
            ->count());

        $this->assertDatabaseHas('the404_activity', [
            'user_id' => $student->id,
            'type' => 'assessment_failed',
        ]);
    }

    public function test_all_patterns_and_all_hidden_tests_passing_clears_the_boss(): void
    {
        Http::fake(['*' => Http::response($this->passedResult(2), 200)]);
        ['student' => $student, 'assessment' => $assessment] = $this->scenario(['ALPHA', 'BETA']);
        AssessmentBehaviorTest::factory()->count(2)->sequence(
            ['order_num' => 1],
            ['order_num' => 2],
        )->create(['assessment_id' => $assessment->id]);
        $service = app(AssessmentService::class);

        $attempt = $service->beginAttempt($student, $assessment->course);
        $attempt = $service->submitAndEvaluateAttempt($student, $attempt, 'ALPHA BETA');

        $this->assertSame('passed', $attempt->status);
        $this->assertSame(100, $attempt->score);
        $this->assertSame(2, $attempt->tests_total);
        $this->assertSame(2, $attempt->tests_passed);
        $this->assertNull($attempt->failure_feedback);
        $this->assertNotNull($attempt->passed_at);

        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'assessment_id' => $assessment->id,
            'type' => 'assessment_completed',
        ]);
        $this->assertDatabaseHas('the404_activity', [
            'user_id' => $student->id,
            'type' => 'assessment_passed',
        ]);
    }

    public function test_grader_unavailability_leaves_the_attempt_submitted_without_a_verdict(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('cURL error 28: timeout');
        });
        ['student' => $student, 'assessment' => $assessment] = $this->scenario();
        AssessmentBehaviorTest::factory()->create(['assessment_id' => $assessment->id]);
        $service = app(AssessmentService::class);

        $attempt = $service->beginAttempt($student, $assessment->course);
        $attempt = $service->submitAndEvaluateAttempt($student, $attempt, 'ALPHA');

        $this->assertSame('submitted', $attempt->status);
        $this->assertNull($attempt->score);
        $this->assertStringContainsString('unavailable', (string) $attempt->failure_feedback);
        $this->assertSame(0, Activity::query()->where('user_id', $student->id)->count());
        $this->assertSame(0, XpTransaction::query()->where('user_id', $student->id)->count());
    }

    public function test_resubmitting_after_an_outage_records_the_verdict(): void
    {
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;

            if ($calls === 1) {
                throw new ConnectionException('cURL error 28: timeout');
            }

            return Http::response($this->passedResult(), 200);
        });
        ['student' => $student, 'assessment' => $assessment] = $this->scenario();
        AssessmentBehaviorTest::factory()->create(['assessment_id' => $assessment->id]);
        $service = app(AssessmentService::class);

        $attempt = $service->beginAttempt($student, $assessment->course);
        $attempt = $service->submitAndEvaluateAttempt($student, $attempt, 'ALPHA');
        $this->assertSame('submitted', $attempt->status);

        $attempt = $service->submitAndEvaluateAttempt($student, $attempt, 'ALPHA');
        $this->assertSame('passed', $attempt->status);
        $this->assertSame(100, $attempt->score);
        $this->assertDatabaseHas('the404_activity', [
            'user_id' => $student->id,
            'type' => 'assessment_passed',
        ]);
    }

    public function test_the_controller_reports_unavailable_without_claiming_a_failure(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('cURL error 28: timeout');
        });
        ['student' => $student, 'assessment' => $assessment] = $this->scenario();
        AssessmentBehaviorTest::factory()->create(['assessment_id' => $assessment->id]);
        app(AssessmentService::class)->beginAttempt($student, $assessment->course);

        $this->actingAs($student)
            ->post(route('assessment.submit', $assessment), ['code' => 'ALPHA'])
            ->assertRedirect(route('assessment.show', $assessment))
            ->assertSessionHas('assessment_error', fn (array $error): bool => $error['title'] === 'Grading unavailable');

        $this->assertDatabaseHas('the404_assessment_attempts', [
            'user_id' => $student->id,
            'assessment_id' => $assessment->id,
            'status' => 'submitted',
        ]);
    }
}
