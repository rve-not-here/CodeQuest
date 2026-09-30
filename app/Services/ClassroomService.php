<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The only write path for classroom records (Classroom / Enrollment
 * Authorization). Admins create and edit classroom rows and manage the three
 * academic assignments — teachers, enrolled students, and assigned courses —
 * all through validated, whitelisted inputs. Teacher visibility never flows
 * through these methods; it is derived purely from the membership pivots by
 * ClassroomAccessService.
 *
 * Every applied change records a AdminAuditService trail row; every refusal
 * records a 'failed' row BEFORE throwing InvalidArgumentException (the same
 * success/failed discipline as CourseService/UserService), so a blocked
 * change is never silent. Classrooms are academic records with no delete
 * path (§14 no-destructive posture).
 */
class ClassroomService
{
    public const PER_PAGE = 10;

    public const STATUSES = [Classroom::STATUS_ACTIVE, Classroom::STATUS_INACTIVE];

    public function __construct(
        private readonly AdminAuditService $audit,
        private readonly CourseService $courses,
    ) {}

    /**
     * Every classroom with its membership counts, searchable and status-
     * filterable, ordered by name.
     *
     * @return LengthAwarePaginator<int, Classroom>
     */
    public function index(?string $search, ?string $status): LengthAwarePaginator
    {
        return Classroom::query()
            ->withCount('teachers')
            ->withCount('students')
            ->withCount('courses')
            ->when(
                $search !== null,
                fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"),
                ),
            )
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * The two selection dropdowns for the admin classroom forms.
     *
     * @return Collection<int, User>
     */
    public function teacherOptions(): Collection
    {
        return User::query()
            ->where('role', 'teacher')
            ->orderBy('username')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function studentOptions(): Collection
    {
        return User::query()
            ->where('role', 'student')
            ->orderBy('username')
            ->get();
    }

    /**
     * @return Collection<int, Course>
     */
    public function courseOptions(): Collection
    {
        return $this->courses->ordered();
    }

    /**
     * Create a classroom from validated admin input. Creates ONLY the
     * classroom row — no assignments happen here (they are separate, explicit
     * membership operations).
     *
     * @param  array{name: string, code?: string|null, status: string}  $attributes
     */
    public function store(User $actor, array $attributes): Classroom
    {
        $name = trim((string) $attributes['name']);
        $code = array_key_exists('code', $attributes) && $attributes['code'] !== null
            ? trim((string) $attributes['code'])
            : null;

        if ($name === '' || mb_strlen($name) > 128) {
            $this->refuse($actor, null, 'Classroom name must be a non-empty string of at most 128 characters.', AdminAuditService::ACTION_CLASSROOM_CREATE);
        }

        if ($code !== null && mb_strlen($code) > 32) {
            $this->refuse($actor, null, 'Classroom code must be at most 32 characters.', AdminAuditService::ACTION_CLASSROOM_CREATE);
        }

        $status = $this->refineStatus($actor, (string) $attributes['status'], AdminAuditService::ACTION_CLASSROOM_CREATE);

        $classroom = Classroom::create([
            'name' => $name,
            'code' => $code,
            'status' => $status,
        ]);

        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_CLASSROOM_CREATE,
            "Created classroom '{$classroom->name}'.".$this->codeSuffix($code),
            targetType: 'classroom',
            targetId: $classroom->id,
        );

        return $classroom;
    }

    /**
     * Update a classroom's base fields (name, code, status). Status IS an
     * authorization visibility boundary, not housekeeping: deactivating a
     * classroom immediately removes teacher visibility. The pivot assignments
     * (teachers/students/courses) and all academic history are deliberately
     * untouched — activating the classroom later restores the same scope.
     *
     * @param  array{name: string, code?: string|null, status: string}  $attributes
     */
    public function update(User $actor, Classroom $classroom, array $attributes): Classroom
    {
        $name = trim((string) $attributes['name']);
        $code = array_key_exists('code', $attributes) && $attributes['code'] !== null
            ? trim((string) $attributes['code'])
            : null;
        $status = (string) $attributes['status'];

        if ($name === '' || mb_strlen($name) > 128) {
            $this->refuse($actor, $classroom, 'Classroom name must be a non-empty string of at most 128 characters.');
        }

        if ($code !== null && mb_strlen($code) > 32) {
            $this->refuse($actor, $classroom, 'Classroom code must be at most 32 characters.');
        }

        $status = $this->refineStatus($actor, (string) $attributes['status']);

        $changes = [];

        if ($name !== $classroom->name) {
            $changes[] = "name → '{$name}'";
        }

        if ((string) ($code ?? '') !== (string) ($classroom->code ?? '')) {
            $changes[] = $code === null ? 'code → (cleared)' : "code → '{$code}'";
        }

        if ($status !== $classroom->status) {
            $changes[] = "status → {$status}";
        }

        if ($changes === []) {
            return $classroom;
        }

        $classroom->name = $name;
        $classroom->code = $code;
        $classroom->status = $status;
        $classroom->save();

        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_CLASSROOM_UPDATE,
            'Classroom updated: '.implode(', ', $changes),
            targetType: 'classroom',
            targetId: $classroom->id,
        );

