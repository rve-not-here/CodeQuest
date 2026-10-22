<?php

namespace Tests\Feature;

use App\Exceptions\AssessmentNotUnlockedException;
use App\Models\AdminAudit;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AdminAssessmentService;
use App\Services\AssessmentService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0-1: the Boss Challenge verdict is `score >= passing_score`, and the score
 * is a percentage — so a zero threshold passed a submission that failed every
 * rule. Invalid grading configuration must fail closed: no passing verdict,
 * no XP, no course completion, whichever direction the request comes from.
 */
class AssessmentThresholdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    /**
     * @return array{course: Course, assessment: Assessment, mission: Mission, student: User}
     */
    private function unlockedChallenge(int $passingScore, string $rules = '[{"type":"contains","value":"SIGNAL"}]'): array
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create(['type' => 'html']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);

        Progress::query()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'pts_earned' => 0,
            'completed_at' => now(),
        ]);

        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'grading_rule' => $rules,
            'passing_score' => $passingScore,
        ]);

        return compact('course', 'assessment', 'mission', 'student');
    }

    /**
     * @param  array{course: Course, assessment: Assessment, student: User}  $challenge
     */
    private function evaluate(array $challenge, string $code): AssessmentAttempt
    {
        $service = app(AssessmentService::class);
        $attempt = $service->beginAttempt($challenge['student'], $challenge['course']);
        $service->submitAttempt($challenge['student'], $attempt, $code);

        return $service->evaluateAttempt($challenge['student'], $attempt);
    }

    public function test_zero_threshold_cannot_pass_a_submission_that_fails_every_rule(): void
    {
        $challenge = $this->unlockedChallenge(0);

        $attempt = $this->evaluate($challenge, 'nothing relevant');

        $this->assertSame('failed', $attempt->status);
        $this->assertSame(0, $attempt->score);
        $this->assertNull($attempt->passed_at);
        $this->assertFalse(app(AssessmentService::class)->hasPassed($challenge['student'], $challenge['course']));
        $this->assertSame(0, XpTransaction::query()
            ->where('user_id', $challenge['student']->id)
            ->where('type', XpService::TYPE_ASSESSMENT_COMPLETED)
            ->count());
    }

    public function test_zero_threshold_cannot_pass_a_submission_that_satisfies_every_rule(): void
    {
        // Failing closed is not "score zero when the work is perfect": an
        // invalid threshold means the assessment cannot award a pass at all.
        $challenge = $this->unlockedChallenge(0);

        $attempt = $this->evaluate($challenge, 'SIGNAL');

        $this->assertSame('failed', $attempt->status);
        $this->assertFalse(app(AssessmentService::class)->hasPassed($challenge['student'], $challenge['course']));
    }

    public function test_malformed_grading_rule_fails_closed_instead_of_scoring_a_student_failure(): void
    {
        $challenge = $this->unlockedChallenge(70, '{"type":"contains"}');

        $attempt = $this->evaluate($challenge, 'SIGNAL');

        $this->assertSame('failed', $attempt->status);
        $this->assertSame(0, $attempt->score);
        $this->assertFalse(app(AssessmentService::class)->hasPassed($challenge['student'], $challenge['course']));
    }

    public function test_empty_grading_rule_fails_closed(): void
    {
        $challenge = $this->unlockedChallenge(70, '[]');

        $attempt = $this->evaluate($challenge, 'anything');

        $this->assertSame('failed', $attempt->status);
        $this->assertFalse(app(AssessmentService::class)->hasPassed($challenge['student'], $challenge['course']));
    }

    public function test_a_valid_threshold_still_passes_qualifying_work(): void
    {
        $challenge = $this->unlockedChallenge(70);

        $attempt = $this->evaluate($challenge, 'SIGNAL');

        $this->assertSame('passed', $attempt->status);
        $this->assertTrue(app(AssessmentService::class)->hasPassed($challenge['student'], $challenge['course']));
    }

    public function test_admin_cannot_set_a_zero_threshold_over_http(): void
    {
        $challenge = $this->unlockedChallenge(70);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.courses.assessment.update', [
                'course' => $challenge['course'],
                'assessment' => $challenge['assessment'],
            ]), [
                'title' => $challenge['assessment']->title,
                'description' => '',
                'instructions' => '',
                'passing_score' => 0,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('passing_score');

        $this->assertSame(70, $challenge['assessment']->fresh()->passing_score);
    }

    public function test_admin_service_refuses_a_zero_threshold_and_audits_the_refusal(): void
    {
        $challenge = $this->unlockedChallenge(70);
        $admin = User::factory()->admin()->create();

        try {
            app(AdminAssessmentService::class)->update($admin, $challenge['course'], $challenge['assessment'], [
                'title' => $challenge['assessment']->title,
                'description' => '',
                'instructions' => '',
                'passing_score' => 0,
                'status' => 'active',
            ]);
            $this->fail('A zero passing score must be refused.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('between 1 and 100', $exception->getMessage());
        }

        $this->assertSame(70, $challenge['assessment']->fresh()->passing_score);
        $this->assertSame(1, AdminAudit::query()
            ->where('admin_user_id', $admin->id)
            ->where('result', 'failed')
            ->count());
    }

    public function test_a_sealed_assessment_still_cannot_be_evaluated(): void
    {
        $challenge = $this->unlockedChallenge(70);
        $challenge['assessment']->update(['status' => 'locked']);

        $this->expectException(AssessmentNotUnlockedException::class);

        app(AssessmentService::class)->beginAttempt($challenge['student'], $challenge['course']);
    }
}
