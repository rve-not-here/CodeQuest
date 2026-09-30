<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\User;
use App\Services\AdminAuditService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_audit_trail_newest_first(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'trail_admin']);
        $older = $this->auditRow($admin, 'user.update', 'User updated: username → \'old\'', Carbon::parse('2026-09-01 09:00:00'));
        $newer = $this->auditRow($admin, 'user.role.change', 'Role changed: student → teacher', Carbon::parse('2026-09-02 09:00:00'));

        $response = $this->actingAs($admin)->get(route('admin.activity'));

        $response->assertOk()
            ->assertSee('Administrative Audit Trail')
            ->assertSee('trail_admin')
            ->assertSee('Role changed: student → teacher')
            ->assertSee('User updated: username → \'old\'');

        $this->assertGreaterThan(
            $older->created_at->timestamp,
            $newer->created_at->timestamp,
        );
    }

    public function test_admin_can_filter_the_audit_trail_by_actor(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'filter_admin']);
        $other = User::factory()->admin()->create(['username' => 'other_admin']);
        $this->auditRow($admin, 'user.create', 'User created: username → \'mine\', role → \'student\'', now()->subMinutes(5));
        $this->auditRow($other, 'user.create', 'User created: username → \'theirs\', role → \'student\'', now()->subMinutes(4));

        $response = $this->actingAs($admin)->get(route('admin.activity', ['actor' => $other->id]));

        $response->assertOk()
            ->assertSee('theirs')
            ->assertDontSee('mine');
    }

    public function test_admin_can_filter_the_audit_trail_by_action(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'action_filter_admin']);
        $this->auditRow($admin, 'user.create', 'User created: username → \'created_one\', role → \'student\'', now()->subMinutes(5));
        $this->auditRow($admin, 'course.update', 'Course updated: name → \'Updated Course\'', now()->subMinutes(4));

        $response = $this->actingAs($admin)->get(route('admin.activity', ['action' => 'user.create']));

        $response->assertOk()
            ->assertSee('created_one')
            ->assertDontSee('Updated Course');
    }

    public function test_admin_can_filter_the_audit_trail_by_result(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'result_filter_admin']);
        $this->auditRow($admin, 'user.create', 'User created: username → \'succeeded\', role → \'student\'', now()->subMinutes(5), 'success');
        $this->auditRow($admin, 'user.role.change', "Refused: Unknown role 'archived'.", now()->subMinutes(4), 'failed');

        $response = $this->actingAs($admin)->get(route('admin.activity', ['result' => 'failed']));

        $response->assertOk()
            ->assertSee('Refused')
            ->assertDontSee('succeeded');
    }

    public function test_admin_can_filter_the_audit_trail_by_date_window(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'window_filter_admin']);
        $this->auditRow($admin, 'user.create', 'User created: username → \'in_window\', role → \'student\'', Carbon::parse('2026-09-02 12:00:00'));
        $this->auditRow($admin, 'user.create', 'User created: username → \'out_of_window\', role → \'student\'', Carbon::parse('2026-08-01 12:00:00'));

        $response = $this->actingAs($admin)->get(route('admin.activity', [
            'from' => '2026-09-01',
            'to' => '2026-09-30',
        ]));

        $response->assertOk()
            ->assertSee('in_window')
            ->assertDontSee('out_of_window');
    }

    public function test_rejects_an_inverted_date_window(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.activity', ['from' => '2026-09-30', 'to' => '2026-09-01']))
            ->assertSessionHasErrors('from');
    }

    public function test_rejects_unknown_filter_values(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.activity', ['actor' => 'not-an-id']))
            ->assertSessionHasErrors('actor');

        $this->actingAs($admin)
            ->get(route('admin.activity', ['action' => 'user.explode']))
            ->assertSessionHasErrors('action');

        $this->actingAs($admin)
            ->get(route('admin.activity', ['result' => 'pending']))
            ->assertSessionHasErrors('result');
    }

    public function test_rejects_actor_ids_that_do_not_exist(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.activity', ['actor' => 99999]))
            ->assertSessionHasErrors('actor');
    }

    public function test_rejects_user_scoping_probe_parameters_with_403(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['user_id', 'userId', 'user', 'owner'] as $probe) {
            $this->actingAs($admin)
                ->get(route('admin.activity', [$probe => $admin->id]))
                ->assertForbidden();
        }
    }

    public function test_audit_trail_paginates_fifty_rows(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'paging_admin']);
        $created = Carbon::parse('2026-09-01 12:00:00')->subMinutes(60);
        foreach (range(1, 50) as $i) {
            $this->auditRow($admin, 'user.update', "User updated: name → 'Paged {$i}'", $created->addMinute());
        }

        $this->actingAs($admin)->get(route('admin.activity'))
            ->assertOk()
            ->assertSee('SHOWING PAGE 1 OF 2')
            ->assertSee("name → 'Paged 50'")
            ->assertDontSee("name → 'Paged 1'");

        $this->actingAs($admin)->get(route('admin.activity', ['page' => 2]))
            ->assertOk()
            ->assertSee('SHOWING PAGE 2 OF 2')
            ->assertSee("name → 'Paged 1'")
            ->assertDontSee("name → 'Paged 50'");
    }

    public function test_audit_feed_bounds_the_database_row_read(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (range(1, 125) as $index) {
            $this->auditRow($admin, 'user.update', "Audit {$index}", now()->subMinutes($index));
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $page = app(AdminAuditService::class)->feed(null, null, null, null, null, []);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $rowQuery = collect($queries)
            ->pluck('query')
            ->first(fn (string $sql): bool => str_contains($sql, 'the404_admin_audit') && str_contains($sql, 'order by'));

        $this->assertNotNull($rowQuery);
        $this->assertMatchesRegularExpression('/\blimit\b/i', $rowQuery);
        $this->assertCount(AdminAuditService::FEED_PER_PAGE, $page->items());
        $this->assertSame(125, $page->total());
    }

    public function test_audit_trail_shows_an_empty_state_when_no_events_match(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.activity'))
            ->assertOk()
            ->assertSee('NO EVENTS');
    }

    private function auditRow(User $admin, string $action, string $summary, Carbon $at, string $result = 'success'): AdminAudit
    {
        $row = (new AdminAudit)->forceFill([
            'admin_user_id' => $admin->id,
            'admin_username' => $admin->username,
            'action' => $action,
            'target_type' => 'user',
            'target_id' => 1,
            'summary' => $summary,
            'result' => $result,
            'created_at' => $at,
        ]);
        $row->save();

        return $row;
    }
}