        return $classroom;
    }

    /**
     * Replace the classroom's teacher assignment set. Every id must name an
     * existing teacher account; any other id, or a nonexistent account, is a
     * refusal (the old set is left untouched).
     *
     * @param  array<int, mixed>  $teacherIds
     */
    public function assignTeachers(User $actor, Classroom $classroom, array $teacherIds): void
    {
        $ids = $this->normaliseIds($teacherIds);

        $valid = User::query()
            ->whereIn('id', $ids)
            ->where('role', 'teacher')
            ->pluck('id')
            ->map(fn (int $id): int => (int) $id)
            ->all();

        if (count($valid) !== count($ids)) {
            $this->refuse($actor, $classroom, 'Every teacher id must reference an existing teacher account.', AdminAuditService::ACTION_CLASSROOM_TEACHERS);
        }

        $classroom->teachers()->sync($valid);

        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_CLASSROOM_TEACHERS,
            'Teachers assigned: '.count($valid),
            targetType: 'classroom',
            targetId: $classroom->id,
        );
    }

    /**
     * Replace the classroom's enrolled-student set. Every id must name an
     * existing student account.
     *
     * @param  array<int, mixed>  $studentIds
     */
    public function enrollStudents(User $actor, Classroom $classroom, array $studentIds): void
    {
        $ids = $this->normaliseIds($studentIds);

        $valid = User::query()
            ->whereIn('id', $ids)
            ->where('role', 'student')
            ->pluck('id')
            ->map(fn (int $id): int => (int) $id)
            ->all();

        if (count($valid) !== count($ids)) {
            $this->refuse($actor, $classroom, 'Every student id must reference an existing student account.', AdminAuditService::ACTION_CLASSROOM_STUDENTS);
        }

        $classroom->students()->sync($valid);

        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_CLASSROOM_STUDENTS,
            'Students enrolled: '.count($valid),
            targetType: 'classroom',
            targetId: $classroom->id,
        );
    }

    /**
     * Replace the classroom's assigned-course set. Every id must reference an
     * existing course.
     *
     * @param  array<int, mixed>  $courseIds
     */
    public function assignCourses(User $actor, Classroom $classroom, array $courseIds): void
    {
        $ids = $this->normaliseIds($courseIds);

        $valid = Course::query()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn (int $id): int => (int) $id)
            ->all();

        if (count($valid) !== count($ids)) {
            $this->refuse($actor, $classroom, 'Every course id must reference an existing course.', AdminAuditService::ACTION_CLASSROOM_COURSES);
        }

        $classroom->courses()->sync($valid);

        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_CLASSROOM_COURSES,
            'Courses assigned: '.count($valid),
            targetType: 'classroom',
            targetId: $classroom->id,
        );
    }

    /**
     * Record a failed classroom write, then throw. Consistent with
     * CourseService::refuse: the ledger carries the failed row before the
     * exception propagates. $classroom is null only for a create attempt; the
     * $action records which operation was refused.
     *
     * @return never
     *
     * @throws InvalidArgumentException
     */
    private function refuse(User $actor, ?Classroom $classroom, string $reason, string $action = AdminAuditService::ACTION_CLASSROOM_UPDATE): void
    {
        $this->audit->record(
            $actor,
            $action,
            'Refused: '.$reason,
            'failed',
            $classroom?->getMorphClass(),
            $classroom?->id,
        );

        throw new InvalidArgumentException($reason);
    }

    /**
     * Validate a raw classroom status and refine it into the two-value domain
     * type. Invalid values are refused (audited + thrown); the matched literal
     * arms let PHPStan prove the result is 'active'|'inactive' without widening
     * Classroom::$status.
     *
     * @return 'active'|'inactive'
     */
    private function refineStatus(User $actor, string $status, string $action = AdminAuditService::ACTION_CLASSROOM_UPDATE): string
    {
        return match ($status) {
            Classroom::STATUS_ACTIVE, Classroom::STATUS_INACTIVE => $status,
            default => $this->refuse($actor, null, "Unknown classroom status '{$status}'.", $action),
        };
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return list<int>
     */
    private function normaliseIds(array $ids): array
    {
        $normalised = collect($ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->all();

        return array_values($normalised);
    }

    private function codeSuffix(?string $code): string
    {
        return $code === null ? '' : " Code '{$code}'.";
    }
}
