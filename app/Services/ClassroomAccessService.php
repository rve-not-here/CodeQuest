<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The classroom authorization scope engine (Classroom / Enrollment Authorization).
 *
 * Teacher visibility requires the current teacher role, membership, and status:
 * a teacher may monitor exactly the students enrolled in the classrooms they
 * teach, and only while those classrooms are ACTIVE. Setting a classroom
 * inactive immediately removes teacher visibility — status IS an
 * authorization visibility boundary, not housekeeping — but never deletes the
 * teacher/student/course pivot assignments or any academic history. An admin
 * is the fleet-wide exception: the admin surface is the management layer and
 * sees both active and inactive classrooms.
 *
 * Authorization is course-level, not merely student-level. A teacher
 * authorized for Student X does NOT automatically receive all of X's academic
 * data. The teacher may monitor, for Student X, only the courses for which an
 * ACTIVE shared classroom exists such that the teacher is assigned, the
 * student is enrolled, and the course is assigned. This matters when a student
 * belongs to multiple classrooms (shared courses come from the intersection of
 * the teacher's classrooms and the student's enrollments).
 *
 * A null scope means "the whole fleet / the student's full course set" (admin),
 * while an empty Collection is a deliberate "no one": callers must scope on the
 * set as-is, because passing an empty set to a service that treats null as
 * fleet-wide would leak the whole roster to a classroom-less teacher.
 */
