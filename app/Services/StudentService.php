<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the teacher-facing student overview (US-602): a paginated list of every
 * student with their current course, mission progress, assessment status,
 * competency summary, and last activity.
 *
 * Academic and timeline evidence is read in batches for authorized students
 * and their monitorable courses. Search and filters are applied server-side;
 * the page takes no student identifier, so a client cannot pivot the list
 * onto a specific user.
 *
 * Current-course and status filters are computed from academic history before
 * pagination. With neither filter, the database selects only the requested
 * page before its rows are enriched.
 */
class StudentService
{
    public const PER_PAGE = 10;

    public function __construct(
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
     * @return LengthAwarePaginator<int, array{id: int, username: string, name: string, currentCourse: array{id: int, name: string}|null, progress: array{completed: int, total: int, percent: int}|null, state: string, assessment: string, competency: array{not_started: int, developing: int, practicing: int, demonstrated: int}, lastActivity: array{message: string, at: string}|null}>
     */
    public function index(?string $search, ?int $courseId, ?string $status, array $paginatorQuery, ?array $studentCourseScopes = null): LengthAwarePaginator
    {
        if ($courseId === null && $status === null) {
            $studentIds = $studentCourseScopes === null
                ? null
                : collect(array_keys(array_filter(
                    $studentCourseScopes,
                    fn (Collection $courseIds): bool => $courseIds->isNotEmpty(),
                )));

            $page = $this->students($search, $studentIds)->paginate(self::PER_PAGE);
            $rows = $this->rowsForStudents($page->getCollection(), $studentCourseScopes);

            return new LengthAwarePaginator(
                $rows,
                $page->total(),
                self::PER_PAGE,
                $page->currentPage(),
                ['path' => route('students'), 'query' => $paginatorQuery],
            );
        }

        if ($studentCourseScopes === null) {
            $students = $this->students($search)->get();
            $rows = $this->rowsForStudents($students);
        } else {
            $students = $this->students($search, studentIds: collect(array_keys($studentCourseScopes)))->get();
            $students = $students->filter(
                fn (User $student): bool => ($studentCourseScopes[$student->id] ?? collect())->isNotEmpty(),
            );
            $rows = $this->rowsForStudents($students, $studentCourseScopes);
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
     * @return Builder<User>
     */
    private function students(?string $search, ?Collection $studentIds = null): Builder
    {
        return User::query()
            ->where('role', 'student')
            ->when($studentIds !== null, fn ($query) => $query->whereIn('id', $studentIds))
            ->when($search !== null, fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"),
            ))
            ->orderBy('username');
    }

    /**
     * Build page rows from authorized Student/Course pairs in grouped reads.
     *
     * @param  Collection<int, User>  $students
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     * @return Collection<int, array{id: int, username: string, name: string, currentCourse: array{id: int, name: string}|null, progress: array{completed: int, total: int, percent: int}|null, state: string, assessment: string, competency: array{not_started: int, developing: int, practicing: int, demonstrated: int}, lastActivity: array{message: string, at: string}|null}>
     */
    private function rowsForStudents(Collection $students, ?array $studentCourseScopes = null): Collection
    {
        if ($students->isEmpty()) {
            return collect();
        }

        $scopes = $studentCourseScopes === null
            ? null
            : array_intersect_key($studentCourseScopes, array_fill_keys($students->pluck('id')->all(), true));
        $courseIds = $scopes === null
            ? null
            : collect($scopes)->flatMap(fn (Collection $ids): Collection => $ids)->unique()->values();
        $competencies = $this->competency->overviewForStudents($students, $courseIds, $scopes);
        $latest = $this->timeline->latestForUsers($students, $scopes);

        $activeCourses = Course::query()
            ->where('status', 'active')
            ->whereHas('missions')
            ->orderBy('order_num')
            ->get(['id', 'order_num']);
        $assessments = Assessment::query()
            ->whereIn('course_id', $activeCourses->pluck('id'))
            ->get(['id', 'course_id', 'status'])
            ->keyBy('course_id');
        $passedRows = DB::table('the404_assessment_attempts as attempt')
            ->join('the404_assessments as assessment', 'assessment.id', '=', 'attempt.assessment_id')
            ->whereIn('attempt.user_id', $students->pluck('id'))
            ->whereIn('assessment.course_id', $activeCourses->pluck('id'))
            ->where('attempt.status', 'passed')
            ->distinct()
            ->get(['attempt.user_id', 'assessment.course_id']);
        $passedByStudent = [];

        foreach ($passedRows as $passedRow) {
            $passedByStudent[(int) $passedRow->user_id][(int) $passedRow->course_id] = true;
        }

        return $students->map(function (User $student) use ($competencies, $latest, $activeCourses, $assessments, $passedByStudent): array {
            $courseRows = $competencies->get($student->id) ?? collect();
            $current = $courseRows->first(fn (array $row): bool => ! $row['challengePassed']);
            $counts = ['not_started' => 0, 'developing' => 0, 'practicing' => 0, 'demonstrated' => 0];

            foreach ($courseRows as $row) {
                $counts[$row['state']]++;
            }

            $course = $current['course'] ?? null;
            $completed = $current['completedMissions'] ?? 0;
            $total = $current['totalMissions'] ?? 0;
            $assessment = $course === null ? null : $assessments->get($course->id);
            $eligible = $course !== null && $total > 0 && $completed === $total;
            $reached = $course !== null && $activeCourses
                ->filter(fn (Course $earlier): bool => $earlier->order_num < $course->order_num)
                ->every(fn (Course $earlier): bool => isset($passedByStudent[$student->id][$earlier->id]));
            $ready = $eligible && $assessment?->status === 'active';
            $beat = $latest->get($student->id);

            return [
                'id' => $student->id,
                'username' => $student->username,
                'name' => $student->name,
                'currentCourse' => $course === null ? null : ['id' => $course->id, 'name' => $course->name],
                'progress' => $course === null ? null : ['completed' => $completed, 'total' => $total, 'percent' => $current['percent'] ?? 0],
                'state' => $course === null ? 'completed' : ($ready ? 'ready' : 'in_progress'),
                'assessment' => $course === null ? 'ALL CLEARED' : ($ready && $reached ? 'READY' : 'LOCKED'),
                'competency' => $counts,
                'lastActivity' => $beat === null ? null : ['message' => $beat['label'], 'at' => $beat['at']->diffForHumans()],
            ];
        });
    }
}
