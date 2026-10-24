<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Support\GameCopy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Knowledge Check panel in the lesson.
 *
 * The check belongs to the mission panel: it opens there, it is answered
 * there, and the panel never navigates the student off the lesson. The
 * challenge workspace carries no check — the adopted workspace keeps its
 * committed Code / Preview / Instructions shape.
 */
class KnowledgeCheckLessonPanelTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{course: Course, section: Section, mission: Mission, user: User} */
    private function curriculum(bool $required = true): array
    {
        $user = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'description' => "A short lesson.\n\nYour task: build the thing.",
        ]);

        return compact('course', 'section', 'mission', 'user');
    }

    /**
     * @return array{check: KnowledgeCheck, question: KnowledgeCheckQuestion, correct: KnowledgeCheckOption}
     */
    private function check(Mission $mission, bool $required = true): array
    {
        $check = KnowledgeCheck::factory()->create([
            'mission_id' => $mission->id,
            'order_num' => 1,
            'title' => 'Lesson Check',
            'status' => 'published',
            'is_required' => $required,
        ]);
        $question = KnowledgeCheckQuestion::factory()->create([
            'knowledge_check_id' => $check->id,
            'order_num' => 1,
            'type' => KnowledgeCheckQuestion::TYPE_MULTIPLE_CHOICE,
            'prompt' => 'Which element names the page region?',
            'explanation' => 'SERVER-ONLY EXPLANATION',
        ]);
        $correct = KnowledgeCheckOption::factory()->correct()->create([
            'knowledge_check_question_id' => $question->id,
            'order_num' => 1,
            'option_text' => 'SERVER-ONLY CORRECT OPTION',
        ]);
        KnowledgeCheckOption::factory()->create([
            'knowledge_check_question_id' => $question->id,
            'order_num' => 2,
            'option_text' => 'Distractor',
        ]);

        return compact('check', 'question', 'correct');
    }

    public function test_lesson_presents_the_check_panel_as_required(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();
        ['check' => $check] = $this->check($mission);

        $this->actingAs($user)->get(route('mission.show', $mission))
            ->assertOk()
            ->assertSee('Lesson Check')
            ->assertSee('Required')
            ->assertSee('Not started')
            ->assertSee('Start check')
            ->assertSee(route('mission.knowledge-check.start', [$mission, $check]), escape: false);
    }

    public function test_starting_the_check_opens_it_in_the_lesson_without_redirecting(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();
        ['check' => $check] = $this->check($mission);

        $response = $this->actingAs($user)
            ->post(route('mission.knowledge-check.start', [$mission, $check]));

        // The panel opens in place: the lesson is re-rendered, not replaced.
        $response->assertOk()
            ->assertSee('In progress')
            ->assertSee('Which element names the page region?')
            ->assertSee('Submit answers');

        $this->assertDatabaseHas('the404_knowledge_check_attempts', [
            'knowledge_check_id' => $check->id,
            'user_id' => $user->id,
            'status' => 'started',
        ]);
    }

    public function test_answers_submitted_in_the_panel_are_scored_without_redirecting(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();
        ['check' => $check, 'question' => $question, 'correct' => $correct] = $this->check($mission);

        $this->actingAs($user)->post(route('mission.knowledge-check.start', [$mission, $check]));
        $attempt = KnowledgeCheckAttempt::query()
            ->where('user_id', $user->id)
            ->where('knowledge_check_id', $check->id)
            ->sole();

        $response = $this->actingAs($user)->post(
            route('mission.knowledge-check.submit', [$mission, $check, $attempt]),
            ['answers' => [$question->id => $correct->id]],
        );

        $response->assertOk()
            ->assertSee('Completed')
            ->assertSee('100%')
            ->assertSee('Review result')
            ->assertSee('data-check-result-modal', escape: false)
            ->assertSee('data-auto-open="true"', escape: false)
            ->assertSee('Pass score: 70%')
            ->assertSee('Passed')
            ->assertSee('Proceed to Coding Challenge')
            ->assertSee('Start challenge')
            ->assertSee('Check passed. The coding challenge is open.')
            ->assertSee(GameCopy::checkBand(100, $check->id))
            ->assertSee('Correct')
            ->assertDontSee('Correct answer');

        $this->actingAs($user)
            ->get(route('mission.show', $mission))
            ->assertOk()
            ->assertDontSee('data-auto-open="true"', false);

        $this->assertSame('submitted', $attempt->fresh()->status);
        $this->assertSame(1, $attempt->fresh()->score);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
        $this->assertDatabaseCount('the404_progress', 0);
    }

    public function test_a_failed_result_dialog_explains_the_miss_and_locks_proceed(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();
        ['check' => $check, 'question' => $question] = $this->check($mission);
        $wrong = KnowledgeCheckOption::query()
            ->where('knowledge_check_question_id', $question->id)
            ->where('is_correct', false)
            ->sole();

        $this->actingAs($user)->post(route('mission.knowledge-check.start', [$mission, $check]));
        $attempt = KnowledgeCheckAttempt::query()
            ->where('user_id', $user->id)
            ->where('knowledge_check_id', $check->id)
            ->sole();

        $response = $this->actingAs($user)->post(
            route('mission.knowledge-check.submit', [$mission, $check, $attempt]),
            ['answers' => [$question->id => $wrong->id]],
        );

        $response->assertOk()
            ->assertSee('Not passed')
            ->assertSee('0%')
            ->assertSee('Retry Quiz')
            ->assertSee('Review Answers')
            ->assertSee('Score at least 70% to unlock the coding challenge.')
            ->assertSee('aria-disabled="true"', escape: false)
            ->assertSee('Wrong')
            ->assertSee('Correct answer')
            ->assertSee('SERVER-ONLY EXPLANATION')
            ->assertSee('Retry required check')
            ->assertSee('You scored below 70%. Retry the check to unlock the coding challenge.')
            ->assertDontSee('Start challenge');
    }

    public function test_the_panel_never_exposes_the_answer_key(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();
        ['check' => $check] = $this->check($mission);

        $this->actingAs($user)->post(route('mission.knowledge-check.start', [$mission, $check]));

        // The option texts are the student's own material, but nothing marks
        // which one is right: no correctness flag, no answer-key snapshot, no
        // explanation.
        $this->actingAs($user)->get(route('mission.show', $mission))
            ->assertOk()
            ->assertSee('Distractor')
            ->assertSee('SERVER-ONLY CORRECT OPTION')
            ->assertDontSee('SERVER-ONLY EXPLANATION')
            ->assertDontSee('is_correct', escape: false)
            ->assertDontSee('correct_option_snapshot', escape: false);
    }

    public function test_an_optional_check_is_labelled_as_such(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();
        $this->check($mission, required: false);

        $this->actingAs($user)->get(route('mission.show', $mission))
            ->assertOk()
            ->assertSee('Optional');
    }

    public function test_a_required_check_gates_the_challenge_until_it_is_answered(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();
        ['check' => $check, 'question' => $question, 'correct' => $correct] = $this->check($mission);

        // The challenge stays sealed while the required check is outstanding.
        $this->actingAs($user)
            ->get(route('mission.challenge', $mission))
            ->assertRedirect(route('mission.show', $mission));

        $this->actingAs($user)->post(route('mission.knowledge-check.start', [$mission, $check]));
        $attempt = KnowledgeCheckAttempt::query()
            ->where('user_id', $user->id)
            ->where('knowledge_check_id', $check->id)
            ->sole();

        $this->actingAs($user)->post(
            route('mission.knowledge-check.submit', [$mission, $check, $attempt]),
            ['answers' => [$question->id => $correct->id]],
        )->assertOk();

        $this->actingAs($user)
            ->get(route('mission.challenge', $mission))
            ->assertOk();
    }

    public function test_the_challenge_workspace_carries_no_check(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();
        // An optional check leaves the challenge open, so the workspace itself
        // is what this assertion looks at.
        $this->check($mission, required: false);

        $this->actingAs($user)->get(route('mission.challenge', $mission))
            ->assertOk()
            ->assertDontSee('data-pane-toggle="check"', escape: false)
            ->assertDontSee('id="workspace-check"', escape: false)
            ->assertDontSee('Lesson Check');
    }

    public function test_only_students_may_answer_the_panel(): void
    {
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check] = $this->check($mission);

        foreach ([User::factory()->teacher()->create(), User::factory()->admin()->create()] as $other) {
            $this->actingAs($other)
                ->post(route('mission.knowledge-check.start', [$mission, $check]))
                ->assertForbidden();
        }

        // Drop the session so the next request is a real guest.
        $this->app['auth']->forgetGuards();
        $this->post(route('mission.knowledge-check.start', [$mission, $check]))
            ->assertRedirect(route('login'));
    }

    public function test_a_check_from_another_mission_is_refused(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();
        ['mission' => $otherMission] = $this->curriculum();
        ['check' => $otherCheck] = $this->check($otherMission);

        $this->actingAs($user)
            ->post(route('mission.knowledge-check.start', [$mission, $otherCheck]))
            ->assertNotFound();

        $this->assertDatabaseCount('the404_knowledge_check_attempts', 0);
    }

    public function test_a_retry_opens_a_fresh_attempt_in_the_panel(): void
    {
        ['mission' => $mission, 'user' => $user] = $this->curriculum();
        ['check' => $check, 'question' => $question, 'correct' => $correct] = $this->check($mission);

        $this->actingAs($user)->post(route('mission.knowledge-check.start', [$mission, $check]));
        $attempt = KnowledgeCheckAttempt::query()
            ->where('user_id', $user->id)
            ->where('knowledge_check_id', $check->id)
            ->sole();

        $this->actingAs($user)->post(
            route('mission.knowledge-check.submit', [$mission, $check, $attempt]),
            ['answers' => [$question->id => $correct->id]],
        )->assertOk();

        $this->actingAs($user)
            ->post(route('mission.knowledge-check.retry', [$mission, $check]))
            ->assertOk()
            ->assertSee('In progress');

        $this->assertSame(2, KnowledgeCheckAttempt::query()
            ->where('user_id', $user->id)
            ->where('knowledge_check_id', $check->id)
            ->count());
    }
}
