<?php

namespace Tests\Feature;

use App\Models\AssessmentAttempt;
use App\Models\Classroom;
use App\Models\KnowledgeCheckAttempt;
use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\Progress;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AdminAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminUserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public static function academicRecords(): array
    {
        return [
            'XP ledger row' => ['xp'],
            'progress row' => ['progress'],
            'mission draft' => ['draft'],
            'assessment attempt' => ['assessment_attempt'],
            'knowledge check attempt' => ['knowledge_check_attempt'],
            'classroom membership' => ['classroom_student'],
            'teaching assignment' => ['classroom_teacher'],
        ];
    }

    public static function nonAdminRoles(): array
    {
        return [
            'teacher' => ['teacher'],
            'student' => ['student'],
            'operator' => ['operator'],
        ];
    }

    public function test_admin_deletes_a_user_without_academic_records_and_purges_sessions(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        DB::table('sessions')->insert([
            'id' => 'target-session',
            'user_id' => $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('status', 'Account deleted.');

        $this->assertModelMissing($target);
        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $admin->id,
            'action' => AdminAuditService::ACTION_USER_DELETE,
            'result' => 'success',
            'target_type' => 'user',
            'target_id' => $target->id,
        ]);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
    }

    #[DataProvider('academicRecords')]
    public function test_admin_cannot_delete_a_user_with_academic_records(string $recordType): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();
        $this->attachAcademicRecord($recordType, $target);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $target))
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect()
            ->assertSessionHas('error', 'This account has learning or classroom records. Deactivate the account instead.');

        $this->assertModelExists($target);
        $this->assertDatabaseHas('the404_admin_audit', [
            'admin_user_id' => $admin->id,
            'action' => 'user.delete',
            'result' => 'failed',
            'target_type' => 'user',
            'target_id' => $target->id,
        ]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.show', $admin))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('error', 'You cannot delete your own account.');

        $this->assertModelExists($admin);
    }

    public function test_admin_cannot_delete_the_last_active_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.show', $target))
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect()
            ->assertSessionHas('error', 'This change would leave fewer than two active admins.');

        $this->assertModelExists($target);
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_roles_cannot_delete_users(string $role): void
    {
        $requester = User::factory()->create(['role' => $role]);
        $target = User::factory()->create();

        $this->actingAs($requester)
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();

        $this->assertModelExists($target);
    }

    public function test_guest_is_redirected_to_login_when_deleting_a_user(): void
    {
        $target = User::factory()->create();

        $this->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('login'));

        $this->assertModelExists($target);
    }

    private function attachAcademicRecord(string $recordType, User $target): void
    {
        $mission = Mission::factory()->create();

        match ($recordType) {
            'xp' => XpTransaction::factory()->create([
                'user_id' => $target->id,
                'mission_id' => $mission->id,
            ]),
            'progress' => Progress::factory()->create([
                'user_id' => $target->id,
                'mission_id' => $mission->id,
            ]),
            'draft' => MissionDraft::factory()->create([
                'user_id' => $target->id,
                'mission_id' => $mission->id,
            ]),
            'assessment_attempt' => AssessmentAttempt::factory()->create(['user_id' => $target->id]),
            'knowledge_check_attempt' => KnowledgeCheckAttempt::factory()->create(['user_id' => $target->id]),
            'classroom_student' => $this->attachClassroomAssignment($target, 'the404_classroom_students', 'student_id'),
            'classroom_teacher' => $this->attachClassroomAssignment($target, 'the404_classroom_teachers', 'teacher_id'),
        };
    }

    private function attachClassroomAssignment(User $target, string $table, string $userColumn): void
    {
        $classroom = Classroom::factory()->create();

        DB::table($table)->insert([
            'classroom_id' => $classroom->id,
            $userColumn => $target->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
