<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use App\Services\ClassroomAccessService;
use App\Services\StudentService;
use App\Services\TeacherDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * Classroom / Enrollment Authorization — how the monitoring scope is derived.
 * The teacher-facing pages (roster, progress, activity, attention, course
 * analytics, dashboard) are scoped to the overlap of the teacher's ACTIVE
 * classrooms: the distinct enrolled students, the distinct assigned courses,
 * and — per student — only the courses shared through an ACTIVE classroom.
 * Deactivating a classroom closes every gate instantly without touching the
 * pivots.
 */
class ClassroomMonitoringTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_teacher_monitors_exactly_the_students_enrolled_at_their_active_classrooms(): void
    {
        $teacher = $this->teacher();
        $enrolled = $this->student();
        $outsider = $this->student();

        $this->classroomFor($teacher, [$enrolled], [$this->course('Classroom Course A', 1)]);

        $access = app(ClassroomAccessService::class);

        $this->assertSame([$enrolled->id], $access->studentIdsFor($teacher)->all());
        $this->assertTrue($access->isAuthorizedForStudent($teacher, $enrolled));
        $this->assertFalse($access->isAuthorizedForStudent($teacher, $outsider));
    }

    public function test_teacher_monitors_exactly_the_courses_assigned_to_their_active_classrooms(): void
    {
        $teacher = $this->teacher();
        $assigned = $this->course('Assigned Course', 1);
        $unassigned = $this->course('Unassigned Course', 2);

        $this->classroomFor($teacher, [$this->student()], [$assigned]);

        $access = app(ClassroomAccessService::class);

        $this->assertSame([$assigned->id], $access->courseIdsFor($teacher)->all());
        $this->assertTrue($access->isAuthorizedForCourse($teacher, $assigned));
        $this->assertFalse($access->isAuthorizedForCourse($teacher, $unassigned));
    }

    public function test_multiple_active_classrooms_union_the_teacher_scope(): void
    {
        $teacher = $this->teacher();
        $alphaStudent = $this->student();
        $alphaCourse = $this->course('Alpha Course', 1);
        $betaStudent = $this->student();
        $betaCourse = $this->course('Beta Course', 2);

        $this->classroomFor($teacher, [$alphaStudent], [$alphaCourse]);
        $this->classroomFor($teacher, [$betaStudent], [$betaCourse]);

        $access = app(ClassroomAccessService::class);

        $this->assertEqualsCanonicalizing([$alphaStudent->id, $betaStudent->id], $access->studentIdsFor($teacher)->all());
        $this->assertEqualsCanonicalizing([$alphaCourse->id, $betaCourse->id], $access->courseIdsFor($teacher)->all());
    }

    public function test_course_level_scoping_shares_only_courses_from_the_students_shared_classrooms(): void
    {
        $teacher = $this->teacher();
        $otherTeacher = $this->teacher();
        $student = $this->student();

        $sharedCourse = $this->course('Shared Course', 1);
        $foreignCourse = $this->course('Foreign Course', 2);

        $this->classroomFor($teacher, [$student], [$sharedCourse]);
        $this->classroomFor($otherTeacher, [$student], [$foreignCourse]);

        $access = app(ClassroomAccessService::class);

        // The student is enrolled in both classrooms, but the teacher shares
        // only their own classroom's course with her.
        $this->assertSame([$sharedCourse->id], $access->courseIdsForStudent($teacher, $student)->all());
        $this->assertTrue($access->isAuthorizedForStudentCourse($teacher, $student, $sharedCourse));
        $this->assertFalse($access->isAuthorizedForStudentCourse($teacher, $student, $foreignCourse));
    }

    public function test_deactivating_a_classroom_closes_every_gate_without_removing_the_memberships(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $course = $this->course('Soon Dormant', 1);
        $classroom = $this->classroomFor($teacher, [$student], [$course]);

        $access = app(ClassroomAccessService::class);
        $this->assertTrue($access->isAuthorizedForStudent($teacher, $student));
        $this->assertTrue($access->isAuthorizedForCourse($teacher, $course));
        $this->assertTrue($access->isAuthorizedForStudentCourse($teacher, $student, $course));

        $classroom->update(['status' => Classroom::STATUS_INACTIVE]);

        $this->assertFalse($access->isAuthorizedForStudent($teacher, $student));
        $this->assertFalse($access->isAuthorizedForCourse($teacher, $course));
        $this->assertFalse($access->isAuthorizedForStudentCourse($teacher, $student, $course));
        $this->assertTrue($access->studentIdsFor($teacher)->isEmpty());
        $this->assertTrue($access->courseIdsFor($teacher)->isEmpty());

        // The memberships are intact; the visibility is what changed.
        $this->assertSame([$student->id], $classroom->students()->pluck('student_id')->all());
        $this->assertSame([$course->id], $classroom->courses()->pluck('course_id')->all());

        // Reactivating restores the exact same scope.
        $classroom->update(['status' => Classroom::STATUS_ACTIVE]);
        $this->assertTrue($access->isAuthorizedForStudent($teacher, $student));
        $this->assertTrue($access->isAuthorizedForCourse($teacher, $course));
    }

    public function test_the_roster_and_dashboard_services_respect_the_scope(): void
    {
        $teacher = $this->teacher();
        $included = $this->student();
        $excluded = $this->student();

        $this->classroomFor($teacher, [$included], [$this->course('Scoped Course', 1)]);

        $this->actingAs($teacher)
            ->get(route('students'))
            ->assertOk()
            ->assertSee($included->username)
            ->assertDontSee($excluded->username);

        $scope = app(ClassroomAccessService::class)->scopesFor($teacher);
        $metrics = app(TeacherDashboardService::class)->overview(
            studentIds: $scope['studentIds'],
            courseIds: $scope['courseIds'],
            studentCourseScopes: $scope['byStudent'],
        );

        $this->assertSame(1, $metrics['metrics']['total_students']);
    }

    public function test_the_student_options_feed_the_filter_dropdowns_from_the_scope(): void
    {
        $teacher = $this->teacher();
        $included = $this->student();
        $excluded = $this->student();

        $this->classroomFor($teacher, [$included], [$this->course('Scoped Course', 1)]);

        $scope = app(ClassroomAccessService::class)->scopesFor($teacher);
        $students = app(StudentService::class)
            ->studentOptions($scope['studentIds'])
            ->pluck('id')
            ->all();

        $this->assertSame([$included->id], $students);
        $this->assertNotContains($excluded->id, $students);
    }

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function student(): User
    {
        return User::factory()->create();
    }

    private function course(string $name, int $order): Course
    {
        return Course::factory()->create(['name' => $name, 'order_num' => $order]);
    }
}
