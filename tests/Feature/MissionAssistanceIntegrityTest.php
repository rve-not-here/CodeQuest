<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\XpService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MissionAssistanceIntegrityTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('unavailableHints')]
    public function test_unavailable_hints_leave_the_ledger_untouched(?string $hints, int $ordinal): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['hints' => $hints]);
        $this->fund($student, $mission);

        $purchased = app(XpService::class)->spendHint($student, $mission, $ordinal);

        $this->assertFalse($purchased);
        $this->assertSame(100, app(XpService::class)->balance($student));
        $this->assertDatabaseMissing('the404_xp_transactions', ['user_id' => $student->id, 'type' => XpService::TYPE_HINT_USED]);
    }

    /** @return array<string, array{?string, int}> */
    public static function unavailableHints(): array
    {
        return [
            'missing' => [null, 1],
            'empty' => ['', 1],
            'whitespace' => [" \n\t", 1],
            'empty JSON list' => ['[]', 1],
            'blank JSON entries' => ['["", " "]', 1],
            'nested JSON entries' => ['[["nested"]]', 1],
            'exhausted one hint' => ['["Only hint"]', 2],
            'exhausted two hints' => ['["First", "Second"]', 3],
            'zero ordinal' => ['["Hint"]', 0],
            'negative ordinal' => ['["Hint"]', -1],
            'beyond supported pricing' => ['["First", "Second", "Third", "Fourth"]', 4],
        ];
    }

    #[DataProvider('unavailableSolutions')]
    public function test_unavailable_solutions_leave_the_ledger_untouched(?string $solution): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['solution_code' => $solution]);
        $this->fund($student, $mission);

        $purchased = app(XpService::class)->spendSolutionReveal($student, $mission);

        $this->assertFalse($purchased);
        $this->assertSame(100, app(XpService::class)->balance($student));
        $this->assertDatabaseMissing('the404_xp_transactions', ['user_id' => $student->id, 'type' => XpService::TYPE_SOLUTION_REVEALED]);
    }

    /** @return array<string, array{?string}> */
    public static function unavailableSolutions(): array
    {
        return ['missing' => [null], 'empty' => [''], 'whitespace' => [" \n\t"]];
    }

    public function test_purchase_checks_current_content_instead_of_a_stale_mission(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['hints' => '["Old hint"]', 'solution_code' => '<p>Old solution</p>']);
        $this->fund($student, $mission);
        Mission::query()->whereKey($mission->id)->update(['hints' => null, 'solution_code' => null]);

        $hintPurchased = app(XpService::class)->spendHint($student, $mission, 1);
        $solutionPurchased = app(XpService::class)->spendSolutionReveal($student, $mission);

        $this->assertFalse($hintPurchased);
        $this->assertFalse($solutionPurchased);
        $this->assertSame(100, app(XpService::class)->balance($student));
        $this->assertDatabaseCount('the404_xp_transactions', 1);
    }

    #[DataProvider('availableHintFormats')]
    public function test_available_hint_formats_keep_their_price_and_replay_behavior(string $hints): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['hints' => $hints]);
        $this->fund($student, $mission);

        $firstPurchase = app(XpService::class)->spendHint($student, $mission, 1);
        $replay = app(XpService::class)->spendHint($student, $mission, 1);

        $this->assertTrue($firstPurchase);
        $this->assertTrue($replay);
        $this->assertSame(95, app(XpService::class)->balance($student));
        $this->assertDatabaseCount('the404_xp_transactions', 2);
    }

    /** @return array<string, array{string}> */
    public static function availableHintFormats(): array
    {
        return ['legacy text' => ['Use a heading'], 'JSON list' => ['["Use a heading"]']];
    }

    public function test_three_available_hints_keep_progressive_pricing(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['hints' => '["First", "Second", "Third", "Fourth"]']);
        $this->fund($student, $mission);

        $first = app(XpService::class)->spendHint($student, $mission, 1);
        $second = app(XpService::class)->spendHint($student, $mission, 2);
        $third = app(XpService::class)->spendHint($student, $mission, 3);

        $this->assertTrue($first);
        $this->assertTrue($second);
        $this->assertTrue($third);
        $this->assertSame(70, app(XpService::class)->balance($student));
        $this->assertSame([-5, -10, -15], XpTransaction::query()->where('type', XpService::TYPE_HINT_USED)->orderBy('id')->pluck('amount')->map(fn ($amount): int => (int) $amount)->all());
    }

    public function test_available_solution_keeps_its_price_and_replay_behavior(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['solution_code' => '<h1>Solution</h1>']);
        $this->fund($student, $mission);

        $firstPurchase = app(XpService::class)->spendSolutionReveal($student, $mission);
        $replay = app(XpService::class)->spendSolutionReveal($student, $mission);

        $this->assertTrue($firstPurchase);
        $this->assertTrue($replay);
        $this->assertSame(70, app(XpService::class)->balance($student));
        $this->assertDatabaseCount('the404_xp_transactions', 2);
    }

    #[DataProvider('assistanceBoundaries')]
    public function test_assistance_rechecks_completion_and_course_status_at_mutation_boundary(string $action, string $state): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['hints' => '["First hint"]', 'solution_code' => '<h1>Solution</h1>']);
        $this->fund($student, $mission);
        $changed = false;
        DB::listen(function (QueryExecuted $query) use ($student, $mission, $state, &$changed): void {
            if (! $changed && str_starts_with(strtolower($query->sql), 'select') && str_contains($query->sql, 'the404_users')) {
                $changed = true;
                if ($state === 'complete') {
                    Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
                } else {
                    Course::query()->whereKey($mission->course_id)->update(['status' => 'locked']);
                }
            }
        });
        $response = $this->actingAs($student)->post(route('mission.'.$action, $mission), ['code' => '<h1>Work</h1>']);
        if ($state === 'complete') {
            $response->assertRedirect()->assertSessionHas($action === 'hint' ? 'hint_flat' : 'reveal_flat');
        } else {
            $response->assertForbidden();
        }
        $this->assertTrue($changed);
        $this->assertSame(100, app(XpService::class)->balance($student));
        $this->assertDatabaseCount('the404_xp_transactions', 1);
    }

    /** @return array<string, array{string, string}> */
    public static function assistanceBoundaries(): array
    {
        return ['hint complete' => ['hint', 'complete'], 'solution complete' => ['reveal', 'complete'], 'hint sealed' => ['hint', 'sealed'], 'solution sealed' => ['reveal', 'sealed']];
    }

    private function fund(User $student, Mission $mission): void
    {
        XpTransaction::query()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'amount' => 100,
            'type' => XpService::TYPE_MISSION_COMPLETED,
        ]);
    }
}
