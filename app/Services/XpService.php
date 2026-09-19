<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Mission;
use App\Models\User;
use App\Models\XpTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Server-authoritative XP ledger.
 *
 * Every XP change is a row in the transaction table. The balance is the sum
 * of all rows and never falls below zero. Costs for hints and the solution
 * reveal are configurable per mission-triggered action; the amount of the
 * transaction is decided here, never by the client.
 */
class XpService
{
    public const TYPE_MISSION_COMPLETED = 'mission_completed';

    public const TYPE_ASSESSMENT_COMPLETED = 'assessment_completed';

    public const TYPE_WRONG_SUBMISSION = 'wrong_submission';

    public const TYPE_HINT_USED = 'hint_used';

    public const TYPE_SOLUTION_REVEALED = 'solution_revealed';

    /**
     * XP awarded for passing a course's Boss Challenge (US-412). A product
     * decision, deliberately a single named constant rather than a per-row
     * value: every course's challenge is worth the same.
     */
    public const ASSESSMENT_PASSED_AMOUNT = 100;

    /**
     * The XP cost of progressive hints, by hint ordinal (1-indexed).
     * The cost of revealing the full solution.
     *
     * @var array{1: int, 2: int, 3: int}
     */
    private const HINT_COSTS = [1 => 5, 2 => 10, 3 => 15];

    private const REVEAL_COST = 30;

    private const WRONG_SUBMISSION_COST = 10;

    /**
     * Ledger types that spend XP. Direction on a row is the action's intent,
     * not the sign of the amount: a wrong-submission penalty clamped to a
     * 0-amount write (balance already at zero) is still a debit.
     */
    private const DEBIT_TYPES = [
        self::TYPE_WRONG_SUBMISSION,
        self::TYPE_HINT_USED,
        self::TYPE_SOLUTION_REVEALED,
    ];

    /**
     * Ledger types that grant XP (mission and Boss Challenge credits).
     */
    public const AWARD_TYPES = [
        self::TYPE_MISSION_COMPLETED,
        self::TYPE_ASSESSMENT_COMPLETED,
    ];

    /**
     * Ledger types the student chose to spend XP on (hints, solution reveals).
     */
    public const SPEND_TYPES = [
        self::TYPE_HINT_USED,
        self::TYPE_SOLUTION_REVEALED,
    ];

    /**
     * Ledger types that deduct XP as a penalty (wrong submissions). Together
     * SPEND_TYPES and DEDUCTION_TYPES are exactly DEBIT_TYPES.
     */
    public const DEDUCTION_TYPES = [
        self::TYPE_WRONG_SUBMISSION,
    ];

    /**
     * Display order for the fleet-level per-type breakdown.
     */
    private const TYPE_ORDER = [
        self::TYPE_MISSION_COMPLETED,
        self::TYPE_ASSESSMENT_COMPLETED,
        self::TYPE_HINT_USED,
        self::TYPE_SOLUTION_REVEALED,
        self::TYPE_WRONG_SUBMISSION,
    ];

    /**
     * @var array<string, string>
     */
    private const TYPE_LABELS = [
        self::TYPE_MISSION_COMPLETED => 'Mission completions',
        self::TYPE_ASSESSMENT_COMPLETED => 'Boss Challenge passes',
        self::TYPE_HINT_USED => 'Hints purchased',
        self::TYPE_SOLUTION_REVEALED => 'Solutions revealed',
        self::TYPE_WRONG_SUBMISSION => 'Wrong submissions',
    ];

    public function balance(User $user): int
    {
        return $this->sumFor($user);
    }

    /**
     * Persists XP for a successfully completed mission. Idempotent: completing
     * a mission already completed by this user does not double-award.
     */
    public function awardCompletion(User $user, Mission $mission): void
    {
        DB::transaction(function () use ($user, $mission): void {
            $exists = XpTransaction::query()
                ->where('user_id', $user->id)
                ->where('mission_id', $mission->id)
                ->where('type', self::TYPE_MISSION_COMPLETED)
                ->exists();

            if ($exists) {
                return;
            }

            $this->record($user, $mission, null, $mission->points, self::TYPE_MISSION_COMPLETED,
                'Mission completed: '.$mission->title);
        });
    }

