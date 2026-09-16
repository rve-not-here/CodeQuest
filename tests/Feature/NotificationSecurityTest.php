<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Notification;
use App\Models\Section;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * US-811 (Notification Privacy and Security, §42): fleet-wide verification
 * that no notification or announcement path lets a client influence delivery
 * (axis 3), that notification content and the render surfaces never leak a
 * password hash or a solution code (axis 4), and that every write route
 * carries the web group's CSRF protection with no configured exceptions
 * (axis 6). The IDOR guard family for the dashboard teaser and the center's
 * oracle-proof reads are locked by StudentDashboardNotificationsTest and
 * NotificationAuthorizationTest; linkFor() allowlist re-application at both
 * render surfaces is locked by their suite tests.
 */
class NotificationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    // ── Axis 3: recipient tampering ──────────────────────────────────

    public function test_announcement_store_ignores_client_recipient_and_status_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $craftedTarget = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Privilege check',
            'message' => 'A crafted payload must not steer delivery.',
            'audience' => 'all',
            'status' => 'published',
            'user_id' => $craftedTarget->id,
            'recipient' => $craftedTarget->id,
            'recipients' => [$craftedTarget->id],
            'target_user' => $craftedTarget->id,
        ]);

        $response->assertRedirect();

        $announcement = Announcement::query()->where('title', 'Privilege check')->firstOrFail();

        $this->assertSame('draft', $announcement->status);
        $this->assertSame('all', $announcement->audience);
        $this->assertSame($admin->id, $announcement->created_by);
        $this->assertDatabaseCount('the404_notifications', 0);
    }

    public function test_announcement_publish_delivers_to_the_server_audience_alone(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $craftedIntruder = User::factory()->create(['role' => 'student']);
        $operator = User::factory()->create(['role' => 'operator']);

        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => 'draft',
            'audience' => 'teachers',
            'title' => 'Incoming broadcast',
            'message' => 'Reach is server-determined.',
        ]);

        // The intruder — a student and an operator — tries to join the teacher
        // audience via crafted recipient keys on the publish route; the
        // server must ignore them and deliver only to the audience it derived.
        $response = $this->actingAs($admin)->post(route('admin.announcements.publish', $announcement), [
            'recipient' => $craftedIntruder->id,
            'recipients' => [$craftedIntruder->id, $operator->id, $teacher->id],
            'user_id' => $craftedIntruder->id,
        ]);

        $response->assertRedirect()->assertSessionHas('status');

        $recipientIds = Notification::query()
            ->where('type', NotificationService::TYPE_SYSTEM_ANNOUNCEMENT)
            ->pluck('user_id')
            ->all();

        // 'teachers' reaches only the teacher — the admin actor, the student
        // intruder, and the operator do not.
        $this->assertSame([$teacher->id], $recipientIds);
    }

    // ── Axis 4: §42 sensitive data ───────────────────────────────────

    public function test_notification_content_never_carries_a_password_hash_or_solution_code(): void
    {
        $user = User::factory()->create(['password' => Hash::make('PASSWORD_SENTINEL_811')]);
        $storedHash = $user->refresh()->password;

        $course = Course::factory()->create(['status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'solution_code' => 'SOLUTION_SENTINEL_811',
            'validate_rule' => json_encode([
                ['type' => 'contains', 'value' => '<h1>', 'label' => 'has an H1'],
            ]),
        ]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'passing_score' => 70,
            'grading_rule' => json_encode([
                ['type' => 'contains', 'value' => 'GRADING_SENTINEL_811', 'label' => 'grading sentinel'],
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => '<h1>Hello</h1>'])
            ->assertRedirect();

        $this->actingAs($user)->post(route('assessment.start', $assessment));
        $this->actingAs($user)
            ->post(route('assessment.submit', $assessment), ['code' => 'GRADING_SENTINEL_811'])
            ->assertRedirect();

        $sentinels = [$storedHash, 'PASSWORD_SENTINEL_811', 'SOLUTION_SENTINEL_811', 'GRADING_SENTINEL_811'];

        // The stored rows themselves never embed a secret: title/message/data
        // are server-authored strings, and no producer interpolates a hash or
        // a solution token.
        $rows = Notification::query()->where('user_id', $user->id)->get();

        foreach ($rows as $row) {
            foreach ($sentinels as $sentinel) {
                $this->assertStringNotContainsString($sentinel, $row->title, "title on {$row->type}");
                $this->assertStringNotContainsString($sentinel, $row->message, "message on {$row->type}");
                $this->assertStringNotContainsString($sentinel, json_encode($row->data), "data on {$row->type}");
            }
        }

        foreach ($sentinels as $sentinel) {
            $this->actingAs($user)->get(route('notifications'))
                ->assertOk()
                ->assertDontSee($sentinel);
        }

        foreach ($sentinels as $sentinel) {
            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee($sentinel);
        }
    }

    public function test_the_render_surfaces_never_display_the_data_payload(): void
    {
        $user = User::factory()->create();

        $dataSentinel = 'DATA_AUXILIARY_SENTINEL_811';

        Notification::factory()->create([
            'user_id' => $user->id,
            'type' => NotificationService::TYPE_TEACHER_ATTENTION,
            'title' => 'ALERT TITLE',
            'message' => 'Alert message.',
            'data' => NotificationService::payload('needs-attention', [], [
                'student' => $dataSentinel,
                'signals' => [$dataSentinel],
            ]),
        ]);

        foreach (['notifications', 'dashboard'] as $routeName) {
            $this->actingAs($user)->get(route($routeName))
                ->assertOk()
                ->assertDontSee($dataSentinel);
        }
    }

    // ── Axis 6: CSRF ─────────────────────────────────────────────────

    public function test_every_write_route_carries_web_group_csrf_with_no_exceptions(): void
    {
        $writeRoutes = [
            'notifications.read',
            'notifications.read-all',
            'admin.announcements.store',
            'admin.announcements.update',
            'admin.announcements.publish',
            'admin.announcements.archive',
        ];

        $webGroup = app(Middleware::class)->getMiddlewareGroups()['web'] ?? [];

        $this->assertContains(PreventRequestForgery::class, $webGroup, 'the web group must enable request-forgery protection');

        foreach ($writeRoutes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "route {$routeName} must exist");
            $this->assertContains('web', $route->gatherMiddleware(), "{$routeName} must ride the web group");
        }

        $this->assertNull(config('middleware.validate_csrf_tokens'), 'no CSRF exceptions may be configured');
        $this->assertNull(config('middleware.prevent_request_forgery'), 'no request-forgery exceptions may be configured');
    }
}
