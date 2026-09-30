<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\KnowledgeCheckService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * Cross-cutting security integration (US-1114). Each test drives a realistic
 * attack path through real HTTP routes and proves the denial plus unchanged
 * database state. Isolated control behavior already lives in AuthTest,
 * StudentAuthorizationTest, TeacherAuthorizationTest, ClassroomAuthorizationTest,
 * ReportSecurityTest, AssessmentSecurityTest, ErrorHandlingTest, MissionTest,
 * KnowledgeCheckTest, and AssessmentTest. This file only covers seams.
 */
class SecurityIntegrationTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    /** @return array{course: Course, section: Section, mission: Mission} */
    private function curriculum(array $missionAttrs = [], array $courseAttrs = []): array
    {
        $course = Course::factory()->create(array_merge(['status' => 'active'], $courseAttrs));
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(array_merge([
            'course_id' => $course->id,
            'section_id' => $section->id,
        ], $missionAttrs));

        return compact('course', 'section', 'mission');
    }

    /** @return array{check: KnowledgeCheck, questions: array<int, KnowledgeCheckQuestion>, wrong: array<int, KnowledgeCheckOption>} */
    private function checkWithQuestions(Mission $mission, bool $required = false): array
    {
        $check = KnowledgeCheck::factory()->create([
            'mission_id' => $mission->id,
            'is_required' => $required,
        ]);

        $questions = [];
        $wrong = [];

        for ($i = 1; $i <= 2; $i++) {
            $question = KnowledgeCheckQuestion::factory()->create([
                'knowledge_check_id' => $check->id,
                'order_num' => $i,
                'type' => KnowledgeCheckQuestion::TYPE_MULTIPLE_CHOICE,
                'prompt' => "Prompt {$i}",
            ]);
            $questions[$question->id] = $question;
            $wrong[$question->id] = KnowledgeCheckOption::factory()->create([
                'knowledge_check_question_id' => $question->id,
                'order_num' => 1,
                'option_text' => "Distractor {$i}",
            ]);
            KnowledgeCheckOption::factory()->correct()->create([
                'knowledge_check_question_id' => $question->id,
                'order_num' => 2,
                'option_text' => "Answer {$i}",
            ]);
        }

        return ['check' => $check, 'questions' => $questions, 'wrong' => $wrong];
    }

    public function test_anonymous_users_are_redirected_from_protected_routes(): void
    {
        ['mission' => $mission] = $this->curriculum();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('mission.show', $mission))->assertRedirect(route('login'));
        $this->get(route('assessments'))->assertRedirect(route('login'));
        $this->get(route('xp-ledger'))->assertRedirect(route('login'));
    }

    public function test_logout_ends_the_session(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->post(route('logout'))->assertRedirect(route('login'));

        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_student_cannot_open_teacher_or_admin_surfaces(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('students'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.users'))->assertForbidden();
    }

    public function test_teacher_cannot_open_admin_surfaces_but_admin_can(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($teacher)->get(route('admin.users'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.users'))->assertOk();
    }

    public function test_operator_has_no_academic_access(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        ['mission' => $mission] = $this->curriculum();

        $this->actingAs($operator)->get(route('mission.show', $mission))->assertForbidden();
        $this->actingAs($operator)->get(route('students'))->assertForbidden();
    }

    public function test_student_cannot_read_another_students_knowledge_check_attempt(): void
    {
        $studentA = User::factory()->create();
        $studentB = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check] = $this->checkWithQuestions($mission);

        $attempt = app(KnowledgeCheckService::class)->start($studentA, $mission, $check);

        $this->actingAs($studentB)
            ->get(route('knowledge-check.show', [$mission, $check, $attempt]))
            ->assertNotFound();

        $this->assertSame(
            KnowledgeCheckAttempt::STATUS_STARTED,
            $attempt->fresh()->status,
            'Denied read must not touch the attempt.'
        );
    }

    public function test_student_cannot_submit_against_another_students_attempt(): void
    {
        $studentA = User::factory()->create();
        $studentB = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check, 'wrong' => $wrong] = $this->checkWithQuestions($mission);

        $attempt = app(KnowledgeCheckService::class)->start($studentA, $mission, $check);
        $answers = collect($wrong)->mapWithKeys(fn (KnowledgeCheckOption $o, int $qid): array => [$qid => $o->id])->all();

        $this->actingAs($studentB)
            ->post(route('knowledge-check.submit', [$mission, $check, $attempt]), ['answers' => $answers])
            ->assertNotFound();

        $fresh = $attempt->fresh();
        $this->assertSame(KnowledgeCheckAttempt::STATUS_STARTED, $fresh->status);
        $this->assertNull($fresh->submitted_at);
        $this->assertSame(0, $fresh->responses()->count());
    }

    public function test_knowledge_check_submit_ignores_injected_score_fields(): void
    {
        $student = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check, 'wrong' => $wrong] = $this->checkWithQuestions($mission);

        $attempt = app(KnowledgeCheckService::class)->start($student, $mission, $check);
        $answers = collect($wrong)->mapWithKeys(fn (KnowledgeCheckOption $o, int $qid): array => [$qid => $o->id])->all();

        $this->actingAs($student)
            ->post(
                route('knowledge-check.submit', [$mission, $check, $attempt]),
                ['answers' => $answers, 'score' => 100, 'percentage' => 100, 'passed' => true]
            )
            ->assertRedirect();

        $fresh = $attempt->fresh();
        $this->assertSame(KnowledgeCheckAttempt::STATUS_SUBMITTED, $fresh->status);
        $this->assertSame(0, (int) $fresh->score);
        $this->assertSame(0, (int) $fresh->percentage);
    }

    public function test_teacher_cannot_open_reports_for_out_of_scope_students(): void
    {
        $teacher = User::factory()->teacher()->create();
        $inside = User::factory()->create();
        $outside = User::factory()->create(['username' => 'outsider-student']);
        $course = Course::factory()->create(['status' => 'active']);
        $this->classroomFor($teacher, [$inside], [$course]);

        $this->actingAs($teacher)
            ->get(route('student-progress', $outside))
            ->assertForbidden()
            ->assertDontSee($outside->username);

        $this->actingAs($teacher)
            ->get(route('export.teacher-student', $outside))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('student-progress', $inside))
            ->assertOk();
    }

    public function test_direct_submit_cannot_skip_a_required_knowledge_check(): void
    {
        $student = User::factory()->create();
        ['mission' => $mission] = $this->curriculum([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 50,
        ]);
        $this->checkWithQuestions($mission, true);

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => '<h1>Title</h1>'])
            ->assertRedirect(route('mission.show', $mission));

        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertSame(0, XpTransaction::query()->where('user_id', $student->id)->count());
    }

    public function test_sealed_course_rejects_direct_mission_submit(): void
    {
        $student = User::factory()->create();
        ['mission' => $mission] = $this->curriculum(
            ['validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']])],
            ['status' => 'locked']
        );

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => '<h1>Title</h1>'])
            ->assertForbidden();

        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertSame(0, XpTransaction::query()->where('user_id', $student->id)->count());
    }

    public function test_boss_submit_before_eligibility_creates_nothing(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active', 'type' => 'html']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'A']]),
            'passing_score' => 70,
        ]);

        $this->actingAs($student)
            ->post(route('assessment.submit', $assessment), ['code' => 'A'])
            ->assertRedirect(route('assessments'));

        $this->assertSame(0, AssessmentAttempt::query()->count());
        $this->assertSame(0, XpTransaction::query()->where('user_id', $student->id)->count());
    }

    public function test_mission_submit_ignores_injected_credit_and_owner_fields_and_replay_awards_once(): void
    {
        $student = User::factory()->create();
        $victim = User::factory()->create();
        ['mission' => $mission] = $this->curriculum([
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 50,
        ]);

        $payload = [
            'code' => '<h1>Title</h1>',
            'xp' => 999999,
            'points' => 999999,
            'passed' => true,
            'user_id' => $victim->id,
        ];

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), $payload)
            ->assertRedirect();

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), $payload)
            ->assertRedirect();

        $this->assertSame(
            1,
            Progress::query()
                ->where('user_id', $student->id)
                ->where('mission_id', $mission->id)
                ->count()
        );

        $this->assertSame(
            1,
            XpTransaction::query()
                ->where('user_id', $student->id)
                ->where('type', XpService::TYPE_MISSION_COMPLETED)
                ->count()
        );

        $this->assertSame(
            50,
            (int) XpTransaction::query()
                ->where('user_id', $student->id)
                ->sum('amount')
        );

        $this->assertDatabaseMissing('the404_progress', [
            'user_id' => $victim->id,
            'mission_id' => $mission->id,
        ]);

        $this->assertSame(
            0,
            XpTransaction::query()
                ->where('user_id', $victim->id)
                ->count()
        );
    }

    public function test_boss_replay_after_passing_awards_xp_once(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active', 'type' => 'html']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'grading_rule' => json_encode([
                ['type' => 'contains', 'value' => 'A'],
                ['type' => 'contains', 'value' => 'B'],
                ['type' => 'contains', 'value' => 'C'],
            ]),
            'passing_score' => 70,
        ]);
        Progress::query()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'pts_earned' => 0,
            'completed_at' => now(),
        ]);

        $this->actingAs($student)->post(route('assessment.start', $assessment))->assertRedirect();
        $this->actingAs($student)
            ->post(route('assessment.submit', $assessment), ['code' => 'A B C', 'score' => 100, 'passed' => true])
            ->assertRedirect()
            ->assertSessionHas('assessment_success');
        $this->actingAs($student)
            ->post(route('assessment.submit', $assessment), ['code' => 'A B C'])
            ->assertRedirect();

        $this->assertSame(1, XpTransaction::query()
            ->where('user_id', $student->id)
            ->where('type', XpService::TYPE_ASSESSMENT_COMPLETED)
            ->count());
        $this->assertSame(100, (int) XpTransaction::query()->where('user_id', $student->id)->sum('amount'));
    }

    public function test_state_changing_academic_routes_stay_behind_web_auth_and_student_middleware(): void
    {
        // CSRF itself is exercised by real browsers, not the test client, so
        // this pins the middleware wiring that carries it: the web group.
        $route = Route::getRoutes()->getByName('mission.submit');
        $this->assertNotNull($route);
        $middleware = Route::gatherRouteMiddleware($route);
        $this->assertContains('web', $middleware);
        $this->assertContains('auth', $middleware);
        $this->assertContains('student', $middleware);
    }

    public function test_denied_responses_do_not_leak_target_data(): void
    {
        $studentA = User::factory()->create(['username' => 'alpha-student']);
        $studentB = User::factory()->create();
        ['mission' => $mission] = $this->curriculum();
        ['check' => $check] = $this->checkWithQuestions($mission);

        $attempt = app(KnowledgeCheckService::class)->start($studentA, $mission, $check);

        $this->actingAs($studentB)
            ->get(route('knowledge-check.show', [$mission, $check, $attempt]))
            ->assertNotFound()
            ->assertDontSee($studentA->username)
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('Stack trace');
    }
}
