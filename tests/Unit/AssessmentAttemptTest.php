<?php

namespace Tests\Unit;

use App\Exceptions\AssessmentAttemptAccessDeniedException;
use App\Exceptions\AssessmentAttemptStateException;
use App\Exceptions\AssessmentNotUnlockedException;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AssessmentService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AssessmentAttemptTest extends TestCase
{
    use RefreshDatabase;

    private AssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);

        $this->service = app(AssessmentService::class);
    }

    private function makeUnlockedCourse(User $user): array
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
        ]);
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 0,
            'completed_at' => now(),
        ]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id, 'status' => 'active']);

        return compact('course', 'assessment');
    }

    public function test_begin_creates_a_started_attempt_when_unlocked(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);

        $attempt = $this->service->beginAttempt($user, $course);

        $this->assertSame('started', $attempt->status);
        $this->assertSame($assessment->id, $attempt->assessment_id);
        $this->assertSame($user->id, $attempt->user_id);
        $this->assertSame(1, AssessmentAttempt::query()->count());
    }

    public function test_begin_transitions_an_available_attempt_to_started(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        $existing = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'available',
        ]);

        $attempt = $this->service->beginAttempt($user, $course);

        $this->assertSame($existing->id, $attempt->id);
        $this->assertSame('started', $attempt->status);
        $this->assertSame(1, AssessmentAttempt::query()->count());
    }

    public function test_begin_resumes_an_already_started_attempt_without_a_new_row(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        $existing = AssessmentAttempt::factory()->started()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);

        $attempt = $this->service->beginAttempt($user, $course);

        $this->assertSame($existing->id, $attempt->id);
        $this->assertSame('started', $attempt->status);
        $this->assertSame(1, AssessmentAttempt::query()->count());
    }

    public function test_begin_refuses_when_student_is_not_eligible(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        Assessment::factory()->create(['course_id' => $course->id, 'status' => 'active']);

        $this->expectException(AssessmentNotUnlockedException::class);

        $this->service->beginAttempt($user, $course);
    }

    public function test_begin_refuses_when_assessment_is_locked(): void
    {
        $user = User::factory()->create();
        ['course' => $course] = (function () use ($user): array {
            $course = Course::factory()->create();
            $section = Section::factory()->create(['course_id' => $course->id]);
            $mission = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
            Progress::query()->create(['user_id' => $user->id, 'mission_id' => $mission->id, 'pts_earned' => 0, 'completed_at' => now()]);
            Assessment::factory()->create(['course_id' => $course->id, 'status' => 'locked']);

            return compact('course');
        })();

        $this->expectException(AssessmentNotUnlockedException::class);

        $this->service->beginAttempt($user, $course);
    }

    public function test_begin_refuses_when_course_has_no_assessment(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $this->expectException(AssessmentNotUnlockedException::class);

        $this->service->beginAttempt($user, $course);
    }

    public function test_begin_refuses_a_terminal_attempt_without_creating_a_retry(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'failed',
            'score' => 40,
        ]);

        $this->expectException(AssessmentAttemptStateException::class);

        $this->service->beginAttempt($user, $course);
    }

    public function test_begin_is_scoped_per_student(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        ['course' => $course] = $this->makeUnlockedCourse($first);
        ['course' => $secondCourse] = $this->makeUnlockedCourse($second);
        $course->update(['order_num' => 1]);
        $secondCourse->update(['order_num' => 1]);

        $firstAttempt = $this->service->beginAttempt($first, $course);
        $secondAttempt = $this->service->beginAttempt($second, $secondCourse);

        $this->assertSame($first->id, $firstAttempt->user_id);
        $this->assertSame($second->id, $secondAttempt->user_id);
        $this->assertSame(2, AssessmentAttempt::query()->count());
    }

    public function test_owner_can_view_their_own_code_and_score(): void
    {
        $user = User::factory()->create();
        $attempt = AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'status' => 'submitted',
            'code' => '<h1>hi</h1>',
            'score' => 92,
        ]);

        $result = $this->service->attemptResult($user, $attempt);

        $this->assertSame($attempt->id, $result['id']);
        $this->assertSame('<h1>hi</h1>', $result['code']);
        $this->assertSame(92, $result['score']);
        $this->assertSame('submitted', $result['status']);
    }

    public function test_non_owner_is_denied_attempt_result(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $attempt = AssessmentAttempt::factory()->create([
            'user_id' => $owner->id,
            'status' => 'submitted',
            'code' => '<h1>secret</h1>',
            'score' => 92,
        ]);

        $this->expectException(AssessmentAttemptAccessDeniedException::class);

        $this->service->attemptResult($intruder, $attempt);
    }

    public function test_has_access_to_attempt_reflects_ownership(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $attempt = AssessmentAttempt::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->service->hasAccessToAttempt($owner, $attempt));
        $this->assertFalse($this->service->hasAccessToAttempt($intruder, $attempt));
    }

    public function test_latest_attempt_for_returns_the_most_recent(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        AssessmentAttempt::factory()->create(['assessment_id' => $assessment->id, 'user_id' => $user->id, 'status' => 'failed']);
        $latest = AssessmentAttempt::factory()->create(['assessment_id' => $assessment->id, 'user_id' => $user->id, 'status' => 'passed']);

        $this->assertSame($latest->id, $this->service->latestAttemptFor($user, $course)->id);
    }

    public function test_submit_records_code_marks_submitted_and_does_not_touch_score(): void
    {
        $user = User::factory()->create();
        $attempt = AssessmentAttempt::factory()->started()->create(['user_id' => $user->id]);

        $submitted = $this->service->submitAttempt($user, $attempt, '<h1>broadcast restored</h1>');

        $this->assertSame('<h1>broadcast restored</h1>', $submitted->code);
        $this->assertSame('submitted', $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
        $this->assertNull($submitted->score);
        $this->assertNull($submitted->passed_at);
    }

    public function test_a_stale_started_attempt_cannot_overwrite_a_submitted_attempt(): void
    {
        $student = User::factory()->create();
        ['course' => $course] = $this->makeUnlockedCourse($student);
        $attempt = $this->service->beginAttempt($student, $course);
        $staleAttempt = AssessmentAttempt::query()->findOrFail($attempt->id);

        $this->service->submitAttempt($student, $attempt, 'first submission');

        try {
            $this->service->submitAttempt($student, $staleAttempt, 'replayed submission');
            $this->fail('A stale attempt must not overwrite submitted evidence.');
        } catch (AssessmentAttemptStateException) {
            $this->assertSame('first submission', $attempt->fresh()->code);
            $this->assertSame('submitted', $attempt->fresh()->status);
        }
    }

    public function test_submit_against_another_students_attempt_is_denied(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $attempt = AssessmentAttempt::factory()->started()->create([
            'user_id' => $owner->id,
            'code' => 'original submission',
        ]);

        $this->expectException(AssessmentAttemptAccessDeniedException::class);

        $this->service->submitAttempt($intruder, $attempt, 'malicious overwrite');
    }

    public function test_denied_submit_leaves_the_owners_attempt_untouched(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $attempt = AssessmentAttempt::factory()->started()->create([
            'user_id' => $owner->id,
            'status' => 'started',
            'code' => 'original submission',
        ]);

        try {
            $this->service->submitAttempt($intruder, $attempt, 'malicious overwrite');
        } catch (AssessmentAttemptAccessDeniedException) {
            // expected
        }

        $attempt->refresh();

        $this->assertSame('original submission', $attempt->code);
        $this->assertSame('started', $attempt->status);
        $this->assertNull($attempt->submitted_at);
    }

    public function test_submit_refuses_an_available_attempt(): void
    {
        $user = User::factory()->create();
        $attempt = AssessmentAttempt::factory()->create(['user_id' => $user->id, 'status' => 'available']);

        $this->expectException(AssessmentAttemptStateException::class);

        $this->service->submitAttempt($user, $attempt, 'code');
    }

    public function test_submit_refuses_an_already_final_attempt(): void
    {
        $user = User::factory()->create();
        $attempt = AssessmentAttempt::factory()->create(['user_id' => $user->id, 'status' => 'passed', 'score' => 85]);

        $this->expectException(AssessmentAttemptStateException::class);

        $this->service->submitAttempt($user, $attempt, 'code');
    }

    private function makeEvaluableAttempt(User $user, array $mustContains, int $passingScore, string $code): AssessmentAttempt
    {
        $rules = array_map(fn (string $needle): array => ['type' => 'contains', 'value' => $needle, 'label' => "contains {$needle}"], $mustContains);
        $assessment = Assessment::factory()->create([
            'grading_rule' => json_encode($rules),
            'passing_score' => $passingScore,
        ]);

        return AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'code' => $code,
        ]);
    }

    public function test_evaluate_scores_the_proportion_of_rules_passed(): void
    {
        $user = User::factory()->create();
        $attempt = $this->makeEvaluableAttempt($user, ['A', 'B', 'C', 'D'], 70, 'A B');

        $result = $this->service->evaluateAttempt($user, $attempt);

        $this->assertSame(50, $result->score);
        $this->assertSame('failed', $result->status);
        $this->assertNull($result->passed_at);
    }

    public function test_evaluate_passes_when_score_meets_passing_score(): void
    {
        $user = User::factory()->create();
        $attempt = $this->makeEvaluableAttempt($user, ['A', 'B', 'C'], 70, 'A B C');

        $result = $this->service->evaluateAttempt($user, $attempt);

        $this->assertSame(100, $result->score);
        $this->assertSame('passed', $result->status);
        $this->assertNotNull($result->passed_at);
    }

    public function test_evaluate_fails_below_passing_score(): void
    {
        $user = User::factory()->create();
        $attempt = $this->makeEvaluableAttempt($user, ['A', 'B', 'C'], 90, 'A B');

        $result = $this->service->evaluateAttempt($user, $attempt);

        $this->assertSame(67, $result->score);
        $this->assertSame('failed', $result->status);
        $this->assertNull($result->passed_at);
    }

    public function test_evaluate_denied_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $attempt = $this->makeEvaluableAttempt($owner, ['A'], 70, 'A');

        $this->expectException(AssessmentAttemptAccessDeniedException::class);

        $this->service->evaluateAttempt($intruder, $attempt);
    }

    public function test_evaluate_requires_a_submitted_attempt(): void
    {
        $user = User::factory()->create();
        $attempt = AssessmentAttempt::factory()->create(['user_id' => $user->id, 'status' => 'started']);

        $this->expectException(AssessmentAttemptStateException::class);

        $this->service->evaluateAttempt($user, $attempt);
    }

    public function test_evaluate_throws_when_the_attempt_has_no_assessment_reference(): void
    {
        $user = User::factory()->create();
        $attempt = AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'status' => 'submitted',
            'code' => 'A',
        ]);

        $attempt->setRelation('assessment', null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('references no assessment');

        $this->service->evaluateAttempt($user, $attempt);
    }

    public function test_evaluate_never_passes_a_submission_with_zero_rules(): void
    {
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create(['grading_rule' => json_encode([]), 'passing_score' => 70]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'code' => '<h1>anything</h1>',
        ]);

        $result = $this->service->evaluateAttempt($user, $attempt);

        $this->assertSame(0, $result->score);
        $this->assertSame('failed', $result->status);
    }

    public function test_passed_result_is_immutable_against_re_evaluation(): void
    {
        $user = User::factory()->create();
        $attempt = $this->makeEvaluableAttempt($user, ['A', 'B'], 70, 'A B');

        $this->service->evaluateAttempt($user, $attempt);
        $attempt->refresh();

        $passedAt = $attempt->passed_at?->timestamp;

        try {
            $this->service->evaluateAttempt($user, $attempt);
            $this->fail('Re-evaluating a passed attempt must be refused.');
        } catch (AssessmentAttemptStateException) {
            // expected
        }

        $attempt->refresh();

        $this->assertSame('passed', $attempt->status);
        $this->assertSame(100, $attempt->score);
        $this->assertSame($passedAt, $attempt->passed_at?->timestamp);
    }

    public function test_a_stale_submitted_attempt_cannot_re_evaluate_a_final_result(): void
    {
        $student = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($student);
        $assessment->update([
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'PASS']]),
            'passing_score' => 100,
        ]);
        $attempt = $this->service->beginAttempt($student, $course);
        $attempt = $this->service->submitAttempt($student, $attempt, 'PASS');
        $staleAttempt = AssessmentAttempt::query()->findOrFail($attempt->id);
        $this->service->evaluateAttempt($student, $attempt);

        try {
            $this->service->evaluateAttempt($student, $staleAttempt);
            $this->fail('A finalized attempt must not be evaluated through a stale model.');
        } catch (AssessmentAttemptStateException) {
            $this->assertSame('passed', $attempt->fresh()->status);
            $this->assertSame(1, XpTransaction::query()->where('type', 'assessment_completed')->count());
        }
    }

    public function test_failed_result_is_immutable_against_re_evaluation(): void
    {
        $user = User::factory()->create();
        $attempt = $this->makeEvaluableAttempt($user, ['A', 'B', 'C'], 90, 'A');

        $this->service->evaluateAttempt($user, $attempt);
        $attempt->refresh();

        try {
            $this->service->evaluateAttempt($user, $attempt);
            $this->fail('Re-evaluating a failed attempt must be refused.');
        } catch (AssessmentAttemptStateException) {
            // expected
        }

        $attempt->refresh();

        $this->assertSame('failed', $attempt->status);
        $this->assertSame(33, $attempt->score);
        $this->assertNull($attempt->passed_at);
    }

    public function test_begin_and_submit_offer_no_write_path_to_an_evaluated_result(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'code' => 'no-rule-assessment',
        ]);

        $this->service->evaluateAttempt($user, $attempt);
        $attempt->refresh();

        foreach (['submit', 'begin'] as $action) {
            try {
                if ($action === 'submit') {
                    $this->service->submitAttempt($user, $attempt, 'overwrite');
                } else {
                    $this->service->beginAttempt($user, $course);
                }

                $this->fail("{$action} against an evaluated attempt must be refused.");
            } catch (AssessmentAttemptStateException) {
                // expected
            }
        }

        $attempt->refresh();

        $this->assertSame('failed', $attempt->status);
        $this->assertSame(0, $attempt->score);
        $this->assertSame('no-rule-assessment', $attempt->code);
        $this->assertNull($attempt->passed_at);
    }

    public function test_begin_and_submit_never_write_score_or_passed_at(): void
    {
        $user = User::factory()->create();
        ['course' => $course] = $this->makeUnlockedCourse($user);

        $attempt = $this->service->beginAttempt($user, $course);
        $attempt = $this->service->submitAttempt($user, $attempt, 'code');

        $this->assertSame('submitted', $attempt->status);
        $this->assertNull($attempt->score);
        $this->assertNull($attempt->passed_at);
        $this->assertNotNull($attempt->submitted_at);
    }

    private function makeTerminalAttempt(User $user, Assessment $assessment, string $status): AssessmentAttempt
    {
        return AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => $status,
            'score' => $status === 'passed' ? 90 : 40,
        ]);
    }

    public function test_retry_after_failure_creates_a_fresh_started_row(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        $prior = $this->makeTerminalAttempt($user, $assessment, 'failed');

        $retry = $this->service->retryAttempt($user, $course);

        $this->assertSame('started', $retry->status);
        $this->assertNull($retry->code);
        $this->assertSame($assessment->id, $retry->assessment_id);
        $this->assertSame($user->id, $retry->user_id);
        $this->assertNotSame($prior->id, $retry->id);
        $this->assertSame(2, AssessmentAttempt::query()->count());
    }

    public function test_retry_is_allowed_after_a_passed_attempt(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        $this->makeTerminalAttempt($user, $assessment, 'passed');

        $retry = $this->service->retryAttempt($user, $course);

        $this->assertSame('started', $retry->status);
        $this->assertSame(2, AssessmentAttempt::query()->count());
    }

    public function test_retry_refused_when_no_prior_attempt_exists(): void
    {
        $user = User::factory()->create();
        ['course' => $course] = $this->makeUnlockedCourse($user);

        $this->expectException(AssessmentAttemptStateException::class);

        $this->service->retryAttempt($user, $course);
    }

    public function test_retry_refused_while_an_attempt_is_active(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        AssessmentAttempt::factory()->started()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(AssessmentAttemptStateException::class);

        $this->service->retryAttempt($user, $course);
    }

    public function test_retry_gated_by_unlock(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        Assessment::factory()->create(['course_id' => $course->id, 'status' => 'active']);

        $this->expectException(AssessmentNotUnlockedException::class);

        $this->service->retryAttempt($user, $course);
    }

    public function test_latest_attempt_after_retry_is_the_new_row(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        $this->makeTerminalAttempt($user, $assessment, 'failed');

        $retry = $this->service->retryAttempt($user, $course);

        $latest = $this->service->latestAttemptFor($user, $course);
        $this->assertSame($retry->id, $latest->id);
        $this->assertSame('started', $latest->status);
    }

    public function test_retry_keeps_prior_attempt_visible_to_its_owner_as_history(): void
    {
        $user = User::factory()->create();
        $intruder = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        $prior = $this->makeTerminalAttempt($user, $assessment, 'failed');

        $retry = $this->service->retryAttempt($user, $course);

        $priorResult = $this->service->attemptResult($user, $prior);
        $retryResult = $this->service->attemptResult($user, $retry);

        $this->assertSame(40, $priorResult['score']);
        $this->assertSame('failed', $priorResult['status']);
        $this->assertNull($retryResult['code']);
        $this->assertSame('started', $retryResult['status']);

        try {
            $this->service->attemptResult($intruder, $retry);
            $this->fail('A non-owner must not view a historical or retry attempt.');
        } catch (AssessmentAttemptAccessDeniedException) {
            // expected
        }
    }

    public function test_has_passed_true_when_a_passed_attempt_exists_in_history(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        $this->makeTerminalAttempt($user, $assessment, 'passed');

        $this->assertTrue($this->service->hasPassed($user, $course));
    }

    public function test_has_passed_false_when_only_failed_attempts_exist(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        $this->makeTerminalAttempt($user, $assessment, 'failed');

        $this->assertFalse($this->service->hasPassed($user, $course));
    }

    public function test_has_passed_reads_history_not_the_latest_verdict(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($user);
        $this->makeTerminalAttempt($user, $assessment, 'passed');
        $this->makeTerminalAttempt($user, $assessment, 'failed');

        $this->assertTrue($this->service->hasPassed($user, $course));
    }

    public function test_has_passed_is_scoped_to_the_student_and_course(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedCourse($alice);
        $otherCourse = Course::factory()->create(['status' => 'active']);
        Assessment::factory()->create(['course_id' => $otherCourse->id]);

        $this->makeTerminalAttempt($alice, $assessment, 'passed');

        $this->assertTrue($this->service->hasPassed($alice, $course));
        $this->assertFalse($this->service->hasPassed($bob, $course));
        $this->assertFalse($this->service->hasPassed($alice, $otherCourse));
    }

    public function test_evaluate_awards_assessment_xp_once_on_the_first_pass(): void
    {
        $user = User::factory()->create();
        $attempt = $this->makeEvaluableAttempt($user, ['A', 'B', 'C'], 70, 'A B C');

        $result = $this->service->evaluateAttempt($user, $attempt);

        $this->assertSame('passed', $result->status);
        $this->assertSame(100, app(XpService::class)->balance($user));
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'assessment_id' => $attempt->assessment_id,
            'mission_id' => null,
            'amount' => 100,
            'type' => XpService::TYPE_ASSESSMENT_COMPLETED,
        ]);
    }

    public function test_evaluate_awards_no_xp_on_a_failed_attempt(): void
    {
        $user = User::factory()->create();
        $attempt = $this->makeEvaluableAttempt($user, ['A', 'B', 'C'], 90, 'A B');

        $result = $this->service->evaluateAttempt($user, $attempt);

        $this->assertSame('failed', $result->status);
        $this->assertSame(0, app(XpService::class)->balance($user));
        $this->assertDatabaseMissing('the404_xp_transactions', [
            'user_id' => $user->id,
            'type' => XpService::TYPE_ASSESSMENT_COMPLETED,
        ]);
    }

    private function makePassingBossChallenge(User $user): array
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 0,
            'completed_at' => now(),
        ]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'A', 'label' => 'has A']]),
            'passing_score' => 70,
        ]);

        return compact('course', 'assessment');
    }

    public function test_repeat_pass_after_retry_does_not_re_award_xp(): void
    {
        $user = User::factory()->create();
        ['course' => $course] = $this->makePassingBossChallenge($user);

        $first = $this->service->beginAttempt($user, $course);
        $first = $this->service->submitAttempt($user, $first, 'A');
        $this->service->evaluateAttempt($user, $first);

        $retry = $this->service->retryAttempt($user, $course);
        $retry = $this->service->submitAttempt($user, $retry, 'A');
        $this->service->evaluateAttempt($user, $retry);

        $this->assertSame(100, app(XpService::class)->balance($user));
        $this->assertSame(1, XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_ASSESSMENT_COMPLETED)
            ->count());
    }

    public function test_failed_retry_after_a_pass_never_reverses_the_xp_award(): void
    {
        $user = User::factory()->create();
        ['course' => $course] = $this->makePassingBossChallenge($user);

        $first = $this->service->beginAttempt($user, $course);
        $first = $this->service->submitAttempt($user, $first, 'A');
        $this->service->evaluateAttempt($user, $first);

        $retry = $this->service->retryAttempt($user, $course);
        $retry = $this->service->submitAttempt($user, $retry, 'Z');
        $this->service->evaluateAttempt($user, $retry);

        $this->assertSame('failed', $retry->status);
        $this->assertSame(100, app(XpService::class)->balance($user));
        $this->assertSame(1, XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_ASSESSMENT_COMPLETED)
            ->count());
    }
}
