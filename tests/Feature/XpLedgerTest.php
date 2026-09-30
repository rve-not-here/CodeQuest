<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Mission;
use App\Models\User;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class XpLedgerTest extends TestCase
{
    use RefreshDatabase;

    private XpService $xp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xp = new XpService;
    }

    public function test_the_xp_ledger_requires_authentication(): void
    {
        $this->get('/xp-ledger')->assertRedirect('/login');
    }

    public function test_the_xp_ledger_shows_an_empty_state_for_a_fresh_student(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)
            ->get('/xp-ledger')
            ->assertOk()
            ->assertSee('XP 0')
            ->assertSee('NO TRANSACTIONS');
    }

    public function test_the_xp_ledger_shows_the_single_authoritative_balance(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $mission = Mission::factory()->create(['points' => 30]);

        $this->xp->awardCompletion($user, $mission);
        $this->xp->deductWrongSubmission($user, $mission);

        $this->actingAs($user)
            ->get('/xp-ledger')
            ->assertOk()
            ->assertSee('XP 20');
    }

    public function test_the_xp_ledger_lists_transactions_with_amount_direction_reason_source_and_timestamp(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $mission = Mission::factory()->create(['title' => 'The Breach', 'points' => 30]);
        $assessment = Assessment::factory()->create(['title' => 'Signal Restoration — HTML Boss Challenge']);

        $this->xp->awardCompletion($user, $mission);
        $this->xp->spendHint($user, $mission, 1);
        $this->xp->awardAssessmentPass($user, $assessment);

        DB::table('the404_xp_transactions')
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_HINT_USED)
            ->update(['created_at' => '2026-08-02 11:30:00']);

        $this->actingAs($user)
            ->get('/xp-ledger')
            ->assertOk()
            ->assertSee('Mission completed: The Breach')
            ->assertSee('Hint 1 on mission: The Breach')
            ->assertSee('Boss Challenge completed: Signal Restoration — HTML Boss Challenge')
            ->assertSee('+30 XP')
            ->assertSee('-5 XP')
            ->assertSee('+100 XP')
            ->assertSee('MISSION')
            ->assertSee('BOSS')
            ->assertSee('SPENT')
            ->assertSee('Aug 02, 11:30');
    }

    public function test_the_xp_ledger_is_scoped_to_the_authenticated_student(): void
    {
        $viewer = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $mission = Mission::factory()->create(['title' => 'Their Mission', 'points' => 30]);

        $this->xp->awardCompletion($other, $mission);

        $this->actingAs($viewer)
            ->get('/xp-ledger')
            ->assertOk()
            ->assertSee('XP 0')
            ->assertSee('NO TRANSACTIONS')
            ->assertDontSee('Their Mission');
    }

    public function test_the_xp_ledger_rejects_user_scoping_parameters(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $param) {
            $this->actingAs($user)
                ->get('/xp-ledger?'.$param.'=999')
                ->assertForbidden();
        }
    }
}
