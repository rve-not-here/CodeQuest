<?php

namespace Tests\Feature;

use App\Exceptions\KnowledgeCheckRequiredException;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\DraftService;
use App\Services\MissionService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0-3: the required Knowledge Check prerequisite belongs to the domain
 * boundary, not to MissionController. Every protected mutation refuses on a
 * direct service call, spends nothing, persists nothing, and preserves the
 * student's source on the HTTP path.
 */
class KnowledgeCheckGateTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{course: Course, section: Section, mission: Mission, user: User} */
    private function curriculum(bool $required = true): array
    {
        $user = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create(['status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'hints' => json_encode(['first hint']),
            'solution_code' => '<h1>solution</h1>',
        ]);

        KnowledgeCheck::factory()->create([
            'mission_id' => $mission->id,
            'order_num' => 1,
            'title' => 'Required Check',
            'is_required' => $required,
        ]);

        return compact('course', 'section', 'mission', 'user');
    }

    private function completeMission(User $user, Mission $mission): void
    {
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 0,
            'completed_at' => now(),
        ]);
    }

    public function test_submit_service_refuses_a_direct_call_while_the_check_is_outstanding(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();

        try {
            app(MissionService::class)->submit($user, $mission, 'my work');
            $this->fail('MissionService::submit must refuse while a required check is outstanding.');
        } catch (KnowledgeCheckRequiredException $exception) {
            $this->assertSame($mission->id, $exception->mission->id);
            $this->assertSame('Required Check', $exception->check->title);
        }

        $this->assertDatabaseCount('the404_progress', 0);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    public function test_draft_service_refuses_a_direct_call_and_persists_nothing(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();

        try {
            app(DraftService::class)->save($user, $mission, 'draft work');
            $this->fail('DraftService::save must refuse while a required check is outstanding.');
        } catch (KnowledgeCheckRequiredException) {
            // expected
        }

        $this->assertSame(0, MissionDraft::query()->count());
    }

    public function test_hint_and_solution_reveal_refuse_at_the_ledger_boundary(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();
        $xp = app(XpService::class);

        foreach ([
            fn () => $xp->spendHint($user, $mission, 1),
            fn () => $xp->spendSolutionReveal($user, $mission),
        ] as $spend) {
            try {
                $spend();
                $this->fail('XP spending must refuse while a required check is outstanding.');
            } catch (KnowledgeCheckRequiredException) {
                // expected
            }
        }

        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    public function test_http_submit_redirects_and_preserves_the_students_code(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();

        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => '<h1>unsaved work</h1>'])
            ->assertRedirect(route('mission.show', $mission))
            ->assertSessionHas('knowledge_check_info');

        $this->assertSame('<h1>unsaved work</h1>', session()->getOldInput('code'));
        $this->assertDatabaseCount('the404_progress', 0);
    }

    public function test_http_hint_reveal_and_draft_are_all_gated_and_preserve_code(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();

        foreach ([
            route('mission.hint', $mission),
            route('mission.reveal', $mission),
            route('mission.draft', $mission),
        ] as $url) {
            $this->actingAs($user)
                ->post($url, ['code' => 'kept code'])
                ->assertRedirect(route('mission.show', $mission))
                ->assertSessionHas('knowledge_check_info');

            $this->assertSame('kept code', session()->getOldInput('code'));
        }

        $this->assertDatabaseCount('the404_xp_transactions', 0);
        $this->assertSame(0, MissionDraft::query()->count());
    }

    public function test_a_completed_mission_is_exempt_from_the_prerequisite(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();
        $this->completeMission($user, $mission);

        $outcome = app(MissionService::class)->submit($user, $mission, 'any later work');

        $this->assertTrue($outcome['alreadyCompleted']);
    }

    public function test_an_optional_check_does_not_gate_draft_persistence(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum(required: false);

        $draft = app(DraftService::class)->save($user, $mission, 'optional work');

        $this->assertSame('optional work', $draft->code);
    }
}
