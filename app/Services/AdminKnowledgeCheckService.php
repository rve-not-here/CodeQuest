<?php

namespace App\Services;

use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The admin write path for Knowledge Check metadata (P1-1).
 *
 * Required Knowledge Checks gate mission work through KnowledgeCheckGate, but
 * before this path the required/optional flag and the check lifecycle were
 * only writable by seeders and direct database work. That made a real
 * prerequisite impossible to operate: content could not be flagged, corrected,
 * or withdrawn without a deployment.
 *
 * update() writes only metadata — title (a display label), instructions, the
 * required gate, and the lifecycle status. Question and option content, the
 * check sequence, and mission attribution stay out of this story's scope and
 * continue to be governed by the curriculum release path. The write never
 * touches attempts, responses, progress, or XP, so recorded learning is never
 * rewritten.
 *
 * A material change (instructions or is_required) bumps the check's version
 * through HasCurriculumVersion. Status-only transitions never bump it. Every
 * applied change records 'knowledge_check.update'; a refused attempt records a
 * 'failed' row before throwing, so a rejected edit is never silent.
 */
class AdminKnowledgeCheckService
{
    /**
     * The lifecycle statuses an update may assign, mirrored from the
     * the404_knowledge_checks enum and enforced here as defense-in-depth (the
     * FormRequest validates first).
     *
     * @var list<string>
     */
    public const STATUSES = [
        KnowledgeCheck::STATUS_DRAFT,
        KnowledgeCheck::STATUS_PUBLISHED,
        KnowledgeCheck::STATUS_ARCHIVED,
    ];

    public function __construct(
        private readonly AdminAuditService $audit,
    ) {}

    /**
     * Checks for one mission in display order, with question counts for the
     * listing. The mission route binding already constrains the parent; the
     * caller still checks that the mission belongs to the course.
     *
     * @return Collection<int, KnowledgeCheck>
     */
    public function forMission(Mission $mission): Collection
    {
        return KnowledgeCheck::query()
            ->where('mission_id', $mission->id)
            ->withCount('questions')
            ->orderBy('order_num')
            ->orderBy('id')
            ->get();
    }

    /**
     * Update a check's metadata. Guards and allow-list checks run before any
     * write, so a refused update leaves the row untouched: the check must
     * belong to the mission, and the mission to the course (the nested route
     * must not let a check leak across curricula).
     *
     * @param  array{
     *     title: string,
     *     instructions?: string|null,
     *     is_required: mixed,
     *     status: string,
     * }  $attributes
     */
    public function update(User $actor, Course $course, Mission $mission, KnowledgeCheck $check, array $attributes): KnowledgeCheck
    {
        if ($check->mission_id !== $mission->id) {
            $this->refuse($actor, $check, 'This Knowledge Check does not belong to the given mission.');
        }

        if ($mission->course_id !== $course->id) {
            $this->refuse($actor, $check, 'This mission does not belong to the given course.');
        }

        $title = (string) $attributes['title'];
        $instructions = array_key_exists('instructions', $attributes)
            ? (string) $attributes['instructions']
            : (string) $check->instructions;
        $isRequired = $attributes['is_required'];
        $status = (string) $attributes['status'];

        if ($title === '' || mb_strlen($title) > 128) {
            $this->refuse($actor, $check, 'Knowledge Check title must be a non-empty string of at most 128 characters.');
        }

        if (! is_bool($isRequired)) {
            $this->refuse($actor, $check, 'Knowledge Check required flag must be a boolean.');
        }

        if (! in_array($status, self::STATUSES, true)) {
            $this->refuse($actor, $check, "Unknown Knowledge Check status '{$status}'.");
        }

        return DB::transaction(function () use ($actor, $check, $title, $instructions, $isRequired, $status): KnowledgeCheck {
            KnowledgeCheck::query()->whereKey($check->id)->lockForUpdate()->firstOrFail();
            $check->refresh();

            $before = [
                'title' => $check->title,
                'instructions' => $check->instructions ?? '',
                'is_required' => $check->is_required,
            ];
            $versionBefore = $check->version;

            $check->title = $title;
            $check->instructions = $instructions === '' ? null : $instructions;
            $check->is_required = $isRequired;
            $check->save();

            $summary = $this->fieldChangesSummary($before, $title, $instructions, $isRequired);

            if ($check->wasChanged('version')) {
                $transition = "version {$versionBefore} → {$check->version}";
                $summary = $summary !== null ? $summary.', '.$transition : $transition;
            }

            if ($summary !== null) {
                $this->audit->record(
                    $actor,
                    AdminAuditService::ACTION_KNOWLEDGE_CHECK_UPDATE,
                    'Knowledge Check updated: '.$summary,
                    targetType: 'knowledge_check',
                    targetId: $check->id,
                );
            }

            if ($status !== $check->status) {
                $from = $check->status;
                $check->status = $status;
                $check->save();

                $this->audit->record(
                    $actor,
                    AdminAuditService::ACTION_KNOWLEDGE_CHECK_UPDATE,
                    "Knowledge Check status changed: {$from} → {$status}",
                    targetType: 'knowledge_check',
                    targetId: $check->id,
                );
            }

            return $check;
        }, attempts: 3);
    }

    /**
     * Record a failed update attempt, then throw.
     *
     * @return never
     *
     * @throws InvalidArgumentException
     */
    private function refuse(User $actor, KnowledgeCheck $check, string $reason): void
    {
        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_KNOWLEDGE_CHECK_UPDATE,
            'Refused: '.$reason,
            'failed',
            'knowledge_check',
            $check->id,
        );

        throw new InvalidArgumentException($reason);
    }

    /**
     * A compact "field → value" list of the fields that actually changed, or
     * null when nothing changed (a no-op round-trip records no audit row).
     * Status is recorded separately.
     *
     * @param  array{title: string, instructions: string, is_required: bool}  $before
     */
    private function fieldChangesSummary(array $before, string $title, string $instructions, bool $isRequired): ?string
    {
        $changes = [];

        if ($title !== $before['title']) {
            $changes[] = "title → '{$title}'";
        }

        if ($instructions !== $before['instructions']) {
            $changes[] = "instructions → '{$instructions}'";
        }

        if ($isRequired !== $before['is_required']) {
            $changes[] = $isRequired ? 'is_required → true' : 'is_required → false';
        }

        return $changes === [] ? null : implode(', ', $changes);
    }
}
