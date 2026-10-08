<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\AnnouncementService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class AnnouncementMutationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('auditedMutations')]
    public function test_audit_failure_rolls_back_announcement_mutation(string $operation): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create(['created_by' => $admin->id, 'title' => 'Original']);
        $this->mock(AdminAuditService::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('audit unavailable'));

        try {
            if ($operation === 'create') {
                app(AnnouncementService::class)->create($admin, ['title' => 'Changed', 'message' => 'Message', 'audience' => 'students']);
            } else {
                app(AnnouncementService::class)->update($admin, $announcement, ['title' => 'Changed', 'message' => $announcement->message, 'audience' => $announcement->audience]);
            }
            $this->fail('Audit failure must roll back the announcement mutation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }

        $this->assertDatabaseCount('the404_announcements', 1);
        $this->assertSame('Original', $announcement->fresh()->title);
        $this->assertDatabaseCount('the404_admin_audit', 0);
    }

    /** @return array<string, array{string}> */
    public static function auditedMutations(): array
    {
        return ['create' => ['create'], 'update' => ['update']];
    }

    #[DataProvider('archivedMutations')]
    public function test_stale_announcement_cannot_modify_archived_state(string $operation): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create();
        $announcement = Announcement::factory()->create(['created_by' => $admin->id, 'audience' => 'students']);
        $draft = $announcement->fresh();
        $service = app(AnnouncementService::class);
        $service->publish($admin, $announcement);
        $stale = $operation === 'publish' ? $draft : $announcement->fresh();
        $service->archive($admin, $announcement);
        $before = $announcement->fresh()->getRawOriginal();
        $notificationCount = Notification::query()->count();

        try {
            if ($operation === 'update') {
                $service->update($admin, $stale, ['title' => 'Changed', 'message' => $stale->message, 'audience' => $stale->audience]);
            } else {
                $service->{$operation}($admin, $stale);
            }
            $this->fail('The persisted archived state must reject stale mutations.');
        } catch (InvalidArgumentException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }

        $this->assertSame($before, $announcement->fresh()->getRawOriginal());
        $this->assertSame($notificationCount, Notification::query()->count());
        $this->assertDatabaseHas('the404_admin_audit', ['action' => 'announcement.'.$operation, 'target_id' => $announcement->id, 'result' => 'failed']);
        $this->assertSame(1, AdminAudit::query()->where('action', 'announcement.'.$operation)->where('result', 'failed')->count());
    }

    /** @return array<string, array{string}> */
    public static function archivedMutations(): array
    {
        return ['publish stale draft' => ['publish'], 'update stale published' => ['update'], 'archive stale published' => ['archive']];
    }

    public function test_stale_draft_cannot_publish_twice_or_change_the_first_publication_time(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create();
        $announcement = Announcement::factory()->create(['created_by' => $admin->id, 'audience' => 'students']);
        $stale = $announcement->fresh();
        $service = app(AnnouncementService::class);
        $service->publish($admin, $announcement);
        $publishedAt = $announcement->fresh()->published_at;
        $this->travel(1)->days();

        try {
            $service->publish($admin, $stale);
            $this->fail('Only the first publication may succeed.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('This announcement has already been published.', $exception->getMessage());
        }

        $this->assertEquals($publishedAt, $announcement->fresh()->published_at);
        $this->assertDatabaseCount('the404_notifications', 1);
        $this->assertSame(1, AdminAudit::query()->where('action', 'announcement.publish')->where('result', 'success')->count());
        $this->assertDatabaseHas('the404_admin_audit', ['action' => 'announcement.publish', 'target_id' => $announcement->id, 'result' => 'failed']);
    }

    public function test_publish_uses_current_saved_content_and_audience_instead_of_stale_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $announcement = Announcement::factory()->create(['created_by' => $admin->id, 'title' => 'Old title', 'message' => 'Old message', 'audience' => 'students']);
        $stale = $announcement->fresh();
        $service = app(AnnouncementService::class);
        $service->update($admin, $announcement, ['title' => 'Current title', 'message' => 'Current message', 'audience' => 'teachers']);
        $service->publish($admin, $stale);

        $this->assertDatabaseHas('the404_notifications', ['user_id' => $teacher->id, 'title' => 'Current title', 'message' => 'Current message']);
        $this->assertDatabaseMissing('the404_notifications', ['user_id' => $student->id]);
        $this->assertDatabaseCount('the404_notifications', 1);
        $this->assertSame('teachers', $announcement->fresh()->audience);
    }

    public function test_partial_notification_failure_rolls_back_publication_and_accepted_audit(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create();
        $announcement = Announcement::factory()->create(['created_by' => $admin->id, 'audience' => 'students']);
        $notifications = app(NotificationService::class);
        $deliveries = 0;
        $this->mock(NotificationService::class)->shouldReceive('create')->twice()->andReturnUsing(function (...$arguments) use ($notifications, &$deliveries): Notification {
            if (++$deliveries === 2) {
                throw new RuntimeException('delivery unavailable');
            }

            return $notifications->create(...$arguments);
        });

        try {
            app(AnnouncementService::class)->publish($admin, $announcement);
            $this->fail('A failed delivery must roll back publication.');
        } catch (RuntimeException $exception) {
            $this->assertSame('delivery unavailable', $exception->getMessage());
        }

        $this->assertSame('draft', $announcement->fresh()->status);
        $this->assertNull($announcement->fresh()->published_at);
        $this->assertDatabaseCount('the404_notifications', 0);
        $this->assertDatabaseCount('the404_admin_audit', 0);
    }
}
