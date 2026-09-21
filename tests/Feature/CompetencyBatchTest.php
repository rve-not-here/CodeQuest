<?php

namespace Tests\Feature;

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
use App\Services\CompetencyService;
use App\Services\MissionService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batch competency parity (US-1004 correction). overviewForStudents() must
 * reproduce the authoritative single-student overview() exactly for every
 * evidence shape, while executing bounded queries.
 */
class CompetencyBatchTest extends TestCase
{
    use RefreshDatabase;

    private static int $orderSequence = 13000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    private function course(string $slug): Course
    {
        self::$orderSequence++;

        $course = new Course([
            'slug' => $slug,
            'name' => 'Course '.self::$orderSequence,
            'type' => 'html',
            'status' => 'active',
            'order_num' => self::$orderSequence,
        ]);
        $course->save();

        return $course;
    }

    private function mission(Course $course, string $title): Mission
    {
        self::$orderSequence++;

        $section = new Section([
            'course_id' => $course->id,
            'order_num' => self::$orderSequence,
            'title' => 'Section '.self::$orderSequence,
        ]);
        $section->save();

        $mission = new Mission([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => self::$orderSequence,
            'title' => $title,
            'difficulty' => 'EASY',
            'points' => 50,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'version' => 1,
        ]);
        $mission->save();

        return $mission->fresh();
    }

    private function assessment(Course $course): Assessment
    {
        return Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'passing_score' => 70,
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'SIGNAL']]),
        ]);
    }

    private function complete(User $user, Mission $mission): void
    {
        $result = app(MissionService::class)->submit($user, $mission, '<h1>Title</h1>');
        $this->assertTrue($result['passed']);
    }

    private function attemptAssessment(User $user, Course $course, string $code): void
    {
        $service = app(AssessmentService::class);
        $attempt = $service->beginAttempt($user, $course);
        $service->submitAttempt($user, $attempt, $code);
        $service->evaluateAttempt($user, $attempt);
    }

    private function answerCheck(User $user, Mission $mission): void
    {
        $check = KnowledgeCheck::factory()->create(['mission_id' => $mission->id, 'status' => 'published']);
        $question = KnowledgeCheckQuestion::factory()->create(['knowledge_check_id' => $check->id]);
        KnowledgeCheckOption::factory()->create([
            'knowledge_check_question_id' => $question->id,
            'order_num' => 1,
            'is_correct' => false,
        ]);
        $correct = KnowledgeCheckOption::factory()->create([
            'knowledge_check_question_id' => $question->id,
            'order_num' => 2,
            'is_correct' => true,
        ]);

        $this->actingAs($user)->post(route('knowledge-check.start', [$mission, $check]))->assertRedirect();
        $attempt = KnowledgeCheckAttempt::query()
            ->where('user_id', $user->id)
            ->where('knowledge_check_id', $check->id)
            ->sole();
        $this->actingAs($user)->post(
            route('knowledge-check.submit', [$mission, $check, $attempt]),
            ['answers' => [$question->id => $correct->id]],
        )->assertRedirect();
    }

    /**
     * @return array{courseA: Course, courseB: Course, students: array<string, User>}
     */
    private function evidenceFixture(): array
    {
        $students = [
            'idle' => User::factory()->create(['role' => 'student']),
            'partial' => User::factory()->create(['role' => 'student']),
            'ready' => User::factory()->create(['role' => 'student']),
            'failed' => User::factory()->create(['role' => 'student']),
            'passed' => User::factory()->create(['role' => 'student']),
            'other_course' => User::factory()->create(['role' => 'student']),
        ];

        $courseA = $this->course('ca');
        $courseB = $this->course('cb');
        $first = $this->mission($courseA, 'Alpha One');
        $second = $this->mission($courseA, 'Alpha Two');
        $other = $this->mission($courseB, 'Beta One');
        $this->assessment($courseA);
        $this->assessment($courseB);

        $this->complete($students['partial'], $first);
        $this->complete($students['ready'], $first);
        $this->complete($students['ready'], $second);
        $this->complete($students['failed'], $first);
        $this->complete($students['failed'], $second);
        $this->attemptAssessment($students['failed'], $courseA, 'NOPE');
        $this->complete($students['passed'], $first);
        $this->complete($students['passed'], $second);
        $this->attemptAssessment($students['passed'], $courseA, 'SIGNAL answer');
        $this->complete($students['other_course'], $other);

        // KC evidence feeds skill scoring, never the course overview rows.
        $this->answerCheck($students['partial'], $first);

        return compact('courseA', 'courseB', 'students');
    }

    private function assertRowsEqual(array $expected, array $actual): void
    {
        $this->assertSame($expected['course']->id, $actual['course']->id);
        $this->assertSame($expected['name'], $actual['name']);
        $this->assertSame($expected['state'], $actual['state']);
        $this->assertSame($expected['completedMissions'], $actual['completedMissions']);
        $this->assertSame($expected['totalMissions'], $actual['totalMissions']);
        $this->assertSame($expected['percent'], $actual['percent']);
        $this->assertSame($expected['wrongSubmissions'], $actual['wrongSubmissions']);
        $this->assertSame($expected['attempts'], $actual['attempts']);
        $this->assertSame($expected['challengePassed'], $actual['challengePassed']);
    }

    public function test_batch_matches_single_student_for_every_evidence_shape(): void
    {
        ['courseA' => $courseA, 'courseB' => $courseB, 'students' => $students] = $this->evidenceFixture();

        $service = app(CompetencyService::class);
        $users = collect(array_values($students));
        $batch = $service->overviewForStudents($users);

        $this->assertSame($users->pluck('id')->sort()->values()->all(), $batch->keys()->sort()->values()->all());

        foreach ($students as $label => $student) {
            $single = $service->overview($student);
            $rows = $batch->get($student->id);

            $this->assertCount($single->count(), $rows, "row count for {$label}");

            foreach ($rows as $index => $row) {
                $this->assertRowsEqual($single[$index], $row);
            }
        }

        // Spot-check the states the evidence implies.
        $byLabel = [];
        foreach ($students as $label => $student) {
            $byLabel[$label] = $batch->get($student->id)->firstWhere('course.id', $courseA->id)['state'];
        }
        $this->assertSame('not_started', $byLabel['idle']);
        $this->assertSame('demonstrated', $byLabel['passed']);

        $otherStates = $batch->get($students['other_course']->id)->firstWhere('course.id', $courseB->id)['state'];
        $this->assertSame('practicing', $otherStates);
    }

    public function test_batch_honors_course_scope_like_single_path(): void
    {
        ['courseA' => $courseA, 'students' => $students] = $this->evidenceFixture();

        $service = app(CompetencyService::class);
        $users = collect(array_values($students));
        $scope = collect([$courseA->id]);
        $batch = $service->overviewForStudents($users, $scope);

        foreach ($students as $label => $student) {
            $single = $service->overview($student, $scope);
            $rows = $batch->get($student->id);

            $this->assertCount($single->count(), $rows, "scoped row count for {$label}");

            foreach ($rows as $index => $row) {
                $this->assertRowsEqual($single[$index], $row);
            }
        }
    }

    public function test_batch_with_no_users_or_no_courses_returns_empty(): void
    {
        $service = app(CompetencyService::class);

        $this->assertTrue($service->overviewForStudents(collect())->isEmpty());

        $student = User::factory()->create(['role' => 'student']);
        $rows = $service->overviewForStudents(collect([$student]), collect([999999]));
        $this->assertTrue($rows->get($student->id)->isEmpty());
    }
}
