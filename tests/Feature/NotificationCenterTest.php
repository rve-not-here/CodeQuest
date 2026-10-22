<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
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
            ->assertSee('3 total')
            ->assertSee('2 unread');
    }

    public function test_notification_center_hides_the_unread_badge_when_everything_is_read(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(2)->read()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('notifications'));

        $response->assertOk()
            ->assertSee('2 total')
            ->assertDontSee('atlas-notification-count', false);
    }

    public function test_shell_bell_shows_the_unread_count(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Notifications, 2 unread"', false)
            ->assertSee('atlas-notification-count', false);
    }

    public function test_notification_center_filters_to_unread_without_losing_all_counts(): void
    {
        $user = User::factory()->create();
        Notification::factory()->create(['user_id' => $user->id, 'title' => 'FRESH-UNREAD']);
        Notification::factory()->read()->create(['user_id' => $user->id, 'title' => 'SEEN-READ']);

        $this->actingAs($user)
            ->get(route('notifications', ['filter' => 'unread']))
            ->assertOk()
            ->assertSee('FRESH-UNREAD')
            ->assertDontSee('SEEN-READ')
            ->assertSee('aria-current="page"', false)
            ->assertSee('1 unread');

        $this->actingAs($user)
            ->get(route('notifications'))
            ->assertOk()
            ->assertSee('FRESH-UNREAD')
            ->assertSee('SEEN-READ')
            ->assertSee('2 total');
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
            ->assertSee('Mark read');
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
            ->assertSee('Page 1 of 2');

        $pageTwo = $this->actingAs($user)->get(route('notifications', ['page' => 2]));
        $pageTwo->assertOk()
            ->assertSee('PAGBATCH_31')
            ->assertDontSee('PAGBATCH_1')
            ->assertSee('Page 2 of 2');
    }

    public function test_notification_feed_bounds_the_database_row_read(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(125)->create(['user_id' => $user->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $page = app(NotificationService::class)->forUser($user);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $rowQuery = collect($queries)
            ->pluck('query')
            ->first(fn (string $sql): bool => str_contains($sql, 'the404_notifications') && str_contains($sql, 'order by'));

        $this->assertNotNull($rowQuery);
        $this->assertMatchesRegularExpression('/\blimit\b/i', $rowQuery);
        $this->assertCount(NotificationService::FEED_PER_PAGE, $page->items());
        $this->assertSame(125, $page->total());
    }

    public function test_unread_count_query_uses_the_read_state_index(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(20)->create(['user_id' => $user->id]);
        Notification::factory()->count(10)->create(['user_id' => $user->id, 'read_at' => now()]);

        $query = 'SELECT count(*) FROM the404_notifications WHERE user_id = ? AND read_at IS NULL';

        if (DB::connection()->getDriverName() === 'sqlite') {
            $plan = DB::select('EXPLAIN QUERY PLAN '.$query, [$user->id]);
            $details = collect($plan)->pluck('detail')->implode(' ');

            $this->assertStringContainsString('the404_notifications_user_id_read_at_index', $details);

            return;
        }

        $plan = DB::select('EXPLAIN '.$query, [$user->id]);

        $this->assertSame('the404_notifications_user_id_read_at_index', $plan[0]->key);
    }

    public function test_notifications_nav_item_links_to_the_center(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('href="'.route('notifications').'"', false);
    }
}
