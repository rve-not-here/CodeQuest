<?php

namespace Tests\Feature;

use App\Exceptions\AssessmentAttemptAccessDeniedException;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AssessmentService;
use App\Services\NotificationService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AssessmentRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluation_failure_rolls_back_submission_and_can_be_retried(): void
    {
        $this->seed(AchievementSeeder::class);
        [$student, $boss, $attempt] = $this->readyAttempt('started');
        $notifications = app(NotificationService::class);
        $unavailable = true;
        $this->mock(NotificationService::class)->shouldReceive('create')->andReturnUsing(function (...$arguments) use ($notifications, &$unavailable) {
            if ($unavailable) {
                throw new RuntimeException('verdict notification storage failed');
            }

            return $notifications->create(...$arguments);
        });
        $this->actingAs($student)->post(route('assessment.submit', $boss), ['code' => 'SIGNAL'])->assertServerError();
        $this->assertSame('started', $attempt->fresh()->status);
        $this->assertNull($attempt->fresh()->code);
        $this->assertNull($attempt->fresh()->submitted_at);
        $this->assertNull($attempt->fresh()->score);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
        $this->assertDatabaseCount('the404_user_achievements', 0);
        $this->assertDatabaseCount('the404_notifications', 0);

        $unavailable = false;
        $this->post(route('assessment.submit', $boss), ['code' => 'SIGNAL'])->assertRedirect(route('assessment.show', $boss));
        $this->assertSame('passed', $attempt->fresh()->status);
        $this->assertDatabaseHas('the404_xp_transactions', ['user_id' => $student->id, 'assessment_id' => $boss->id, 'amount' => 100]);
        $count = XpTransaction::query()->count();
        $this->post(route('assessment.submit', $boss), ['code' => 'SIGNAL'])->assertSessionHas('assessment_error');
        $this->assertDatabaseCount('the404_xp_transactions', $count);
    }

    public function test_resuming_submitted_attempt_uses_stored_source_and_ignores_forged_verdict(): void
    {
        [$student, $boss, $attempt] = $this->readyAttempt('submitted');
        $this->actingAs($student)->get(route('assessment.show', $boss))->assertOk()->assertSee('RESUME VERIFICATION');
        $this->actingAs($student)->post(route('assessment.submit', $boss), [
            'code' => 'SIGNAL', 'score' => 100, 'passed' => true, 'status' => 'passed', 'xp' => 1000,
        ])->assertRedirect(route('assessment.show', $boss));
        $this->assertSame('failed', $attempt->fresh()->status);
        $this->assertSame(0, $attempt->fresh()->score);
        $this->assertSame('wrong', $attempt->fresh()->code);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
        $this->assertDatabaseCount('the404_assessment_attempts', 1);
    }

    public function test_recovering_a_valid_stored_submission_awards_once(): void
    {
        $this->seed(AchievementSeeder::class);
        [$student, $boss, $attempt] = $this->readyAttempt('submitted');
        $attempt->code = 'SIGNAL';
        $attempt->save();
        $this->actingAs($student)->post(route('assessment.submit', $boss), ['code' => 'wrong'])->assertRedirect(route('assessment.show', $boss));
        $this->assertSame('passed', $attempt->fresh()->status);
        $this->assertSame('SIGNAL', $attempt->fresh()->code);
        $this->assertSame(100, $attempt->fresh()->score);
        $this->post(route('assessment.submit', $boss), ['code' => 'SIGNAL'])->assertSessionHas('assessment_error');
        $this->assertDatabaseCount('the404_xp_transactions', 1);
        $this->assertDatabaseCount('the404_assessment_attempts', 1);
    }

    public function test_recovery_cannot_target_another_students_submitted_attempt(): void
    {
        [$owner, $boss, $attempt] = $this->readyAttempt('submitted');
        $other = User::factory()->create();
        Progress::factory()->create(['user_id' => $other->id, 'mission_id' => $boss->course->missions()->firstOrFail()->id]);
        $this->actingAs($other)->post(route('assessment.submit', $boss), [
            'code' => 'SIGNAL', 'attempt_id' => $attempt->id, 'user_id' => $owner->id,
        ])->assertSessionHas('assessment_error');
        $this->assertSame('submitted', $attempt->fresh()->status);
        $this->assertNull($attempt->fresh()->score);
        $this->assertDatabaseCount('the404_xp_transactions', 0);

        $this->expectException(AssessmentAttemptAccessDeniedException::class);
        app(AssessmentService::class)->submitAndEvaluateAttempt($other, $attempt, 'SIGNAL');
    }

    /** @return array{User, Assessment, AssessmentAttempt} */
    private function readyAttempt(string $status): array
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
        $boss = Assessment::factory()->create(['course_id' => $course->id, 'status' => 'active', 'grading_rule' => '[{"type":"contains","value":"SIGNAL"}]', 'passing_score' => 70]);
        $attempt = AssessmentAttempt::factory()->create(['user_id' => $student->id, 'assessment_id' => $boss->id, 'status' => $status, 'code' => $status === 'submitted' ? 'wrong' : null, 'submitted_at' => $status === 'submitted' ? now() : null]);

        return [$student, $boss, $attempt];
    }
}
