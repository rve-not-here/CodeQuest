<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\KnowledgeCheckResponse;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\MissionService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class JournalSchemaAcceptanceTest extends TestCase
{
    use DatabaseMigrations;

    private const JOURNAL = 'the404_mission_submission_results';

    private const MIGRATION = 'database/migrations/2026_10_07_020341_create_the404_mission_submission_results_table.php';

    public function test_fresh_schema_supports_failed_replay_and_one_completion_reward(): void
    {
        $this->seed(AchievementSeeder::class);
        $student = User::factory()->create();
        $mission = $this->challenge();
        $this->credit($student, Mission::factory()->create());

        $this->assertSubmissionLifecycle($student, $mission);
    }

    public function test_populated_pre_journal_upgrade_preserves_academic_history_and_accepts_new_work(): void
    {
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION, '--force' => true])->assertSuccessful();
        $this->assertFalse(Schema::hasTable(self::JOURNAL));
        $this->seed(AchievementSeeder::class);
        [$student, $mission] = $this->academicHistory();
        $before = $this->historySnapshot();

        $this->artisan('migrate', ['--path' => self::MIGRATION, '--force' => true])->assertSuccessful();

        $this->assertTrue(Schema::hasTable(self::JOURNAL));
        $this->assertSame($before, $this->historySnapshot());
        $this->assertDatabaseCount(self::JOURNAL, 0);
        $this->assertSubmissionLifecycle($student, $mission);
    }

    public function test_rollback_discards_replay_cache_preserves_history_and_refuses_new_completion(): void
    {
        $this->seed(AchievementSeeder::class);
        [$student, $mission] = $this->academicHistory();
        app(MissionService::class)->submit($student, $mission, 'wrong', (string) Str::uuid());
        $before = $this->historySnapshot();
        $this->assertDatabaseCount(self::JOURNAL, 1);

        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION, '--force' => true])->assertSuccessful();
        $result = app(MissionService::class)->submit($student, $mission, 'valid', (string) Str::uuid());

        $this->assertFalse(Schema::hasTable(self::JOURNAL));
        $this->assertFalse($result['passed']);
        $this->assertSame(0, $result['xpAwarded']);
        $this->assertStringContainsString('temporarily unavailable', $result['failures'][0]);
        $this->assertSame($before, $this->historySnapshot());
    }

    public function test_journal_unique_and_parent_constraints_protect_replay_ownership(): void
    {
        $student = User::factory()->create();
        $mission = $this->challenge();
        $row = [
            'user_id' => $student->id, 'mission_id' => $mission->id,
            'mission_version' => $mission->version, 'submission_id' => (string) Str::uuid(),
            'source_hash' => hash('sha256', 'wrong'), 'failures' => '["Incorrect output"]',
        ];
        DB::table(self::JOURNAL)->insert($row);

        $this->assertConstraintRefuses(fn () => DB::table(self::JOURNAL)->insert($row));
        $this->assertConstraintRefuses(fn () => DB::table('the404_users')->where('id', $student->id)->delete());
        $this->assertConstraintRefuses(fn () => DB::table('the404_missions')->where('id', $mission->id)->delete());
        $this->assertConstraintRefuses(fn () => DB::table(self::JOURNAL)->insert([
            ...$row, 'user_id' => $student->id + 1000000, 'submission_id' => (string) Str::uuid(),
        ]));
        $this->assertConstraintRefuses(fn () => DB::table(self::JOURNAL)->insert([
            ...$row, 'mission_id' => $mission->id + 1000000, 'submission_id' => (string) Str::uuid(),
        ]));

        $this->assertDatabaseCount(self::JOURNAL, 1);
        $this->assertNotNull(DB::table(self::JOURNAL)->value('created_at'));
        $this->assertModelExists($student);
        $this->assertModelExists($mission);
    }

    public function test_journal_insert_failure_rolls_back_penalty_and_activity(): void
    {
        $student = User::factory()->create();
        $mission = $this->challenge();
        $this->credit($student, Mission::factory()->create());
        $before = $this->historySnapshot();
        $trigger = DB::connection()->getDriverName() === 'sqlite'
            ? 'CREATE TRIGGER reject_journal_insert BEFORE INSERT ON '.self::JOURNAL." BEGIN SELECT RAISE(ABORT, 'journal unavailable'); END"
            : 'CREATE TRIGGER reject_journal_insert BEFORE INSERT ON '.self::JOURNAL." FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'journal unavailable'";
        DB::unprepared($trigger);

        try {
            app(MissionService::class)->submit($student, $mission, 'wrong', (string) Str::uuid());
            $this->fail('A failed journal write must not commit academic effects.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('journal unavailable', $exception->getMessage());
        } finally {
            DB::unprepared('DROP TRIGGER reject_journal_insert');
        }

        $this->assertSame($before, $this->historySnapshot());
        $this->assertSame(150, app(XpService::class)->balance($student));
        $this->assertDatabaseCount(self::JOURNAL, 0);
        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id]);
    }

    private function assertSubmissionLifecycle(User $student, Mission $mission): void
    {
        $service = app(MissionService::class);
        $before = app(XpService::class)->balance($student);
        $operation = (string) Str::uuid();
        $failed = $service->submit($student, $mission, 'wrong', $operation);
        $replayed = $service->submit($student, $mission, 'wrong', $operation);

        $this->assertFalse($failed['passed']);
        $this->assertSame($failed['failures'], $replayed['failures']);
        $this->assertSame($before - 10, app(XpService::class)->balance($student));
        $this->assertDatabaseCount(self::JOURNAL, 1);
        $this->assertDatabaseHas(self::JOURNAL, [
            'user_id' => $student->id, 'mission_id' => $mission->id,
            'mission_version' => $mission->version, 'submission_id' => $operation,
            'source_hash' => hash('sha256', 'wrong'),
        ]);
        $passed = $service->submit($student, $mission, 'valid', (string) Str::uuid());
        $repeated = $service->submit($student, $mission, 'valid', (string) Str::uuid());

        $this->assertTrue($passed['passed']);
        $this->assertSame(50, $passed['xpAwarded']);
        $this->assertTrue($repeated['alreadyCompleted']);
        $this->assertSame(0, $repeated['xpAwarded']);
        $this->assertSame($before + 40, app(XpService::class)->balance($student));
        $this->assertSame(1, Progress::query()->where('user_id', $student->id)->where('mission_id', $mission->id)->count());
        $this->assertSame(1, XpTransaction::query()->where('user_id', $student->id)->where('mission_id', $mission->id)
            ->where('type', XpService::TYPE_MISSION_COMPLETED)->where('amount', 50)->count());
        $this->assertDatabaseCount(self::JOURNAL, 1);
    }

    private function challenge(): Mission
    {
        return Mission::factory()->create([
            'points' => 50,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'valid']], JSON_THROW_ON_ERROR),
        ]);
    }

    private function credit(User $student, Mission $mission): void
    {
        XpTransaction::query()->create([
            'user_id' => $student->id, 'mission_id' => $mission->id,
            'type' => XpService::TYPE_MISSION_COMPLETED, 'amount' => 150,
        ]);
    }

    /** @return array{User, Mission} */
    private function academicHistory(): array
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $completed = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id, 'points' => 150]);
        Progress::factory()->create([
            'user_id' => $student->id, 'mission_id' => $completed->id, 'pts_earned' => 150,
            'completed_at' => '2026-10-01 12:00:00', 'mission_version' => 1, 'skill_keys' => ['html.structure'],
        ]);
        $this->credit($student, $completed);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);
        AssessmentAttempt::factory()->create([
            'user_id' => $student->id, 'assessment_id' => $assessment->id, 'status' => 'passed',
            'score' => 90, 'code' => '<p>Historical solution</p>', 'passed_at' => '2026-10-01 12:10:00',
            'submitted_at' => '2026-10-01 12:10:00', 'created_at' => '2026-10-01 12:05:00',
        ]);
        $check = KnowledgeCheck::factory()->create(['mission_id' => $completed->id]);
        $question = KnowledgeCheckQuestion::factory()->create(['knowledge_check_id' => $check->id]);
        $option = KnowledgeCheckOption::factory()->correct()->create(['knowledge_check_question_id' => $question->id]);
        $attempt = KnowledgeCheckAttempt::factory()->submitted()->create(['user_id' => $student->id, 'knowledge_check_id' => $check->id]);
        KnowledgeCheckResponse::factory()->create([
            'knowledge_check_attempt_id' => $attempt->id, 'knowledge_check_question_id' => $question->id,
            'selected_option_id' => $option->id, 'correct_option_id' => $option->id, 'is_correct' => true,
            'prompt_snapshot' => 'Historical prompt', 'selected_option_snapshot' => 'Historical answer',
            'correct_option_snapshot' => 'Historical answer', 'explanation_snapshot' => 'Historical explanation',
            'skill_keys' => ['html.structure'],
        ]);

        return [$student, $this->challenge()];
    }

    /** @return array<string, array{count: int, checksum: string}> */
    private function historySnapshot(): array
    {
        $snapshot = [];
        foreach ([
            'the404_users', 'the404_courses', 'the404_sections', 'the404_missions',
            'the404_progress', 'the404_xp_transactions', 'the404_activity',
            'the404_assessments', 'the404_assessment_attempts', 'the404_knowledge_checks',
            'the404_knowledge_check_questions', 'the404_knowledge_check_options',
            'the404_knowledge_check_attempts', 'the404_knowledge_check_responses',
            'the404_notifications', 'the404_user_achievements',
        ] as $table) {
            $rows = DB::table($table)->orderBy('id')->get()->all();
            $snapshot[$table] = ['count' => count($rows), 'checksum' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
        }

        return $snapshot;
    }

    private function assertConstraintRefuses(\Closure $operation): void
    {
        try {
            $operation();
            $this->fail('The journal database constraint must reject this mutation.');
        } catch (QueryException $exception) {
            $this->assertContains((string) $exception->getCode(), ['23000', '19']);
        }
    }
}
