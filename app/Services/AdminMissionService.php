<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The only admin write path for missions (US-707, §19.0). Missions are the
 * leaves of the Course → Section → Mission hierarchy and carry NO status of
 * their own — the access gate for everything beneath a course sits on
 * course.status (US-705) and assessment.status, never on the mission row, so
 * there is no status field to manage here.
 *
 * update() writes ONLY the mission's editable fields (title, description,
 * difficulty, points, order_num, section_id, hints, broken_code, target_html).
 * solution_code and validate_rule are deliberately NOT writable in this story
 * (§19 view-only: the editing of the reference solution and validation rules
 * is deferred); they can never pass through this write path even if a crafted
 * payload smuggles them. The write never touches the404_progress, the
 * the404_xp_transactions, or the404_activity, so changing points (or any
 * field) never rewrites history: rows already on the books keep the points
 * they were awarded at completion time — only future completions see the new
 * value.
 */
class AdminMissionService
{
    /**
     * The mission difficulty names, mirrored from the the404_missions enum
     * column and enforced here as defense-in-depth (the FormRequest validates
     * first). Server-determined; no free strings.
     *
     * @var array<int, string>
     */
    public const DIFFICULTIES = ['EASY', 'MEDIUM', 'HARD'];

    public function __construct(
        private readonly AdminAuditService $audit,
    ) {}

    /**
     * The missions of one course in display order, with their section. The
     * listing's ordering key is order_num then id — the same single ordering
     * mechanism the learning path uses.
     *
     * @return Collection<int, Mission>
     */
    public function forCourse(Course $course): Collection
    {
        return Mission::query()
            ->where('course_id', $course->id)
            ->with('section')
            ->orderBy('order_num')
            ->orderBy('id')
            ->get();
    }

    /**
     * Update a mission's editable fields. Guards and allow-list checks run
     * BEFORE any write, so a refused update leaves the mission untouched: the
     * mission must belong to the given course (the nested {course}/missions/
     * {mission} route must not let a mission leak across courses), difficulty
     * must be one of DIFFICULTIES, points and order_num non-negative integers,
     * and a section_id must reference a real section of the SAME course as the
     * mission (the schema's FK alone does not stop a cross-course assignment —
     * a composite (course_id, section_id) constraint does not exist, so the
     * value is enforced here).
     *
     * An actual change records 'mission.update'; a no-op round-trip records
     * nothing, mirroring CourseService and SectionService. A material change
     * also bumps the mission version by one and names the transition in the
     * audit summary. A value outside the
     * allow lists records a 'failed' audit row before InvalidArgumentException
     * is thrown, so a rejected attempt is never silent.
     *
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     difficulty: string,
     *     points: mixed,
     *     order_num: mixed,
     *     section_id: int|null,
     *     hints?: string|null,
     *     broken_code?: string|null,
     *     target_html?: string|null,
     * }  $attributes
     */
    public function update(User $actor, Course $course, Mission $mission, array $attributes): Mission
    {
        if ($mission->course_id !== $course->id) {
            $this->refuse($actor, $mission, 'This mission does not belong to the given course.');
        }

        $title = (string) $attributes['title'];
        $description = array_key_exists('description', $attributes)
            ? (string) $attributes['description']
            : (string) $mission->description;
        $difficulty = (string) $attributes['difficulty'];
        $points = $attributes['points'];
        $orderNum = $attributes['order_num'];
        $sectionId = $attributes['section_id'];
        $hints = array_key_exists('hints', $attributes)
            ? (string) $attributes['hints']
            : (string) $mission->hints;
        $brokenCode = array_key_exists('broken_code', $attributes)
            ? (string) $attributes['broken_code']
            : (string) $mission->broken_code;
        $targetHtml = array_key_exists('target_html', $attributes)
            ? (string) $attributes['target_html']
            : (string) $mission->target_html;

        if ($title === '' || mb_strlen($title) > 128) {
            $this->refuse($actor, $mission, 'Mission title must be a non-empty string of at most 128 characters.');
        }

        if (! in_array($difficulty, self::DIFFICULTIES, true)) {
            $this->refuse($actor, $mission, "Unknown mission difficulty '{$difficulty}'.");
        }

        if (! is_int($points) || $points < 0) {
            $this->refuse($actor, $mission, 'Mission points must be a non-negative integer.');
        }

        if (! is_int($orderNum) || $orderNum < 0) {
            $this->refuse($actor, $mission, 'Mission order must be a non-negative integer.');
        }

        if ($sectionId !== null) {
            if ($sectionId < 0) {
                $this->refuse($actor, $mission, 'Mission section id must be a positive integer.');
            }

            $section = Section::query()->find($sectionId);

            if ($section === null || $section->course_id !== $mission->course_id) {
                $this->refuse($actor, $mission, 'Mission section must belong to the same course as the mission.');
            }
        }

        return DB::transaction(function () use ($actor, $mission, $title, $description, $difficulty, $points, $orderNum, $sectionId, $hints, $brokenCode, $targetHtml): Mission {
            Mission::query()->whereKey($mission->id)->lockForUpdate()->firstOrFail();
            $mission->refresh();

            $before = [
                'title' => $mission->title,
                'description' => $mission->description ?? '',
                'difficulty' => $mission->difficulty,
                'points' => $mission->points,
                'order_num' => $mission->order_num,
                'section_id' => $mission->section_id,
                'hints' => $mission->hints ?? '',
                'broken_code' => $mission->broken_code ?? '',
                'target_html' => $mission->target_html ?? '',
            ];

            $versionBefore = $mission->version;

            $mission->title = $title;
            $mission->description = $description === '' ? null : $description;
            $mission->difficulty = $difficulty;
            $mission->points = $points;
            $mission->order_num = $orderNum;
            $mission->section_id = $sectionId;
            $mission->hints = $hints === '' ? null : $hints;
            $mission->broken_code = $brokenCode === '' ? null : $brokenCode;
            $mission->target_html = $targetHtml === '' ? null : $targetHtml;
            $mission->save();

            $summary = $this->fieldChangesSummary(
                $before,
                $title,
                $description,
                $difficulty,
                $points,
                $orderNum,
                $sectionId,
                $hints,
                $brokenCode,
                $targetHtml,
            );

            if ($mission->wasChanged('version')) {
                $transition = "version {$versionBefore} → {$mission->version}";
                $summary = $summary !== null ? $summary.', '.$transition : $transition;
            }

            if ($summary !== null) {
                $this->audit->record(
                    $actor,
                    AdminAuditService::ACTION_MISSION_UPDATE,
                    'Mission updated: '.$summary,
                    targetType: 'mission',
                    targetId: $mission->id,
                );
            }

            return $mission;
        }, attempts: 3);
    }

