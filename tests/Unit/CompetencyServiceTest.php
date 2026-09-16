<?php

namespace Tests\Unit;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\CompetencyService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetencyServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompetencyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);

        $this->service = app(CompetencyService::class);
    }

    public function test_overview_names_competencies_after_course_type(): void
    {
        $user = User::factory()->create();
        $this->createCourseWithMissions(1, 'html');
        $this->createCourseWithMissions(2, 'css');
        $this->createCourseWithMissions(3, 'js');

        $this->assertSame(
            ['HTML', 'CSS', 'JavaScript'],
            $this->service->overview($user)->pluck('name')->all(),
        );
    }

    public function test_overview_orders_competencies_by_course_order_num(): void
    {
        $user = User::factory()->create();
        $this->createCourseWithMissions(2, 'css');
        $this->createCourseWithMissions(1, 'html');

        $this->assertSame(
            ['HTML', 'CSS'],
            $this->service->overview($user)->pluck('name')->all(),
        );
    }

    public function test_overview_excludes_zero_mission_and_non_active_courses(): void
    {
        $user = User::factory()->create();
        Course::factory()->create(['status' => 'active', 'order_num' => 1, 'name' => 'Empty Area']);
        $this->createCourseWithMissions(2, 'html');
        $this->createCourseWithMissions(3, 'css', status: 'locked');
        $this->createCourseWithMissions(4, 'js', status: 'draft');

        $rows = $this->service->overview($user);

        $this->assertCount(1, $rows);
        $this->assertSame('HTML', $rows->first()['name']);
    }

    public function test_an_untouched_course_is_not_started(): void
    {
        $user = User::factory()->create();
        $this->createCourseWithMissions(1, 'html', 3);

        $row = $this->service->overview($user)->first();

        $this->assertSame('not_started', $row['state']);
        $this->assertSame(0, $row['completedMissions']);
        $this->assertSame(3, $row['totalMissions']);
        $this->assertSame(0, $row['percent']);
    }

    public function test_a_first_completed_mission_is_developing(): void
    {
        $user = User::factory()->create();
        [, $missions] = $this->createCourseWithMissions(1, 'html', 3);

        $this->complete($user, $missions[0]);

        $this->assertSame('developing', $this->service->overview($user)->first()['state']);
    }

    public function test_a_wrong_submission_without_completions_is_developing(): void
    {
        $user = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, 'html', 3);

        app(XpService::class)->deductWrongSubmission($user, $missions[0]);

        $row = $this->service->overview($user)->first();

        $this->assertSame('developing', $row['state']);
        $this->assertSame(1, $row['wrongSubmissions']);
    }

    public function test_half_the_missions_completed_is_practicing(): void
    {
        $user = User::factory()->create();
        [, $missions] = $this->createCourseWithMissions(1, 'html', 4);

        $this->complete($user, $missions[0]);
        $this->complete($user, $missions[1]);

        $this->assertSame('practicing', $this->service->overview($user)->first()['state']);
    }

    public function test_all_missions_completed_with_challenge_outstanding_is_practicing(): void
    {
        $user = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, 'html', 2);

        $this->complete($user, $missions[0]);
        $this->complete($user, $missions[1]);

        $this->assertSame('practicing', $this->service->overview($user)->first()['state']);
    }

    public function test_a_failed_challenge_attempt_keeps_a_complete_course_practicing(): void
    {
        $user = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, 'html', 2);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $missions[0]);
        $this->complete($user, $missions[1]);
        $this->attempt($user, $assessment, 'failed');

        $this->assertSame('practicing', $this->service->overview($user)->first()['state']);
    }

    public function test_an_attempt_record_alone_promotes_to_practicing_without_completions(): void
    {
        $user = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, 'html', 3);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->attempt($user, $assessment, 'failed');

        $row = $this->service->overview($user)->first();

        $this->assertSame(0, $row['completedMissions']);
        $this->assertSame('practicing', $row['state']);
    }

    public function test_all_missions_and_passed_challenge_is_demonstrated(): void
    {
        $user = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, 'html', 2);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $missions[0]);
        $this->complete($user, $missions[1]);
        $this->attempt($user, $assessment, 'passed');

        $row = $this->service->overview($user)->first();

        $this->assertSame('demonstrated', $row['state']);
        $this->assertTrue($row['challengePassed']);
        $this->assertSame(1, $row['attempts']);
    }

    public function test_overview_is_scoped_to_the_given_user(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, 'html', 2);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($student, $missions[0]);
        $this->attempt($other, $assessment, 'passed');

        $studentRow = $this->service->overview($student)->first();
        $otherRow = $this->service->overview($other)->first();

        $this->assertSame(1, $studentRow['completedMissions']);
        $this->assertFalse($studentRow['challengePassed']);
        $this->assertSame(0, $otherRow['completedMissions']);
        $this->assertTrue($otherRow['challengePassed']);
    }

    public function test_competency_stays_demonstrated_across_a_failed_retry(): void
    {
        $user = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, 'html', 1);
        Assessment::factory()->create([
            'course_id' => $course->id,
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'ok', 'label' => 'Token']]),
            'passing_score' => 70,
        ]);
        $this->complete($user, $missions[0]);

        $assessments = app(AssessmentService::class);

        $attempt = $assessments->beginAttempt($user, $course);
        $assessments->submitAttempt($user, $attempt, 'restore ok');
        $assessments->evaluateAttempt($user, $attempt);

        $this->assertSame('demonstrated', $this->service->overview($user)->first()['state']);

        $retry = $assessments->retryAttempt($user, $course);
        $assessments->submitAttempt($user, $retry, 'broken');
        $assessments->evaluateAttempt($user, $retry);

        $row = $this->service->overview($user)->first();

        $this->assertSame('demonstrated', $row['state']);
        $this->assertSame(2, $row['attempts']);
    }

    /**
     * @return array{0: Course, 1: array<int, Mission>}
     */
    private function createCourseWithMissions(int $orderNum, string $type, int $missionCount = 1, string $status = 'active'): array
    {
        $course = Course::factory()->create(['status' => $status, 'type' => $type, 'order_num' => $orderNum]);
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1]);

        $missions = [];
        foreach (range(1, $missionCount) as $order) {
            $missions[] = Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $order,
            ]);
        }

        return [$course, $missions];
    }

    private function complete(User $user, Mission $mission): void
    {
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => now(),
        ]);
    }

    private function attempt(User $user, Assessment $assessment, string $status): void
    {
        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => $status,
            'passed_at' => $status === 'passed' ? now() : null,
        ]);
    }
}
