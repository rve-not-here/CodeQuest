<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The only admin write path for a course's Boss Challenge (US-708, §24.0).
 * One course has exactly one assessment — the the404_assessments unique
 * course_id constraint (§3.2) — so the write target is always a single row.
 *
 * update() writes ONLY the assessment's editable fields (title, description,
 * instructions, passing_score, status). grading_rule is deliberately NOT
 * writable in this story (§24 view-only: the grading rules engine is authored
 * via the AssessmentSeeder, the same controlled path as solution_code and
 * validate_rule on missions); it can never pass through this write path even
 * if a crafted payload smuggles it. The write never touches the404_assessment_
 * attempts, the404_xp_transactions, the404_progress, or course completion
 * (§26.0: pass/fail, score, attempt results, and course completion are academic
 * records, not admin-editable configuration — they stay derived from attempt
 * history, never rewritten here).
 *
 * assessment.status (active|locked|draft) is an ACCESS GATE, exactly like
 * course.status (US-705): 'locked' and 'draft' seal the Boss Challenge
 * (AssessmentService::isUnlocked requires assessment.status === 'active',
 * surfaced to students as "Challenge sealed"). Sealing never rewrites recorded
 * attempts — it only blocks new use. passing_score edits are never retroactive
 * either: evaluateAttempt reads it at evaluation time, but score/status are
 * already persisted to the attempt row, so a threshold edit changes only
 * FUTURE submissions, never a verdict already on the books.
 */
class AdminAssessmentService
{
    /**
     * The exact set of assessment statuses an update may assign, mirrored from
     * the the404_assessments enum column and enforced here as defense-in-depth
     * (the FormRequest validates first). Server-determined; no free strings.
     */
    public const STATUSES = ['active', 'locked', 'draft'];

    public function __construct(
        private readonly AdminAuditService $audit,
    ) {}

    /**
     * The single Boss Challenge of one course, if one exists. The unique
     * course_id constraint guarantees at most one row.
     */
    public function forCourse(Course $course): ?Assessment
    {
        return Assessment::query()
            ->where('course_id', $course->id)
            ->first();
    }

    /**
     * Update a course's Boss Challenge. Guards and allow-list checks run
     * BEFORE any write, so a refused update leaves the assessment untouched:
     * the assessment must belong to the given course (the nested
     * {course}/assessment/{assessment} route must not let a challenge leak
     * across courses), title non-empty at most 128 chars, passing_score a
     * non-negative integer, status one of STATUSES.
     *
     * An actual change records 'assessment.update'; a status change records a
     * separate 'assessment.status.change' row (mirroring CourseService); a
     * no-op round-trip records nothing. A material field change also bumps
     * the assessment version by one (status-only transitions do not) and
     * names the transition in the audit summary. A value outside the allow lists, or a
     * cross-course assessment, records a 'failed' audit row before
     * InvalidArgumentException is thrown, so a rejected attempt is never
     * silent.
     *
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     instructions?: string|null,
     *     passing_score: mixed,
     *     status: string,
     * }  $attributes
     */
    public function update(User $actor, Course $course, Assessment $assessment, array $attributes): Assessment
    {
        if ($assessment->course_id !== $course->id) {
            $this->refuse($actor, $assessment, 'This assessment does not belong to the given course.');
        }

        $title = (string) $attributes['title'];
        $description = array_key_exists('description', $attributes)
            ? (string) $attributes['description']
            : (string) $assessment->description;
        $instructions = array_key_exists('instructions', $attributes)
            ? (string) $attributes['instructions']
            : (string) $assessment->instructions;
        $passingScore = $attributes['passing_score'];
        $status = (string) $attributes['status'];

        if ($title === '' || mb_strlen($title) > 128) {
            $this->refuse($actor, $assessment, 'Assessment title must be a non-empty string of at most 128 characters.');
        }

        if (! is_int($passingScore) || $passingScore < 0 || $passingScore > 100) {
            $this->refuse($actor, $assessment, 'Assessment passing score must be an integer between 0 and 100.');
        }

        if (! in_array($status, self::STATUSES, true)) {
            $this->refuse($actor, $assessment, "Unknown assessment status '{$status}'.");
        }

        return DB::transaction(function () use ($actor, $assessment, $title, $description, $instructions, $passingScore, $status): Assessment {
            Assessment::query()->whereKey($assessment->id)->lockForUpdate()->firstOrFail();
            $assessment->refresh();

            $before = [
                'title' => $assessment->title,
                'description' => $assessment->description ?? '',
                'instructions' => $assessment->instructions ?? '',
                'passing_score' => $assessment->passing_score,
            ];

            $versionBefore = $assessment->version;

            $assessment->title = $title;
            $assessment->description = $description === '' ? null : $description;
            $assessment->instructions = $instructions === '' ? null : $instructions;
            $assessment->passing_score = $passingScore;
            $assessment->save();

            $summary = $this->fieldChangesSummary($before, $title, $description, $instructions, $passingScore);

            if ($assessment->wasChanged('version')) {
                $transition = "version {$versionBefore} → {$assessment->version}";
                $summary = $summary !== null ? $summary.', '.$transition : $transition;
            }

            if ($summary !== null) {
                $this->audit->record(
                    $actor,
                    AdminAuditService::ACTION_ASSESSMENT_UPDATE,
                    'Assessment updated: '.$summary,
                    targetType: 'assessment',
                    targetId: $assessment->id,
                );
            }

            if ($status !== $assessment->status) {
                $from = $assessment->status;
                $assessment->status = $status;
                $assessment->save();

                $this->audit->record(
                    $actor,
                    AdminAuditService::ACTION_ASSESSMENT_STATUS_CHANGE,
                    "Assessment status changed: {$from} → {$status}",
                    targetType: 'assessment',
                    targetId: $assessment->id,
                );
            }

            return $assessment;
        }, attempts: 3);
    }

    /**
     * Record a failed assessment-update attempt, then throw. Consistent with
     * CourseService's and SectionService's refusal flow: the ledger carries the
     * row before the exception propagates.
     *
     * @return never
     *
     * @throws InvalidArgumentException
     */
    private function refuse(User $actor, Assessment $assessment, string $reason): void
    {
        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_ASSESSMENT_UPDATE,
            'Refused: '.$reason,
            'failed',
            'assessment',
            $assessment->id,
        );

        throw new InvalidArgumentException($reason);
    }

    /**
     * A compact "field → value" list of the editable fields that actually
     * changed, or null when nothing changed (a no-op round-trip records no
     * audit row). Status is excluded here — it is recorded separately as
     * 'assessment.status.change'. grading_rule never appears: it is not
     * writable.
     *
     * @param  array{
     *     title: string,
     *     description: string,
     *     instructions: string,
     *     passing_score: int,
     * }  $before
     */
    private function fieldChangesSummary(
        array $before,
        string $title,
        string $description,
        string $instructions,
        int $passingScore,
    ): ?string {
        $changes = [];

        if ($title !== $before['title']) {
            $changes[] = "title → '{$title}'";
        }

        if ($description !== $before['description']) {
            $changes[] = "description → '{$description}'";
        }

        if ($instructions !== $before['instructions']) {
            $changes[] = "instructions → '{$instructions}'";
        }

        if ($passingScore !== $before['passing_score']) {
            $changes[] = "passing_score → {$passingScore}";
        }

        return $changes === [] ? null : implode(', ', $changes);
    }
}
