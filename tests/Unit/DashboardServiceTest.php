<?php

namespace Tests\Unit;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\AssessmentService;
use App\Services\DashboardService;
use App\Services\NotificationService;
use App\Services\ValidationService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourseWithMissions(int $missionCount, array $overrides = []): Course
    {
        $course = Course::factory()->create($overrides);

        foreach (range(1, $missionCount) as $order) {
            Mission::factory()->create([
                'course_id' => $course->id,
                'order_num' => $order,
                'title' => "Mission {$order}",
                'points' => $order * 10,
            ]);
        }

        return $course;
    }

    public function test_next_mission_returns_next_uncompleted_mission_in_order(): void
    {
        $user = User::factory()->create();
        $course = $this->makeCourseWithMissions(5);

        foreach ([1, 2] as $order) {
            $mission = $course->missions()->where('order_num', $order)->firstOrFail();
            Progress::factory()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'pts_earned' => 10,
            ]);
        }

        $service = new DashboardService(new XpService, new AssessmentService(new ValidationService, new XpService, new AchievementService(new NotificationService), new NotificationService), new AchievementService(new NotificationService));
        $next = $service->nextMission($user, $course);

        $this->assertNotNull($next);
        $this->assertSame(3, $next->order_num);
        $this->assertSame('Mission 3', $next->title);
    }

    public function test_next_mission_returns_null_when_all_missions_completed(): void
    {
        $user = User::factory()->create();
        $course = $this->makeCourseWithMissions(3);

        foreach ($course->missions as $mission) {
            Progress::factory()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'pts_earned' => 10,
            ]);
        }

        $service = new DashboardService(new XpService, new AssessmentService(new ValidationService, new XpService, new AchievementService(new NotificationService), new NotificationService), new AchievementService(new NotificationService));

        $this->assertNull($service->nextMission($user, $course));
    }

    public function test_current_course_returns_first_in_progress_course_for_user(): void
    {
        $user = User::factory()->create();
        $courseOne = $this->makeCourseWithMissions(3, [
            'order_num' => 1,
            'status' => 'active',
        ]);
        $this->makeCourseWithMissions(2, [
            'order_num' => 2,
            'status' => 'locked',
        ]);

        // Course one's Boss Challenge is not passed, so it stays current.
        $first = $courseOne->missions()->orderBy('order_num')->firstOrFail();
        Progress::factory()->create([
            'user_id' => $user->id,
            'mission_id' => $first->id,
            'pts_earned' => 10,
        ]);

        $service = new DashboardService(new XpService, new AssessmentService(new ValidationService, new XpService, new AchievementService(new NotificationService), new NotificationService), new AchievementService(new NotificationService));

        $this->assertSame($courseOne->id, $service->currentCourse($user)?->id);
    }

    public function test_current_course_skips_courses_whose_assessment_has_been_passed(): void
    {
        $user = User::factory()->create();
        $courseOne = $this->makeCourseWithMissions(3, [
            'order_num' => 1,
            'status' => 'active',
        ]);
        $courseTwo = $this->makeCourseWithMissions(2, [
            'order_num' => 2,
            'status' => 'active',
        ]);

        // Course one is fully worked out AND its Boss Challenge was passed, so
        // it is complete and course two surfaces as the next current course.
        foreach ($courseOne->missions as $mission) {
            Progress::factory()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'pts_earned' => 10,
            ]);
        }
        $this->makeAssessmentAttempts($user, $courseOne, ['passed' => 100]);

        $service = new DashboardService(new XpService, new AssessmentService(new ValidationService, new XpService, new AchievementService(new NotificationService), new NotificationService), new AchievementService(new NotificationService));

        $this->assertSame($courseTwo->id, $service->currentCourse($user)?->id);
    }

    public function test_current_course_stays_on_a_course_whose_missions_are_complete_until_its_assessment_is_passed(): void
    {
        $user = User::factory()->create();
        $courseOne = $this->makeCourseWithMissions(3, [
            'order_num' => 1,
            'status' => 'active',
        ]);
        $this->makeCourseWithMissions(2, [
            'order_num' => 2,
            'status' => 'active',
        ]);

        // All missions are done, which only unlocks the Boss Challenge; the
        // challenge itself has not been attempted yet, so course one stays
        // current. Under Phase 3's 100%-missions definition this would have
        // skipped straight to course two.
        foreach ($courseOne->missions as $mission) {
            Progress::factory()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'pts_earned' => 10,
            ]);
        }
        Assessment::factory()->create(['course_id' => $courseOne->id, 'passing_score' => 70]);

        $service = new DashboardService(new XpService, new AssessmentService(new ValidationService, new XpService, new AchievementService(new NotificationService), new NotificationService), new AchievementService(new NotificationService));

        $this->assertSame($courseOne->id, $service->currentCourse($user)?->id);
    }

    public function test_current_course_ignores_a_failed_retry_after_a_pass(): void
    {
        $user = User::factory()->create();
        $courseOne = $this->makeCourseWithMissions(3, [
            'order_num' => 1,
            'status' => 'active',
        ]);
        $courseTwo = $this->makeCourseWithMissions(2, [
            'order_num' => 2,
            'status' => 'active',
        ]);

        foreach ($courseOne->missions as $mission) {
            Progress::factory()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'pts_earned' => 10,
            ]);
        }
        // Passed once, then opened a retry (US-409) that failed. Completion is
        // history-based, so course one stays complete despite the new verdict.
        $this->makeAssessmentAttempts($user, $courseOne, ['passed' => 100, 'failed' => 40]);

        $service = new DashboardService(new XpService, new AssessmentService(new ValidationService, new XpService, new AchievementService(new NotificationService), new NotificationService), new AchievementService(new NotificationService));

        $this->assertSame($courseTwo->id, $service->currentCourse($user)?->id);
    }

    public function test_current_course_advances_one_course_at_a_time_through_the_sequence(): void
    {
        $user = User::factory()->create();
        $courseOne = $this->makeCourseWithMissions(2, ['order_num' => 1, 'status' => 'active']);
        $courseTwo = $this->makeCourseWithMissions(2, ['order_num' => 2, 'status' => 'active']);
        $courseThree = $this->makeCourseWithMissions(2, ['order_num' => 3, 'status' => 'active']);

        $service = new DashboardService(new XpService, new AssessmentService(new ValidationService, new XpService, new AchievementService(new NotificationService), new NotificationService), new AchievementService(new NotificationService));

        $this->assertSame($courseOne->id, $service->currentCourse($user)?->id);

        // Passing course one advances current to course two, never jumping
        // ahead to course three (§19: sequential, no branching).
        $this->makeAssessmentAttempts($user, $courseOne, ['passed' => 100]);
        $this->assertSame($courseTwo->id, $service->currentCourse($user)?->id);

        $this->makeAssessmentAttempts($user, $courseTwo, ['passed' => 100]);
        $this->assertSame($courseThree->id, $service->currentCourse($user)?->id);
    }

    public function test_current_course_never_surfaces_a_non_active_course(): void
    {
        $user = User::factory()->create();
        $courseOne = $this->makeCourseWithMissions(2, ['order_num' => 1, 'status' => 'active']);
        $this->makeCourseWithMissions(2, ['order_num' => 2, 'status' => 'locked']);

        $this->makeAssessmentAttempts($user, $courseOne, ['passed' => 100]);

        $service = new DashboardService(new XpService, new AssessmentService(new ValidationService, new XpService, new AchievementService(new NotificationService), new NotificationService), new AchievementService(new NotificationService));

        $this->assertNull($service->currentCourse($user));
    }

    public function test_current_course_skips_an_active_course_that_has_no_missions(): void
    {
        $user = User::factory()->create();
        $zeroMission = Course::factory()->create(['order_num' => 1, 'status' => 'active']);
        $real = $this->makeCourseWithMissions(2, ['order_num' => 2, 'status' => 'active']);

        $service = new DashboardService(new XpService, new AssessmentService(new ValidationService, new XpService, new AchievementService(new NotificationService), new NotificationService), new AchievementService(new NotificationService));

        $this->assertNotSame($zeroMission->id, $service->currentCourse($user)?->id);
        $this->assertSame($real->id, $service->currentCourse($user)?->id);
    }

    public function test_current_course_returns_null_when_the_only_active_course_has_no_missions(): void
    {
        $user = User::factory()->create();
        Course::factory()->create(['order_num' => 1, 'status' => 'active']);

        $service = new DashboardService(new XpService, new AssessmentService(new ValidationService, new XpService, new AchievementService(new NotificationService), new NotificationService), new AchievementService(new NotificationService));

        $this->assertNull($service->currentCourse($user));
    }

    public function test_current_course_returns_null_when_every_active_course_has_been_passed(): void
    {
        $user = User::factory()->create();
        $course = $this->makeCourseWithMissions(2, [
            'order_num' => 1,
            'status' => 'active',
        ]);

        foreach ($course->missions as $mission) {
            Progress::factory()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'pts_earned' => 10,
            ]);
        }
        $this->makeAssessmentAttempts($user, $course, ['passed' => 100]);

        $service = new DashboardService(new XpService, new AssessmentService(new ValidationService, new XpService, new AchievementService(new NotificationService), new NotificationService), new AchievementService(new NotificationService));

        $this->assertNull($service->currentCourse($user));
    }

    public function test_assessment_readiness_and_course_progress_share_single_source_of_truth(): void
    {
        $user = User::factory()->create();
        $course = $this->makeCourseWithMissions(4);

        foreach ([1, 2] as $order) {
            $mission = $course->missions()->where('order_num', $order)->firstOrFail();
            Progress::factory()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'pts_earned' => 10,
            ]);
        }

        $service = new DashboardService(new XpService, new AssessmentService(new ValidationService, new XpService, new AchievementService(new NotificationService), new NotificationService), new AchievementService(new NotificationService));
        $progress = $service->courseProgress($user, $course);

        $this->assertSame([2, 4, 50], [$progress['completed'], $progress['total'], $progress['percent']]);
        $this->assertSame(50, $service->assessmentReadiness($progress['completed'], $progress['total']));
    }

    /**
     * Attach an assessment to a course and seed attempt-history rows.
     *
     * @param  array<string, int>  $attempts  status => score, in creation order
     */
    private function makeAssessmentAttempts(User $user, Course $course, array $attempts): void
    {
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'passing_score' => 70,
        ]);

        foreach ($attempts as $status => $score) {
            AssessmentAttempt::factory()->create([
                'assessment_id' => $assessment->id,
                'user_id' => $user->id,
                'status' => $status,
                'score' => $score,
                'passed_at' => $status === 'passed' ? now() : null,
            ]);
        }
    }
}
