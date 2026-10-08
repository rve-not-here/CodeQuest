<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\MissionService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MissionSubmissionReplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_request_replay_charges_once_but_distinct_deliberate_attempts_charge_separately(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['validate_rule' => json_encode([['type' => 'contains', 'value' => 'valid']])]);
        XpTransaction::query()->create(['user_id' => $student->id, 'mission_id' => $mission->id, 'type' => XpService::TYPE_MISSION_COMPLETED, 'amount' => 150]);
        $id = (string) Str::uuid();
        $payload = ['code' => 'wrong', 'submission_id' => $id];
        $this->actingAs($student)->post(route('mission.submit', $mission), $payload)->assertRedirect()->assertSessionHas('mission_error');
        $this->post(route('mission.submit', $mission), $payload)->assertRedirect()->assertSessionHas('mission_error');
        $this->assertSame(140, app(XpService::class)->balance($student));
        $this->post(route('mission.submit', $mission), ['code' => 'wrong', 'submission_id' => (string) Str::uuid()])->assertRedirect();
        $this->assertSame(130, app(XpService::class)->balance($student));
        $this->post(route('mission.submit', $mission), ['code' => 'changed', 'submission_id' => $id])->assertSessionHasErrors('submission_id');
        $this->assertSame(130, app(XpService::class)->balance($student));
    }

    public function test_legacy_identical_submission_does_not_duplicate_a_penalty(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['validate_rule' => json_encode([['type' => 'contains', 'value' => 'valid']])]);
        app(MissionService::class)->submit($student, $mission, 'wrong');
        app(MissionService::class)->submit($student, $mission, 'wrong');
        $this->assertSame(1, XpTransaction::query()->where('user_id', $student->id)->where('type', XpService::TYPE_WRONG_SUBMISSION)->count());
    }

    public function test_unavailable_grading_is_not_cached_as_a_student_failure(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['validate_rule' => 'invalid definition']);
        $id = (string) Str::uuid();
        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => 'wrong', 'submission_id' => $id])->assertRedirect();
        $this->assertDatabaseCount('the404_mission_submission_results', 0);
        $this->assertDatabaseMissing('the404_xp_transactions', ['user_id' => $student->id]);
    }

    public function test_replayed_old_failure_does_not_override_authoritative_completion(): void
    {
        $this->seed(AchievementSeeder::class);
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['points' => 50, 'validate_rule' => json_encode([['type' => 'contains', 'value' => 'valid']])]);
        $id = (string) Str::uuid();
        app(MissionService::class)->submit($student, $mission, 'wrong', $id);
        app(MissionService::class)->submit($student, $mission, 'valid', (string) Str::uuid());
        $result = app(MissionService::class)->submit($student, $mission, 'wrong', $id);
        $this->assertTrue($result['alreadyCompleted']);
        $this->assertTrue($result['passed']);
        $this->assertSame(50, app(XpService::class)->balance($student));
    }

    public function test_missing_replay_schema_refuses_submission_without_partial_academic_writes(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['validate_rule' => json_encode([['type' => 'contains', 'value' => 'valid']])]);
        Schema::drop('the404_mission_submission_results');
        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => 'valid'])
            ->assertRedirect()->assertSessionHas('mission_error', fn (array $error): bool => str_contains($error['message'], 'temporarily unavailable'));
        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id]);
        $this->assertDatabaseMissing('the404_xp_transactions', ['user_id' => $student->id]);
    }
}
