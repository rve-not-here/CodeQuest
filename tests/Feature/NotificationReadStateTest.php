<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationReadStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_read_flags_an_owned_unread_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->unread()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('notifications.read', $notification));

        $response->assertRedirect(route('notifications'));
        $response->assertSessionHas('notification_success', [
            'title' => 'MARKED AS READ',
            'message' => 'Notification marked as read.',
        ]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_read_on_an_already_read_notification_is_an_idempotent_no_op(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->read()->create(['user_id' => $user->id]);
        $readAt = $notification->fresh()->read_at;

        $response = $this->actingAs($user)->post(route('notifications.read', $notification));

        $response->assertRedirect(route('notifications'));
        $response->assertSessionMissing('notification_success');

        $this->assertEquals($readAt, $notification->fresh()->read_at);
    }

    public function test_mark_read_on_nonexistent_and_foreign_ids_is_an_identical_no_op(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $foreign = Notification::factory()->unread()->create(['user_id' => $other->id]);

        // A nonexistent id and a foreign (exists-but-not-yours) id must behave
        // identically — a silent redirect, never a 404/403 split that would
        // reveal a row exists. This is findForUser's null result, the same
        // uniform rejection the US-801 read path uses.
        $nonexistent = $this->actingAs($owner)->post(route('notifications.read', 999999));
        $nonexistent->assertRedirect(route('notifications'));
        $nonexistent->assertSessionMissing('notification_success');

        $foreignResponse = $this->actingAs($owner)->post(route('notifications.read', $foreign->id));
        $foreignResponse->assertRedirect(route('notifications'));
        $foreignResponse->assertStatus($nonexistent->status());
        $foreignResponse->assertSessionMissing('notification_success');

        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_mark_read_never_touches_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $foreign = Notification::factory()->unread()->create(['user_id' => $other->id]);

        $this->actingAs($owner)->post(route('notifications.read', $foreign->id));

        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_mark_read_routes_require_authentication(): void
    {
        $this->post(route('notifications.read', 1))->assertRedirect(route('login'));
        $this->post(route('notifications.read-all'))->assertRedirect(route('login'));
    }

    public function test_mark_all_read_marks_only_the_callers_unread_notifications(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Notification::factory()->count(2)->unread()->create(['user_id' => $owner->id]);
        Notification::factory()->read()->create(['user_id' => $owner->id]);
        Notification::factory()->unread()->create(['user_id' => $other->id]);

        $response = $this->actingAs($owner)->post(route('notifications.read-all'));

        $response->assertRedirect(route('notifications'));
        $response->assertSessionHas('notification_success', [
            'title' => 'INBOX CLEARED',
            'message' => '2 notifications marked as read.',
        ]);

        $this->assertDatabaseMissing('the404_notifications', [
            'user_id' => $owner->id,
            'read_at' => null,
        ]);
        $this->assertDatabaseHas('the404_notifications', [
            'user_id' => $other->id,
            'read_at' => null,
        ]);
    }

    public function test_mark_all_read_is_idempotent(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(2)->unread()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('notifications.read-all'));

        $response = $this->actingAs($user)->post(route('notifications.read-all'));

        $response->assertRedirect(route('notifications'));
        $response->assertSessionMissing('notification_success');

        $this->assertDatabaseMissing('the404_notifications', [
            'user_id' => $user->id,
            'read_at' => null,
        ]);
    }

    public function test_unread_count_drops_after_mark_all_read(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(3)->unread()->create(['user_id' => $user->id]);

        $service = app(NotificationService::class);
        $this->assertSame(3, $service->unreadCount($user));

        $this->actingAs($user)->post(route('notifications.read-all'));

        $this->assertSame(0, $service->unreadCount($user));
    }
}