    /**
     * Records the one-time Boss Challenge reward (US-412).
     *
     * Exactly-once per course is guaranteed two ways. The primary guard lives
     * in AssessmentService::evaluateAttempt(), which calls this only when the
     * evaluation produced the student's FIRST pass (the history transition
     * rule); and this method is idempotent at the ledger, short-circuiting on
     * an existing assessment_completed row for this user/assessment. A later
     * retry, pass or fail, therefore never re-awards and never reverses the
     * original award.
     */
    public function awardAssessmentPass(User $user, Assessment $assessment): void
    {
        DB::transaction(function () use ($user, $assessment): void {
            $exists = XpTransaction::query()
                ->where('user_id', $user->id)
                ->where('assessment_id', $assessment->id)
                ->where('type', self::TYPE_ASSESSMENT_COMPLETED)
                ->exists();

            if ($exists) {
                return;
            }

            $this->record($user, null, $assessment, self::ASSESSMENT_PASSED_AMOUNT, self::TYPE_ASSESSMENT_COMPLETED,
                'Boss Challenge completed: '.$assessment->title);
        });
    }

    /**
     * Deducts the wrong-submission penalty. The balance can dip below zero in
     * the transaction, but the recorded amount is clamped so the ledger never
     * sends the running total negative. Clamping is atomic in the transaction.
     */
    public function deductWrongSubmission(User $user, Mission $mission): void
    {
        DB::transaction(function () use ($user, $mission): void {
            $this->spendFrom($user, $mission, self::WRONG_SUBMISSION_COST, self::TYPE_WRONG_SUBMISSION,
                'Wrong submission on mission: '.$mission->title);
        });
    }

    /**
     * Deducts the cost of revealing the nth hint. Returns false when the
     * student cannot afford it, leaving the ledger untouched.
     */
    public function spendHint(User $user, Mission $mission, int $hintNumber): bool
    {
        $cost = $this->hintCost($hintNumber);

        return $this->spend($user, $mission, $cost, self::TYPE_HINT_USED,
            'Hint '.$hintNumber.' on mission: '.$mission->title);
    }

    /**
     * Deducts the cost of revealing the full solution. Returns false when the
     * student cannot afford it.
     */
    public function spendSolutionReveal(User $user, Mission $mission): bool
    {
        return $this->spend($user, $mission, self::REVEAL_COST, self::TYPE_SOLUTION_REVEALED,
            'Solution reveal on mission: '.$mission->title);
    }

    public function hintCost(int $hintNumber): int
    {
        return self::HINT_COSTS[$hintNumber] ?? self::HINT_COSTS[max(1, min(3, $hintNumber))];
    }

    public function revealCost(): int
    {
        return self::REVEAL_COST;
    }

    public function assessmentPassedAmount(): int
    {
        return self::ASSESSMENT_PASSED_AMOUNT;
    }

    public function wrongSubmissionCost(): int
    {
        return self::WRONG_SUBMISSION_COST;
    }

    public function canAfford(User $user, int $cost): bool
    {
        return $this->balance($user) >= $cost;
    }

    /**
     * How many hints a user has already paid to reveal on a mission.
     */
    public function revealedHintCount(User $user, Mission $mission): int
    {
        return (int) XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->where('type', self::TYPE_HINT_USED)
            ->count();
    }

    /**
     * Whether the user has paid to reveal the full solution on a mission.
     */
    public function hasRevealedSolution(User $user, Mission $mission): bool
    {
        return XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->where('type', self::TYPE_SOLUTION_REVEALED)
            ->exists();
    }

