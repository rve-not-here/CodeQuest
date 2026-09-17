<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The student dashboard no longer duplicates the notification center. The
 * authenticated top navigation owns the single Notifications destination;
 * dashboard rendering neither exposes notification rows nor mutates them.
 */
class StudentDashboardNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_keeps_notifications_in_the_primary_navigation_without_rendering_the_feed(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'title' => 'PRIVATE TRANSMISSION',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('href="'.route('notifications').'"', false)
            ->assertSee('Notifications')
            ->assertDontSee('Incoming notifications')
            ->assertDontSee('PRIVATE TRANSMISSION');

        $this->assertSame(1, substr_count($response->getContent(), 'href="'.route('notifications').'"'));
        $this->assertNull($notification->refresh()->read_at);
    }

    public function test_dashboard_rejects_user_scoping_probes(): void
    {
        $user = User::factory()->create();

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $param) {
            $this->actingAs($user)->get(route('dashboard').'?'.$param.'=1')->assertForbidden();
        }
    }
}
