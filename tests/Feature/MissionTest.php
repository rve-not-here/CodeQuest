<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    private function createMissionWithCourse(array $missionAttrs = [], array $courseAttrs = []): array
    {
        $course = Course::factory()->create($courseAttrs);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(array_merge([
            'course_id' => $course->id,
            'section_id' => $section->id,
        ], $missionAttrs));

        return compact('course', 'section', 'mission');
    }

    public function test_mission_show_requires_authentication(): void
    {
        $mission = Mission::factory()->create();

        $this->get(route('mission.show', $mission))->assertRedirect(route('login'));
    }

    public function test_mission_show_renders_lesson_and_challenge_renders_workspace(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse();

        // mission.show is the teaching Lesson: concept, XP reward, START CHALLENGE,
        // and no authoritative coding workspace (editor, RUN, SUBMIT, SAVE DRAFT).
        $this->actingAs($user)
            ->get(route('mission.show', $mission))
            ->assertOk()
            ->assertSee($mission->title)
            ->assertSee('+'.$mission->points.' XP')
            ->assertSee('START CHALLENGE')
            ->assertDontSee('SUBMIT')
            ->assertDontSee('SAVE DRAFT');

        // mission.challenge is the focused Coding Challenge Workspace.
        $this->actingAs($user)
            ->get(route('mission.challenge', $mission))
            ->assertOk()
            ->assertSee($mission->title)
            ->assertSee('BACK TO LESSON')
            ->assertSee('SUBMIT')
            ->assertSee('RUN')
            ->assertSee('SAVE DRAFT')
            ->assertSee('Assistance')
            ->assertSee('SHOW SOLUTION')
            ->assertSee('Draft:')
            ->assertDontSee('NEXT MISSION')
            ->assertDontSee('NEW DRAFT')
            ->assertDontSee('data-open', false);
    }

    public function test_challenge_preview_grants_scripts_only_for_javascript_courses(): void
    {
        $student = User::factory()->create();

        foreach (['html', 'css'] as $type) {
            ['mission' => $mission] = $this->createMissionWithCourse([], ['type' => $type, 'order_num' => 1]);

            $this->actingAs($student)
                ->get(route('mission.challenge', $mission))
                ->assertOk()
                ->assertSee('sandbox=""', false)
                ->assertDontSee('sandbox="allow-scripts"', false);
        }

        ['mission' => $javascriptMission] = $this->createMissionWithCourse([], ['type' => 'js', 'order_num' => 1]);

        $this->actingAs($student)
            ->get(route('mission.challenge', $javascriptMission))
            ->assertOk()
            ->assertSee('sandbox="allow-scripts"', false);
    }

    public function test_mission_submit_requires_authentication(): void
    {
        $mission = Mission::factory()->create();

        $this->post(route('mission.submit', $mission), ['code' => 'test'])
            ->assertRedirect(route('login'));
    }

    public function test_mission_submit_requires_code_field(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $this->actingAs($user)
            ->post(route('mission.submit', $mission), [])
            ->assertSessionHasErrors('code');
    }

    public function test_mission_submit_correct_code_awards_xp_and_creates_progress(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 50,
        ]);

        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => '<h1>Title</h1>'])
            ->assertRedirect();

        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => 50,
        ]);
    }

    public function test_student_cannot_submit_a_later_course_mission_before_the_current_course_is_complete(): void
    {
        $student = User::factory()->create();
        $this->createMissionWithCourse([], ['order_num' => 1]);
        ['mission' => $laterMission] = $this->createMissionWithCourse([
            'validate_rule' => null,
            'points' => 50,
        ], ['order_num' => 2]);

        $this->actingAs($student)
            ->post(route('mission.submit', $laterMission), [
                'code' => 'valid code',
                'completed' => true,
                'xp' => 999999,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $laterMission->id,
        ]);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    public function test_student_can_submit_a_later_course_mission_after_passing_the_earlier_boss_challenge(): void
    {
        $student = User::factory()->create();
        ['course' => $earlierCourse] = $this->createMissionWithCourse([], ['order_num' => 1]);
        $earlierAssessment = Assessment::factory()->create(['course_id' => $earlierCourse->id]);
        AssessmentAttempt::factory()->create([
            'assessment_id' => $earlierAssessment->id,
            'user_id' => $student->id,
            'status' => 'passed',
            'score' => 100,
            'passed_at' => now(),
        ]);
        ['mission' => $laterMission] = $this->createMissionWithCourse([
            'validate_rule' => null,
            'points' => 50,
        ], ['order_num' => 2]);

        $this->actingAs($student)
            ->post(route('mission.submit', $laterMission), ['code' => 'valid code'])
            ->assertSessionHas('mission_success');

        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $laterMission->id,
        ]);
        $this->assertSame(50, app(XpService::class)->balance($student));
    }

    public function test_submission_ignores_client_supplied_ownership_and_reward_fields(): void
    {
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 50,
        ]);

        $this->actingAs($student)->post(route('mission.submit', $mission), [
            'code' => '<h1>Title</h1>',
            'user_id' => $otherStudent->id,
            'student_id' => $otherStudent->id,
            'xp' => 999999,
            'points' => 999999,
            'score' => 100,
            'completed_at' => '2020-01-01 00:00:00',
        ])->assertRedirect();

        $progress = Progress::query()->sole();
        $transaction = XpTransaction::query()->where('type', 'mission_completed')->sole();
        $this->assertSame($student->id, $progress->user_id);
        $this->assertSame(50, $progress->pts_earned);
        $this->assertNotEquals('2020-01-01 00:00:00', $progress->completed_at->toDateTimeString());
        $this->assertSame($student->id, $transaction->user_id);
        $this->assertSame(50, $transaction->amount);
        $this->assertDatabaseMissing('the404_progress', ['user_id' => $otherStudent->id]);
    }

    public function test_mission_submit_correct_code_shows_success_message(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'validate_rule' => null,
            'points' => 30,
        ]);

        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => 'anything'])
            ->assertRedirect()
            ->assertSessionHas('mission_success');
    }

    public function test_new_authoritative_pass_renders_completion_dialog_with_server_results(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'mission' => $mission] = $this->createMissionWithCourse([
            'validate_rule' => null,
            'points' => 30,
        ]);
        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $mission->section_id,
            'order_num' => $mission->order_num + 1,
        ]);

        $this->actingAs($user)
            ->from(route('mission.challenge', $mission))
            ->followingRedirects()
            ->post(route('mission.submit', $mission), ['code' => 'anything'])
            ->assertOk()
            ->assertSee('class="completion-overlay"', false)
            ->assertSee('Challenge complete')
            ->assertSee('Validation')
            ->assertSee('Passed')
            ->assertSee('+30')
            ->assertSee('0% → 50%')
            ->assertSee('Achievement unlocked')
            ->assertSee('First Challenge')
            ->assertSee('CONTINUE TO LEARNING PATH')
            ->assertSee('href="'.route('learning-path').'"', false)
            ->assertDontSee('NEXT MISSION');
    }

    public function test_failed_submission_keeps_the_student_in_the_workspace_without_completion_controls(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
        ]);

        $this->actingAs($user)
            ->from(route('mission.challenge', $mission))
            ->followingRedirects()
            ->post(route('mission.submit', $mission), ['code' => '<p>Not valid</p>'])
            ->assertOk()
            ->assertSee('Mission not restored')
            ->assertSee('Code editor')
            ->assertDontSee('class="completion-overlay"', false)
            ->assertDontSee('CONTINUE TO LEARNING PATH')
            ->assertDontSee('NEXT MISSION');
    }

    public function test_mission_submit_wrong_code_deducts_xp(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 50,
        ]);

        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => '<h2>Wrong</h2>'])
            ->assertRedirect();

        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'type' => 'wrong_submission',
        ]);
        $this->assertDatabaseMissing('the404_progress', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);
    }

    public function test_mission_submit_wrong_code_shows_error_message(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 50,
        ]);

        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => '<h2>Wrong</h2>'])
            ->assertRedirect()
            ->assertSessionHas('mission_error');
    }

    public function test_mission_submit_already_completed_returns_info(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse(['validate_rule' => null, 'points' => 30]);

        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 30,
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => 'anything'])
            ->assertRedirect()
            ->assertSessionHas('mission_info');
    }

    public function test_mission_view_does_not_expose_solution_code(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'solution_code' => '<h1>SECRET_SOLUTION</h1>',
            'validate_rule' => 'some_rule',
        ]);

        $this->actingAs($user)
            ->get(route('mission.show', $mission))
            ->assertOk()
            ->assertDontSee('SECRET_SOLUTION');
    }

    public function test_mission_view_does_not_expose_validate_rule(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'SECRET_RULE']]),
        ]);

        $this->actingAs($user)
            ->get(route('mission.show', $mission))
            ->assertOk()
            ->assertDontSee('SECRET_RULE');
    }

    public function test_mission_draft_requires_authentication(): void
    {
        $mission = Mission::factory()->create();

        $this->post(route('mission.draft', $mission), ['code' => 'test'])
            ->assertRedirect(route('login'));
    }

    public function test_mission_draft_saves_code(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse();

        $this->actingAs($user)
            ->post(route('mission.draft', $mission), ['code' => '<h1>Saved</h1>'])
            ->assertRedirect()
            ->assertSessionHas('draft_saved');

        $this->assertDatabaseHas('the404_mission_drafts', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'code' => '<h1>Saved</h1>',
        ]);
    }

    public function test_mission_draft_restores_code_on_next_load(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse();

        MissionDraft::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'code' => '<h1>Restored</h1>',
        ]);

        $this->actingAs($user)
            ->get(route('mission.challenge', $mission))
            ->assertOk()
            ->assertSee('Restored');
    }

    public function test_mission_hint_requires_authentication(): void
    {
        $mission = Mission::factory()->create();

        $this->post(route('mission.hint', $mission))
            ->assertRedirect(route('login'));
    }

    public function test_mission_hint_when_all_revealed_returns_flat(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'hints' => json_encode(['Try this', 'Try that', 'Try this other thing']),
            'points' => 50,
        ]);

        XpTransaction::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => 30,
            'type' => 'mission_completed',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            XpTransaction::query()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'amount' => -$i * 5,
                'type' => 'hint_used',
            ]);
        }

        $this->actingAs($user)
            ->post(route('mission.hint', $mission))
            ->assertRedirect()
            ->assertSessionHas('hint_flat');
    }

    public function test_mission_hint_deducts_xp_when_affordable(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'hints' => json_encode(['Try this', 'Try that']),
            'points' => 30,
        ]);

        XpTransaction::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => 30,
            'type' => 'mission_completed',
        ]);

        $this->actingAs($user)
            ->post(route('mission.hint', $mission))
            ->assertRedirect();

        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'type' => 'hint_used',
        ]);
    }

    public function test_mission_hint_when_unaffordable_returns_error(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'hints' => json_encode(['Try this']),
            'points' => 30,
        ]);

        $this->actingAs($user)
            ->post(route('mission.hint', $mission))
            ->assertRedirect()
            ->assertSessionHas('hint_error');
    }

    public function test_mission_reveal_requires_authentication(): void
    {
        $mission = Mission::factory()->create();

        $this->post(route('mission.reveal', $mission))
            ->assertRedirect(route('login'));
    }

    public function test_mission_reveal_when_unaffordable_returns_error(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'solution_code' => '<h1>Solution</h1>',
            'points' => 30,
        ]);

        $this->actingAs($user)
            ->post(route('mission.reveal', $mission))
            ->assertRedirect()
            ->assertSessionHas('reveal_error');
    }

    public function test_mission_reveal_when_affordable_shows_solution(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'solution_code' => '<h1>Solution</h1>',
            'points' => 50,
        ]);

        XpTransaction::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => 50,
            'type' => 'mission_completed',
        ]);

        $this->actingAs($user)
            ->post(route('mission.reveal', $mission))
            ->assertRedirect()
            ->assertSessionHas('solution_revealed');
    }

    public function test_replaying_solution_reveal_does_not_charge_xp_twice(): void
    {
        $student = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'solution_code' => '<h1>Solution</h1>',
        ]);

        XpTransaction::query()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'amount' => 100,
            'type' => 'mission_completed',
        ]);

        $this->actingAs($student)->post(route('mission.reveal', $mission))->assertSessionHas('solution_revealed');
        $this->actingAs($student)->post(route('mission.reveal', $mission))->assertSessionHas('solution_revealed');

        $this->assertSame(1, XpTransaction::query()
            ->where('user_id', $student->id)
            ->where('mission_id', $mission->id)
            ->where('type', 'solution_revealed')
            ->count());
        $this->assertSame(70, app(XpService::class)->balance($student));
    }

    public function test_mission_reveal_loads_solution_code_in_editor(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'solution_code' => '<h1>SOLUTION_LOADED</h1>',
            'points' => 50,
        ]);

        XpTransaction::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => 50,
            'type' => 'mission_completed',
        ]);

        XpTransaction::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => -30,
            'type' => 'solution_revealed',
        ]);

        $this->actingAs($user)
            ->get(route('mission.challenge', $mission))
            ->assertOk()
            ->assertSee('SOLUTION_LOADED');
    }

    public function test_mission_reveal_when_already_completed_returns_flat(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'solution_code' => '<h1>Solution</h1>',
            'points' => 30,
        ]);

        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 30,
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('mission.reveal', $mission))
            ->assertRedirect()
            ->assertSessionHas('reveal_flat');
    }

    public function test_mission_submit_correct_clears_draft(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse(['validate_rule' => null, 'points' => 30]);

        MissionDraft::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'code' => 'old code',
        ]);

        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => 'new code'])
            ->assertRedirect();

        $this->assertDatabaseMissing('the404_mission_drafts', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);
    }

    public function test_mission_submit_wrong_keeps_draft(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 30,
        ]);

        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => 'wrong code'])
            ->assertRedirect();

        $this->assertDatabaseMissing('the404_mission_drafts', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);
    }

    public function test_xp_floor_at_zero_on_wrong_submissions(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 50,
        ]);

        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => 'wrong'])
            ->assertRedirect();
        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => 'wrong again'])
            ->assertRedirect();

        $balance = XpTransaction::query()->where('user_id', $user->id)->sum('amount');
        $this->assertGreaterThanOrEqual(0, (int) $balance);
    }

    public function test_cannot_override_user_identity_in_draft(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        ['mission' => $mission] = $this->createMissionWithCourse();

        $this->actingAs($user1)
            ->post(route('mission.draft', $mission), ['code' => 'user1 code'])
            ->assertRedirect();

        $this->actingAs($user2)
            ->get(route('mission.challenge', $mission))
            ->assertOk()
            ->assertDontSee('user1 code');
    }
}
