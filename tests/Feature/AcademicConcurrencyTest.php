<?php

namespace Tests\Feature;

use App\Exceptions\AssessmentAttemptStateException;
use App\Exceptions\UserProtectionException;
use App\Models\Achievement;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\Mission;
use App\Models\Notification;
use App\Models\Progress;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\XpTransaction;
use App\Services\AchievementService;
use App\Services\AssessmentService;
use App\Services\KnowledgeCheckService;
use App\Services\MissionService;
use App\Services\UserService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class AcademicConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function beforeTruncatingDatabase(): void
    {
        if (DB::getDriverName() !== 'mariadb') {
            $this->markTestSkipped('Independent row-lock behavior requires disposable MariaDB.');
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->app) && DB::getDriverName() === 'mariadb') {
            $this->truncateTablesForAllConnections();
        }

        parent::tearDown();
    }

    public function test_concurrent_requests_cannot_purchase_the_same_hint_twice(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create();
        XpTransaction::query()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'amount' => 5,
            'type' => XpService::TYPE_MISSION_COMPLETED,
        ]);

        $results = $this->runPair(
            fn (int $worker): bool => app(XpService::class)->spendHint(
                User::query()->findOrFail($student->id),
                Mission::query()->findOrFail($mission->id),
                1,
            ),
            'the404_xp_transactions',
        );

        $this->assertSame([true, true], $results);
        $this->assertSame(1, XpTransaction::query()
            ->where('user_id', $student->id)
            ->where('mission_id', $mission->id)
            ->where('type', XpService::TYPE_HINT_USED)
            ->count());
        $this->assertSame(0, app(XpService::class)->balance($student));
    }

    public function test_concurrent_solution_reveals_charge_once(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create();
        XpTransaction::query()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'amount' => 50,
            'type' => XpService::TYPE_MISSION_COMPLETED,
        ]);

        $results = $this->runPair(
            fn (int $worker): bool => app(XpService::class)->spendSolutionReveal(
                User::query()->findOrFail($student->id),
                Mission::query()->findOrFail($mission->id),
            ),
            'the404_xp_transactions',
        );

        $this->assertSame([true, true], $results);
        $this->assertSame(1, XpTransaction::query()
            ->where('user_id', $student->id)
            ->where('mission_id', $mission->id)
            ->where('type', XpService::TYPE_SOLUTION_REVEALED)
            ->count());
        $this->assertSame(20, app(XpService::class)->balance($student));
    }

    public function test_concurrent_distinct_hint_spends_cannot_overdraw_xp(): void
    {
        $student = User::factory()->create();
        $missions = Mission::factory()->count(2)->create();
        XpTransaction::query()->create([
            'user_id' => $student->id,
            'mission_id' => $missions[0]->id,
            'amount' => 5,
            'type' => XpService::TYPE_MISSION_COMPLETED,
        ]);

        $results = $this->runPair(
            fn (int $worker): bool => app(XpService::class)->spendHint(
                User::query()->findOrFail($student->id),
                Mission::query()->findOrFail($missions[$worker]->id),
                1,
            ),
            'the404_xp_transactions',
        );

        $this->assertSame(1, count(array_filter($results)));
        $this->assertSame(1, XpTransaction::query()->where('user_id', $student->id)->where('type', XpService::TYPE_HINT_USED)->count());
        $this->assertSame(0, app(XpService::class)->balance($student));
    }

    public function test_concurrent_knowledge_check_starts_share_one_numbered_attempt(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create();
        $check = KnowledgeCheck::factory()->create(['mission_id' => $mission->id]);

        $attemptIds = $this->runPair(
            fn (int $worker): int => app(KnowledgeCheckService::class)->start(
                User::query()->findOrFail($student->id),
                Mission::query()->findOrFail($mission->id),
                KnowledgeCheck::query()->findOrFail($check->id),
            )->id,
            'the404_knowledge_check_attempts',
        );

        $this->assertSame($attemptIds[0], $attemptIds[1]);
        $this->assertSame(1, KnowledgeCheckAttempt::query()->where('user_id', $student->id)->where('knowledge_check_id', $check->id)->count());
    }

    public function test_concurrent_knowledge_check_retries_preserve_history_and_number(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create();
        $check = KnowledgeCheck::factory()->create(['mission_id' => $mission->id]);
        $original = KnowledgeCheckAttempt::factory()->submitted()->create([
            'user_id' => $student->id,
            'knowledge_check_id' => $check->id,
        ]);

        $attemptIds = $this->runPair(
            fn (int $worker): int => app(KnowledgeCheckService::class)->retry(
                User::query()->findOrFail($student->id),
                Mission::query()->findOrFail($mission->id),
                KnowledgeCheck::query()->findOrFail($check->id),
            )->id,
            'the404_knowledge_check_attempts',
            releaseLockedRead: true,
        );

        $this->assertSame($attemptIds[0], $attemptIds[1]);
        $this->assertSame(KnowledgeCheckAttempt::STATUS_SUBMITTED, $original->fresh()->status);
        $this->assertSame([1, 2], KnowledgeCheckAttempt::query()->where('user_id', $student->id)->where('knowledge_check_id', $check->id)->orderBy('attempt_number')->pluck('attempt_number')->all());
    }

    public function test_concurrent_boss_starts_share_one_active_attempt(): void
    {
        [$student, $course, $assessment] = $this->unlockedAssessment();

        $attemptIds = $this->runPair(
            fn (int $worker): int => app(AssessmentService::class)->beginAttempt(
                User::query()->findOrFail($student->id),
                Course::query()->findOrFail($course->id),
            )->id,
            'the404_assessment_attempts',
        );

        $this->assertSame($attemptIds[0], $attemptIds[1]);
        $this->assertSame(1, AssessmentAttempt::query()
            ->where('user_id', $student->id)
            ->where('assessment_id', $assessment->id)
            ->where('status', 'started')
            ->count());
    }

    public function test_concurrent_boss_retries_preserve_one_new_attempt_and_history(): void
    {
        [$student, $course, $assessment] = $this->unlockedAssessment();
        $original = AssessmentAttempt::factory()->create([
            'user_id' => $student->id,
            'assessment_id' => $assessment->id,
            'status' => 'failed',
            'score' => 0,
            'submitted_at' => now(),
        ]);

        $attemptIds = $this->runPair(
            function (int $worker) use ($student, $course): int {
                try {
                    return app(AssessmentService::class)->retryAttempt(
                        User::query()->findOrFail($student->id),
                        Course::query()->findOrFail($course->id),
                    )->id;
                } catch (AssessmentAttemptStateException) {
                    return 0;
                }
            },
            'the404_assessment_attempts',
        );

        $this->assertSame(1, count(array_filter($attemptIds, fn (int $id): bool => $id > 0)));
        $this->assertContains(0, $attemptIds);
        $this->assertSame('failed', $original->fresh()->status);
        $this->assertSame(2, AssessmentAttempt::query()
            ->where('user_id', $student->id)
            ->where('assessment_id', $assessment->id)
            ->count());
    }

    public function test_concurrent_valid_mission_submissions_complete_and_award_once(): void
    {
        $this->seed(AchievementSeeder::class);
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['validate_rule' => '[{"type":"contains","value":"valid code"}]', 'points' => 50]);

        $results = $this->runPair(
            fn (int $worker): bool => app(MissionService::class)->submit(
                User::query()->findOrFail($student->id),
                Mission::query()->findOrFail($mission->id),
                'valid code',
            )['passed'],
            'the404_progress',
        );

        $this->assertSame([true, true], $results);
        $this->assertSame(1, Progress::query()
            ->where('user_id', $student->id)
            ->where('mission_id', $mission->id)
            ->count());
        $this->assertSame(1, XpTransaction::query()
            ->where('user_id', $student->id)
            ->where('mission_id', $mission->id)
            ->where('type', XpService::TYPE_MISSION_COMPLETED)
            ->count());
        $this->assertSame(1, Notification::query()->where('user_id', $student->id)->where('dedupe_key', 'mission_completed:'.$mission->id)->count());
        $this->assertSame(50, app(XpService::class)->balance($student));
    }

    public function test_concurrent_boss_evaluations_grant_first_pass_effects_once(): void
    {
        $this->seed(AchievementSeeder::class);
        [$student, $course, $assessment] = $this->unlockedAssessment();
        $assessment->update(['grading_rule' => json_encode([['type' => 'contains', 'value' => 'hello']]), 'passing_score' => 100]);
        $attempts = AssessmentAttempt::factory()->count(2)->create([
            'user_id' => $student->id,
            'assessment_id' => $assessment->id,
            'status' => 'submitted',
            'code' => 'hello',
            'submitted_at' => now(),
        ]);

        $results = $this->runPair(
            fn (int $worker): bool => app(AssessmentService::class)->evaluateAttempt(
                User::query()->findOrFail($student->id),
                AssessmentAttempt::query()->findOrFail($attempts[$worker]->id),
            )->status === 'passed',
            'the404_assessment_attempts',
        );

        $this->assertSame([true, true], $results);
        $this->assertSame(2, AssessmentAttempt::query()->where('user_id', $student->id)->where('assessment_id', $assessment->id)->where('status', 'passed')->count());
        $this->assertSame(1, XpTransaction::query()->where('user_id', $student->id)->where('assessment_id', $assessment->id)->where('type', XpService::TYPE_ASSESSMENT_COMPLETED)->count());
        $this->assertSame(1, Notification::query()->where('user_id', $student->id)->where('dedupe_key', 'course_completed:'.$course->id)->count());
        $firstCourse = Achievement::query()->where('slug', AchievementService::SLUG_FIRST_COURSE)->firstOrFail();
        $this->assertSame(1, UserAchievement::query()->where('user_id', $student->id)->where('achievement_id', $firstCourse->id)->count());
    }

    #[DataProvider('bossSubmissionStates')]
    public function test_concurrent_boss_submission_or_recovery_commits_one_verdict(string $state): void
    {
        $this->seed(AchievementSeeder::class);
        [$student, $course, $assessment] = $this->unlockedAssessment();
        $assessment->grading_rule = '[{"type":"contains","value":"hello"}]';
        $assessment->save();
        $attempt = AssessmentAttempt::factory()->create([
            'user_id' => $student->id, 'assessment_id' => $assessment->id,
            'status' => $state, 'code' => $state === 'submitted' ? 'hello' : null,
            'submitted_at' => $state === 'submitted' ? now() : null,
        ]);
        $results = $this->runPair(function (int $worker) use ($student, $attempt): bool {
            try {
                return app(AssessmentService::class)->submitAndEvaluateAttempt(
                    User::query()->findOrFail($student->id),
                    AssessmentAttempt::query()->findOrFail($attempt->id),
                    'hello',
                )->status === 'passed';
            } catch (AssessmentAttemptStateException) {
                return false;
            }
        }, 'the404_assessment_attempts');
        sort($results);
        $this->assertSame([false, true], $results);
        $this->assertSame('passed', $attempt->fresh()->status);
        $this->assertDatabaseCount('the404_assessment_attempts', 1);
        $this->assertSame(1, XpTransaction::query()->where('assessment_id', $assessment->id)->count());
        $this->assertSame(1, Notification::query()->where('dedupe_key', 'assessment_passed:'.$attempt->id)->count());
    }

    /** @return array<string, array{string}> */
    public static function bossSubmissionStates(): array
    {
        return ['new submission' => ['started'], 'saved submission recovery' => ['submitted']];
    }

    /** @return array{User, Course, Assessment} */
    private function unlockedAssessment(): array
    {
        $student = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id, 'status' => 'active']);
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);

        return [$student, $course, $assessment];
    }

    #[DataProvider('adminFleetMutations')]
    public function test_concurrent_admin_removals_preserve_two_active_admins(array $mutations): void
    {
        $actor = User::factory()->admin()->create();
        $targets = User::factory()->admin()->count(2)->create();
        $results = $this->runPair(function (int $worker) use ($actor, $targets, $mutations): bool {
            $target = User::query()->findOrFail($targets[$worker]->id);
            try {
                app(UserService::class)->update($actor, $target, array_merge(['username' => $target->username, 'name' => $target->name], $mutations[$worker]));

                return true;
            } catch (UserProtectionException) {
                return false;
            }
        }, 'admin-fleet');
        sort($results);
        $this->assertSame([false, true], $results);
        $this->assertSame(2, User::query()->where('role', 'admin')->where('status', 'active')->count());
        $this->assertDatabaseCount('the404_admin_audit', 2);
        $this->assertDatabaseHas('the404_admin_audit', ['result' => 'failed']);
    }

    /** @return array<string, array{array{array<string, string>, array<string, string>}}> */
    public static function adminFleetMutations(): array
    {
        return [
            'demotions' => [[['role' => 'teacher'], ['role' => 'teacher']]],
            'deactivations' => [[['status' => 'inactive'], ['status' => 'inactive']]],
            'mixed' => [[['role' => 'teacher'], ['status' => 'inactive']]],
        ];
    }

    /**
     * @param  callable(int): bool|int  $operation
     * @return list<bool|int>
     */
    private function runPair(callable $operation, string $readTable, bool $releaseLockedRead = false): array
    {
        DB::disconnect();
        $workers = [];

        try {
            for ($index = 0; $index < 2; $index++) {
                $streams = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);

                if ($streams === false) {
                    throw new RuntimeException('Cannot create concurrency test channels.');
                }

                $pid = pcntl_fork();

                if ($pid === -1) {
                    throw new RuntimeException('Cannot fork concurrency test worker.');
                }

                if ($pid === 0) {
                    foreach ($workers as $worker) {
                        fclose($worker['stream']);
                    }

                    fclose($streams[0]);
                    $this->runWorker($streams[1], $operation, $readTable, $index, $releaseLockedRead);
                }

                fclose($streams[1]);
                $workers[] = ['pid' => $pid, 'stream' => $streams[0], 'done' => false];
            }

            $ready = 0;
            $waiting = [];
            $results = [];

            while (count($results) < 2) {
                $read = array_column(array_filter($workers, fn (array $worker): bool => ! $worker['done']), 'stream');
                $write = null;
                $except = null;

                if (stream_select($read, $write, $except, 20) < 1) {
                    throw new RuntimeException('Timed out waiting for concurrency test workers.');
                }

                foreach ($read as $stream) {
                    $index = array_search($stream, array_column($workers, 'stream'), true);
                    $message = fgets($stream);

                    if ($index === false || $message === false) {
                        throw new RuntimeException('A concurrency test worker closed unexpectedly.');
                    }

                    $message = trim($message);

                    if ($message === 'READY') {
                        $ready++;

                        if ($ready === 2) {
                            foreach ($workers as $worker) {
                                fwrite($worker['stream'], "BEGIN\n");
                            }
                        }
                    } elseif ($message === 'LOCK') {
                        fwrite($stream, "CONTINUE\n");
                    } elseif ($message === 'READ') {
                        $waiting[] = $stream;

                        if (count($waiting) === 2) {
                            foreach ($waiting as $blocked) {
                                fwrite($blocked, "CONTINUE\n");
                            }
                        }
                    } elseif (str_starts_with($message, 'RESULT ')) {
                        $result = json_decode(substr($message, 7), true, flags: JSON_THROW_ON_ERROR);

                        if (! $result['ok']) {
                            throw new RuntimeException($result['error']);
                        }

                        $results[$index] = $result['value'];
                        $workers[$index]['done'] = true;
                    } else {
                        throw new RuntimeException('Unexpected concurrency test message: '.$message);
                    }
                }
            }

            ksort($results);

            return array_values($results);
        } finally {
            foreach ($workers as $worker) {
                if (is_resource($worker['stream']) && ! feof($worker['stream'])) {
                    @fwrite($worker['stream'], "CONTINUE\n");
                }
                fclose($worker['stream']);
                pcntl_waitpid($worker['pid'], $status);
            }

            DB::reconnect();
        }
    }

    /** @param resource $stream */
    private function runWorker($stream, callable $operation, string $readTable, int $index, bool $releaseLockedRead): never
    {
        pcntl_alarm(15);

        try {
            DB::purge();
            DB::reconnect();
            $sawUserLock = false;
            $paused = false;

            DB::listen(function (QueryExecuted $query) use ($stream, $readTable, $releaseLockedRead, &$sawUserLock, &$paused): void {
                $sql = strtolower($query->sql);

                if (str_contains($sql, 'the404_users') && str_contains($sql, 'for update')) {
                    $sawUserLock = true;
                    fwrite($stream, "LOCK\n");
                    fgets($stream);

                    return;
                }

                $matchesRead = $readTable === 'admin-fleet'
                    ? str_contains($sql, 'count(') && str_contains($sql, 'the404_users')
                    : str_contains($sql, $readTable);

                if (! $sawUserLock && ! $paused && $matchesRead && str_starts_with($sql, 'select')) {
                    $paused = true;
                    fwrite($stream, $releaseLockedRead && str_contains($sql, 'for update') ? "LOCK\n" : "READ\n");
                    fgets($stream);
                }
            });

            fwrite($stream, "READY\n");
            fgets($stream);
            $value = $operation($index);
            fwrite($stream, 'RESULT '.json_encode(['ok' => true, 'value' => $value], JSON_THROW_ON_ERROR)."\n");
        } catch (Throwable $exception) {
            fwrite($stream, 'RESULT '.json_encode(['ok' => false, 'error' => $exception->getMessage()], JSON_THROW_ON_ERROR)."\n");
        }

        fclose($stream);
        exit(0);
    }
}
