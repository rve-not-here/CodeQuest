<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\User;
use App\Services\AnnouncementService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US-807 feature suite: the admin announcement lifecycle (draft → published →
 * archived) over real HTTP, plus service-layer guard proof. Audience reach
 * (all|students|teachers|admins) is server-determined; publish fans out ONE
 * SYSTEM_ANNOUNCEMENT notification per matching ACTIVE user exactly once
 * (first publish only, dedupe_key announcement:{id}); operators and inactive
 * accounts are never delivered; editing a published announcement never
 * re-notifies; archiving is silent; a refusal is always a 'failed' audit row.
 */
class AdminAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_announcements_newest_first(): void
    {
        $admin = User::factory()->admin()->create();
        $older = Announcement::factory()->create(['created_by' => $admin->id, 'title' => 'Older Announcement']);
        $newer = Announcement::factory()->create(['created_by' => $admin->id, 'title' => 'Newer Announcement']);
        $older->forceFill(['created_at' => now()->subDay()])->save();
        $newer->forceFill(['created_at' => now()])->save();

        $response = $this->actingAs($admin)->get(route('admin.announcements'));

        $response->assertOk();
        $content = $response->getContent();

        $olderAt = strpos($content, 'Older Announcement');
        $newerAt = strpos($content, 'Newer Announcement');

        $this->assertNotFalse($olderAt);
        $this->assertNotFalse($newerAt);
        $this->assertLessThan($olderAt, $newerAt, 'newest announcement must render first');
    }

    public function test_create_renders_the_form(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.announcements.create'))
            ->assertOk()
            ->assertSee('New Announcement');
    }

    public function test_admin_creates_an_announcement_as_a_draft(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Scheduled Maintenance',
            'message' => 'System offline Saturday 02:00–04:00.',
            'audience' => 'all',
        ])->assertRedirect(route('admin.announcements'));

        $this->assertDatabaseHas('the404_announcements', [
            'title' => 'Scheduled Maintenance',
            'message' => 'System offline Saturday 02:00–04:00.',
            'audience' => 'all',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $announcement = Announcement::where('title', 'Scheduled Maintenance')->firstOrFail();
        $this->assertNull($announcement->published_at);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'announcement.create',
            'target_type' => 'announcement',
            'target_id' => $announcement->id,
            'result' => 'success',
        ]);

        $this->assertSame(0, Notification::count(), 'creating a draft must never notify');
    }

    public function test_create_rejects_an_audience_outside_the_server_set(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Sneaky',
            'message' => 'X',
            'audience' => 'guests',
        ])->assertSessionHasErrors('audience');

        $this->assertDatabaseCount('the404_announcements', 0);
    }

    public function test_create_requires_a_title_and_message(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => '',
            'message' => '',
            'audience' => 'all',
        ])->assertSessionHasErrors(['title', 'message']);

        $this->assertDatabaseCount('the404_announcements', 0);
    }

    public function test_edit_renders_the_current_announcement(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'title' => 'Known Title',
            'message' => 'Known body',
            'audience' => 'teachers',
        ]);

        $this->actingAs($admin)->get(route('admin.announcements.edit', $announcement))
            ->assertOk()
            ->assertSee('Known Title')
            ->assertSee('Known body');
    }

    public function test_update_edits_a_draft_and_audits_the_change(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'title' => 'Before',
            'message' => 'Old body',
            'audience' => 'students',
        ]);

        $this->actingAs($admin)->put(route('admin.announcements.update', $announcement), [
            'title' => 'After',
            'message' => 'New body',
            'audience' => 'teachers',
        ])->assertRedirect(route('admin.announcements'));

        $announcement->refresh();
        $this->assertSame('After', $announcement->title);
        $this->assertSame('New body', $announcement->message);
        $this->assertSame('teachers', $announcement->audience);
        $this->assertSame('draft', $announcement->status);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'announcement.update',
            'target_type' => 'announcement',
            'target_id' => $announcement->id,
            'result' => 'success',
            'summary' => "Announcement updated: title → 'After', message → (updated), audience → 'teachers'",
        ]);
    }

    public function test_update_of_a_published_announcement_never_re_notifies(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['role' => 'student']);
        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'title' => 'Original Title',
            'message' => 'Original message',
            'audience' => 'students',
        ]);

        $this->actingAs($admin)->post(route('admin.announcements.publish', $announcement))
            ->assertRedirect(route('admin.announcements'));

        $this->assertSame(1, Notification::count());
        $publishedAt = $announcement->refresh()->published_at;

        $this->actingAs($admin)->put(route('admin.announcements.update', $announcement), [
            'title' => 'Edited Title',
            'message' => 'Edited message',
            'audience' => 'all',
        ])->assertRedirect(route('admin.announcements'));

        $this->assertSame('Edited Title', $announcement->refresh()->title);
        $this->assertEquals($publishedAt, $announcement->published_at, 'published_at stays from the first publish');

        $this->assertSame(1, Notification::count(), 'editing a published announcement must never re-notify');
        $delivered = Notification::firstOrFail();
        $this->assertSame('Original Title', $delivered->title, 'delivered notifications are never rewritten');
        $this->assertSame('Original message', $delivered->message);
    }

    public function test_no_op_update_writes_no_audit_row(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)->put(route('admin.announcements.update', $announcement), [
            'title' => $announcement->title,
            'message' => $announcement->message,
            'audience' => $announcement->audience,
        ])->assertRedirect(route('admin.announcements'));

        $this->assertDatabaseMissing('the404_admin_audit', [
            'target_type' => 'announcement',
            'target_id' => $announcement->id,
        ]);
    }

    public function test_an_archived_announcement_is_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->archived()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)->put(route('admin.announcements.update', $announcement), [
            'title' => 'Rewrite Attempt',
            'message' => 'Should not apply.',
            'audience' => 'all',
        ])->assertSessionHas('error');

        $this->assertSame('archived', $announcement->refresh()->status);
        $this->assertNotSame('Rewrite Attempt', $announcement->title);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'announcement.update',
            'target_id' => $announcement->id,
            'result' => 'failed',
            'summary' => 'Refused: An archived announcement is read-only.',
        ]);
    }

    public function test_publish_sets_published_at_once_and_fans_out_to_matching_active_users(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $inactiveStudent = User::factory()->deactivated()->create(['role' => 'student']);
        $teacher = User::factory()->teacher()->create();
        $operator = User::factory()->create(['role' => 'operator']);

        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'title' => 'Maintenance Window',
            'message' => 'Down for upgrades Friday 22:00.',
            'audience' => 'students',
        ]);

        $this->actingAs($admin)->post(route('admin.announcements.publish', $announcement))
            ->assertRedirect(route('admin.announcements'));

        $announcement->refresh();
        $this->assertSame('published', $announcement->status);
        $this->assertNotNull($announcement->published_at);

        $this->assertSame(2, Notification::count(), 'only the two active students receive');
        $this->assertDatabaseHas('the404_notifications', [
            'user_id' => $student->id,
            'type' => 'system_announcement',
            'title' => 'Maintenance Window',
            'message' => 'Down for upgrades Friday 22:00.',
            'dedupe_key' => "announcement:{$announcement->id}",
        ]);
        $this->assertDatabaseHas('the404_notifications', [
            'user_id' => $otherStudent->id,
            'type' => 'system_announcement',
            'dedupe_key' => "announcement:{$announcement->id}",
        ]);

        foreach ([$inactiveStudent->id, $teacher->id, $operator->id] as $userId) {
            $this->assertDatabaseMissing('the404_notifications', ['user_id' => $userId]);
        }

        $row = Notification::where('user_id', $student->id)->firstOrFail();
        $this->assertSame(['route' => 'notifications', 'params' => []], $row->data);
        $this->assertSame(route('notifications'), app(NotificationService::class)->linkFor($row),
            'a system announcement links to the notification center');

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'announcement.publish',
            'target_type' => 'announcement',
            'target_id' => $announcement->id,
            'result' => 'success',
        ]);
    }

    public function test_all_audience_fans_out_to_every_active_non_operator_role(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->teacher()->create();
        $otherAdmin = User::factory()->admin()->create();
        $operator = User::factory()->create(['role' => 'operator']);
        $inactiveStudent = User::factory()->deactivated()->create(['role' => 'student']);

        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'audience' => 'all',
        ]);

        $this->actingAs($admin)->post(route('admin.announcements.publish', $announcement))
            ->assertRedirect(route('admin.announcements'));

        // student + teacher + both admins (the publisher matches the 'all'
        // audience like everyone else); operator and inactive accounts never
        // receive, for any audience.
        $this->assertSame(4, Notification::count());

        foreach ([$student->id, $teacher->id, $admin->id, $otherAdmin->id] as $userId) {
            $this->assertDatabaseHas('the404_notifications', ['user_id' => $userId, 'type' => 'system_announcement']);
        }

        foreach ([$operator->id, $inactiveStudent->id] as $userId) {
            $this->assertDatabaseMissing('the404_notifications', ['user_id' => $userId]);
        }
    }

    public function test_double_publish_is_refused_and_never_re_fans_out(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['role' => 'student']);
        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'title' => 'First Run',
            'audience' => 'students',
        ]);

        $this->actingAs($admin)->post(route('admin.announcements.publish', $announcement))
            ->assertRedirect(route('admin.announcements'));

        $publishedAt = $announcement->refresh()->published_at;

        $this->actingAs($admin)->post(route('admin.announcements.publish', $announcement))
            ->assertSessionHas('error');

        $this->assertSame('published', $announcement->refresh()->status);
        $this->assertEquals($publishedAt, $announcement->published_at, 'published_at must never be rewritten');
        $this->assertSame(1, Notification::count(), 'a refused re-publish must not re-fan-out');

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'announcement.publish',
            'target_id' => $announcement->id,
            'result' => 'failed',
            'summary' => 'Refused: This announcement has already been published.',
        ]);
    }

    public function test_publish_of_an_archived_announcement_is_refused(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->archived()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)->post(route('admin.announcements.publish', $announcement))
            ->assertSessionHas('error');

        $this->assertSame('archived', $announcement->refresh()->status);
        $this->assertSame(0, Notification::count());

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'announcement.publish',
            'target_id' => $announcement->id,
            'result' => 'failed',
            'summary' => 'Refused: An archived announcement cannot be published.',
        ]);
    }

    public function test_archive_only_works_from_published_and_is_silent(): void
    {
        $admin = User::factory()->admin()->create();
        $draft = Announcement::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)->post(route('admin.announcements.archive', $draft))
            ->assertSessionHas('error');

        $this->assertSame('draft', $draft->refresh()->status);
        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'announcement.archive',
            'target_id' => $draft->id,
            'result' => 'failed',
            'summary' => 'Refused: Only a published announcement can be archived.',
        ]);

        $published = Announcement::factory()->create(['created_by' => $admin->id]);
        $this->actingAs($admin)->post(route('admin.announcements.publish', $published))
            ->assertRedirect(route('admin.announcements'));

        $this->assertSame(1, Notification::count());
        $before = Notification::count();

        $this->actingAs($admin)->post(route('admin.announcements.archive', $published))
            ->assertRedirect(route('admin.announcements'));

        $this->assertSame('archived', $published->refresh()->status);
        $this->assertSame($before, Notification::count(), 'archiving must never notify a new set of users');
        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'announcement.archive',
            'target_id' => $published->id,
            'result' => 'success',
            'summary' => "Announcement archived: '{$published->title}'",
        ]);
    }

    public function test_draft_and_unpublished_announcements_never_reach_the_notification_center(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['role' => 'student']);

        Announcement::factory()->create([
            'created_by' => $admin->id,
            'title' => 'Secret Draft',
            'audience' => 'all',
        ]);

        $this->assertSame(0, Notification::count(), 'a draft must produce no notification rows');

        $this->actingAs($student)->get(route('notifications'))
            ->assertOk()
            ->assertDontSee('Secret Draft');
    }

    public function test_a_teacher_cannot_publish_an_announcement(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $announcement = Announcement::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($teacher)->post(route('admin.announcements.publish', $announcement), [])
            ->assertForbidden();

        $this->assertSame('draft', $announcement->refresh()->status);
        $this->assertSame(0, AdminAudit::count(), 'a 403 must not write an audit row');
    }

    public function test_publish_refuses_an_incomplete_announcement(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create(['created_by' => $admin->id]);
        $announcement->forceFill(['title' => ''])->save();

        $service = app(AnnouncementService::class);

        try {
            $service->publish($admin, $announcement);
            $this->fail('publishing an announcement with an empty title must throw');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('A published announcement needs both a title and a message.', $e->getMessage());
        }

        $this->assertSame('draft', $announcement->refresh()->status);
        $this->assertSame(0, Notification::count());
        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'announcement.publish',
            'target_id' => $announcement->id,
            'result' => 'failed',
            'summary' => 'Refused: A published announcement needs both a title and a message.',
        ]);
    }

    public function test_out_of_allow_list_audience_at_the_service_layer_records_a_failed_row(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create(['created_by' => $admin->id]);
        $service = app(AnnouncementService::class);

        try {
            $service->update($admin, $announcement, [
                'title' => $announcement->title,
                'message' => $announcement->message,
                'audience' => 'contact-list',
            ]);
            $this->fail('an out-of-allow-list audience must throw');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame("Unknown audience 'contact-list'.", $e->getMessage());
        }

        $this->assertSame('all', $announcement->refresh()->audience);
        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'announcement.update',
            'target_id' => $announcement->id,
            'result' => 'failed',
            'summary' => "Refused: Unknown audience 'contact-list'.",
        ]);
    }
}
