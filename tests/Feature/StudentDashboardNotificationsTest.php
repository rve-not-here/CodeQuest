<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Dashboard notification teaser (US-810, §29): GET /dashboard may surface the
 * unread count and the two newest "priority" transmissions from the caller's
 * own feed, but must never replace Continue Learning as the page's purpose and
 * must never become a second notification center. Rows are a display-only
 * composition over the existing read paths (forUser / unreadCount / linkFor) —
 * no producer and no write — and every link goes through the same linkFor()
 * TYPE_ROUTES allowlist the center uses.
 */
class StudentDashboardNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_the_unread_count_and_a_view_all_link(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(2)->create(['user_id' => $user->id]);
        Notification::factory()->create(['user_id' => $user->id, 'read_at' => now()]);

        $unread = Notification::query()->where('user_id', $user->id)->whereNull('read_at')->firstOrFail();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Incoming notifications')
            ->assertSee('2 unread')
            ->assertSee(route('notifications'))
            ->assertSee('View all →');

        // A dashboard GET is a display read — it never rewrites read state.
        $this->assertNull($unread->refresh()->read_at);
    }

    public function test_dashboard_surfaces_only_the_two_newest_priority_notifications(): void
    {
        $user = User::factory()->create();

        Notification::factory()->create([
            'user_id' => $user->id,
            'type' => NotificationService::TYPE_ASSESSMENT_UNLOCKED,
            'title' => 'ASSESSMENT UNLOCKED',
            'created_at' => now()->subMinutes(1),
        ]);
        Notification::factory()->create([
            'user_id' => $user->id,
            'type' => NotificationService::TYPE_LEARNING_REMINDER,
            'title' => 'COURSE STALLED',
            'created_at' => now()->subMinutes(2),
        ]);
        Notification::factory()->create([
            'user_id' => $user->id,
            'type' => NotificationService::TYPE_DRAFT_REMINDER,
            'title' => 'DRAFT STALE',
            'created_at' => now()->subMinutes(3),
        ]);
        // Newest overall, but mission feedback is event noise, not a priority
        // transmission: it must not displace the two reminders above.
        Notification::factory()->create([
            'user_id' => $user->id,
            'type' => NotificationService::TYPE_MISSION_COMPLETED,
            'title' => 'MISSION COMPLETE',
            'created_at' => now(),
        ]);

        $panel = $this->panel($this->actingAs($user)->get(route('dashboard')));

        $this->assertStringContainsString('ASSESSMENT UNLOCKED', $panel);
        $this->assertStringContainsString('COURSE STALLED', $panel);
        $this->assertStringNotContainsString('DRAFT STALE', $panel);
        $this->assertStringNotContainsString('MISSION COMPLETE', $panel);

        $this->assertLessThan(
            strpos($panel, 'COURSE STALLED'),
            strpos($panel, 'ASSESSMENT UNLOCKED'),
        );
    }

    public function test_dashboard_links_pass_through_the_linkfor_allowlist(): void
    {
        $user = User::factory()->create();

        Notification::factory()->create([
            'user_id' => $user->id,
            'type' => NotificationService::TYPE_LEARNING_REMINDER,
            'title' => 'STALL WARNING',
            'data' => NotificationService::payload('learning-path'),
            'created_at' => now()->subMinutes(1),
        ]);
        // A row corrupted at the database layer to point at a destination its
        // own type does not allow must render as a plain title — the dashboard
        // uses the same linkFor() allowlist as the center, never raw data.
        Notification::factory()->create([
            'user_id' => $user->id,
            'type' => NotificationService::TYPE_SYSTEM_ANNOUNCEMENT,
            'title' => 'TAMPERED ROW',
            'data' => NotificationService::payload('mission.show', [1]),
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee(route('learning-path'))
            ->assertSee('TAMPERED ROW')
            ->assertDontSee(route('mission.show', 1));
    }

    public function test_dashboard_panel_is_a_teaser_not_the_feed(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            Notification::factory()->create([
                'user_id' => $user->id,
                'type' => NotificationService::TYPE_SYSTEM_ANNOUNCEMENT,
                'title' => "BROADCAST {$i}",
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $content = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $panel = substr($content, strpos($content, 'Incoming notifications'));

        $this->assertStringContainsString('BROADCAST 1', $panel);
        $this->assertStringContainsString('BROADCAST 2', $panel);
        $this->assertStringNotContainsString('BROADCAST 3', $panel);

        // No feed affordances leak onto the dashboard: no per-row actions, no
        // mark-all form, no pagination footer.
        $this->assertStringNotContainsString('MARK READ', $content);
        $this->assertStringNotContainsString('MARK ALL AS READ', $content);
        $this->assertStringNotContainsString('SHOWING PAGE', $content);
    }

    public function test_event_feedback_alone_keeps_the_panel_body_empty(): void
    {
        $user = User::factory()->create();

        Notification::factory()->create([
            'user_id' => $user->id,
            'type' => NotificationService::TYPE_MISSION_COMPLETED,
            'title' => 'WELL DONE',
            'created_at' => now(),
        ]);

        $content = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringNotContainsString('WELL DONE', $content);
        $this->assertStringContainsString('No priority notifications.', $content);
    }

    public function test_teachers_see_the_panel_scoped_to_their_own_rows(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $other = User::factory()->create();

        Notification::factory()->create([
            'user_id' => $teacher->id,
            'type' => NotificationService::TYPE_TEACHER_ATTENTION,
            'title' => 'STUDENT UNATTENDED',
            'created_at' => now(),
        ]);
        Notification::factory()->create([
            'user_id' => $other->id,
            'type' => NotificationService::TYPE_SYSTEM_ANNOUNCEMENT,
            'title' => 'OTHER USERS INBOX',
            'created_at' => now()->subMinutes(1),
        ]);

        $panel = $this->panel($this->actingAs($teacher)->get(route('dashboard')));

        $this->assertStringContainsString('1 unread', $panel);
        $this->assertStringContainsString('STUDENT UNATTENDED', $panel);
        $this->assertStringNotContainsString('OTHER USERS INBOX', $panel);
    }

    /**
     * The dashboard panel slice: everything from the panel title onward, so
     * priority-row assertions cannot collide with unrelated page tokens.
     */
    public function test_dashboard_rejects_user_scoping_probes(): void
    {
        $user = User::factory()->create();

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $param) {
            $this->actingAs($user)->get(route('dashboard').'?'.$param.'=1')->assertForbidden();
        }
    }

    private function panel(TestResponse $response): string
    {
        $content = $response->getContent();

        return substr($content, strpos($content, 'Incoming notifications'));
    }
}