    /**
     * The student's recent XP ledger history, newest first, as display rows.
     * The balance shown beside this list must come from balance(); nothing
     * here recomputes a second authoritative total (§18.0/§20.0). Rows expose
     * no internal database IDs, validation rules, or grading logic.
     *
     * @return Collection<int, array{
     *     amount: int,
     *     direction: 'credit'|'debit',
     *     reason: string,
     *     source: 'mission'|'assessment',
     *     at: Carbon,
     * }>
     */
    public function transactionHistory(User $user, int $limit = 30): Collection
    {
        return XpTransaction::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (XpTransaction $txn): array => $this->presentTransaction($txn));
    }

    /**
     * Fleet-wide XP administration statistics (US-710, §30.0). A read-only
     * aggregate over the ledger, never a per-student fan-out: one grouped
     * query classifies every row by its TYPE (the action's intent) — awarded =
     * mission + assessment credits, spent = hints + solution reveals, deducted
     * = wrong-submission penalties. Outstanding is the net ledger sum
     * (awarded − spent − deducted). Amounts for spend/deduct groups use the
     * absolute value, so a penalty clamped to a 0-amount row still counts
     * with the direction its type declares.
     *
     * @return array{
     *     awarded: int,
     *     spent: int,
     *     deducted: int,
     *     outstanding: int,
     *     accounts: int,
     *     by_type: list<array{
     *         type: string,
     *         label: string,
     *         direction: 'award'|'spend'|'deduct',
     *         entries: int,
     *         total: int,
     *     }>,
     * }
     */
    public function fleetSummary(): array
    {
        $rows = XpTransaction::query()
            ->toBase()
            ->selectRaw('type, COUNT(*) as entries, COALESCE(SUM(amount), 0) as net')
            ->groupBy('type')
            ->get();

        $awarded = 0;
        $spent = 0;
        $deducted = 0;
        $byType = [];

        foreach ($rows as $row) {
            $type = (string) $row->type;
            $net = (int) $row->net;
            $entries = (int) $row->entries;

            if (in_array($type, self::AWARD_TYPES, true)) {
                $awarded += $net;
                $direction = 'award';
                $total = $net;
            } elseif (in_array($type, self::SPEND_TYPES, true)) {
                $spent += -$net;
                $direction = 'spend';
                $total = -$net;
            } else {
                $deducted += -$net;
                $direction = 'deduct';
                $total = -$net;
            }

            $byType[] = [
                'type' => $type,
                'label' => self::TYPE_LABELS[$type] ?? $type,
                'direction' => $direction,
                'entries' => $entries,
                'total' => max(0, $total),
            ];
        }

        $orderIndex = array_flip(self::TYPE_ORDER);

        usort($byType, function (array $a, array $b) use ($orderIndex): int {
            return ($orderIndex[$a['type']] ?? PHP_INT_MAX) <=> ($orderIndex[$b['type']] ?? PHP_INT_MAX);
        });

        return [
            'awarded' => $awarded,
            'spent' => $spent,
            'deducted' => $deducted,
            'outstanding' => $awarded - $spent - $deducted,
            'accounts' => (int) XpTransaction::query()->distinct()->count('user_id'),
            'by_type' => $byType,
        ];
    }

    /**
     * @return array{
     *     amount: int,
     *     direction: 'credit'|'debit',
     *     reason: string,
     *     source: 'mission'|'assessment',
     *     at: Carbon,
     * }
     */
    private function presentTransaction(XpTransaction $txn): array
    {
        return [
            'amount' => $txn->amount,
            'direction' => in_array($txn->type, self::DEBIT_TYPES, true) ? 'debit' : 'credit',
            'reason' => $txn->description ?? $txn->type,
            'source' => $txn->assessment_id !== null ? 'assessment' : 'mission',
            'at' => Carbon::parse($txn->created_at),
        ];
    }

    /**
     * General spend: deducts cost if affordable, else returns false.
     */
    private function spend(User $user, Mission $mission, int $cost, string $type, string $description): bool
    {
        if ($cost <= 0 || $this->balance($user) < $cost) {
            return false;
        }

        DB::transaction(function () use ($user, $mission, $cost, $type, $description): void {
            $this->spendFrom($user, $mission, $cost, $type, $description);
        });

        return true;
    }

    /**
     * Records a negative transaction clamped against the current balance so
     * the running total never goes below zero.
     */
    private function spendFrom(User $user, Mission $mission, int $cost, string $type, string $description): void
    {
        $current = $this->sumFor($user);

        if ($cost >= $current) {
            $cost = $current;
        }

        if ($cost <= 0) {
            $this->record($user, $mission, null, 0, $type, $description);

            return;
        }

        $this->record($user, $mission, null, -$cost, $type, $description);
    }

    /**
     * Writes a ledger row. Exactly one of the two source references must be
     * set: the populated FK identifies the source (§25). There is no
     * source_type column; mission transactions carry mission_id (assessment_id
     * null), assessment transactions the mirror image. The version reference
     * rides alongside its FK: the curriculum revision in force when the
     * transaction was recorded. Versions are evidence only — nothing here or
     * downstream may recompute amounts from them.
     */
    private function record(User $user, ?Mission $mission, ?Assessment $assessment, int $amount, string $type, string $description): void
    {
        if (($mission === null) === ($assessment === null)) {
            throw new InvalidArgumentException('An XP transaction must reference exactly one of a mission or an assessment.');
        }

        XpTransaction::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission?->id,
            'mission_version' => $mission?->version,
            'assessment_id' => $assessment?->id,
            'assessment_version' => $assessment?->version,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
        ]);
    }

    private function sumFor(User $user): int
    {
        return (int) XpTransaction::query()
            ->where('user_id', $user->id)
            ->sum('amount');
    }
}
