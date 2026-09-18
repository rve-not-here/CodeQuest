<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityFeedRequest;
use App\Models\User;
use App\Services\ClassroomAccessService;
use App\Services\StudentService;
use App\Services\TimelineService;
use Carbon\Carbon;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly StudentService $students,
        private readonly ClassroomAccessService $access,
    ) {}

    /**
     * The Learning Activity feed (US-606), realized on the existing /activity
     * route inside the teacher gate. Classroom-scoped by design (Classroom /
     * Enrollment Authorization): the feed spans exactly the requesting user's
     * monitorable scope — every student for an admin, the students and courses
     * of the teacher's ACTIVE classrooms. A student or course filter can only
     * narrow that scope, never widen it: an unauthorized filter collapses to
     * an empty feed rather than leaking.
     */
    public function __invoke(ActivityFeedRequest $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'owner'])) {
            abort(403, 'Learning activity takes no user identifier.');
        }

        $filters = $request->safe(['student', 'course', 'type', 'from', 'to']);

        /** @var array<string, string> $paginatorQuery */
        $paginatorQuery = array_filter($filters, static fn (mixed $value): bool => $value !== null);

        /** @var User $user */
        $user = auth()->user();

        $scope = $this->access->scopesFor($user);

        $studentFilter = isset($filters['student'])
            ? collect([(int) $filters['student']])
            : null;

        if ($scope['studentIds'] !== null) {
            $studentFilter = $studentFilter === null
                ? $scope['studentIds']
                : $scope['studentIds']->intersect($studentFilter);
        }

        $courseFilter = isset($filters['course']) ? (int) $filters['course'] : null;

        if ($courseFilter !== null && $scope['courseIds'] !== null && ! $scope['courseIds']->contains($courseFilter)) {
            $courseFilter = -1;
        }

        $rows = $this->timeline->feed(
            $studentFilter,
            $courseFilter,
            $filters['type'] ?? null,
            Carbon::parse($filters['from'])->startOfDay(),
            Carbon::parse($filters['to'])->endOfDay(),
            $paginatorQuery,
            $scope['byStudent'],
        );

        return view('activity', [
            'role' => $user->role,
            'events' => $rows,
            'filterStudents' => $this->students->studentOptions($scope['studentIds']),
            'filterCourses' => $this->students->courseOptions($scope['courseIds']),
            'filterTypes' => TimelineService::FILTERABLE_TYPES,
            'filters' => $filters,
        ]);
    }
}
