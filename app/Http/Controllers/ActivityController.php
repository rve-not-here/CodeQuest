<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityFeedRequest;
use App\Models\User;
use App\Services\StudentService;
use App\Services\TimelineService;
use Carbon\Carbon;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly StudentService $students,
    ) {}

    /**
     * The Learning Activity feed (US-606), realized on the existing /activity
     * route inside the teacher gate. System-wide by design: 'teacher' is a
     * role-based, classless gate (US-601), so a student filter can only narrow
     * the view, never widen it — 'student' is therefore a legit, validated
     * server-side filter here (unlike the identifier-less roster pages).
     */
    public function __invoke(ActivityFeedRequest $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'owner'])) {
            abort(403, 'Learning activity takes no user identifier.');
        }

        $filters = $request->safe(['student', 'course', 'type', 'from', 'to']);

        /** @var array<string, string> $paginatorQuery */
        $paginatorQuery = array_filter($filters, static fn (mixed $value): bool => $value !== null);

        $rows = $this->timeline->feed(
            isset($filters['student']) ? collect([(int) $filters['student']]) : null,
            isset($filters['course']) ? (int) $filters['course'] : null,
            $filters['type'] ?? null,
            Carbon::parse($filters['from'])->startOfDay(),
            Carbon::parse($filters['to'])->endOfDay(),
            $paginatorQuery,
        );

        /** @var User $user */
        $user = auth()->user();

        return view('activity', [
            'role' => $user->role,
            'events' => $rows,
            'filterStudents' => $this->students->studentOptions(),
            'filterCourses' => $this->students->courseOptions(),
            'filterTypes' => TimelineService::FILTERABLE_TYPES,
            'filters' => $filters,
        ]);
    }
}
