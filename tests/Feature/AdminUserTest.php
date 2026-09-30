<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_all_users_with_role_status_and_created_date(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'chief_admin']);
        $student = User::factory()->create(['username' => 'roster_student', 'name' => 'Roster Student']);
        $teacher = User::factory()->teacher()->create(['username' => 'roster_teacher']);
        User::factory()->create(['role' => 'operator', 'username' => 'roster_operator']);
        User::factory()->deactivated()->create(['role' => 'teacher', 'username' => 'off_teacher']);

        $response = $this->actingAs($admin)->get(route('admin.users'));

        $response->assertOk()
            ->assertSee('User Management')
            ->assertSee('chief_admin')
            ->assertSee('roster_student')
            ->assertSee('Roster Student')
            ->assertSee('roster_teacher')
            ->assertSee('roster_operator')
            ->assertSee('off_teacher')
            ->assertSee('STUDENT')
            ->assertSee('TEACHER')
            ->assertSee('ADMIN')
            ->assertSee('OPERATOR')
            ->assertSee('ACTIVE')
            ->assertSee('INACTIVE')
            ->assertSee(now()->format('Y-m-d'));
    }

    public function test_user_directory_search_filters_username_and_name_server_side(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['username' => 'alpha_pilot', 'name' => 'First Cadet']);
        User::factory()->create(['username' => 'beta_ranger', 'name' => 'Second Cadet']);
        User::factory()->create(['username' => 'gamma_scout', 'name' => 'Pilot Candidate']);

        // Server-side username match.
        $this->actingAs($admin)
            ->get(route('admin.users', ['q' => 'beta']))
            ->assertOk()
            ->assertSee('beta_ranger')
            ->assertDontSee('alpha_pilot')
            ->assertDontSee('gamma_scout');

        // Server-side name match.
        $this->actingAs($admin)
            ->get(route('admin.users', ['q' => 'Pilot Candidate']))
            ->assertOk()
            ->assertSee('gamma_scout')
            ->assertDontSee('alpha_pilot')
            ->assertDontSee('beta_ranger');
    }

    public function test_user_directory_filters_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['username' => 'filter_student']);
        User::factory()->teacher()->create(['username' => 'filter_teacher']);

        $this->actingAs($admin)
            ->get(route('admin.users', ['role' => 'teacher']))
            ->assertOk()
            ->assertSee('filter_teacher')
            ->assertDontSee('filter_student');
    }

    public function test_user_directory_paginates_server_side(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'page_admin']);

        User::factory()->create(['username' => 'page_user_01']);
        User::factory()->create(['username' => 'page_user_02']);
        User::factory()->create(['username' => 'page_user_03']);
        User::factory()->create(['username' => 'page_user_04']);
        User::factory()->create(['username' => 'page_user_05']);
        User::factory()->create(['username' => 'page_user_06']);
        User::factory()->create(['username' => 'page_user_07']);
        User::factory()->create(['username' => 'page_user_08']);
        User::factory()->create(['username' => 'page_user_09']);
        User::factory()->create(['username' => 'page_user_10']);
        User::factory()->create(['username' => 'page_user_11']);
        User::factory()->create(['username' => 'page_user_12']);

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('SHOWING PAGE 1 OF 2')
            ->assertSee('page_user_01')
            ->assertDontSee('page_user_12');

        $this->actingAs($admin)
            ->get(route('admin.users', ['page' => 2]))
            ->assertOk()
            ->assertSee('SHOWING PAGE 2 OF 2')
            ->assertSee('page_user_11')
            ->assertDontSee('page_user_01');
    }

    public function test_user_directory_queries_stay_bounded_after_the_first_page(): void
    {
        User::factory()->admin()->create();
        User::factory()->count(12)->create();

        $small = $this->directoryQueryCount();

        User::factory()->count(13)->create();
        $large = $this->directoryQueryCount();

        $this->assertLessThanOrEqual($small + 10, $large, "Directory queries grew from {$small} to {$large} for 13 versus 26 users.");
    }

    public function test_displayed_directory_rows_do_not_multiply_queries(): void
    {
        User::factory()->admin()->create();

        $one = $this->directoryQueryCount();

        User::factory()->count(4)->create();
        $five = $this->directoryQueryCount();

        User::factory()->count(5)->create();
        $ten = $this->directoryQueryCount();

        $this->assertLessThanOrEqual($one + 10, $five, "Directory queries grew {$one} → {$five} for 1 → 5 displayed users.");
        $this->assertLessThanOrEqual($one + 10, $ten, "Directory queries grew {$one} → {$ten} for 1 → 10 displayed users.");
    }

    public function test_directory_queries_stay_bounded_for_users_with_completed_sections(): void
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        $user = User::factory()->create();
        Progress::factory()->create(['user_id' => $user->id, 'mission_id' => $mission->id]);

        $one = $this->directoryQueryCount();

        $more = User::factory()->count(9)->create();
        foreach ($more as $student) {
            Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
        }
        $ten = $this->directoryQueryCount();

        $this->assertLessThanOrEqual($one + 10, $ten, "Completed-section directory queries grew {$one} → {$ten} for 1 → 10 users.");
    }

    private function directoryQueryCount(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            app(UserService::class)->index(null, null, []);

            return count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public function test_user_directory_last_activity_uses_timeline_vocabulary(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['username' => 'active_cadet']);
        $fresh = User::factory()->teacher()->create(['username' => 'fresh_teacher']);

        $student->activities()->create([
            'type' => 'mission_completed',
            'message' => 'Restored comms relay Alpha-1',
            'pts' => 100,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Restored comms relay Alpha-1');
    }

    public function test_admin_can_view_user_detail_with_recent_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create([
            'username' => 'detail_cadet',
            'name' => 'Detail Cadet',
        ]);

        $student->activities()->create([
            'type' => 'mission_completed',
            'message' => 'Booted the uplink array',
            'pts' => 50,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $student))
            ->assertOk()
            ->assertSee('detail_cadet')
            ->assertSee('Detail Cadet')
            ->assertSee('STUDENT')
            ->assertSee('ACTIVE')
            ->assertSee($student->created_at->format('Y-m-d'))
            ->assertSee('Booted the uplink array')
            ->assertSee('EDIT →')
            ->assertSee('◀ ALL USERS');
    }

    public function test_admin_can_create_a_student_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'username' => 'rookie_cadet',
            'name' => 'Rookie Cadet',
            'role' => 'student',
            'password' => 'temporary-parol-8',
        ]);

        $created = User::where('username', 'rookie_cadet')->firstOrFail();

        $response->assertRedirect(route('admin.users.show', $created));

        $this->assertSame('student', $created->role);
        $this->assertSame('active', $created->status);
        $this->assertNotSame('temporary-parol-8', $created->password);
        $this->assertTrue(Hash::check('temporary-parol-8', $created->password));
    }

    public function test_a_crafted_status_in_the_create_payload_is_dropped_at_the_controller(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'username' => 'status_sneak',
            'name' => 'Status Sneak',
            'role' => 'student',
            'password' => 'password-123',
            'status' => 'inactive',
        ])->assertRedirect();

        $created = User::where('username', 'status_sneak')->firstOrFail();

        $this->assertSame('active', $created->status, 'status is server-decided at create and never read from the request');
        $this->assertNotSame('inactive', $created->status);
    }

    public function test_admin_can_create_teacher_and_admin_accounts(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'username' => 'new_teacher',
            'name' => 'New Teacher',
            'role' => 'teacher',
            'password' => 'teacher-pass-1',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'username' => 'new_admin',
            'name' => 'New Admin',
            'role' => 'admin',
            'password' => 'admin-pass-1',
        ])->assertRedirect();

        $this->assertSame('teacher', User::where('username', 'new_teacher')->value('role'));
        $this->assertSame('admin', User::where('username', 'new_admin')->value('role'));
    }

    public function test_user_creation_rejects_the_operator_role_and_invalid_roles(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'username' => 'evil_operator',
                'name' => 'Evil Operator',
                'role' => 'operator',
                'password' => 'operator-pass',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('the404_users', ['username' => 'evil_operator']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'username' => 'mystery_role',
                'name' => 'Mystery Role',
                'role' => 'superuser',
                'password' => 'super-pass-1',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('the404_users', ['username' => 'mystery_role']);
    }

    public function test_user_creation_validates_all_input(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['username' => 'clash_cadet']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [])
            ->assertSessionHasErrors(['username', 'name', 'role', 'password']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'username' => 'clash_cadet',
                'name' => 'Duplicate',
                'role' => 'student',
                'password' => 'longenough',
            ])
            ->assertSessionHasErrors('username');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'username' => 'short_pass',
                'name' => 'Short',
                'role' => 'student',
                'password' => 'short',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_user_creation_rejects_pre_hashed_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'username' => 'hash_tamperer',
                'name' => 'Hash Tamperer',
                'role' => 'student',
                'password' => Hash::make('should-not-accept'),
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('the404_users', ['username' => 'hash_tamperer']);
    }

    public function test_user_creation_records_an_audit_trail_entry(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'create_auditor']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'username' => 'audited_cadet',
            'name' => 'Audited Cadet',
            'role' => 'student',
            'password' => 'audited-pass-9',
        ])->assertRedirect();

        $created = User::where('username', 'audited_cadet')->firstOrFail();

        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $admin->id,
            'admin_username' => 'create_auditor',
            'action' => AdminAuditService::ACTION_USER_CREATE,
            'target_type' => 'user',
            'target_id' => $created->id,
            'result' => 'success',
        ]);

        $this->assertDatabaseMissing('the404_admin_audit', [
            'action' => AdminAuditService::ACTION_USER_CREATE,
            'summary' => 'audited-pass-9',
        ]);
    }

    public function test_user_edit_records_an_audit_trail_entry_for_base_field_changes(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'edit_auditor']);
        $target = User::factory()->create([
            'username' => 'old_handle_2',
            'name' => 'Old Name Two',
            'password' => Hash::make('old-password-2'),
        ]);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'username' => 'new_handle_2',
            'name' => 'New Name Two',
            'password' => 'new-password-2',
        ])->assertRedirect();

        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $admin->id,
            'admin_username' => 'edit_auditor',
            'action' => AdminAuditService::ACTION_USER_UPDATE,
            'target_type' => 'user',
            'target_id' => $target->id,
            'result' => 'success',
            'summary' => "User updated: username → 'new_handle_2', name → 'New Name Two', password → (changed)",
        ]);

        $this->assertDatabaseMissing('the404_admin_audit', [
            'action' => AdminAuditService::ACTION_USER_UPDATE,
            'summary' => 'new-password-2',
        ]);
    }

    public function test_user_edit_records_no_audit_row_when_base_fields_are_unchanged(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create([
            'username' => 'noop_cadet',
            'name' => 'Noop Cadet',
            'password' => Hash::make('noop-password-1'),
        ]);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'username' => 'noop_cadet',
            'name' => 'Noop Cadet',
            'password' => 'noop-password-1',
        ])->assertRedirect();

        $this->assertDatabaseMissing('the404_admin_audit', [
            'target_type' => 'user',
            'target_id' => $target->id,
            'action' => AdminAuditService::ACTION_USER_UPDATE,
        ]);
    }

    public function test_unknown_role_at_the_service_layer_records_a_failed_audit_row(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['username' => 'shape_target']);

        try {
            app(UserService::class)->update($admin, $target, [
                'username' => 'shape_target',
                'name' => 'Shape Target',
                'role' => 'archived',
            ]);
            $this->fail('Expected InvalidArgumentException for an out-of-role-list value.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("Unknown role 'archived'.", $e->getMessage());
        }

        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $admin->id,
            'action' => AdminAuditService::ACTION_ROLE_CHANGE,
            'target_type' => 'user',
            'target_id' => $target->id,
            'result' => 'failed',
            'summary' => "Refused: Unknown role 'archived'.",
        ]);

        $this->assertSame('student', $target->refresh()->role);
    }

    public function test_unknown_status_at_the_service_layer_records_a_failed_audit_row(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['username' => 'status_target']);

        try {
            app(UserService::class)->update($admin, $target, [
                'username' => 'status_target',
                'name' => 'Status Target',
                'status' => 'archived',
            ]);
            $this->fail('Expected InvalidArgumentException for an out-of-status value.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("Unknown status 'archived'.", $e->getMessage());
        }

        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $admin->id,
            'action' => AdminAuditService::ACTION_STATUS_CHANGE,
            'target_type' => 'user',
            'target_id' => $target->id,
            'result' => 'failed',
            'summary' => "Refused: Unknown status 'archived'.",
        ]);

        $this->assertSame('active', $target->refresh()->status);
    }

    public function test_user_creation_ignores_client_supplied_status(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'username' => 'status_cadet',
            'name' => 'Status Cadet',
            'role' => 'student',
            'password' => 'status-pass-1',
            'status' => 'inactive',
        ])->assertRedirect();

        $this->assertSame('active', User::where('username', 'status_cadet')->value('status'));
    }

    public function test_admin_can_edit_username_name_and_password(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create([
            'username' => 'old_handle',
            'name' => 'Old Name',
            'password' => Hash::make('old-password-1'),
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'username' => 'new_handle',
            'name' => 'New Name',
            'password' => 'new-password-1',
        ]);

        $target->refresh();

        $response->assertRedirect(route('admin.users.show', $target));
        $this->assertSame('new_handle', $target->username);
        $this->assertSame('New Name', $target->name);
        $this->assertTrue(Hash::check('new-password-1', $target->password));
        $this->assertFalse(Hash::check('old-password-1', $target->password));
    }

    public function test_user_edit_applies_role_and_status_when_posted(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create([
            'username' => 'mutable_cadet',
            'name' => 'Mutable Cadet',
        ]);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'username' => 'mutable_cadet',
            'name' => 'Mutable Cadet',
            'role' => 'teacher',
            'status' => 'inactive',
        ])->assertRedirect();

        $target->refresh();

        $this->assertSame('teacher', $target->role);
        $this->assertSame('inactive', $target->status);
    }

    public function test_user_edit_password_is_optional_and_keeps_current_hash(): void
    {
        $admin = User::factory()->admin()->create();
        $password = 'original-pass-9';
        $target = User::factory()->create([
            'username' => 'keep_pass_cadet',
            'name' => 'Keep Pass',
            'password' => Hash::make($password),
        ]);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'username' => 'keep_pass_cadet',
            'name' => 'Keep Pass Renamed',
        ])->assertRedirect();

        $target->refresh();

        $this->assertSame('Keep Pass Renamed', $target->name);
        $this->assertTrue(Hash::check($password, $target->password));
    }

    public function test_user_edit_requires_unique_username_except_self(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create(['username' => 'taken_handle']);
        $target = User::factory()->create([
            'username' => 'editable_handle',
            'name' => 'Editable',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'username' => 'taken_handle',
                'name' => 'Editable',
            ])
            ->assertSessionHasErrors('username');

        // Identical username on the same account is allowed (ignores self).
        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'username' => 'editable_handle',
                'name' => 'Editable',
            ])
            ->assertRedirect();

        $this->assertSame('student', User::find($other->id)->role);
    }

    public function test_non_admin_roles_cannot_create_or_update_users(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->teacher()->create();
        $operator = User::factory()->create(['role' => 'operator']);
        $target = User::factory()->create(['username' => 'guarded_cadet']);

        foreach ([$student, $teacher, $operator] as $user) {
            $this->actingAs($user)
                ->post(route('admin.users.store'), [
                    'username' => 'sneaky_create',
                    'name' => 'Sneaky',
                    'role' => 'student',
                    'password' => 'password-123',
                ])
                ->assertForbidden();

            $this->actingAs($user)
                ->put(route('admin.users.update', $target), [
                    'username' => 'guarded_cadet',
                    'name' => 'Hacked',
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('the404_users', ['username' => 'sneaky_create']);
        $this->assertSame('guarded_cadet', User::find($target->id)->username);
    }

    public function test_guests_are_redirected_to_login_before_reaching_user_write_routes(): void
    {
        $this->post(route('admin.users.store'), [])->assertRedirect(route('login'));

        $target = User::factory()->create();

        $this->put(route('admin.users.update', $target), [])->assertRedirect(route('login'));
    }
}
