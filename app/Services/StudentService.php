<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Builds the teacher-facing student overview (US-602): a paginated list of every
 * student with their current course, mission progress, assessment status,
 * competency summary, and last activity.
 *
 * Everything is composed from the existing Phase 5 services — CourseProgressService,
 * AssessmentService, CompetencyService, DashboardService, TimelineService — and never
 * re-derives their formulas for a teacher-specific view. Search and filters are applied
 * server-side; the page takes no student identifier, so a client cannot pivot
 * the list onto a specific user.
 *
 * Rows are computed for every matching student, then filtered and paginated in
 * memory. That ordering is correct-by-construction for the current-course
 * filters (derived from progress/assessment history, not stored columns). It is
 * fine at the fleet's current scale; DB-level pagination of these computed
 * filters is a known optimisation only if the roster grows.
 */
class StudentService
{
    public const PER_PAGE = 10;

    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly CourseProgressService $progress,
        private readonly AssessmentService $assessments,
        private readonly CompetencyService $competency,
        private readonly TimelineService $timeline,
    ) {}

    /**
     * Active courses for the "current course" filter dropdown. An optional
     * scope restricts the list to a monitorable course set.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, Course>
     */
    public function courseOptions(?Collection $courseIds = null): Collection
    {
        return Course::query()
            ->where('status', 'active')
            ->orderBy('order_num')
            ->when($courseIds !== null, fn ($query) => $query->whereIn('id', $courseIds))
            ->get();
    }

    /**
     * Every student, ordered for the teacher-area filter dropdowns. An
     * optional scope restricts the list to a monitorable student set.
     *
     * @param  Collection<int, int>|null  $studentIds
     * @return Collection<int, User>
     */
    public function studentOptions(?Collection $studentIds = null): Collection
    {
        return User::query()
            ->where('role', 'student')
            ->when($studentIds !== null, fn ($query) => $query->whereIn('id', $studentIds))
            ->orderBy('username')
            ->get();
    }

    /**
     * @param  array<string, string>  $paginatorQuery
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes  per-student
     *                                                                      monitorable course ids for a scoped teacher (null = whole roster,
     *                                                                      every student unrestricted; an empty map = nobody to monitor)
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function index(?string $search, ?int $courseId, ?string $status, array $paginatorQuery, ?array $studentCourseScopes = null): LengthAwarePaginator
    {
        if ($studentCourseScopes === null) {
            $students = $this->students($search);
            $rows = $students->map(fn (User $student): array => $this->rowFor($student));
        } else {
            $students = $this->students($search, studentIds: collect(array_keys($studentCourseScopes)));
            $rows = $students
                ->filter(
                    fn (User $student): bool => ($studentCourseScopes[$student->id] ?? collect())->isNotEmpty(),
                )
                ->map(
                    fn (User $student): array => $this->rowFor(
                        $student,
                        $studentCourseScopes[$student->id] ?? collect(),
                    ),
                );
        }

        if ($courseId !== null) {
            $rows = $rows->filter(fn (array $row): bool => ($row['currentCourse']['id'] ?? null) === $courseId);
        }

        if ($status !== null) {
            $rows = $rows->filter(fn (array $row): bool => $row['state'] === $status);
        }

        $rows = $rows->values();

        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $rows->forPage($page, self::PER_PAGE),
            $rows->count(),
            self::PER_PAGE,
            $page,
            [
                'path' => route('students'),
                'query' => $paginatorQuery,
            ],
        );
    }

    /**
     * @param  Collection<int, int>|null  $studentIds
     * @return Collection<int, User>
     */
    private function students(?string $search, ?Collection $studentIds = null): Collection
    {
        return User::query()
            ->where('role', 'student')
            ->when($studentIds !== null, fn ($query) => $query->whereIn('id', $studentIds))
            ->when($search !== null, fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"),
            ))
            ->orderBy('username')
            ->get();
    }

    /**
     * @param  Collection<int, int>|null  $courseIds  the monitorable course set
     *                                                for this student (a teacher's shared classrooms); null keeps every
     *                                                course
     * @return array<string, mixed>
     */
    private function rowFor(User $student, ?Collection $courseIds = null): array
    {
        $current = $this->dashboard->currentCourse($student, $courseIds);

        if ($current === null) {
            $currentCourse = null;
            $progress = null;
            $state = 'completed';
            $assessment = 'ALL CLEARED';
        } else {
            /**
             * @var array{
             *     course: Course,
             *     progress: array{completed: int, total: int, percent: int},
             *     state: string,
             * }|null $currentRow
             */
            $currentRow = $this->progress->overview($student, $courseIds)
                ->first(fn (array $row): bool => $row['course']->id === $current->id);

            $currentCourse = ['id' => $current->id, 'name' => $current->name];
            $progress = $currentRow['progress'] ?? null;
            $state = $this->stateKey($currentRow['state'] ?? null);
            $assessment = $this->assessments->isUnlocked($student, $current) ? 'READY' : 'LOCKED';
        }

        return [
            'id' => $student->id,
            'username' => $student->username,
            'name' => $student->name,
            'currentCourse' => $currentCourse,
            'progress' => $progress,
            'state' => $state,
            'assessment' => $assessment,
            'competency' => $this->competencySummary($student, $courseIds),
            'lastActivity' => $this->lastActivity($student, $courseIds),
        ];
    }

    /**
     * Map a CourseProgressService label to the overview's status-filter key.
     * The current course is only ever IN PROGRESS or READY (a passed course is
     * skipped by currentCourse() and becomes 'completed' above).
     */
    private function stateKey(?string $label): string
    {
        return match ($label) {
            'READY' => 'ready',
            default => 'in_progress',
        };
    }

    /**
     * Compact per-state counts across the monitorable competency areas.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return array{not_started: int, developing: int, practicing: int, demonstrated: int}
     */
    private function competencySummary(User $student, ?Collection $courseIds = null): array
    {
        $counts = ['not_started' => 0, 'developing' => 0, 'practicing' => 0, 'demonstrated' => 0];

        foreach ($this->competency->overview($student, $courseIds) as $row) {
            if (array_key_exists($row['state'], $counts)) {
                $counts[$row['state']]++;
            }
        }

        return $counts;
    }

    /**
     * Newest learning beat in the SAME four-source vocabulary as the Recent
     * Activity strip (TimelineService::events), so the roster column and the
     * strip can never disagree about what a student did last. When a monitorable
     * course set is supplied the beat is confined to it — a scoped teacher never
     * sees a student's activity from a course they do not share.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return array{message: string, at: string}|null
     */
    private function lastActivity(User $student, ?Collection $courseIds = null): ?array
    {
        $beat = $this->timeline->events($student, 1, $courseIds)->first();

        if ($beat === null) {
            return null;
        }

        return [
            'message' => $beat['label'],
            'at' => $beat['at']->diffForHumans(),
        ];
    }
}