class ClassroomAccessService
{
    /**
     * An admin sits above the classroom layer and monitors the whole fleet.
     */
    public function isFleetWide(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * The classrooms this user is authorized for: an admin's every classroom
     * (active or inactive), a teacher's OWN teaching assignments restricted to
     * ACTIVE classrooms only. Inactive classrooms are invisible to teachers.
     *
     * @return EloquentCollection<int, Classroom>
     */
    public function classroomsFor(User $user): EloquentCollection
    {
        if ($this->isFleetWide($user)) {
            return Classroom::query()
                ->orderBy('name')
                ->get();
        }

        if ($user->role !== 'teacher') {
            return new EloquentCollection;
        }

        return $user->teachingClassrooms()
            ->where('the404_classrooms.status', Classroom::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();
    }

    /**
     * The ids of the classrooms in the user's authorized scope (see
     * classroomsFor for the active-only teacher restriction).
     *
     * @return Collection<int, int>
     */
    public function classroomIdsFor(User $user): Collection
    {
        return $this->classroomsFor($user)
            ->pluck('id')
            ->map(fn (int $id): int => (int) $id)
            ->values();
    }

    /**
     * The student ids the user may monitor. null = the whole fleet (admin);
     * teacher = the distinct students enrolled at the teacher's active
     * classrooms; an empty Collection means no one.
     *
     * @return Collection<int, int>|null
     */
    public function studentIdsFor(User $user): ?Collection
    {
        if ($this->isFleetWide($user)) {
            return null;
        }

        $classroomIds = $this->classroomIdsFor($user);

        if ($classroomIds->isEmpty()) {
            return collect();
        }

        return DB::table('the404_classroom_students')
            ->whereIn('classroom_id', $classroomIds)
            ->distinct()
            ->pluck('student_id')
            ->map(fn (int $id): int => (int) $id)
            ->values();
    }

    /**
     * The course ids the user may monitor overall. null = whole catalog (admin);
     * teacher = the distinct courses assigned to the teacher's active
     * classrooms; an empty Collection means no courses.
     *
     * @return Collection<int, int>|null
     */
    public function courseIdsFor(User $user): ?Collection
    {
        if ($this->isFleetWide($user)) {
            return null;
        }

        $classroomIds = $this->classroomIdsFor($user);

        if ($classroomIds->isEmpty()) {
            return collect();
        }

        return DB::table('the404_classroom_courses')
            ->whereIn('classroom_id', $classroomIds)
            ->distinct()
            ->pluck('course_id')
            ->map(fn (int $id): int => (int) $id)
            ->values();
    }

    /**
     * The course ids the teacher may monitor for ONE student: the intersection
     * of the teacher's active classrooms and the student's enrollments — i.e.
     * active shared classrooms in which the teacher is assigned AND the student
     * is enrolled AND the course is assigned. null for an admin (the student's
     * full course set); an empty Collection means the teacher shares no courses
     * with this student.
     *
     * @return Collection<int, int>|null
     */
    public function courseIdsForStudent(User $user, User $student): ?Collection
    {
        if ($this->isFleetWide($user)) {
            return null;
        }

        $classroomIds = $this->classroomIdsFor($user);

        if ($classroomIds->isEmpty()) {
            return collect();
        }

        return DB::table('the404_classroom_students as s')
            ->join('the404_classroom_courses as c', 'c.classroom_id', '=', 's.classroom_id')
            ->whereIn('s.classroom_id', $classroomIds)
            ->where('s.student_id', $student->id)
            ->distinct()
            ->pluck('c.course_id')
            ->map(fn (int $id): int => (int) $id)
            ->values();
    }

    /**
     * May this user view this student's learning data? Admin = any account;
     * teacher = the student must be a student enrolled at one of the teacher's
     * ACTIVE classrooms.
     */
    public function isAuthorizedForStudent(User $user, User $student): bool
    {
        if ($this->isFleetWide($user)) {
            return true;
        }

        if ($student->role !== 'student') {
            return false;
        }

        $classroomIds = $this->classroomIdsFor($user);

        if ($classroomIds->isEmpty()) {
            return false;
        }

        return DB::table('the404_classroom_students')
            ->whereIn('classroom_id', $classroomIds)
            ->where('student_id', $student->id)
            ->exists();
    }

    /**
     * May this user monitor this course at all? Admin = any course; teacher =
     * the course must be assigned to one of the teacher's ACTIVE classrooms.
     */
    public function isAuthorizedForCourse(User $user, Course $course): bool
    {
        if ($this->isFleetWide($user)) {
            return true;
        }

        $courseIds = $this->courseIdsFor($user);

        return $courseIds !== null && $courseIds->contains($course->id);
    }

    /**
     * The precise per-student/per-course gate: an ACTIVE shared classroom must
     * exist in which the teacher is assigned, the student is enrolled, and the
     * course is assigned. Admin = any course for any student.
     */
    public function isAuthorizedForStudentCourse(User $user, User $student, Course $course): bool
    {
        if ($this->isFleetWide($user)) {
            return true;
        }

        $courseIds = $this->courseIdsForStudent($user, $student);

        return $courseIds !== null && $courseIds->contains($course->id);
    }

    /**
     * May this user set foot in (teach or administer) the given classroom?
     * Admin = every classroom; teacher = only their OWN teaching assignments,
     * and only while the classroom is ACTIVE.
     */
    public function teachesClassroom(User $user, Classroom $classroom): bool
    {
        if ($this->isFleetWide($user)) {
            return true;
        }

        if ($user->role !== 'teacher') {
            return false;
        }

        return $user->teachingClassrooms()
            ->whereKey($classroom->id)
            ->where('the404_classrooms.status', Classroom::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Constrain a query over rows that carry a (student_id, course_id) pair to
     * the monitorable pairs in $studentCourseScopes. The two columns are passed
     * in because the consuming queries alias them through joins (e.g. the
     * progress rows teamed with their mission's course_id).
     *
     * Rows outside every allowed pair are excluded entirely; an empty scope
     * matches nothing, never the whole fleet. A student with no shared course
     * contributes nothing, because whereIn against an empty set is always false.
     *
     * @template TBuilder of \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     *
     * @param  TBuilder  $query
     * @param  string  $userColumn  the student-id column of the row, e.g. 'p.user_id'
     * @param  string  $courseColumn  the course-id column of the row, e.g. 'm.course_id'
     * @param  array<int, Collection<int, int>>  $studentCourseScopes
     * @return TBuilder
     */
    public function whereAllowedPairs($query, string $userColumn, string $courseColumn, array $studentCourseScopes)
    {
        return $query->where(function ($query) use ($studentCourseScopes, $userColumn, $courseColumn): void {
            if ($studentCourseScopes === []) {
                $query->whereRaw('1 = 0');

                return;
            }

            foreach ($studentCourseScopes as $userId => $courseIds) {
                $query->orWhere(function ($query) use ($userId, $courseIds, $userColumn, $courseColumn): void {
                    $query->where($userColumn, $userId)->whereIn($courseColumn, $courseIds);
                });
            }
        });
    }

    /**
     * The ready-made monitor scope for a user, shaped for the fleet services.
     *
     *  - studentIds: Collection<int, int>|null — the students to monitor (null = fleet).
     *  - courseIds:  Collection<int, int>|null — the courses the user may monitor (null = catalog).
     *  - byStudent:  array<int, Collection<int, int>>|null — per-student shared-course
     *                ids for teachers (a given student's monitorable course set),
     *                null for admins (no restriction).
     *
     * @return array{studentIds: Collection<int, int>|null, courseIds: Collection<int, int>|null, byStudent: array<int, Collection<int, int>>|null}
     */
    public function scopesFor(User $user): array
    {
        if ($this->isFleetWide($user)) {
            return [
                'studentIds' => null,
                'courseIds' => null,
                'byStudent' => null,
            ];
        }

        $classroomIds = $this->classroomIdsFor($user);

        if ($classroomIds->isEmpty()) {
            return [
                'studentIds' => collect(),
                'courseIds' => collect(),
                'byStudent' => [],
            ];
        }

        $studentIds = DB::table('the404_classroom_students')
            ->whereIn('classroom_id', $classroomIds)
            ->distinct()
            ->pluck('student_id')
            ->map(fn (int $id): int => (int) $id)
            ->values();

        $courseIds = DB::table('the404_classroom_courses')
            ->whereIn('classroom_id', $classroomIds)
            ->distinct()
            ->pluck('course_id')
            ->map(fn (int $id): int => (int) $id)
            ->values();

        $pairs = DB::table('the404_classroom_students as s')
            ->join('the404_classroom_courses as c', 'c.classroom_id', '=', 's.classroom_id')
            ->select('s.student_id', 'c.course_id')
            ->whereIn('s.classroom_id', $classroomIds)
            ->distinct()
            ->get();

        $byStudent = $pairs
            ->groupBy('student_id')
            ->map(
                fn (Collection $rows): Collection => $rows
                    ->pluck('course_id')
                    ->map(fn (int $id): int => (int) $id)
                    ->values(),
            )
            ->all();

        return [
            'studentIds' => $studentIds,
            'courseIds' => $courseIds,
            'byStudent' => $byStudent,
        ];
    }
}
