<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\User;
use App\Services\DraftService;
use App\Services\MissionService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MissionMutationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_revoked_at_submission_boundary_cannot_award_progress(): void
    {
        $this->seed(AchievementSeeder::class);
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['validate_rule' => json_encode([['type' => 'contains', 'value' => 'valid']])]);
        $changed = false;
        DB::listen(function (QueryExecuted $query) use ($mission, &$changed): void {
            if (! $changed && str_starts_with(strtolower($query->sql), 'select') && str_contains($query->sql, 'the404_users')) {
                $changed = true;
                Course::query()->whereKey($mission->course_id)->update(['status' => 'locked']);
            }
        });

        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => 'valid'])->assertForbidden();
        $this->assertTrue($changed);
        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id]);
        $this->assertDatabaseMissing('the404_xp_transactions', ['user_id' => $student->id]);
    }

    public function test_completed_mission_rejects_a_delayed_draft_without_recreating_it(): void
    {
        $this->seed(AchievementSeeder::class);
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['validate_rule' => json_encode([['type' => 'contains', 'value' => 'valid']])]);
        app(DraftService::class)->save($student, $mission, 'work in progress');
        app(MissionService::class)->submit($student, $mission, 'valid');

        $this->actingAs($student)->post(route('mission.draft', $mission), ['code' => 'stale draft'])->assertSessionHasErrors('code');
        $this->assertDatabaseHas('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertSame(0, MissionDraft::query()->where('user_id', $student->id)->count());
    }

    public function test_course_revoked_at_draft_boundary_cannot_save_code(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create();
        $changed = false;
        DB::listen(function (QueryExecuted $query) use ($mission, &$changed): void {
            if (! $changed && str_starts_with(strtolower($query->sql), 'select') && str_contains($query->sql, 'the404_users')) {
                $changed = true;
                Course::query()->whereKey($mission->course_id)->update(['status' => 'locked']);
            }
        });
        $this->actingAs($student)->post(route('mission.draft', $mission), ['code' => 'stale work'])->assertForbidden();
        $this->assertTrue($changed);
        $this->assertDatabaseMissing('the404_mission_drafts', ['user_id' => $student->id]);
    }
}
