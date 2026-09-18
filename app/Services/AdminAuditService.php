<?php

namespace App\Services;

use App\Models\AdminAudit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Admin audit trail. recent() is the US-702 read side for the console's
 * "Recent system activity"; record() is the write side and the ONLY writer in
 * the codebase. feed() is the US-709 read side for the full trail on
 * /admin/activity. The guarded write paths call record() for every attempted
 * change: UserService (US-704, role/status; US-709, create and base fields)
 * and CourseService (US-705)/SectionService (US-706)/AdminMissionService
 * (US-707)/AdminAssessmentService (US-708) record 'success' rows for applied
 * changes and 'failed' rows for refusals, so a blocked change is never silent.
 */
class AdminAuditService
{
    public const ACTION_USER_CREATE = 'user.create';

    public const ACTION_USER_UPDATE = 'user.update';

    public const ACTION_ROLE_CHANGE = 'user.role.change';

    public const ACTION_STATUS_CHANGE = 'user.status.change';

    public const ACTION_COURSE_UPDATE = 'course.update';

    public const ACTION_COURSE_STATUS_CHANGE = 'course.status.change';

    public const ACTION_SECTION_UPDATE = 'section.update';

    public const ACTION_MISSION_UPDATE = 'mission.update';

    public const ACTION_ASSESSMENT_UPDATE = 'assessment.update';

    public const ACTION_ASSESSMENT_STATUS_CHANGE = 'assessment.status.change';

    public const ACTION_ANNOUNCEMENT_CREATE = 'announcement.create';

    public const ACTION_ANNOUNCEMENT_UPDATE = 'announcement.update';

    public const ACTION_ANNOUNCEMENT_PUBLISH = 'announcement.publish';

    public const ACTION_ANNOUNCEMENT_ARCHIVE = 'announcement.archive';

    public const ACTION_CLASSROOM_CREATE = 'classroom.create';

    public const ACTION_CLASSROOM_UPDATE = 'classroom.update';

    public const ACTION_CLASSROOM_TEACHERS = 'classroom.teachers';

    public const ACTION_CLASSROOM_STUDENTS = 'classroom.students';

    public const ACTION_CLASSROOM_COURSES = 'classroom.courses';

    /**
     * Every action the trail can produce, in display order, for the server-side
     * action filter.
     */
    public const ACTIONS = [
        self::ACTION_USER_CREATE,
        self::ACTION_USER_UPDATE,
        self::ACTION_ROLE_CHANGE,
        self::ACTION_STATUS_CHANGE,
        self::ACTION_COURSE_UPDATE,
        self::ACTION_COURSE_STATUS_CHANGE,
        self::ACTION_SECTION_UPDATE,
        self::ACTION_MISSION_UPDATE,
        self::ACTION_ASSESSMENT_UPDATE,
        self::ACTION_ASSESSMENT_STATUS_CHANGE,
        self::ACTION_ANNOUNCEMENT_CREATE,
        self::ACTION_ANNOUNCEMENT_UPDATE,
        self::ACTION_ANNOUNCEMENT_PUBLISH,
        self::ACTION_ANNOUNCEMENT_ARCHIVE,
        self::ACTION_CLASSROOM_CREATE,
        self::ACTION_CLASSROOM_UPDATE,
        self::ACTION_CLASSROOM_TEACHERS,
        self::ACTION_CLASSROOM_STUDENTS,
        self::ACTION_CLASSROOM_COURSES,
    ];

    public const FEED_PER_PAGE = 30;

    /**
     * @return Collection<int, AdminAudit>
     */
    public function recent(int $limit = 10): Collection
    {
        return AdminAudit::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * The full, filterable audit trail for /admin/activity (US-709, §27.0):
     * who acted (admin_user_id), on what action, with which result, inside an
     * optional date window. Every filter is pushed into SQL — the append-only
     * table is indexed on admin_user_id, action, created_at, and
     * (target_type, target_id). Rows are newest-first; a null filter narrows
     * nothing.
     *
     * @param  array<string, string>  $paginatorQuery  validated filters preserved
     *                                                 across pagination
     * @return LengthAwarePaginator<int, AdminAudit>
     */
    public function feed(
        ?int $actorId,
        ?string $action,
        ?string $result,
        ?Carbon $from,
        ?Carbon $to,
        array $paginatorQuery,
    ): LengthAwarePaginator {
        $rows = AdminAudit::query()
            ->when(
                $actorId !== null,
                fn ($query) => $query->where('admin_user_id', $actorId),
            )
            ->when(
                $action !== null,
                fn ($query) => $query->where('action', $action),
            )
            ->when(
                $result !== null,
                fn ($query) => $query->where('result', $result),
            )
            ->when(
                $from !== null,
                fn ($query) => $query->where('created_at', '>=', $from),
            )
            ->when(
                $to !== null,
                fn ($query) => $query->where('created_at', '<=', $to),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $rows->forPage($page, self::FEED_PER_PAGE),
            $rows->count(),
            self::FEED_PER_PAGE,
            $page,
            [
                'path' => route('admin.activity'),
                'query' => $paginatorQuery,
            ],
        );
    }

    /**
     * Every admin account, ordered for the actor filter dropdown.
     *
     * @return Collection<int, User>
     */
    public function actorOptions(): Collection
    {
        return User::query()
            ->where('role', 'admin')
            ->orderBy('username')
            ->get();
    }

    /**
     * One append-only row per administrative action. The actor's username is
     * captured at the time of the action (a later rename does not rewrite
     * history); created_at is the table's useCurrent timestamp and is never
     * caller-supplied.
     */
    public function record(
        User $admin,
        string $action,
        string $summary,
        string $result = 'success',
        ?string $targetType = null,
        ?int $targetId = null,
    ): AdminAudit {
        return AdminAudit::create([
            'admin_user_id' => $admin->id,
            'admin_username' => $admin->username,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'summary' => $summary,
            'result' => $result,
        ]);
    }
}
