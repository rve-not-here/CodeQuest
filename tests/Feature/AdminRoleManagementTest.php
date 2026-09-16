<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_change_own_role_away_from_admin(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'self_admin']);
        $originalName = $admin->name;

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'username' => 'self_admin',
            'name' => 'Changed Name',
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('error', 'You cannot change your own role away from admin.');

        $admin->refresh();
        $this->assertSame('admin', $admin->role);
        $this->assertSame($originalName, $admin->name, 'the refusal must run before any field is written');

        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $admin->id,
            'admin_username' => 'self_admin',
            'action' => 'user.role.change',
            'target_type' => 'user',
            'target_id' => $admin->id,
            'result' => 'failed',
        ]);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'self_guard']);

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'username' => 'self_guard',
            'name' => $admin->name,
            'role' => 'admin',
            'status' => 'inactive',
        ])->assertRedirect()
            ->assertSessionHas('error', 'You cannot deactivate your own account.');

        $this->assertSame('active', $admin->refresh()->status);

        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $admin->id,
            'action' => 'user.status.change',
            'target_id' => $admin->id,
            'result' => 'failed',
        ]);
    }

    public function test_admin_cannot_demote_the_only_other_active_admin(): void
    {
        $adminA = User::factory()->admin()->create(['username' => 'admin_a']);
        $adminB = User::factory()->admin()->create(['username' => 'admin_b']);

        $this->actingAs($adminA)->put(route('admin.users.update', $adminB), [
            'username' => 'admin_b',
            'name' => $adminB->name,
            'role' => 'teacher',
            'status' => 'active',
        ])->assertRedirect()
            ->assertSessionHas('error', 'This change would leave fewer than two active admins.');

        $this->assertSame('admin', $adminB->refresh()->role);

        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $adminA->id,
            'action' => 'user.role.change',
            'target_id' => $adminB->id,
            'result' => 'failed',
        ]);
    }

    public function test_admin_cannot_deactivate_the_only_other_active_admin(): void
    {
        $adminA = User::factory()->admin()->create(['username' => 'admin_x']);
        $adminB = User::factory()->admin()->create(['username' => 'admin_y']);

        $this->actingAs($adminA)->put(route('admin.users.update', $adminB), [
            'username' => 'admin_y',
            'name' => $adminB->name,
            'role' => 'admin',
            'status' => 'inactive',
        ])->assertRedirect()
            ->assertSessionHas('error', 'This change would leave fewer than two active admins.');

        $this->assertSame('active', $adminB->refresh()->status);

        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $adminA->id,
            'action' => 'user.status.change',
            'target_id' => $adminB->id,
            'result' => 'failed',
        ]);
    }

    public function test_admin_can_demote_an_active_admin_when_two_would_remain(): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create(['username' => 'demotable']);
        User::factory()->admin()->create();

        $this->actingAs($adminA)->put(route('admin.users.update', $adminB), [
            'username' => 'demotable',
            'name' => $adminB->name,
            'role' => 'teacher',
            'status' => 'active',
        ])->assertRedirect(route('admin.users.show', $adminB));

        $this->assertSame('teacher', $adminB->refresh()->role);

        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $adminA->id,
            'action' => 'user.role.change',
            'target_id' => $adminB->id,
            'result' => 'success',
            'summary' => 'Role changed: admin → teacher',
        ]);
    }

    public function test_guards_do_not_apply_when_the_target_is_not_an_active_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create(['username' => 'plain_teacher']);

        $this->actingAs($admin)->put(route('admin.users.update', $teacher), [
            'username' => 'plain_teacher',
            'name' => $teacher->name,
            'role' => 'student',
            'status' => 'inactive',
        ])->assertRedirect(route('admin.users.show', $teacher));

        $this->assertSame('student', $teacher->refresh()->role);
        $this->assertSame('inactive', $teacher->refresh()->status);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'user.status.change',
            'target_id' => $teacher->id,
            'result' => 'success',
        ]);
    }

    public function test_role_change_can_assign_the_operator_role_on_an_existing_account(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['username' => 'future_operator']);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'username' => 'future_operator',
            'name' => $target->name,
            'role' => 'operator',
            'status' => 'active',
        ])->assertRedirect(route('admin.users.show', $target));

        $this->assertSame('operator', $target->refresh()->role);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'user.role.change',
            'target_id' => $target->id,
            'result' => 'success',
            'summary' => 'Role changed: student → operator',
        ]);
    }

    public function test_role_change_rejects_arbitrary_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['username' => 'role_target']);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'username' => 'role_target',
            'name' => $target->name,
            'role' => 'superuser',
            'status' => 'active',
        ])->assertSessionHasErrors('role');

        $this->assertSame('student', $target->refresh()->role);
        $this->assertDatabaseMissing('the404_admin_audit', ['action' => 'user.role.change']);
    }

    public function test_status_change_rejects_arbitrary_statuses(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['username' => 'status_target']);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'username' => 'status_target',
            'name' => $target->name,
            'role' => 'student',
            'status' => 'banned',
        ])->assertSessionHasErrors('status');

        $this->assertSame('active', $target->refresh()->status);
    }

    public function test_admin_can_deactivate_and_reactivate_a_student_account(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['username' => 'flu_guard']);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'username' => 'flu_guard',
            'name' => $target->name,
            'role' => 'student',
            'status' => 'inactive',
        ])->assertRedirect();

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'username' => 'flu_guard',
            'name' => $target->name,
            'role' => 'student',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertSame('active', $target->refresh()->status);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'user.status.change',
            'target_id' => $target->id,
            'result' => 'success',
            'summary' => 'Status changed: active → inactive',
        ]);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'user.status.change',
            'target_id' => $target->id,
            'result' => 'success',
            'summary' => 'Status changed: inactive → active',
        ]);
    }

    public function test_posting_unchanged_role_and_status_writes_no_audit_row(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['username' => 'noop_cadet']);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'username' => 'noop_cadet',
            'name' => 'Renamed',
            'role' => 'student',
            'status' => 'active',
        ])->assertRedirect(route('admin.users.show', $target));

        $this->assertSame('student', $target->refresh()->role);
        $this->assertSame('Renamed', $target->refresh()->name);
        $this->assertDatabaseMissing('the404_admin_audit', [
            'action' => 'user.status.change',
        ]);
    }

    public function test_non_admin_submitting_role_admin_is_rejected_on_the_update_path(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->teacher()->create();
        $operator = User::factory()->create(['role' => 'operator']);
        $target = User::factory()->create(['username' => 'guarded_target']);

        foreach ([$student, $teacher, $operator] as $user) {
            $this->actingAs($user)->put(route('admin.users.update', $target), [
                'username' => 'guarded_target',
                'name' => $target->name,
                'role' => 'admin',
                'status' => 'active',
            ])->assertForbidden();
        }

        $this->assertSame('student', User::find($target->id)->role);
        $this->assertSame(0, AdminAudit::count(), 'a 403 must not write an audit row');
    }
}