    /**
     * Record a failed mission-update attempt, then throw. Consistent with
     * CourseService's and SectionService's refusal flow: the ledger carries
     * the row before the exception propagates.
     *
     * @return never
     *
     * @throws InvalidArgumentException
     */
    private function refuse(User $actor, Mission $mission, string $reason): void
    {
        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_MISSION_UPDATE,
            'Refused: '.$reason,
            'failed',
            'mission',
            $mission->id,
        );

        throw new InvalidArgumentException($reason);
    }

    /**
     * A compact "field → value" list of the editable fields that actually
     * changed, or null when nothing changed (a no-op round-trip records no
     * audit row). solution_code and validate_rule never appear here — they are
     * not writable in this story.
     *
     * @param  array{
     *     title: string,
     *     description: string,
     *     difficulty: string,
     *     points: int,
     *     order_num: int,
     *     section_id: int|null,
     *     hints: string,
     *     broken_code: string,
     *     target_html: string,
     * }  $before
     */
    private function fieldChangesSummary(
        array $before,
        string $title,
        string $description,
        string $difficulty,
        mixed $points,
        mixed $orderNum,
        mixed $sectionId,
        string $hints,
        string $brokenCode,
        string $targetHtml,
    ): ?string {
        $changes = [];

        if ($title !== $before['title']) {
            $changes[] = "title → '{$title}'";
        }

        if ($description !== $before['description']) {
            $changes[] = "description → '{$description}'";
        }

        if ($difficulty !== $before['difficulty']) {
            $changes[] = "difficulty → '{$difficulty}'";
        }

        if ($points !== $before['points']) {
            $changes[] = "points → {$points}";
        }

        if ($orderNum !== $before['order_num']) {
            $changes[] = "order_num → {$orderNum}";
        }

        if ($sectionId !== $before['section_id']) {
            $changes[] = 'section_id → '.($sectionId ?? 'null');
        }

        if ($hints !== $before['hints']) {
            $changes[] = "hints → '{$hints}'";
        }

        if ($brokenCode !== $before['broken_code']) {
            $changes[] = "broken_code → '{$brokenCode}'";
        }

        if ($targetHtml !== $before['target_html']) {
            $changes[] = "target_html → '{$targetHtml}'";
        }

        return $changes === [] ? null : implode(', ', $changes);
    }
}
