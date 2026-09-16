<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // ── Schema / migration tests ──────────────────────────────────────

    public function test_the404_notifications_table_has_the_approved_columns_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('the404_notifications'));

        $columns = Schema::getColumns('the404_notifications');
        $columnNames = array_column($columns, 'name');
        sort($columnNames);

        $this->assertSame([
            'created_at', 'data', 'dedupe_key', 'id', 'message',
            'read_at', 'title', 'type', 'user_id',
        ], $columnNames);

        $indexes = collect(Schema::getIndexes('the404_notifications'))
            ->pluck('name')
            ->all();
        $this->assertContains('the404_notifications_user_id_dedupe_key_unique', $indexes);
        $this->assertContains('the404_notifications_user_id_read_at_index', $indexes);
        $this->assertContains('the404_notifications_user_id_created_at_index', $indexes);
    }

    public function test_the404_announcements_table_has_the_approved_columns_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('the404_announcements'));

        $columns = Schema::getColumns('the404_announcements');
        $columnNames = array_column($columns, 'name');
        sort($columnNames);

        $this->assertSame([
            'audience', 'created_at', 'created_by', 'id', 'message',
            'published_at', 'status', 'title', 'updated_at',
        ], $columnNames);

        $indexes = collect(Schema::getIndexes('the404_announcements'))
            ->pluck('name')
            ->all();
        $this->assertContains('the404_announcements_status_index', $indexes);
        $this->assertContains('the404_announcements_audience_index', $indexes);
        $this->assertContains('the404_announcements_created_at_index', $indexes);
    }

    public function test_notification_user_id_is_restricted_on_delete(): void
    {
        $user = User::factory()->create();
        Notification::factory()->create(['user_id' => $user->id]);

        $this->expectException(QueryException::class);
        $user->delete();
    }

    public function test_notification_unique_dedupe_key_allows_null_duplicates(): void
    {
        $user = User::factory()->create();

        Notification::factory()->create([
            'user_id' => $user->id,
            'dedupe_key' => null,
        ]);

        Notification::factory()->create([
            'user_id' => $user->id,
            'dedupe_key' => null,
        ]);

        $this->assertDatabaseCount('the404_notifications', 2);
    }

    public function test_notification_unique_dedupe_key_rejects_duplicates(): void
    {
        $user = User::factory()->create();

        Notification::factory()->create([
            'user_id' => $user->id,
            'dedupe_key' => 'mission_completed:1',
        ]);

        $this->expectException(QueryException::class);
        Notification::factory()->create([
            'user_id' => $user->id,
            'dedupe_key' => 'mission_completed:1',
        ]);
    }

    // ── Route authorization ───────────────────────────────────────────

    public function test_guests_are_redirected_to_login_when_requesting_notification_center(): void
    {
        $this->get(route('notifications'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_access_the_notification_center(): void
    {
        foreach (['student', 'teacher', 'admin', 'operator'] as $role) {
            $user = ($role === 'teacher')
                ? User::factory()->teacher()->create()
                : User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('notifications'))->assertOk();
        }
    }

    public function test_notification_center_rejects_user_scoping_parameters(): void
    {
        $user = User::factory()->create();

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $param) {
            $this->actingAs($user)
                ->get(route('notifications', [$param => 999]))
                ->assertForbidden();
        }
    }

    // ── Ownership isolation ───────────────────────────────────────────

    public function test_users_cannot_see_another_users_notifications(): void
    {
        $owner = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);

        Notification::factory()->create([
            'user_id' => $owner->id,
            'title' => 'OWNER ONLY',
        ]);

        Notification::factory()->create([
            'user_id' => $other->id,
            'title' => 'OTHER ONLY',
        ]);

        $response = $this->actingAs($owner)->get(route('notifications'));
        $response->assertOk();
        $response->assertSee('OWNER ONLY');
        $response->assertDontSee('OTHER ONLY');
    }

    public function test_role_specific_notifications_are_protected_by_ownership(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->teacher()->create();

        Notification::factory()->create([
            'user_id' => $teacher->id,
            'type' => 'teacher_attention',
            'title' => 'Teacher attention alert',
        ]);

        Notification::factory()->create([
            'user_id' => $student->id,
            'type' => 'mission_completed',
            'title' => 'Your mission is done',
        ]);

        $studentResponse = $this->actingAs($student)->get(route('notifications'));
        $studentResponse->assertOk();
        $studentResponse->assertSee('Your mission is done');
        $studentResponse->assertDontSee('Teacher attention alert');

        $teacherResponse = $this->actingAs($teacher)->get(route('notifications'));
        $teacherResponse->assertOk();
        $teacherResponse->assertSee('Teacher attention alert');
        $teacherResponse->assertDontSee('Your mission is done');
    }

    public function test_users_cannot_retrieve_another_users_notification_by_id(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $notification = Notification::factory()->create([
            'user_id' => $other->id,
            'title' => 'SECRET',
        ]);

        $service = app(NotificationService::class);

        // The service refuses foreign access.
        $this->assertFalse($service->belongsTo($owner, $notification));
        $this->assertNull($service->findForUser($owner, $notification->id));

        // Ownership confirmed.
        $this->assertTrue($service->belongsTo($other, $notification));
        $this->assertNotNull($service->findForUser($other, $notification->id));
    }

    public function test_find_for_user_returns_null_for_nonexistent_notification(): void
    {
        $user = User::factory()->create();

        $service = app(NotificationService::class);
        $this->assertNull($service->findForUser($user, 99999));
    }

    public function test_for_user_returns_only_owned_rows_ordered_newest_first(): void
    {
        $owner = User::factory()->create();

        Notification::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Newest',
            'created_at' => now()->subHour(),
        ]);

        Notification::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Middle',
            'created_at' => now()->subDay(),
        ]);

        Notification::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Oldest',
            'created_at' => now()->subDays(5),
        ]);

        $other = User::factory()->create();
        Notification::factory()->create([
            'user_id' => $other->id,
            'title' => 'Alien',
            'created_at' => now(),
        ]);

        $service = app(NotificationService::class);
        $results = $service->forUser($owner);

        $this->assertCount(3, $results);
        $this->assertSame(
            ['Newest', 'Middle', 'Oldest'],
            $results->pluck('title')->all(),
        );
        $this->assertEmpty($results->where('title', 'Alien'));
    }
}
