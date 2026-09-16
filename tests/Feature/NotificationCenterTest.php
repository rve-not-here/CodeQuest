<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_center_shows_total_and_unread_counts(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(2)->create(['user_id' => $user->id]);
        Notification::factory()->create(['user_id' => $user->id, 'read_at' => now()]);

        $response = $this->actingAs($user)->get(route('notifications'));

        $response->assertOk()
            ->assertSee('3 TOTAL')
            ->assertSee('2 UNREAD');
    }

    public function test_notification_center_hides_the_unread_badge_when_everything_is_read(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(2)->read()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('notifications'));

        $response->assertOk()
            ->assertSee('2 TOTAL')
            ->assertDontSee('UNREAD');
    }

    public function test_notification_center_distinguishes_read_and_unread_rows(): void
    {
        $user = User::factory()->create();

        Notification::factory()->create(['user_id' => $user->id, 'title' => 'FRESH ALERT']);
        Notification::factory()->create(['user_id' => $user->id, 'title' => 'SEEN ALERT', 'read_at' => now()]);

        $response = $this->actingAs($user)->get(route('notifications'));

        $response->assertOk()
            ->assertSee('FRESH ALERT')
            ->assertSee('SEEN ALERT')
            ->assertSee('MARK READ');
    }

    public function test_notification_center_paginates_the_callers_history_newest_first(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 31) as $i) {
            Notification::factory()->create([
                'user_id' => $user->id,
                'title' => "PAGBATCH_{$i}",
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $pageOne = $this->actingAs($user)->get(route('notifications'));
        $pageOne->assertOk()
            ->assertSee('PAGBATCH_1')
            ->assertSee('PAGBATCH_30')
            ->assertDontSee('PAGBATCH_31')
            ->assertSee('SHOWING PAGE 1 OF 2');

        $pageTwo = $this->actingAs($user)->get(route('notifications', ['page' => 2]));
        $pageTwo->assertOk()
            ->assertSee('PAGBATCH_31')
            ->assertDontSee('PAGBATCH_1')
            ->assertSee('SHOWING PAGE 2 OF 2');
    }

    public function test_unread_count_query_uses_the_read_state_index(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(20)->create(['user_id' => $user->id]);
        Notification::factory()->count(10)->create(['user_id' => $user->id, 'read_at' => now()]);

        $plan = DB::select(
            'EXPLAIN QUERY PLAN SELECT count(*) FROM the404_notifications WHERE user_id = ? AND read_at IS NULL',
            [$user->id],
        );

        $details = collect($plan)->pluck('detail')->implode(' ');

        $this->assertStringContainsString('the404_notifications_user_id_read_at_index', $details);
    }

    public function test_notifications_nav_item_links_to_the_center(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('href="'.route('notifications').'"', false);
    }
}
