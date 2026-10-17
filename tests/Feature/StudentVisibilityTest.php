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
use App\Models\MissionBehaviorTest;
use App\Models\MissionDraft;
use App\Models\Notification;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\XpService;
use App\Support\AssessmentAttemptView;
use App\Support\AssessmentView;
use App\Support\KnowledgeCheckView;
use App\Support\MissionView;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What a student may and may not receive, on every student page, in HTML
 * and in embedded JSON. Secrets are planted as sentinels in the database
 * and asserted absent from every response body: reference solutions before
 * a paid reveal, unpurchased hints, validation rules, hidden behavioural
 * test inputs and expected outputs, Boss Challenge grading rules, the
 * Knowledge Check answer key before submission, another student's rows,
 * and stack traces.
 *
 * The contract gates exposures, not just rendering: a paid hint / paid
 * reveal / submitted Knowledge Check may surface its own material (proved
 * here so the sentinels are known to be live), everything else must not.
 */
class StudentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const SOLUTION_SENTINEL = 'SOLUTION_SENTINEL';

    private const HINT_ONE_SENTINEL = 'HINT_ONE_SENTINEL';

    private const HINT_TWO_SENTINEL = 'HINT_TWO_SENTINEL';

    private const RULE_SENTINEL = 'RULE_SENTINEL';

    private const BEHAVIOR_INPUT_SENTINEL = 'BEHAVIOR_INPUT_SENTINEL';

    private const BEHAVIOR_EXPECTED_SENTINEL = 'BEHAVIOR_EXPECTED_SENTINEL';

    private const BOSS_RULE_SENTINEL = 'BOSS_RULE_SENTINEL';

    private const KEY_SENTINEL = 'KEY_SENTINEL';

    private const OTHER_NAME_SENTINEL = 'OTHER_STUDENT_SENTINEL';

    private const OTHER_DRAFT_SENTINEL = 'OTHER_DRAFT_SENTINEL';

    private const OTHER_NOTIFICATION_SENTINEL = 'OTHER_NOTIFICATION_SENTINEL';

    private const OTHER_XP_AMOUNT = 9876543;

    /**
     * Every secret that must never appear in any student response.
     *
     * @var array<int, string>
     */
    private const SECRET_NEEDLES = [
        self::SOLUTION_SENTINEL,
        self::HINT_ONE_SENTINEL,
        self::HINT_TWO_SENTINEL,
        self::RULE_SENTINEL,
        self::BEHAVIOR_INPUT_SENTINEL,
        self::BEHAVIOR_EXPECTED_SENTINEL,
        self::BOSS_RULE_SENTINEL,
        self::KEY_SENTINEL,
        self::OTHER_NAME_SENTINEL,
        self::OTHER_DRAFT_SENTINEL,
        self::OTHER_NOTIFICATION_SENTINEL,
        '9876543',
        'solution_code',
        'validate_rule',
        'grading_rule',
        'is_correct',
        'isCorrect',
        'Stack trace',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    public function test_every_student_page_hides_secret_material_on_first_load(): void
    {
        [
            'student' => $student,
            'mission' => $mission,
            'check' => $check,
            'attempt' => $attempt,
            'assessment' => $assessment,
        ] = $this->fixture();
        $this->unlockBoss($student, $mission);

        foreach ($this->studentPages($mission, $check, $attempt, $assessment) as $page => $url) {
            $response = $this->actingAs($student)->get($url);

            $response->assertOk();
            $response->assertDontSee('class="sort-code"', false);
            $this->assertHidesSecrets($response->getContent(), $page);
        }
    }

    public function test_failed_mission_submission_never_exposes_rules_solution_or_hidden_tests(): void
    {
        ['student' => $student, 'mission' => $mission] = $this->fixture();

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => '<p>WRONG ANSWER</p>'])
            ->assertRedirect();

        $this->assertHidesSecrets(json_encode(session()->all()) ?: '', 'mission.submit flash');

        foreach ([
            route('mission.show', $mission),
            route('mission.challenge', $mission),
            route('mission.experiment', $mission),
            route('dashboard'),
        ] as $url) {
            $response = $this->actingAs($student)->get($url);

            $response->assertOk();
            $this->assertHidesSecrets($response->getContent(), $url);
        }
    }

    public function test_hint_purchase_reveals_only_the_purchased_hint(): void
    {
        ['student' => $student, 'mission' => $mission] = $this->fixture();
        $this->fund($student, $mission);

        $this->actingAs($student)->post(route('mission.hint', $mission))->assertRedirect();

        $challenge = $this->actingAs($student)->get(route('mission.challenge', $mission));
        $challenge->assertOk();
        $challenge->assertSee(self::HINT_ONE_SENTINEL);
        $challenge->assertDontSee(self::HINT_TWO_SENTINEL);
        $this->assertHidesSecrets(
            $challenge->getContent(),
            'mission.challenge after first hint purchase',
            array_values(array_diff(self::SECRET_NEEDLES, [self::HINT_ONE_SENTINEL])),
        );

        foreach ([route('mission.show', $mission), route('dashboard'), route('missions')] as $url) {
            $response = $this->actingAs($student)->get($url);

            $response->assertOk();
            $this->assertHidesSecrets($response->getContent(), $url);
        }
    }

    public function test_solution_reveal_requires_the_paid_reveal_and_stays_off_other_pages(): void
    {
        ['student' => $student, 'mission' => $mission] = $this->fixture();
        $this->fund($student, $mission);

        $before = $this->actingAs($student)->get(route('mission.challenge', $mission));
        $before->assertOk();
        $before->assertDontSee(self::SOLUTION_SENTINEL);

        $this->actingAs($student)
            ->post(route('mission.reveal', $mission), ['code' => '<p>DRAFT IN PROGRESS</p>'])
            ->assertRedirect();

        // The reveal redirect restores the in-progress draft for one request
        // (flashed old input), so the paid solution surfaces on the next load.
        $reload = $this->actingAs($student)->get(route('mission.challenge', $mission));
        $reload->assertOk();
        $this->assertHidesSecrets($reload->getContent(), 'mission.challenge reload after paid reveal');

        $after = $this->actingAs($student)->get(route('mission.challenge', $mission));
        $after->assertOk();
        $after->assertSee(self::SOLUTION_SENTINEL);
        $this->assertHidesSecrets(
            $after->getContent(),
            'mission.challenge after paid reveal',
            array_values(array_diff(self::SECRET_NEEDLES, [self::SOLUTION_SENTINEL])),
        );

        foreach ([route('mission.show', $mission), route('dashboard')] as $url) {
            $response = $this->actingAs($student)->get($url);

            $response->assertOk();
            $this->assertHidesSecrets($response->getContent(), $url);
        }
    }

    public function test_knowledge_check_answer_key_stays_hidden_before_submission(): void
    {
        [
            'student' => $student,
            'mission' => $mission,
            'check' => $check,
            'question' => $question,
            'wrong' => $wrong,
            'attempt' => $attempt,
        ] = $this->fixture();

        $page = $this->actingAs($student)->get(route('knowledge-check.show', [$mission, $check, $attempt]));
        $page->assertOk();
        $page->assertSee('Which signal applies?');
        $page->assertDontSee(self::KEY_SENTINEL);
        $this->assertHidesSecrets($page->getContent(), 'knowledge-check before submission');

        $this->actingAs($student)->post(
            route('knowledge-check.submit', [$mission, $check, $attempt]),
            ['answers' => [$question->id => $wrong->id]],
        )->assertRedirect();

        // Feedback after submission is the intended reveal; the sentinel must
        // appear here to prove the fixture is live, and every other secret
        // must stay hidden.
        $after = $this->actingAs($student)->get(route('knowledge-check.show', [$mission, $check, $attempt]));
        $after->assertOk();
        $after->assertSee(self::KEY_SENTINEL);
        $this->assertHidesSecrets(
            $after->getContent(),
            'knowledge-check after submission',
            array_values(array_diff(self::SECRET_NEEDLES, [self::KEY_SENTINEL])),
        );
    }

    public function test_failed_boss_submission_never_exposes_the_grading_rule(): void
    {
        ['student' => $student, 'mission' => $mission, 'assessment' => $assessment] = $this->fixture();
        $this->unlockBoss($student, $mission);

        $this->actingAs($student)->post(route('assessment.start', $assessment))->assertRedirect();
        $this->actingAs($student)
            ->post(route('assessment.submit', $assessment), ['code' => '<p>WRONG BOSS ANSWER</p>'])
            ->assertRedirect();

        $this->assertHidesSecrets(json_encode(session()->all()) ?: '', 'assessment.submit flash');

        $page = $this->actingAs($student)->get(route('assessment.show', $assessment));
        $page->assertOk();
        $this->assertHidesSecrets($page->getContent(), 'assessment.show after failed submission');
    }

    public function test_assessment_pages_never_expose_the_attempt_grading_rule_snapshot(): void
    {
        ['student' => $student, 'mission' => $mission, 'assessment' => $assessment] = $this->fixture();
        $this->unlockBoss($student, $mission);

        $this->actingAs($student)->post(route('assessment.start', $assessment))->assertRedirect();
        $attempt = AssessmentAttempt::query()->where('user_id', $student->id)->sole();
        $attempt->forceFill(['grading_rule_snapshot' => self::BOSS_RULE_SENTINEL])->save();

        foreach (['assessments', 'assessment.show'] as $page) {
            $response = $page === 'assessments'
                ? $this->actingAs($student)->get(route('assessments'))
                : $this->actingAs($student)->get(route('assessment.show', $assessment));
            $response->assertOk();
            $this->assertStringNotContainsString(self::BOSS_RULE_SENTINEL, $response->getContent());
        }

        $this->actingAs($student)->post(
            route('assessment.submit', $assessment),
            ['code' => '<p>WRONG BOSS ANSWER</p>'],
        )->assertRedirect();

        foreach (['assessments', 'assessment.show'] as $page) {
            $response = $page === 'assessments'
                ? $this->actingAs($student)->get(route('assessments'))
                : $this->actingAs($student)->get(route('assessment.show', $assessment));
            $response->assertOk();
            $this->assertStringNotContainsString(self::BOSS_RULE_SENTINEL, $response->getContent());
        }

        $this->actingAs($student)->post(route('assessment.retry', $assessment))->assertRedirect();
        $retry = AssessmentAttempt::query()->where('user_id', $student->id)->latest('id')->firstOrFail();
        $retry->forceFill(['grading_rule_snapshot' => self::BOSS_RULE_SENTINEL])->save();

        foreach (['assessments', 'assessment.show'] as $page) {
            $response = $page === 'assessments'
                ? $this->actingAs($student)->get(route('assessments'))
                : $this->actingAs($student)->get(route('assessment.show', $assessment));
            $response->assertOk();
            $this->assertStringNotContainsString(self::BOSS_RULE_SENTINEL, $response->getContent());
        }
    }

    public function test_another_students_records_are_neither_rendered_nor_reachable(): void
    {
        [
            'student' => $student,
            'mission' => $mission,
            'check' => $check,
            'other' => $other,
            'otherAttempt' => $otherAttempt,
            'otherNotification' => $otherNotification,
        ] = $this->fixture();

        $response = $this->actingAs($student)->get(route('knowledge-check.show', [$mission, $check, $otherAttempt]));
        $response->assertStatus(404);
        $this->assertHidesSecrets($response->getContent(), 'foreign knowledge check attempt');

        $response = $this->actingAs($student)->post(route('notifications.read', $otherNotification));
        // Deliberate anti-enumeration shape: a foreign id is the same no-op
        // redirect as an already-read row, and the row stays untouched.
        $response->assertRedirect(route('notifications'));
        $this->assertHidesSecrets($response->getContent(), 'foreign notification mark-read');
        $otherNotification->refresh();
        $this->assertNull($otherNotification->read_at, 'A foreign notification must not be marked as read.');

        $this->actingAs($student)->get(route('reports.progress', ['user_id' => $other->id]))
            ->assertForbidden();

        $this->actingAs($student)->get(route('notifications', ['user_id' => $other->id]))
            ->assertForbidden();
    }

    /**
     * The whitelists are the enforcement boundary: sensitive attributes can
     * never ride along even though every Blade renders unchanged. Adding a
     * field to a view-model must be a conscious change to this list.
     */
    public function test_student_view_models_expose_exactly_the_whitelisted_fields(): void
    {
        $mission = Mission::factory()->create();
        $assessment = Assessment::factory()->create();
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'status' => 'failed',
            'score' => 42,
            'code' => 'student draft',
            'grading_rule_snapshot' => self::BOSS_RULE_SENTINEL,
        ]);
        $check = KnowledgeCheck::factory()->create();

        $missionView = MissionView::from($mission);
        $assessmentView = AssessmentView::from($assessment);
        $attemptView = AssessmentAttemptView::from($attempt);
        $checkView = KnowledgeCheckView::from($check);

        $this->assertSame(
            ['id', 'title', 'description', 'broken_code', 'target_html', 'difficulty', 'points', 'order_num'],
            array_keys(get_object_vars($missionView)),
        );
        $this->assertNotContains('solution_code', array_keys(get_object_vars($missionView)));
        $this->assertNotContains('hints', array_keys(get_object_vars($missionView)));
        $this->assertNotContains('validate_rule', array_keys(get_object_vars($missionView)));

        $this->assertSame(
            ['id', 'title', 'description', 'instructions', 'passing_score'],
            array_keys(get_object_vars($assessmentView)),
        );
        $this->assertNotContains('grading_rule', array_keys(get_object_vars($assessmentView)));

        $this->assertSame(['status', 'score', 'code'], array_keys(get_object_vars($attemptView)));
        $this->assertNotContains('grading_rule_snapshot', array_keys(get_object_vars($attemptView)));

        $this->assertSame(
            ['id', 'title', 'instructions', 'is_required'],
            array_keys(get_object_vars($checkView)),
        );

        // Blade route() calls keep working because the view-models are
        // UrlRoutable and expose the model's own route key.
        $this->assertSame($mission->id, $missionView->getRouteKey());
        $this->assertSame($assessment->id, $assessmentView->getRouteKey());
        $this->assertSame($check->id, $checkView->getRouteKey());
    }

    /**
     * One active course with a sentinel-laden mission, a published knowledge
     * check, an active Boss Challenge, and a second student owning rows that
     * must never surface for the first student.
     *
     * @return array{
     *     student: User,
     *     other: User,
     *     course: Course,
     *     section: Section,
     *     mission: Mission,
     *     check: KnowledgeCheck,
     *     question: KnowledgeCheckQuestion,
     *     wrong: KnowledgeCheckOption,
     *     attempt: KnowledgeCheckAttempt,
     *     otherAttempt: KnowledgeCheckAttempt,
     *     otherNotification: Notification,
     *     assessment: Assessment
     * }
     */
    private function fixture(): array
    {
        $student = User::factory()->create();

        $course = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'points' => 50,
            'solution_code' => '<p>'.self::SOLUTION_SENTINEL.'</p>',
            'hints' => json_encode([self::HINT_ONE_SENTINEL, self::HINT_TWO_SENTINEL]),
            'validate_rule' => json_encode([[
                'type' => 'contains',
                'value' => self::RULE_SENTINEL,
                'label' => 'Broadcast requirement',
            ]]),
        ]);

        MissionBehaviorTest::factory()->create([
            'mission_id' => $mission->id,
            'order_num' => 1,
            'active' => false,
            'configuration' => json_encode([
                'function' => 'broadcast',
                'cases' => [[
                    'args' => [self::BEHAVIOR_INPUT_SENTINEL],
                    'expected' => self::BEHAVIOR_EXPECTED_SENTINEL,
                ]],
            ]),
        ]);

        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'grading_rule' => json_encode([[
                'type' => 'contains',
                'value' => self::BOSS_RULE_SENTINEL,
                'label' => 'Boss requirement',
            ]]),
        ]);

        $check = KnowledgeCheck::factory()->create([
            'mission_id' => $mission->id,
            'order_num' => 1,
            'is_required' => false,
            'title' => 'Signal Concepts',
        ]);

        $question = KnowledgeCheckQuestion::factory()->create([
            'knowledge_check_id' => $check->id,
            'order_num' => 1,
            'type' => KnowledgeCheckQuestion::TYPE_MULTIPLE_CHOICE,
            'prompt' => 'Which signal applies?',
            'explanation' => self::KEY_SENTINEL,
        ]);

        $wrong = KnowledgeCheckOption::factory()->create([
            'knowledge_check_question_id' => $question->id,
            'order_num' => 1,
            'option_text' => 'Distractor choice',
        ]);

        KnowledgeCheckOption::factory()->correct()->create([
            'knowledge_check_question_id' => $question->id,
            'order_num' => 2,
            'option_text' => 'Correct choice',
        ]);

        $attempt = $this->startCheck($student, $mission, $check);

        $other = User::factory()->create(['name' => self::OTHER_NAME_SENTINEL]);

        MissionDraft::factory()->create([
            'user_id' => $other->id,
            'mission_id' => $mission->id,
            'code' => self::OTHER_DRAFT_SENTINEL,
        ]);

        AssessmentAttempt::factory()->create([
            'user_id' => $other->id,
            'assessment_id' => $assessment->id,
            'status' => 'started',
            'code' => self::OTHER_DRAFT_SENTINEL.'_ASSESSMENT',
        ]);

        XpTransaction::query()->create([
            'user_id' => $other->id,
            'mission_id' => $mission->id,
            'amount' => self::OTHER_XP_AMOUNT,
            'type' => XpService::TYPE_MISSION_COMPLETED,
        ]);

        $otherNotification = Notification::factory()->create([
            'user_id' => $other->id,
            'title' => self::OTHER_NOTIFICATION_SENTINEL,
            'message' => self::OTHER_NOTIFICATION_SENTINEL,
        ]);

        Progress::factory()->create([
            'user_id' => $other->id,
            'mission_id' => $mission->id,
        ]);

        $otherAttempt = KnowledgeCheckAttempt::factory()->create([
            'user_id' => $other->id,
            'knowledge_check_id' => $check->id,
        ]);

        return compact(
            'student',
            'other',
            'course',
            'section',
            'mission',
            'check',
            'question',
            'wrong',
            'attempt',
            'otherAttempt',
            'otherNotification',
            'assessment',
        );
    }

    /** @return array<string, string> page label => URL */
    private function studentPages(
        Mission $mission,
        KnowledgeCheck $check,
        KnowledgeCheckAttempt $attempt,
        Assessment $assessment,
    ): array {
        return [
            'dashboard' => route('dashboard'),
            'learning-path' => route('learning-path'),
            'missions' => route('missions'),
            'mission.show' => route('mission.show', $mission),
            'mission.challenge' => route('mission.challenge', $mission),
            'mission.experiment' => route('mission.experiment', $mission),
            'knowledge-check.show' => route('knowledge-check.show', [$mission, $check, $attempt]),
            'assessments' => route('assessments'),
            'assessment.show' => route('assessment.show', $assessment),
            'progress' => route('progress'),
            'section-progress' => route('section-progress'),
            'competency' => route('competency'),
            'recommendations' => route('recommendations'),
            'xp-ledger' => route('xp-ledger'),
            'timeline' => route('timeline'),
            'achievements' => route('achievements'),
            'notifications' => route('notifications'),
            'reports.progress' => route('reports.progress'),
        ];
    }

    private function startCheck(User $user, Mission $mission, KnowledgeCheck $check): KnowledgeCheckAttempt
    {
        $this->actingAs($user)
            ->post(route('knowledge-check.start', [$mission, $check]))
            ->assertRedirect();

        return KnowledgeCheckAttempt::query()
            ->where('user_id', $user->id)
            ->where('knowledge_check_id', $check->id)
            ->sole();
    }

    private function unlockBoss(User $student, Mission $mission): void
    {
        Progress::factory()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
        ]);
    }

    private function fund(User $student, Mission $mission, int $amount = 100): void
    {
        XpTransaction::query()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'amount' => $amount,
            'type' => XpService::TYPE_MISSION_COMPLETED,
        ]);
    }

    /**
     * @param  array<int, string>|null  $needles
     */
    private function assertHidesSecrets(string $body, string $page, ?array $needles = null): void
    {
        foreach ($needles ?? self::SECRET_NEEDLES as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $body,
                "Response [{$page}] leaked [{$needle}].",
            );
        }
    }
}
