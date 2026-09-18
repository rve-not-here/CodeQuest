<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * Classroom / Enrollment Authorization — the admin write surface. Admins
 * create and edit classroom rows (name/code/status) and manage the three
 * academic membership sets (teachers, students, courses) each through its own
 * explicit operation. Status IS an authorization boundary: deactivating a
 * classroom removes teacher visibility without touching the pivots or any
 * history; activating later restores the same scope. Every applied change
 * records an audit row; every refusal records a 'failed' row BEFORE throwing.
 */
class ClassroomManagementTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_an_admin_can_create_a_classroom_and_lands_on_the_edit_page(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.classrooms.store'), [
                'name' => 'Section 7',
                'code' => 'SEC7',
                'status' => Classroom::STATUS_ACTIVE,
            ])
            ->assertRedirect();

        $classroom = Classroom::query()->where('name', 'Section 7')->firstOrFail();
        $this->assertSame('SEC7', $classroom->code);
        $this->assertSame('active', $classroom->status);
        $this->assertSame(0, $classroom->teachers()->count());
        $this->assertSame(0, $classroom->students()->count());
        $this->assertSame(0, $classroom->courses()->count());

        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $admin->id,
            'action' => AdminAuditService::ACTION_CLASSROOM_CREATE,
            'result' => 'success',
            'target_type' => 'classroom',
            'target_id' => $classroom->id,
        ]);
    }

    public function test_an_admin_can_update_classroom_base_fields_and_the_change_is_audited(): void
    {
        $admin = $this->admin();
        $classroom = Classroom::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.classrooms.update', $classroom), [
                'name' => 'Renamed Section',
                'code' => null,
                'status' => Classroom::STATUS_INACTIVE,
            ])
            ->assertRedirect(route('admin.classrooms.edit', $classroom))
            ->assertSessionHas('status', 'Classroom updated.');

        $classroom->refresh();
        $this->assertSame('Renamed Section', $classroom->name);
        $this->assertNull($classroom->code);
        $this->assertSame('inactive', $classroom->status);

        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $admin->id,
            'action' => AdminAuditService::ACTION_CLASSROOM_UPDATE,
            'result' => 'success',
            'target_id' => $classroom->id,
        ]);
    }

    public function test_assignments_are_separate_explicit_membership_operations(): void
    {
        $admin = $this->admin();
        $classroom = Classroom::factory()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.classrooms.teachers', $classroom), ['teacher_ids' => [$teacher->id]])
            ->assertRedirect(route('admin.classrooms.edit', $classroom))
            ->assertSessionHas('status', 'Teachers assigned.');

        $this->actingAs($admin)
            ->post(route('admin.classrooms.students', $classroom), ['student_ids' => [$student->id]])
            ->assertRedirect(route('admin.classrooms.edit', $classroom));

        $this->actingAs($admin)
            ->post(route('admin.classrooms.courses', $classroom), ['course_ids' => [$course->id]])
            ->assertRedirect(route('admin.classrooms.edit', $classroom));

        $this->assertSame([$teacher->id], $classroom->teachers()->pluck('teacher_id')->all());
        $this->assertSame([$student->id], $classroom->students()->pluck('student_id')->all());
        $this->assertSame([$course->id], $classroom->courses()->pluck('course_id')->all());

        $this->assertDatabaseHas('the404_admin_audit', ['action' => AdminAuditService::ACTION_CLASSROOM_TEACHERS, 'result' => 'success']);
        $this->assertDatabaseHas('the404_admin_audit', ['action' => AdminAuditService::ACTION_CLASSROOM_STUDENTS, 'result' => 'success']);
        $this->assertDatabaseHas('the404_admin_audit', ['action' => AdminAuditService::ACTION_CLASSROOM_COURSES, 'result' => 'success']);
    }

    public function test_reassigning_the_teacher_set_replaces_the_previous_one(): void
    {
        $admin = $this->admin();
        $classroom = Classroom::factory()->create();
        $first = User::factory()->teacher()->create();
        $second = User::factory()->teacher()->create();

        $this->actingAs($admin)
            ->post(route('admin.classrooms.teachers', $classroom), ['teacher_ids' => [$first->id]])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.classrooms.teachers', $classroom), ['teacher_ids' => [$second->id]])
            ->assertRedirect();

        $this->assertSame([$second->id], $classroom->teachers()->pluck('teacher_id')->all());
    }

    public function test_assigning_a_non_teacher_account_to_the_teacher_set_is_rejected_at_validation(): void
    {
        $admin = $this->admin();
        $classroom = Classroom::factory()->create();
        $realTeacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.classrooms.teachers', $classroom), ['teacher_ids' => [$realTeacher->id]])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.classrooms.teachers', $classroom), ['teacher_ids' => [$student->id]])
            ->assertRedirect()
            ->assertSessionHasErrors([
                'teacher_ids.0' => 'The selected teacher_ids.0 is invalid.',
            ]);

        // The previous set is left untouched, and no audit row was written for
        // the rejected assignment — the request never reached the service.
        $this->assertSame([$realTeacher->id], $classroom->teachers()->pluck('teacher_id')->all());
        $this->assertDatabaseMissing('the404_admin_audit', [
            'action' => AdminAuditService::ACTION_CLASSROOM_TEACHERS,
            'result' => 'failed',
        ]);
    }

    public function test_the_admin_index_searches_and_filters(): void
    {
        $admin = $this->admin();
        Classroom::factory()->create(['name' => 'Alpha Section', 'status' => Classroom::STATUS_ACTIVE]);
        Classroom::factory()->create(['name' => 'Dormant Section', 'status' => Classroom::STATUS_INACTIVE, 'code' => 'DSEC']);

        $this->actingAs($admin)
            ->get(route('admin.classrooms', ['q' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Section')
            ->assertDontSee('Dormant Section');

        $this->actingAs($admin)
            ->get(route('admin.classrooms', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Dormant Section')
            ->assertDontSee('Alpha Section');
    }

    public function test_the_admin_index_page_renders_the_create_entry_point(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.classrooms'))
            ->assertOk()
            ->assertSee('+ NEW CLASSROOM')
            ->assertSee(route('admin.classrooms.create'));
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
