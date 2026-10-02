<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\Assessment;
use App\Models\Concerns\HasCurriculumVersion;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AdminAssessmentService;
use App\Services\AdminMissionService;
use App\Services\AssessmentService;
use App\Services\CourseService;
use App\Services\KnowledgeCheckService;
use App\Services\MissionService;
use App\Services\SectionService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class CurriculumVersioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    private function missionAttributes(int $points = 50): array
    {
        return [
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => $points,
        ];
    }

    private function missionUpdateAttributes(Mission $mission, array $overrides = []): array
    {
        return array_merge([
            'title' => $mission->title,
            'description' => $mission->description,
            'difficulty' => $mission->difficulty,
            'points' => $mission->points,
            'order_num' => $mission->order_num,
            'section_id' => $mission->section_id,
            'hints' => $mission->hints,
            'broken_code' => $mission->broken_code,
            'target_html' => $mission->target_html,
        ], $overrides);
    }

    private function assessmentAttributes(): array
    {
        return [
            'grading_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'passing_score' => 70,
            'status' => 'active',
        ];
    }

    private function passingAssessmentScenario(): array
    {
        $course = Course::factory()->create(['status' => 'active']);
        $mission = Mission::factory()->create(array_merge(
            ['course_id' => $course->id],
            $this->missionAttributes(50)
        ));
        $assessment = Assessment::factory()->create(array_merge(
            ['course_id' => $course->id],
            $this->assessmentAttributes()
        ));
        $user = User::factory()->create();

        app(MissionService::class)->submit($user, $mission, '<h1>Title</h1>');

        $assessments = app(AssessmentService::class);
        $attempt = $assessments->beginAttempt($user, $course);
        $assessments->submitAttempt($user, $attempt, '<h1>Boss</h1>');
        $assessments->evaluateAttempt($user, $attempt);

        return compact('course', 'mission', 'assessment', 'user', 'attempt');
    }

    /**
     * @return array{
     *     check: KnowledgeCheck,
     *     questions: array<int, KnowledgeCheckQuestion>,
     *     correct: array<int, KnowledgeCheckOption>
     * }
     */
    private function checkWithQuestions(Mission $mission): array
    {
        $check = KnowledgeCheck::factory()->create([
            'mission_id' => $mission->id,
            'title' => 'Markup Concepts',
        ]);

        $questions = [];
        $correct = [];

        foreach ([1, 2] as $index) {
            $question = KnowledgeCheckQuestion::factory()->create([
                'knowledge_check_id' => $check->id,
                'order_num' => $index,
                'prompt' => 'Question prompt '.$index,
                'explanation' => 'Explanation '.$index,
            ]);
            $questions[$question->id] = $question;
            KnowledgeCheckOption::factory()->create([
                'knowledge_check_question_id' => $question->id,
                'order_num' => 1,
                'option_text' => 'Distractor '.$index,
            ]);
            $correct[$question->id] = KnowledgeCheckOption::factory()->correct()->create([
                'knowledge_check_question_id' => $question->id,
                'order_num' => 2,
                'option_text' => 'Correct concept '.$index,
            ]);
        }

        return compact('check', 'questions', 'correct');
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

    public function test_mission_points_edit_preserves_history_and_future_completions_use_new_points(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(array_merge(
            ['course_id' => $course->id],
            $this->missionAttributes(50)
        ));
        $student = User::factory()->create();

        app(MissionService::class)->submit($student, $mission, '<h1>Title</h1>');

        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'mission_version' => 1,
            'pts_earned' => 50,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'mission_version' => 1,
            'amount' => 50,
            'type' => XpService::TYPE_MISSION_COMPLETED,
        ]);

        app(AdminMissionService::class)->update(
            $admin, $course, $mission, $this->missionUpdateAttributes($mission, ['points' => 80])
        );

        $this->assertSame(2, $mission->fresh()->version);

        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'mission_version' => 1,
            'pts_earned' => 50,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'mission_version' => 1,
            'amount' => 50,
            'type' => XpService::TYPE_MISSION_COMPLETED,
        ]);

        $lateStudent = User::factory()->create();
        app(MissionService::class)->submit($lateStudent, $mission->fresh(), '<h1>Title</h1>');

        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $lateStudent->id,
            'mission_id' => $mission->id,
            'mission_version' => 2,
            'pts_earned' => 80,
        ]);
    }

    public function test_assessment_threshold_edit_preserves_pass_and_snapshots(): void
    {
        $admin = User::factory()->admin()->create();
        ['course' => $course, 'assessment' => $assessment, 'user' => $user, 'attempt' => $attempt]
            = $this->passingAssessmentScenario();

        $attempt->refresh();
        $this->assertSame('passed', $attempt->status);
        $this->assertSame(1, $attempt->assessment_version);
        $this->assertSame($assessment->grading_rule, $attempt->grading_rule_snapshot);
        $this->assertSame(70, $attempt->passing_score_snapshot);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'assessment_version' => 1,
            'amount' => XpService::ASSESSMENT_PASSED_AMOUNT,
            'type' => XpService::TYPE_ASSESSMENT_COMPLETED,
        ]);

        app(AdminAssessmentService::class)->update($admin, $course, $assessment, [
            'title' => $assessment->title,
            'description' => $assessment->description,
            'instructions' => $assessment->instructions,
            'passing_score' => 100,
            'status' => 'active',
        ]);

        $this->assertSame(2, $assessment->fresh()->version);
        $this->assertTrue(app(AssessmentService::class)->hasPassed($user, $course));

        $attempt->refresh();
        $this->assertSame(1, $attempt->assessment_version);
        $this->assertSame(70, $attempt->passing_score_snapshot);

        $assessments = app(AssessmentService::class);
        $retry = $assessments->retryAttempt($user, $course);
        $assessments->submitAttempt($user, $retry, '<p>Wrong</p>');
        $assessments->evaluateAttempt($user, $retry);

        $retry->refresh();
        $this->assertSame('failed', $retry->status);
        $this->assertSame(2, $retry->assessment_version);
        $this->assertSame(100, $retry->passing_score_snapshot);
        $this->assertTrue($assessments->hasPassed($user, $course));
    }

    public function test_knowledge_check_edit_preserves_attempt_and_response_snapshots(): void
    {
        $course = Course::factory()->create(['status' => 'active']);
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        ['check' => $check, 'questions' => $questions, 'correct' => $correct]
            = $this->checkWithQuestions($mission);
        $user = User::factory()->create();

        $checks = app(KnowledgeCheckService::class);
        $attempt = $checks->start($user, $mission, $check);
        $checks->submit($user, $mission, $check, $attempt, $this->answerIds($correct));

        $attempt->refresh();
        $this->assertSame(1, $attempt->knowledge_check_version);
        $snapshotsBefore = $attempt->responses()->orderBy('id')->pluck('prompt_snapshot')->all();

        $check->instructions = 'Reworded check guidance';
        $check->save();
        $this->assertSame(2, $check->fresh()->version);

        $question = reset($questions);
        $question->prompt = 'Reworded prompt';
        $question->save();

        $attempt->refresh();
        $this->assertSame(1, $attempt->knowledge_check_version);
        $this->assertSame(2, $attempt->score);
        $this->assertSame(
            $snapshotsBefore,
            $attempt->responses()->orderBy('id')->pluck('prompt_snapshot')->all()
        );
        $this->assertSame(
            'Question prompt 1',
            $attempt->responses()->orderBy('id')->first()->prompt_snapshot
        );
    }

    public function test_material_mission_edit_bumps_version_exactly_once(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id, 'points' => 50]);

        app(AdminMissionService::class)->update(
            $admin, $course, $mission, $this->missionUpdateAttributes($mission, ['points' => 80])
        );

        $this->assertSame(2, $mission->fresh()->version);

        $audit = AdminAudit::query()
            ->where('action', 'mission.update')
            ->where('target_id', $mission->id)
            ->where('result', 'success')
            ->sole();

        $this->assertStringContainsString('version 1 → 2', $audit->summary);
    }

    public function test_noop_mission_edit_does_not_bump_version_or_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);

        app(AdminMissionService::class)->update(
            $admin, $course, $mission, $this->missionUpdateAttributes($mission)
        );

        $this->assertSame(1, $mission->fresh()->version);
        $this->assertSame(0, AdminAudit::query()->where('target_id', $mission->id)->count());
    }

    public function test_refused_mission_edit_does_not_bump_version(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        $pointsBefore = $mission->points;

        try {
            app(AdminMissionService::class)->update(
                $admin, $course, $mission, $this->missionUpdateAttributes($mission, ['points' => -5])
            );
            $this->fail('Refused points must throw.');
        } catch (InvalidArgumentException) {
        }

        $this->assertSame(1, $mission->fresh()->version);
        $this->assertSame($pointsBefore, $mission->fresh()->points);
        $this->assertSame(1, AdminAudit::query()
            ->where('action', 'mission.update')
            ->where('target_id', $mission->id)
            ->where('result', 'failed')
            ->count());
    }

    public function test_unauthorized_mission_edit_does_not_bump_version(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id, 'points' => 50]);

        $this->actingAs($student)->put(
            route('admin.courses.missions.update', [$course, $mission]),
            ['title' => 'Hacked', 'difficulty' => 'EASY', 'points' => 999, 'order_num' => 1]
        )->assertForbidden();

        $this->assertSame(1, $mission->fresh()->version);
        $this->assertSame(50, $mission->fresh()->points);
    }

    public function test_rolled_back_edit_does_not_bump_version(): void
    {
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id, 'title' => 'Original']);

        try {
            DB::transaction(function () use ($mission): void {
                $mission->title = 'Changed';
                $mission->save();
                throw new RuntimeException('boom');
            });
            $this->fail('Rolled-back transaction must throw.');
        } catch (RuntimeException) {
        }

        $fresh = $mission->fresh();
        $this->assertSame('Original', $fresh->title);
        $this->assertSame(1, $fresh->version);
    }

    public function test_course_material_edit_bumps_but_status_only_change_does_not(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['description' => 'Original overview', 'status' => 'active']);
        $service = app(CourseService::class);

        $service->update($admin, $course, [
            'name' => $course->name,
            'slug' => $course->slug,
            'type' => $course->type,
            'description' => 'Rewired overview',
            'status' => 'active',
            'order_num' => $course->order_num,
        ]);

        $this->assertSame(2, $course->fresh()->version);

        $service->update($admin, $course->fresh(), [
            'name' => $course->name,
            'slug' => $course->slug,
            'type' => $course->type,
            'description' => 'Rewired overview',
            'status' => 'locked',
            'order_num' => $course->order_num,
        ]);

        $fresh = $course->fresh();
        $this->assertSame('locked', $fresh->status);
        $this->assertSame(2, $fresh->version);
    }

    public function test_identity_label_edits_do_not_bump_versions(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        app(CourseService::class)->update($admin, $course, [
            'name' => 'Renamed Course',
            'slug' => $course->slug,
            'type' => $course->type,
            'description' => $course->description,
            'status' => 'active',
            'order_num' => $course->order_num,
        ]);

        app(AdminMissionService::class)->update(
            $admin, $course, $mission, $this->missionUpdateAttributes($mission, [
                'title' => 'Renamed Mission',
                'difficulty' => $mission->difficulty === 'EASY' ? 'HARD' : 'EASY',
            ])
        );

        app(AdminAssessmentService::class)->update($admin, $course, $assessment, [
            'title' => 'Renamed Challenge',
            'description' => $assessment->description,
            'instructions' => $assessment->instructions,
            'passing_score' => $assessment->passing_score,
            'status' => 'active',
        ]);

        $this->assertSame(1, $course->fresh()->version);
        $this->assertSame(1, $mission->fresh()->version);
        $this->assertSame(1, $assessment->fresh()->version);
        $this->assertSame(3, AdminAudit::query()
            ->whereIn('action', ['course.update', 'mission.update', 'assessment.update'])
            ->where('result', 'success')
            ->count());
        $this->assertSame(0, AdminAudit::query()->where('summary', 'like', '%version%')->count());
    }

    public function test_knowledge_check_question_edit_bumps_parent_check_version(): void
    {
        $mission = Mission::factory()->create();
        ['check' => $check, 'questions' => $questions] = $this->checkWithQuestions($mission);

        $question = reset($questions);
        $question->prompt = 'Reworded prompt';
        $question->save();

        $this->assertSame(2, $check->fresh()->version);
    }

    public function test_knowledge_check_option_correctness_flip_bumps_parent_check_version(): void
    {
        $mission = Mission::factory()->create();
        ['check' => $check, 'questions' => $questions] = $this->checkWithQuestions($mission);

        $question = reset($questions);
        $options = KnowledgeCheckOption::query()
            ->where('knowledge_check_question_id', $question->id)
            ->orderBy('order_num')
            ->get();

        foreach ($options as $option) {
            $option->is_correct = ! $option->is_correct;
            $option->save();
        }

        $this->assertSame(3, $check->fresh()->version);
    }

    public function test_knowledge_check_required_flag_edit_bumps_version(): void
    {
        $mission = Mission::factory()->create();
        ['check' => $check] = $this->checkWithQuestions($mission);

        $check->is_required = ! $check->is_required;
        $check->save();

        $this->assertSame(2, $check->fresh()->version);
    }

    public function test_attempt_submitted_after_check_edit_carries_the_evaluated_version(): void
    {
        $course = Course::factory()->create(['status' => 'active']);
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        ['check' => $check, 'correct' => $correct] = $this->checkWithQuestions($mission);
        $user = User::factory()->create();

        $checks = app(KnowledgeCheckService::class);
        $attempt = $checks->start($user, $mission, $check);
        $this->assertSame(1, $attempt->knowledge_check_version);

        $check->instructions = 'Reworded check guidance';
        $check->save();
        $this->assertSame(2, $check->fresh()->version);

        $submitted = $checks->submit($user, $mission, $check, $attempt, $this->answerIds($correct));

        $this->assertSame(2, $submitted->knowledge_check_version);
        $this->assertSame(2, $submitted->score);
        $this->assertSame(2, $submitted->total_questions);
        $this->assertSame(100, $submitted->percentage);
        $this->assertTrue(
            $submitted->responses->every(fn ($response): bool => $response->is_correct)
        );
        $this->assertSame(
            ['Correct concept 1', 'Correct concept 2'],
            $submitted->responses()->orderBy('id')->pluck('correct_option_snapshot')->all()
        );
    }

    public function test_section_edit_bumps_version(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);

        app(SectionService::class)->update($admin, $course, $section, [
            'title' => $section->title,
            'description' => 'Rewired section overview',
            'order_num' => $section->order_num,
        ]);

        $this->assertSame(2, $section->fresh()->version);
    }

    public function test_xp_rows_stamp_the_current_mission_version(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $earn = Mission::factory()->create(array_merge(
            ['course_id' => $course->id],
            $this->missionAttributes(50)
        ));
        $spend = Mission::factory()->create(array_merge(
            ['course_id' => $course->id, 'hints' => json_encode(['First hint'])],
            $this->missionAttributes(50)
        ));

        app(AdminMissionService::class)->update(
            $admin, $course, $spend, $this->missionUpdateAttributes($spend, ['description' => 'Rewired spend mission'])
        );
        $this->assertSame(2, $spend->fresh()->version);

        $student = User::factory()->create();
        $missions = app(MissionService::class);
        $missions->submit($student, $earn, 'no heading here');
        $missions->submit($student, $earn, '<h1>Title</h1>');

        $xp = app(XpService::class);
        $this->assertTrue($xp->spendHint($student, $spend->fresh(), 1));
        $this->assertTrue($xp->spendSolutionReveal($student, $spend->fresh()));

        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'mission_id' => $earn->id,
            'mission_version' => 1,
            'type' => XpService::TYPE_WRONG_SUBMISSION,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'mission_id' => $earn->id,
            'mission_version' => 1,
            'type' => XpService::TYPE_MISSION_COMPLETED,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'mission_id' => $spend->id,
            'mission_version' => 2,
            'type' => XpService::TYPE_HINT_USED,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'mission_id' => $spend->id,
            'mission_version' => 2,
            'type' => XpService::TYPE_SOLUTION_REVEALED,
        ]);
    }

    public function test_resubmission_stays_idempotent_with_versions_stamped(): void
    {
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(array_merge(
            ['course_id' => $course->id],
            $this->missionAttributes(50)
        ));
        $student = User::factory()->create();
        $missions = app(MissionService::class);

        $first = $missions->submit($student, $mission, '<h1>Title</h1>');
        $second = $missions->submit($student, $mission, '<h1>Title</h1>');

        $this->assertFalse($first['alreadyCompleted']);
        $this->assertTrue($second['alreadyCompleted']);
        $this->assertSame(1, Progress::query()
            ->where('user_id', $student->id)
            ->where('mission_id', $mission->id)
            ->count());
        $this->assertSame(1, XpTransaction::query()
            ->where('user_id', $student->id)
            ->where('mission_id', $mission->id)
            ->where('type', XpService::TYPE_MISSION_COMPLETED)
            ->count());
    }

    public function test_preexisting_rows_backfill_to_version_one(): void
    {
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);

        $this->assertSame(1, $course->fresh()->version);
        $this->assertSame(1, $mission->fresh()->version);
    }

    public function test_every_versioned_model_declares_its_own_material_policy(): void
    {
        $models = [
            Course::class,
            Section::class,
            Mission::class,
            Assessment::class,
            KnowledgeCheck::class,
            KnowledgeCheckQuestion::class,
            KnowledgeCheckOption::class,
        ];

        foreach ($models as $class) {
            $this->assertContains(
                HasCurriculumVersion::class,
                class_uses_recursive($class),
                "{$class} must version through the shared trait."
            );

            $method = new ReflectionMethod($class, 'curriculumVersionMaterialFields');

            $this->assertSame(
                $class,
                $method->getDeclaringClass()->getName(),
                "{$class} must declare its own material fields, not inherit a silent default."
            );
            $this->assertIsArray((new $class)->curriculumVersionMaterialFields());
        }
    }

    public function test_assessment_status_only_change_does_not_bump_version(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create(array_merge(
            ['course_id' => $course->id],
            $this->assessmentAttributes()
        ));

        app(AdminAssessmentService::class)->update($admin, $course, $assessment, [
            'title' => $assessment->title,
            'description' => $assessment->description,
            'instructions' => $assessment->instructions,
            'passing_score' => $assessment->passing_score,
            'status' => 'locked',
        ]);

        $fresh = $assessment->fresh();
        $this->assertSame('locked', $fresh->status);
        $this->assertSame(1, $fresh->version);
    }
}
