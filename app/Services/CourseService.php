<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The only write path for the course catalog (US-705, §15.0–§17.0). Courses
 * are listed in order_num order (the single ordering mechanism; no second
 * sort is invented), and update() writes ONLY the course row — a name,
 * slug, type, description, status, or order_num change never touches
 * the404_progress, so no course edit can silently rewrite a student's
 * per-mission progress.
 *
 * course.status (active|locked|draft) is an ACCESS GATE (US-705, confirmed
 * by the user): 'locked' and 'draft' seal the course's missions (Mission
 * Controller ::ensureCourseActive, 403) and its Boss Challenge (Assessment
 * Service ::isUnlocked requires course.status === 'active', surfaced as
 * "Challenge sealed"), so a directly-URL'd mission can no longer accrue
 * progress, XP, or achievements under a sealed course. Locking also removes
 * the course from every status='active' consumer (DashboardService::
 * currentCourse, CompetencyService, CourseAnalyticsService universe,
 * AchievementService full_clear, RecommendationService, AttentionService).
 * Progress recorded before the seal is preserved; only NEW use is blocked.
 */
class CourseService
{
    /**
     * The exact set of catalog statuses an update may assign, mirrored from
     * the the404_courses enum column and enforced here as defense-in-depth
     * (the FormRequest validates first). Server-determined; no free strings.
     */
    public const STATUSES = ['active', 'locked', 'draft'];

    public function __construct(
        private readonly AdminAuditService $audit,
    ) {}

    /**
     * The whole catalog in progression order. The listing's only ordering
     * key is order_num (preserved from the existing data model — no second
     * ordering mechanism is introduced).
     *
     * @return Collection<int, Course>
     */
    public function ordered(): Collection
    {
        return Course::query()
            ->withCount('sections')
            ->withCount('missions')
            ->withCount('assessment')
            ->orderBy('order_num')
            ->get();
    }

    /**
     * Update a course's editable fields plus its optional status. Guards and
     * allow-list checks run BEFORE any write, so a refused update leaves the
     * course untouched. Only the course row is written; student Progress rows
     * are never selected, deleted, or rewritten here.
     *
     * An actual status change is recorded as 'course.status.change'; any
     * actual non-status field change is recorded as 'course.update'. An
     * unchanged submission (no-op round-trip) records nothing, mirroring
     * UserService. A material field change also bumps the course version by
     * one (status-only transitions do not) and names the transition in the
     * audit summary. A value outside the allow lists is recorded as a 'failed'
     * audit row for the course before InvalidArgumentException is thrown, so
     * a rejected attempt is never silent.
     *
     * @param  array{name: string, slug: string, type: string, description?: string|null, status: string, order_num: mixed}  $attributes
     */
    public function update(User $actor, Course $course, array $attributes): Course
    {
        $status = (string) $attributes['status'];

        if (! in_array($status, self::STATUSES, true)) {
            $this->refuse($actor, $course, "Unknown status '{$status}'.");
        }

        $name = (string) $attributes['name'];
        $slug = (string) $attributes['slug'];
        $type = (string) $attributes['type'];
        $description = array_key_exists('description', $attributes)
            ? (string) $attributes['description']
            : (string) $course->description;
        $orderNum = $attributes['order_num'];

        if ($name === '' || mb_strlen($name) > 128) {
            $this->refuse($actor, $course, 'Course name must be a non-empty string of at most 128 characters.');
        }

        if ($slug === '' || mb_strlen($slug) > 64) {
            $this->refuse($actor, $course, 'Course slug must be a non-empty string of at most 64 characters.');
        }

        if ($type === '' || mb_strlen($type) > 16) {
            $this->refuse($actor, $course, 'Course type must be a non-empty string of at most 16 characters.');
        }

        if (! is_int($orderNum) || $orderNum < 0) {
            $this->refuse($actor, $course, 'Course order must be a non-negative integer.');
        }

        $before = [
            'name' => $course->name,
            'slug' => $course->slug,
            'type' => $course->type,
            'description' => $course->description ?? '',
            'order_num' => $course->order_num,
        ];

        $versionBefore = $course->version;

        $course->name = $name;
        $course->slug = $slug;
        $course->type = $type;
        $course->description = $description === '' ? null : $description;
        $course->order_num = $orderNum;
        $course->save();

        $summary = $this->fieldChangesSummary($before, $name, $slug, $type, $description, $orderNum);

        if ($course->wasChanged('version')) {
            $transition = "version {$versionBefore} → {$course->version}";
            $summary = $summary !== null ? $summary.', '.$transition : $transition;
        }

        if ($summary !== null) {
            $this->audit->record(
                $actor,
                AdminAuditService::ACTION_COURSE_UPDATE,
                'Course updated: '.$summary,
                targetType: 'course',
                targetId: $course->id,
            );
        }

        if ($status !== $course->status) {
            $from = $course->status;
            $course->status = $status;
            $course->save();

            $this->audit->record(
                $actor,
                AdminAuditService::ACTION_COURSE_STATUS_CHANGE,
                "Status changed: {$from} → {$status}",
                targetType: 'course',
                targetId: $course->id,
            );
        }

        return $course;
    }

    /**
     * Record a failed course-update attempt, then throw. Consistent with
     * UserService's refusal flow: the ledger carries the row before the
     * exception propagates.
     *
     * @return never
     *
     * @throws InvalidArgumentException
     */
    private function refuse(User $actor, Course $course, string $reason): void
    {
        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_COURSE_UPDATE,
            'Refused: '.$reason,
            'failed',
            'course',
            $course->id,
        );

        throw new InvalidArgumentException($reason);
    }

    /**
     * A compact "field → value" list of the non-status fields that actually
     * changed, or null when nothing non-status changed (a no-op round-trip
     * records no audit row).
     *
     * @param  array{name: string, slug: string, type: string, description: string, order_num: int}  $before
     */
    private function fieldChangesSummary(array $before, string $name, string $slug, string $type, string $description, int $orderNum): ?string
    {
        $changes = [];

        if ($name !== $before['name']) {
            $changes[] = "name → '{$name}'";
        }

        if ($slug !== $before['slug']) {
            $changes[] = "slug → '{$slug}'";
        }

        if ($type !== $before['type']) {
            $changes[] = "type → '{$type}'";
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
