<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use App\Services\ClassroomAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * Classroom / Enrollment Authorization — the visibility boundary. Teacher
 * visibility is membership-driven AND status-driven, never role-driven: a
 * teacher may open exactly their own teaching classrooms while ACTIVE, and an
 * inactive classroom vanishes from teacher visibility immediately (the pivots
 * and history stay). An admin sits above the layer and sees everything,
 * active or inactive.
 */
class ClassroomAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_retained_assignments_do_not_authorize_former_teachers(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $course = $this->course('Assigned Course', 1);
        $classroom = $this->classroomFor($teacher, [$student], [$course]);

        $this->assertTrue(app(ClassroomAccessService::class)->isAuthorizedForStudent($teacher, $student));

        $teacher->update(['role' => 'operator']);

        $access = app(ClassroomAccessService::class);
        $this->assertFalse($access->teachesClassroom($teacher, $classroom));
        $this->assertFalse($access->isAuthorizedForStudent($teacher, $student));
        $this->assertFalse($access->isAuthorizedForCourse($teacher, $course));
        $this->assertTrue($access->studentIdsFor($teacher)->isEmpty());
        $this->assertFalse(Gate::forUser($teacher)->allows('view', $classroom));
        $this->assertFalse(Gate::forUser($teacher)->allows('view', $student));
        $this->assertFalse(Gate::forUser($teacher)->allows('view', $teacher));
    }

    public function test_a_teacher_sees_only_their_own_classrooms(): void
    {
        $teacher = $this->teacher();
        $otherTeacher = $this->teacher();

        $mine = $this->classroomFor($teacher, [$this->student()], [$this->course('My Course', 1)]);
        $theirs = $this->classroomFor($otherTeacher, [$this->student()], [$this->course('Their Course', 2)]);

        $this->actingAs($teacher)
            ->get(route('classrooms'))
            ->assertOk()
            ->assertSee($mine->name)
            ->assertDontSee($theirs->name);
    }

    public function test_an_inactive_classroom_vanishes_from_teacher_visibility_immediately(): void
    {
        $teacher = $this->teacher();
        $classroom = $this->classroomFor($teacher, [$this->student()], [$this->course('Soon Gone', 1)]);

        $this->actingAs($teacher)
            ->get(route('classrooms'))
            ->assertOk()
            ->assertSee($classroom->name);

        $classroom->update(['status' => Classroom::STATUS_INACTIVE]);

        $this->actingAs($teacher)
            ->get(route('classrooms'))
            ->assertOk()
            ->assertDontSee($classroom->name);

        // The teacher cannot force-open it either: teachesClassroom and the
        // policy gate both close once the classroom is inactive.
        $this->actingAs($teacher)->get(route('classrooms.show', $classroom))->assertForbidden();
        $this->assertFalse(app(ClassroomAccessService::class)->teachesClassroom($teacher, $classroom));
    }

    public function test_a_teacher_cannot_open_another_teachers_classroom(): void
    {
        $owner = $this->teacher();
        $intruder = $this->teacher();
        $classroom = $this->classroomFor($owner, [$this->student()], [$this->course('Restricted', 1)]);

        $this->actingAs($intruder)
            ->get(route('classrooms.show', $classroom))
            ->assertForbidden();
    }

    public function test_a_teacher_cannot_open_an_inactive_classroom_even_their_own(): void
    {
        $teacher = $this->teacher();
        $classroom = $this->classroomFor($teacher, [$this->student()], [$this->course('Dormant', 1)]);
        $classroom->update(['status' => Classroom::STATUS_INACTIVE]);

        $this->actingAs($teacher)
            ->get(route('classrooms.show', $classroom))
            ->assertForbidden();
    }

    public function test_an_admin_sees_every_classroom_active_or_inactive(): void
    {
        $admin = $this->admin();
        $active = $this->classroomFor($this->teacher(), [$this->student()], [$this->course('Wide Open', 1)]);
        $inactive = Classroom::factory()->inactive()->create(['name' => 'Dormant Wide']);

        $this->actingAs($admin)
            ->get(route('classrooms'))
            ->assertOk()
            ->assertSee($active->name)
            ->assertSee($inactive->name);

        $this->actingAs($admin)->get(route('classrooms.show', $inactive))->assertOk();
    }

    public function test_the_teacher_classroom_detail_lists_its_memberships(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $course = $this->course('Membership Course', 1);
        $classroom = $this->classroomFor($teacher, [$student], [$course]);

        $this->actingAs($teacher)
            ->get(route('classrooms.show', $classroom))
            ->assertOk()
            ->assertSee($classroom->name)
            ->assertSee($student->username)
            ->assertSee($course->name);
    }

    public function test_visibility_is_membership_driven_a_teacher_without_an_active_classroom_sees_nothing(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $course = $this->course('Lonely Course', 1);

        $this->actingAs($teacher)
            ->get(route('classrooms'))
            ->assertOk()
            ->assertSee('NO CLASSROOMS');

        $access = app(ClassroomAccessService::class);
        $this->assertFalse($access->isAuthorizedForStudent($teacher, $student));
        $this->assertFalse($access->isAuthorizedForCourse($teacher, $course));
        $this->assertTrue($access->studentIdsFor($teacher)->isEmpty());
    }

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
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
