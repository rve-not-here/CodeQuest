<?php

namespace Tests\Unit;

use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\User;
use App\Services\DraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftServiceTest extends TestCase
{
    use RefreshDatabase;

    private DraftService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DraftService;
    }

    public function test_find_returns_null_when_no_draft_exists(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $this->assertNull($this->service->find($user, $mission));
    }

    public function test_save_creates_new_draft(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $draft = $this->service->save($user, $mission, '<h1>Hello</h1>');

        $this->assertSame('<h1>Hello</h1>', $draft->code);
        $this->assertSame($user->id, $draft->user_id);
        $this->assertSame($mission->id, $draft->mission_id);
        $this->assertDatabaseHas('the404_mission_drafts', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'code' => '<h1>Hello</h1>',
        ]);
    }

    public function test_save_updates_existing_draft(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $this->service->save($user, $mission, '<h1>First</h1>');
        $this->service->save($user, $mission, '<h1>Second</h1>');

        $draft = $this->service->find($user, $mission);
        $this->assertSame('<h1>Second</h1>', $draft->code);
        $this->assertSame(1, MissionDraft::query()
            ->where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->count());
    }

    public function test_delete_removes_draft(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $this->service->save($user, $mission, '<h1>Code</h1>');
        $this->service->delete($user, $mission);

        $this->assertNull($this->service->find($user, $mission));
        $this->assertDatabaseMissing('the404_mission_drafts', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);
    }

    public function test_drafts_are_isolated_per_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $mission = Mission::factory()->create();

        $this->service->save($user1, $mission, '<h1>User1</h1>');
        $this->service->save($user2, $mission, '<h1>User2</h1>');

        $this->assertSame('<h1>User1</h1>', $this->service->find($user1, $mission)->code);
        $this->assertSame('<h1>User2</h1>', $this->service->find($user2, $mission)->code);
    }

    public function test_drafts_are_isolated_per_mission(): void
    {
        $user = User::factory()->create();
        $mission1 = Mission::factory()->create();
        $mission2 = Mission::factory()->create();

        $this->service->save($user, $mission1, '<h1>Mission1</h1>');
        $this->service->save($user, $mission2, '<h1>Mission2</h1>');

        $this->assertSame('<h1>Mission1</h1>', $this->service->find($user, $mission1)->code);
        $this->assertSame('<h1>Mission2</h1>', $this->service->find($user, $mission2)->code);
    }

    public function test_delete_only_removes_own_draft(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $this->service->save($user, $mission, '<h1>Code</h1>');
        $this->service->delete(User::factory()->create(), $mission);

        $this->assertNotNull($this->service->find($user, $mission));
    }
}
