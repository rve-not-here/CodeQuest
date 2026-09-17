<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\KnowledgeCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeCheckTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{course: Course, section: Section, mission: Mission} */
    private function curriculum(): array
    {
        $course = Course::factory()->create(['status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
        ]);

        return compact('course', 'section', 'mission');
    }

    /**
     * @return array{
     *     check: KnowledgeCheck,
     *     questions: array<int, KnowledgeCheckQuestion>,
     *     correct: array<int, KnowledgeCheckOption>,
     *     wrong: array<int, KnowledgeCheckOption>
     * }
     */
    private function check(Mission $mission, bool $required = false, int $order = 1): array
    {
        $check = KnowledgeCheck::factory()->create([
            'mission_id' => $mission->id,
            'order_num' => $order,
            'title' => "Markup Concepts {$order}",
            'is_required' => $required,
        ]);
        $questions = [];
        $correct = [];
        $wrong = [];
        $types = [
            KnowledgeCheckQuestion::TYPE_MULTIPLE_CHOICE,
            KnowledgeCheckQuestion::TYPE_CODE_READING,
            KnowledgeCheckQuestion::TYPE_CONCEPT_IDENTIFICATION,
        ];

        foreach ($types as $index => $type) {
            $question = KnowledgeCheckQuestion::factory()->create([
                'knowledge_check_id' => $check->id,
                'order_num' => $index + 1,
                'type' => $type,
                'prompt' => 'Question prompt '.($index + 1),
                'code_snippet' => $type === KnowledgeCheckQuestion::TYPE_CODE_READING
                    ? '<h1>Signal</h1>'
                    : null,
                'explanation' => 'SERVER-ONLY EXPLANATION '.($index + 1),
            ]);
            $questions[$question->id] = $question;
            $wrong[$question->id] = KnowledgeCheckOption::factory()->create([
                'knowledge_check_question_id' => $question->id,
                'order_num' => 1,
                'option_text' => 'Distractor '.($index + 1),
            ]);
            $correct[$question->id] = KnowledgeCheckOption::factory()->correct()->create([
                'knowledge_check_question_id' => $question->id,
                'order_num' => 2,
                'option_text' => 'Correct concept '.($index + 1),
            ]);
        }

        return compact('check', 'questions', 'correct', 'wrong');
    }

    private function start(User $user, Mission $mission, KnowledgeCheck $check): KnowledgeCheckAttempt
    {
        $this->actingAs($user)
            ->post(route('knowledge-check.start', [$mission, $check]))
            ->assertRedirect();

        return KnowledgeCheckAttempt::query()
            ->where('user_id', $user->id)
            ->where('knowledge_check_id', $check->id)
            ->sole();
    }

    /**
     * @param  array<int, KnowledgeCheckOption>  $options
     * @return array<int, int>
     */
    private function answerIds(array $options): array
    {
        return collect($options)
            ->mapWithKeys(fn (KnowledgeCheckOption $option, int $questionId): array => [
                $questionId => $option->id,
            ])
            ->all();
    }

    public function test_student_can_start_and_render_a_configured_check_without_answer_key_metadata(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check, 'questions' => $questions, 'correct' => $correct] = $this->check($mission);

        $attempt = $this->start($user, $mission, $check);

        $this->actingAs($user)
            ->get(route('knowledge-check.show', [$mission, $check, $attempt]))
            ->assertOk()
            ->assertSee('FORMATIVE // CONCEPT CHECK')
            ->assertSee('Question 1 of 3')
            ->assertSee('type="radio"', false)
            ->assertDontSee('SERVER-ONLY EXPLANATION')
            ->assertDontSee('is_correct')
            ->assertDontSee('correct_option');

        $this->assertArrayNotHasKey('explanation', reset($questions)->toArray());
        $this->assertArrayNotHasKey('is_correct', reset($correct)->toArray());
    }

    public function test_check_routes_require_authentication_and_student_role(): void
    {
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check] = $this->check($mission);

        $this->post(route('knowledge-check.start', [$mission, $check]))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->teacher()->create())
            ->post(route('knowledge-check.start', [$mission, $check]))
            ->assertForbidden();
    }

    public function test_unpublished_and_unrelated_checks_are_not_accessible(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $draft] = $this->check($mission);
        $draft->update(['status' => KnowledgeCheck::STATUS_DRAFT]);
        ['mission' => $otherMission] = $this->curriculum();
        ['check' => $otherCheck] = $this->check($otherMission);

        $this->actingAs($user)
            ->post(route('knowledge-check.start', [$mission, $draft]))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('knowledge-check.start', [$mission, $otherCheck]))
            ->assertNotFound();
    }

    public function test_correct_submission_scores_and_reveals_structured_feedback(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check, 'correct' => $correct] = $this->check($mission);
        $attempt = $this->start($user, $mission, $check);

        $this->actingAs($user)
            ->post(route('knowledge-check.submit', [$mission, $check, $attempt]), [
                'answers' => $this->answerIds($correct),
            ])
            ->assertRedirect(route('knowledge-check.show', [$mission, $check, $attempt]));

        $attempt->refresh();
        $this->assertSame(KnowledgeCheckAttempt::STATUS_SUBMITTED, $attempt->status);
        $this->assertSame(3, $attempt->score);
        $this->assertSame(3, $attempt->total_questions);
        $this->assertSame(100, $attempt->percentage);

        $this->actingAs($user)
            ->get(route('knowledge-check.show', [$mission, $check, $attempt]))
            ->assertOk()
            ->assertSee('3 / 3 correct')
            ->assertSee('100%')
            ->assertSee('SERVER-ONLY EXPLANATION 1')
            ->assertSee('CORRECT');
    }

    public function test_incorrect_submission_scores_zero(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check, 'wrong' => $wrong] = $this->check($mission);
        $attempt = $this->start($user, $mission, $check);

        $this->actingAs($user)->post(
            route('knowledge-check.submit', [$mission, $check, $attempt]),
            ['answers' => $this->answerIds($wrong)],
        )->assertRedirect();

        $attempt->refresh();
        $this->assertSame(0, $attempt->score);
        $this->assertSame(0, $attempt->percentage);
    }

    public function test_mixed_submission_uses_simple_question_ratio_scoring(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check, 'correct' => $correct, 'wrong' => $wrong] = $this->check($mission);
        $attempt = $this->start($user, $mission, $check);
        $answers = $this->answerIds($correct);
        $firstQuestionId = array_key_first($answers);
        $answers[$firstQuestionId] = $wrong[$firstQuestionId]->id;

        $this->actingAs($user)->post(
            route('knowledge-check.submit', [$mission, $check, $attempt]),
            ['answers' => $answers],
        )->assertRedirect();

        $attempt->refresh();
        $this->assertSame(2, $attempt->score);
        $this->assertSame(67, $attempt->percentage);
    }

    public function test_responses_are_snapshotted_and_retry_preserves_prior_history(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check, 'correct' => $correct] = $this->check($mission);
        $firstAttempt = $this->start($user, $mission, $check);

        $this->actingAs($user)->post(
            route('knowledge-check.submit', [$mission, $check, $firstAttempt]),
            ['answers' => $this->answerIds($correct)],
        );

        $this->assertDatabaseCount('the404_knowledge_check_responses', 3);
        $this->assertDatabaseHas('the404_knowledge_check_responses', [
            'knowledge_check_attempt_id' => $firstAttempt->id,
            'prompt_snapshot' => 'Question prompt 1',
            'correct_option_snapshot' => 'Correct concept 1',
            'explanation_snapshot' => 'SERVER-ONLY EXPLANATION 1',
        ]);

        $this->actingAs($user)
            ->post(route('knowledge-check.retry', [$mission, $check]))
            ->assertRedirect();

        $attempts = KnowledgeCheckAttempt::query()
            ->where('knowledge_check_id', $check->id)
            ->where('user_id', $user->id)
            ->orderBy('attempt_number')
            ->get();

        $this->assertCount(2, $attempts);
        $this->assertSame(KnowledgeCheckAttempt::STATUS_SUBMITTED, $attempts[0]->status);
        $this->assertSame(KnowledgeCheckAttempt::STATUS_STARTED, $attempts[1]->status);
        $this->assertSame(2, $attempts[1]->attempt_number);
        $this->assertDatabaseCount('the404_knowledge_check_responses', 3);
    }

    public function test_incomplete_and_cross_question_answers_are_rejected_without_partial_writes(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check, 'correct' => $correct] = $this->check($mission);
        $attempt = $this->start($user, $mission, $check);
        $answers = $this->answerIds($correct);
        array_pop($answers);

        $this->actingAs($user)
            ->post(route('knowledge-check.submit', [$mission, $check, $attempt]), ['answers' => $answers])
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('the404_knowledge_check_responses', 0);
        $this->assertSame(KnowledgeCheckAttempt::STATUS_STARTED, $attempt->fresh()->status);

        $answers = $this->answerIds($correct);
        $questionIds = array_keys($answers);
        $answers[$questionIds[0]] = $correct[$questionIds[1]]->id;

        $this->actingAs($user)
            ->post(route('knowledge-check.submit', [$mission, $check, $attempt]), ['answers' => $answers])
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('the404_knowledge_check_responses', 0);
    }

    public function test_student_cannot_view_or_submit_another_students_attempt(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check, 'correct' => $correct] = $this->check($mission);
        $attempt = $this->start($owner, $mission, $check);

        $this->actingAs($intruder)
            ->get(route('knowledge-check.show', [$mission, $check, $attempt]))
            ->assertNotFound();

        $this->actingAs($intruder)
            ->post(route('knowledge-check.submit', [$mission, $check, $attempt]), [
                'answers' => $this->answerIds($correct),
            ])
            ->assertNotFound();

        $this->assertSame(KnowledgeCheckAttempt::STATUS_STARTED, $attempt->fresh()->status);
    }

    public function test_submission_is_idempotent_and_records_one_activity_event(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check, 'correct' => $correct] = $this->check($mission);
        $attempt = $this->start($user, $mission, $check);
        $payload = ['answers' => $this->answerIds($correct)];
        $route = route('knowledge-check.submit', [$mission, $check, $attempt]);

        $this->actingAs($user)->post($route, $payload)->assertRedirect();
        $this->actingAs($user)->post($route, $payload)->assertRedirect();

        $this->assertDatabaseCount('the404_knowledge_check_responses', 3);
        $this->assertSame(1, Activity::query()
            ->where('user_id', $user->id)
            ->where('type', KnowledgeCheckService::ACTIVITY_TYPE_COMPLETED)
            ->count());
    }

    public function test_check_does_not_award_xp_complete_a_mission_or_unlock_boss_challenge(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'mission' => $mission] = $this->curriculum();
        Assessment::factory()->create(['course_id' => $course->id, 'status' => 'active']);
        ['check' => $check, 'correct' => $correct] = $this->check($mission);
        $attempt = $this->start($user, $mission, $check);

        $this->actingAs($user)->post(
            route('knowledge-check.submit', [$mission, $check, $attempt]),
            ['answers' => $this->answerIds($correct)],
        );

        $this->assertDatabaseCount('the404_xp_transactions', 0);
        $this->assertDatabaseCount('the404_progress', 0);
        $this->assertDatabaseCount('the404_assessment_attempts', 0);
        $this->assertFalse(app(AssessmentService::class)->isUnlocked($user, $course));
        $this->assertFalse(app(AssessmentService::class)->hasPassed($user, $course));
    }

    public function test_required_check_gates_challenge_until_any_attempt_is_submitted(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check, 'wrong' => $wrong] = $this->check($mission, required: true);

        $this->actingAs($user)
            ->get(route('mission.challenge', $mission))
            ->assertRedirect(route('mission.show', $mission))
            ->assertSessionHas('knowledge_check_info');

        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => 'anything'])
            ->assertRedirect(route('mission.show', $mission));

        $this->assertDatabaseCount('the404_progress', 0);
        $this->assertDatabaseCount('the404_xp_transactions', 0);

        $attempt = $this->start($user, $mission, $check);
        $this->actingAs($user)->post(
            route('knowledge-check.submit', [$mission, $check, $attempt]),
            ['answers' => $this->answerIds($wrong)],
        );

        $this->actingAs($user)
            ->get(route('mission.challenge', $mission))
            ->assertOk()
            ->assertSee('Code editor');
    }

    public function test_optional_check_does_not_gate_the_challenge(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        $this->check($mission, required: false);

        $this->actingAs($user)
            ->get(route('mission.challenge', $mission))
            ->assertOk();
    }

    public function test_lesson_presents_checks_contextually_without_a_top_level_quiz_destination(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check] = $this->check($mission, required: true);

        $this->actingAs($user)
            ->get(route('mission.show', $mission))
            ->assertOk()
            ->assertSee('Knowledge Check')
            ->assertSee($check->title)
            ->assertSee('START REQUIRED CHECK')
            ->assertDontSee('START CHALLENGE')
            ->assertDontSee('>QUIZZES<', false)
            ->assertDontSee('>EXAMS<', false);
    }

    public function test_completion_event_appears_in_the_existing_student_timeline(): void
    {
        $user = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check, 'correct' => $correct] = $this->check($mission);
        $attempt = $this->start($user, $mission, $check);

        $this->actingAs($user)->post(
            route('knowledge-check.submit', [$mission, $check, $attempt]),
            ['answers' => $this->answerIds($correct)],
        );

        $this->actingAs($user)
            ->get(route('timeline'))
            ->assertOk()
            ->assertSee("Knowledge Check completed: {$check->title} (3/3, 100%)");
    }

    public function test_locked_course_rejects_check_access(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'mission' => $mission] = $this->curriculum();
        ['check' => $check] = $this->check($mission);
        $course->update(['status' => 'locked']);

        $this->actingAs($user)
            ->post(route('knowledge-check.start', [$mission, $check]))
            ->assertForbidden();
    }
}
