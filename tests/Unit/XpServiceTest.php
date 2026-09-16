<?php

namespace Tests\Unit;

use App\Models\Assessment;
use App\Models\Mission;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\XpService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class XpServiceTest extends TestCase
{
    use RefreshDatabase;

    private XpService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new XpService;
    }

    public function test_balance_starts_at_zero(): void
    {
        $user = User::factory()->create();

        $this->assertSame(0, $this->service->balance($user));
    }

    public function test_award_completion_records_positive_transaction(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['points' => 50]);

        $this->service->awardCompletion($user, $mission);

        $this->assertSame(50, $this->service->balance($user));
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => 50,
            'type' => XpService::TYPE_MISSION_COMPLETED,
        ]);
    }

    public function test_award_completion_is_idempotent(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['points' => 50]);

        $this->service->awardCompletion($user, $mission);
        $this->service->awardCompletion($user, $mission);

        $this->assertSame(50, $this->service->balance($user));
        $this->assertSame(1, XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_MISSION_COMPLETED)
            ->count());
    }

    public function test_award_assessment_pass_records_positive_transaction_with_assessment_reference(): void
    {
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create(['title' => 'Signal Restoration — HTML Boss Challenge']);

        $this->service->awardAssessmentPass($user, $assessment);

        $this->assertSame(100, $this->service->balance($user));
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'mission_id' => null,
            'amount' => 100,
            'type' => XpService::TYPE_ASSESSMENT_COMPLETED,
        ]);
    }

    public function test_award_assessment_pass_uses_the_named_constant(): void
    {
        $this->assertSame(100, XpService::ASSESSMENT_PASSED_AMOUNT);
    }

    public function test_award_assessment_pass_is_idempotent(): void
    {
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create();

        $this->service->awardAssessmentPass($user, $assessment);
        $this->service->awardAssessmentPass($user, $assessment);

        $this->assertSame(100, $this->service->balance($user));
        $this->assertSame(1, XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('assessment_id', $assessment->id)
            ->where('type', XpService::TYPE_ASSESSMENT_COMPLETED)
            ->count());
    }

    public function test_mission_and_assessment_transactions_carry_exactly_one_source_fk(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['points' => 50]);
        $assessment = Assessment::factory()->create();

        $this->service->awardCompletion($user, $mission);
        $this->service->awardAssessmentPass($user, $assessment);

        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'assessment_id' => null,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'mission_id' => null,
            'assessment_id' => $assessment->id,
        ]);
    }

    public function test_deduct_wrong_submission_records_negative_transaction(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $this->service->deductWrongSubmission($user, $mission);

        $this->assertSame(0, $this->service->balance($user));
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'amount' => 0,
            'type' => XpService::TYPE_WRONG_SUBMISSION,
        ]);
    }

    public function test_deduct_wrong_submission_floors_at_zero(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $this->service->deductWrongSubmission($user, $mission);
        $this->service->deductWrongSubmission($user, $mission);

        $this->assertSame(0, $this->service->balance($user));
    }

    public function test_deduct_wrong_submission_from_positive_balance(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['points' => 30]);

        $this->service->awardCompletion($user, $mission);
        $this->service->deductWrongSubmission($user, $mission);

        $this->assertSame(20, $this->service->balance($user));
    }

    public function test_mixed_mission_and_assessment_ledger_sums_correctly_and_floors_at_zero(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['points' => 30]);
        $assessment = Assessment::factory()->create();

        // 30 -> 20 -> 10 -> 0. The fifth penalty would push the combined
        // ledger negative; the floor clamps that write to a 0-amount row.
        $this->service->awardCompletion($user, $mission);
        $this->service->deductWrongSubmission($user, $mission);
        $this->service->deductWrongSubmission($user, $mission);
        $this->service->deductWrongSubmission($user, $mission);
        $this->service->deductWrongSubmission($user, $mission);

        $this->assertSame(0, $this->service->balance($user));

        // An assessment pass still lands on top of the mixed ledger.
        $this->service->awardAssessmentPass($user, $assessment);

        $this->assertSame(100, $this->service->balance($user));
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'assessment_id' => null,
            'amount' => 0,
            'type' => XpService::TYPE_WRONG_SUBMISSION,
        ]);
    }

    public function test_spend_hint_returns_true_when_affordable(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['points' => 30]);

        $this->service->awardCompletion($user, $mission);
        $result = $this->service->spendHint($user, $mission, 1);

        $this->assertTrue($result);
        $this->assertSame(25, $this->service->balance($user));
    }

    public function test_spend_hint_returns_false_when_unaffordable(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $result = $this->service->spendHint($user, $mission, 1);

        $this->assertFalse($result);
        $this->assertSame(0, $this->service->balance($user));
        $this->assertDatabaseMissing('the404_xp_transactions', [
            'user_id' => $user->id,
            'type' => XpService::TYPE_HINT_USED,
        ]);
    }

    public function test_spend_solution_reveal_returns_true_when_affordable(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['points' => 50]);

        $this->service->awardCompletion($user, $mission);
        $result = $this->service->spendSolutionReveal($user, $mission);

        $this->assertTrue($result);
        $this->assertSame(20, $this->service->balance($user));
    }

    public function test_spend_solution_reveal_returns_false_when_unaffordable(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $result = $this->service->spendSolutionReveal($user, $mission);

        $this->assertFalse($result);
        $this->assertDatabaseMissing('the404_xp_transactions', [
            'user_id' => $user->id,
            'type' => XpService::TYPE_SOLUTION_REVEALED,
        ]);
    }

    public function test_can_afford_returns_true_when_balance_sufficient(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['points' => 50]);

        $this->service->awardCompletion($user, $mission);

        $this->assertTrue($this->service->canAfford($user, 30));
    }

    public function test_can_afford_returns_false_when_balance_insufficient(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->service->canAfford($user, 1));
    }

    public function test_revealed_hint_count_increments(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['points' => 50]);

        $this->service->awardCompletion($user, $mission);

        $this->assertSame(0, $this->service->revealedHintCount($user, $mission));

        $this->service->spendHint($user, $mission, 1);
        $this->assertSame(1, $this->service->revealedHintCount($user, $mission));

        $this->service->spendHint($user, $mission, 2);
        $this->assertSame(2, $this->service->revealedHintCount($user, $mission));
    }

    public function test_has_revealed_solution(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['points' => 50]);

        $this->assertFalse($this->service->hasRevealedSolution($user, $mission));

        $this->service->awardCompletion($user, $mission);
        $this->service->spendSolutionReveal($user, $mission);

        $this->assertTrue($this->service->hasRevealedSolution($user, $mission));
    }

    public function test_hint_costs_are_progressive(): void
    {
        $this->assertSame(5, $this->service->hintCost(1));
        $this->assertSame(10, $this->service->hintCost(2));
        $this->assertSame(15, $this->service->hintCost(3));
    }

    public function test_reveal_cost(): void
    {
        $this->assertSame(30, $this->service->revealCost());
    }

    public function test_wrong_submission_cost(): void
    {
        $this->assertSame(10, $this->service->wrongSubmissionCost());
    }

    public function test_transaction_history_is_empty_without_transactions(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->service->transactionHistory($user)->isEmpty());
    }

    public function test_transaction_history_returns_rows_newest_first(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['title' => 'First Contact', 'points' => 30]);

        $this->service->awardCompletion($user, $mission);
        $this->service->spendHint($user, $mission, 1);

        DB::table('the404_xp_transactions')
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_MISSION_COMPLETED)
            ->update(['created_at' => '2026-08-01 10:00:00']);
        DB::table('the404_xp_transactions')
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_HINT_USED)
            ->update(['created_at' => '2026-08-02 10:00:00']);

        $rows = $this->service->transactionHistory($user);

        $this->assertCount(2, $rows);
        $this->assertSame('Hint 1 on mission: First Contact', $rows[0]['reason']);
        $this->assertInstanceOf(Carbon::class, $rows[0]['at']);
        $this->assertSame('Aug 02, 10:00', $rows[0]['at']->format('M d, H:i'));
        $this->assertSame('Mission completed: First Contact', $rows[1]['reason']);
    }

    public function test_transaction_history_rows_expose_action_fields_and_no_internal_ids(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['title' => 'First Contact', 'points' => 30]);
        $assessment = Assessment::factory()->create(['title' => 'Signal Restoration — HTML Boss Challenge']);

        $this->service->awardCompletion($user, $mission);
        $this->service->spendHint($user, $mission, 1);
        $this->service->deductWrongSubmission($user, $mission);
        $this->service->awardAssessmentPass($user, $assessment);

        DB::table('the404_xp_transactions')
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_MISSION_COMPLETED)
            ->update(['created_at' => '2026-08-01 10:00:00']);
        DB::table('the404_xp_transactions')
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_HINT_USED)
            ->update(['created_at' => '2026-08-02 10:00:00']);
        DB::table('the404_xp_transactions')
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_WRONG_SUBMISSION)
            ->update(['created_at' => '2026-08-03 10:00:00']);
        DB::table('the404_xp_transactions')
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_ASSESSMENT_COMPLETED)
            ->update(['created_at' => '2026-08-04 10:00:00']);

        $rows = $this->service->transactionHistory($user);

        $this->assertCount(4, $rows);

        foreach ($rows as $row) {
            $this->assertSame(['amount', 'direction', 'reason', 'source', 'at'], array_keys($row));
        }

        $this->assertSame('Boss Challenge completed: Signal Restoration — HTML Boss Challenge', $rows[0]['reason']);
        $this->assertSame('assessment', $rows[0]['source']);
        $this->assertSame('Wrong submission on mission: First Contact', $rows[1]['reason']);
        $this->assertSame('mission', $rows[1]['source']);
        $this->assertSame('Hint 1 on mission: First Contact', $rows[2]['reason']);
        $this->assertSame('Mission completed: First Contact', $rows[3]['reason']);

        $credits = $rows->filter(fn (array $row): bool => $row['direction'] === 'credit')->values();
        $debits = $rows->filter(fn (array $row): bool => $row['direction'] === 'debit')->values();

        $this->assertSame([100, 30], $credits->pluck('amount')->all());
        $this->assertSame([-10, -5], $debits->pluck('amount')->all());
    }

    public function test_transaction_history_marks_a_zero_amount_penalty_as_a_debit(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['title' => 'First Contact']);

        $this->service->deductWrongSubmission($user, $mission);

        $rows = $this->service->transactionHistory($user);

        $this->assertCount(1, $rows);
        $this->assertSame(0, $rows[0]['amount']);
        $this->assertSame('debit', $rows[0]['direction']);
        $this->assertSame('Wrong submission on mission: First Contact', $rows[0]['reason']);
    }
}
