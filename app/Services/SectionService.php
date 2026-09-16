<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The only write path for course sections (US-706, §18.0). Sections are
 * the middle level of the Course → Section → Mission hierarchy: they group
 * missions inside a course and carry no status of their own — the access
 * gate for everything beneath them sits on course.status (US-705), never on
 * the section row.
 *
 * update() writes ONLY the section row (title / description / order_num);
 * it never selects, deletes, or rewrites the404_missions or the404_progress,
 * so the hierarchy and every student's per-mission progress stay intact.
 * Sections are listed per-course in order_num order — the same single
 * ordering key the learning path uses — and nothing here re-orders missions.
 */
class SectionService
{
    public function __construct(
        private readonly AdminAuditService $audit,
    ) {}

    /**
     * The sections of one course in display order. order_num is the sole
     * ordering key (mirrors CourseService::ordered / LearningPathService);
     * the id is a deterministic tiebreaker only, never a second ordering
     * mechanism.
     *
     * @return Collection<int, Section>
     */
    public function forCourse(Course $course): Collection
    {
        return Section::query()
            ->where('course_id', $course->id)
            ->withCount('missions')
            ->orderBy('order_num')
            ->orderBy('id')
            ->get();
    }

    /**
     * Update a section's editable fields. Guards run BEFORE any write, so a
     * refused update leaves the section untouched: the section must belong to
     * the given course (the nested {course}/sections/{section} route must not
     * let a section leak across courses), and the allow-list re-checks happen
     * here as defense-in-depth over the FormRequest.
     *
     * An actual change records 'section.update'; a no-op round-trip records
     * nothing, mirroring UserService and CourseService. A value outside the
     * allow list, or a cross-course section, records a 'failed' audit row
     * before InvalidArgumentException is thrown, so a rejected attempt is
     * never silent.
     *
     * @param  array{title: string, description?: string|null, order_num: mixed}  $attributes
     */
    public function update(User $actor, Course $course, Section $section, array $attributes): Section
    {
        if ($section->course_id !== $course->id) {
            $this->refuse($actor, $section, 'This section does not belong to the given course.');
        }

        $title = (string) $attributes['title'];
        $description = array_key_exists('description', $attributes)
            ? (string) $attributes['description']
            : (string) $section->description;
        $orderNum = $attributes['order_num'];

        if ($title === '' || mb_strlen($title) > 128) {
            $this->refuse($actor, $section, 'Section title must be a non-empty string of at most 128 characters.');
        }

        if (! is_int($orderNum) || $orderNum < 0) {
            $this->refuse($actor, $section, 'Section order must be a non-negative integer.');
        }

        $before = [
            'title' => $section->title,
            'description' => $section->description ?? '',
            'order_num' => $section->order_num,
        ];

        $section->title = $title;
        $section->description = $description === '' ? null : $description;
        $section->order_num = $orderNum;
        $section->save();

        $summary = $this->fieldChangesSummary($before, $title, $description, $orderNum);

        if ($summary !== null) {
            $this->audit->record(
                $actor,
                AdminAuditService::ACTION_SECTION_UPDATE,
                'Section updated: '.$summary,
                targetType: 'section',
                targetId: $section->id,
            );
        }

        return $section;
    }

    /**
     * Record a failed section-update attempt, then throw. Consistent with
     * CourseService's refusal flow: the ledger carries the row before the
     * exception propagates.
     *
     * @return never
     *
     * @throws InvalidArgumentException
     */
    private function refuse(User $actor, Section $section, string $reason): void
    {
        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_SECTION_UPDATE,
            'Refused: '.$reason,
            'failed',
            'section',
            $section->id,
        );

        throw new InvalidArgumentException($reason);
    }

    /**
     * A compact "field → value" list of the fields that actually changed, or
     * null when nothing changed (a no-op round-trip records no audit row).
     *
     * @param  array{title: string, description: string, order_num: int}  $before
     */
    private function fieldChangesSummary(array $before, string $title, string $description, int $orderNum): ?string
    {
        $changes = [];

        if ($title !== $before['title']) {
            $changes[] = "title → '{$title}'";
        }

        if ($description !== $before['description']) {
            $changes[] = "description → '{$description}'";
        }

        if ($orderNum !== $before['order_num']) {
            $changes[] = "order_num → {$orderNum}";
        }

        return $changes === [] ? null : implode(', ', $changes);
    }
}
