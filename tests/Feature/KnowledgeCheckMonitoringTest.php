<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\Mission;
use App\Models\User;
use App\Services\ClassroomAccessService;
use App\Services\KnowledgeCheckService;
use App\Services\TeacherDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

class KnowledgeCheckMonitoringTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_knowledge_check_only_student_is_active_in_teacher_dashboard(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        $check = KnowledgeCheck::factory()->create(['mission_id' => $mission->id]);
        $this->classroomFor($teacher, [$student], [$course]);
        KnowledgeCheckAttempt::factory()->submitted()->create(['user_id' => $student->id, 'knowledge_check_id' => $check->id]);

        $scope = app(ClassroomAccessService::class)->scopesFor($teacher);
        $dashboard = app(TeacherDashboardService::class)->overview(8, $scope['studentIds'], $scope['courseIds'], $scope['byStudent']);

        $this->assertSame([KnowledgeCheckService::ACTIVITY_TYPE_COMPLETED], $dashboard['recent_activity']->pluck('type')->all());
        $this->assertSame(1, $dashboard['metrics']['active_students']);
        $this->assertSame(1, app(TeacherDashboardService::class)->countActiveStudents());
        $this->assertDatabaseCount('the404_progress', 0);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
        $this->actingAs($teacher)->get(route('students'))->assertOk()
            ->assertViewHas('dashboardMetrics', fn (array $metrics): bool => $metrics['active_students'] === 1);
    }

    #[DataProvider('inactiveKnowledgeChecks')]
    public function test_unsubmitted_or_old_knowledge_checks_do_not_make_a_student_active(string $status, int $daysAgo): void
    {
        $this->travelTo(now()->startOfDay()->addHours(6));
        $student = User::factory()->create();
        $check = KnowledgeCheck::factory()->create();
        KnowledgeCheckAttempt::factory()->create(['user_id' => $student->id, 'knowledge_check_id' => $check->id, 'status' => $status, 'submitted_at' => $status === KnowledgeCheckAttempt::STATUS_SUBMITTED ? now()->subDays($daysAgo) : null]);

        $this->assertSame(0, app(TeacherDashboardService::class)->countActiveStudents(collect([$student->id])));
    }

    /** @return array<string, array{string, int}> */
    public static function inactiveKnowledgeChecks(): array
    {
        return [
            'started' => [KnowledgeCheckAttempt::STATUS_STARTED, 0],
            'outside window' => [KnowledgeCheckAttempt::STATUS_SUBMITTED, 15],
        ];
    }

    public function test_teacher_active_metric_rejects_cross_classroom_student_course_pairs(): void
    {
        $teacher = User::factory()->teacher()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $firstCourse = Course::factory()->create(['order_num' => 1]);
        $secondCourse = Course::factory()->create(['order_num' => 2]);
        $firstMission = Mission::factory()->create(['course_id' => $firstCourse->id]);
        $secondMission = Mission::factory()->create(['course_id' => $secondCourse->id]);
        $firstCheck = KnowledgeCheck::factory()->create(['mission_id' => $firstMission->id]);
        $secondCheck = KnowledgeCheck::factory()->create(['mission_id' => $secondMission->id]);
        $this->classroomFor($teacher, [$first], [$firstCourse]);
        $this->classroomFor($teacher, [$second], [$secondCourse]);
        KnowledgeCheckAttempt::factory()->submitted()->create(['user_id' => $first->id, 'knowledge_check_id' => $secondCheck->id]);
        KnowledgeCheckAttempt::factory()->submitted()->create(['user_id' => $second->id, 'knowledge_check_id' => $firstCheck->id]);
        $scope = app(ClassroomAccessService::class)->scopesFor($teacher);

        $this->assertSame(0, app(TeacherDashboardService::class)->countActiveStudents($scope['studentIds'], $scope['byStudent']));

        KnowledgeCheckAttempt::factory()->submitted()->create(['user_id' => $first->id, 'knowledge_check_id' => $firstCheck->id]);

        $this->assertSame(1, app(TeacherDashboardService::class)->countActiveStudents($scope['studentIds'], $scope['byStudent']));
        $this->assertSame(0, app(TeacherDashboardService::class)->countActiveStudents(collect(), []));
    }

    public function test_active_student_is_counted_once_after_multiple_knowledge_checks(): void
    {
        $student = User::factory()->create();
        $check = KnowledgeCheck::factory()->create();
        KnowledgeCheckAttempt::factory()->submitted()->create(['user_id' => $student->id, 'knowledge_check_id' => $check->id]);
        KnowledgeCheckAttempt::factory()->submitted()->create(['user_id' => $student->id, 'knowledge_check_id' => $check->id, 'attempt_number' => 2]);

        $this->assertSame(1, app(TeacherDashboardService::class)->countActiveStudents());
    }
}
