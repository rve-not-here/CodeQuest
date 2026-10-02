<?php

namespace Tests\Unit;

use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\Progress;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\MissionService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private MissionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);

        $this->service = app(MissionService::class);
    }

    public function test_submit_passes_and_awards_xp_and_creates_progress(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 50,
        ]);

        $result = $this->service->submit($user, $mission, '<h1>Title</h1>');

        $this->assertTrue($result['passed']);
        $this->assertFalse($result['alreadyCompleted']);
        $this->assertSame([], $result['failures']);
        $this->assertSame(50, $result['xpAwarded']);
        $this->assertSame(50, $result['xpBalance']);
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 50,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => 50,
            'type' => 'mission_completed',
        ]);
    }

    public function test_submit_passes_clears_draft(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'anything']]),
            'points' => 30,
        ]);

        MissionDraft::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'code' => 'some code',
        ]);

        $this->service->submit($user, $mission, 'anything');

        $this->assertDatabaseMissing('the404_mission_drafts', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);
    }

    public function test_submit_passes_logs_activity(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['validate_rule' => json_encode([['type' => 'contains', 'value' => 'code']]), 'points' => 30]);

        $this->service->submit($user, $mission, 'code');

        $this->assertDatabaseHas('the404_activity', [
            'user_id' => $user->id,
            'type' => 'mission_completed',
        ]);
    }

    public function test_submit_fails_and_deducts_xp(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 50,
        ]);

        $result = $this->service->submit($user, $mission, '<h2>Wrong</h2>');

        $this->assertFalse($result['passed']);
        $this->assertFalse($result['alreadyCompleted']);
        $this->assertCount(1, $result['failures']);
        $this->assertSame(0, $result['xpAwarded']);
        $this->assertSame(0, $result['xpBalance']);
        $this->assertDatabaseMissing('the404_progress', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'type' => 'wrong_submission',
        ]);
    }

    public function test_submit_fails_logs_activity(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 50,
        ]);

        $this->service->submit($user, $mission, '<h2>Wrong</h2>');

        $this->assertDatabaseHas('the404_activity', [
            'user_id' => $user->id,
            'type' => 'wrong_submission',
        ]);
    }

    public function test_submit_already_completed_returns_without_re_award(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['validate_rule' => null, 'points' => 50]);

        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 50,
            'completed_at' => now(),
        ]);

        $result = $this->service->submit($user, $mission, 'anything');

        $this->assertTrue($result['passed']);
        $this->assertTrue($result['alreadyCompleted']);
        $this->assertSame(0, $result['xpAwarded']);
        $this->assertSame(0, XpTransaction::query()->where('user_id', $user->id)->count());
    }

    public function test_is_completed_returns_true_when_progress_exists(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => now(),
        ]);

        $this->assertTrue($this->service->isCompleted($user, $mission));
    }

    public function test_is_completed_returns_false_when_no_progress(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $this->assertFalse($this->service->isCompleted($user, $mission));
    }

    public function test_blank_validate_rule_is_unavailable_without_award(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create(['validate_rule' => null, 'points' => 10]);

        $result = $this->service->submit($user, $mission, '');

        $this->assertFalse($result['passed']);
        $this->assertSame(0, $result['xpAwarded']);
        $this->assertDatabaseCount('the404_progress', 0);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }
}
