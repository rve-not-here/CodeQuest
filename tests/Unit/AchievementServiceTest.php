<?php

namespace Tests\Unit;

use App\Models\Achievement;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementServiceTest extends TestCase
{
    use RefreshDatabase;

    private AchievementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AchievementService::class);
    }

    public function test_first_challenge_awards_on_the_first_mission_completion(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [, $missions] = $this->createCourseWithMissions(1, 'html', 2);

        $this->complete($user, $missions[0]);
        $this->service->evaluateMissionCompletion($user);

        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_FIRST_CHALLENGE),
        ]);
    }

    public function test_first_challenge_is_not_re_awarded_on_a_later_mission(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [, $missions] = $this->createCourseWithMissions(1, 'html', 2);

        $this->complete($user, $missions[0]);
        $this->service->evaluateMissionCompletion($user);
        $this->complete($user, $missions[1]);
        $this->service->evaluateMissionCompletion($user);

        $this->assertSame(1, $this->awardCount($user, AchievementService::SLUG_FIRST_CHALLENGE));
    }

    public function test_streak_awards_after_three_consecutive_days(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [, $missions] = $this->createCourseWithMissions(1, 'html', 3);

        $this->complete($user, $missions[0], today()->subDays(2));
        $this->complete($user, $missions[1], today()->subDay());
        $this->complete($user, $missions[2], today());

        $this->service->evaluateMissionCompletion($user);

        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_STREAK_3),
        ]);
    }

    public function test_a_gap_breaks_the_streak(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [, $missions] = $this->createCourseWithMissions(1, 'html', 3);

        $this->complete($user, $missions[0], today()->subDays(3));
        $this->complete($user, $missions[1], today()->subDays(2));
        $this->complete($user, $missions[2], today());

        $this->service->evaluateMissionCompletion($user);

        $this->assertSame(1, $this->service->currentStreak($user));
        $this->assertSame(0, $this->awardCount($user, AchievementService::SLUG_STREAK_3));
    }

    public function test_the_streak_counts_the_current_run_over_an_older_one(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [, $missions] = $this->createCourseWithMissions(1, 'html', 6);

        $this->complete($user, $missions[0], today()->subDays(8));
        $this->complete($user, $missions[1], today()->subDays(7));
        $this->complete($user, $missions[2], today()->subDays(6));
        $this->complete($user, $missions[3], today()->subDays(2));
        $this->complete($user, $missions[4], today()->subDay());
        $this->complete($user, $missions[5], today());

        $this->assertSame(3, $this->service->currentStreak($user));

        $this->service->evaluateMissionCompletion($user);

        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_STREAK_3),
        ]);
    }

    public function test_two_consecutive_days_do_not_award_the_streak(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [, $missions] = $this->createCourseWithMissions(1, 'html', 2);

        $this->complete($user, $missions[0], today()->subDay());
        $this->complete($user, $missions[1], today());

        $this->service->evaluateMissionCompletion($user);

        $this->assertSame(0, $this->awardCount($user, AchievementService::SLUG_STREAK_3));
    }

    public function test_first_course_awards_on_the_first_passed_attempt(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [$course, $missions] = $this->createCourseWithMissions(1, 'html', 2);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $missions[0]);
        $this->complete($user, $missions[1]);
        $this->attempt($user, $assessment, 'passed');

        $this->service->evaluateAssessmentPass($user);

        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_FIRST_COURSE),
        ]);
    }

    public function test_first_course_is_not_re_awarded_after_a_second_course_passes(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [$courseA, $missionsA] = $this->createCourseWithMissions(1, 'html', 1);
        [$courseB, $missionsB] = $this->createCourseWithMissions(2, 'css', 1);
        $assessmentA = Assessment::factory()->create(['course_id' => $courseA->id]);
        $assessmentB = Assessment::factory()->create(['course_id' => $courseB->id]);

        $this->complete($user, $missionsA[0]);
        $this->attempt($user, $assessmentA, 'passed');
        $this->service->evaluateAssessmentPass($user);

        $this->assertSame(1, $this->awardCount($user, AchievementService::SLUG_FIRST_COURSE));

        $this->complete($user, $missionsB[0]);
        $this->attempt($user, $assessmentB, 'passed');
        $this->service->evaluateAssessmentPass($user);

        $this->assertSame(1, $this->awardCount($user, AchievementService::SLUG_FIRST_COURSE));
    }

    public function test_full_clear_awards_when_every_active_course_is_passed(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [$courseA, $missionsA] = $this->createCourseWithMissions(1, 'html', 1);
        [$courseB, $missionsB] = $this->createCourseWithMissions(2, 'css', 1);
        $assessmentA = Assessment::factory()->create(['course_id' => $courseA->id]);
        $assessmentB = Assessment::factory()->create(['course_id' => $courseB->id]);

        $this->complete($user, $missionsA[0]);
        $this->complete($user, $missionsB[0]);
        $this->attempt($user, $assessmentA, 'passed');
        $this->attempt($user, $assessmentB, 'passed');

        $this->service->evaluateAssessmentPass($user);

        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_FULL_CLEAR),
        ]);
    }

    public function test_full_clear_is_not_awarded_while_a_course_is_outstanding(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [$courseA, $missionsA] = $this->createCourseWithMissions(1, 'html', 1);
        [$courseB, $missionsB] = $this->createCourseWithMissions(2, 'css', 1);
        $assessmentA = Assessment::factory()->create(['course_id' => $courseA->id]);
        $assessmentB = Assessment::factory()->create(['course_id' => $courseB->id]);

        $this->complete($user, $missionsA[0]);
        $this->attempt($user, $assessmentA, 'passed');

        $this->service->evaluateAssessmentPass($user);

        $this->assertSame(0, $this->awardCount($user, AchievementService::SLUG_FULL_CLEAR));
        $this->assertCount(0, $assessmentB->attempts);
    }

    public function test_full_clear_ignores_zero_mission_and_locked_courses(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [$courseA, $missionsA] = $this->createCourseWithMissions(1, 'html', 1);
        $assessmentA = Assessment::factory()->create(['course_id' => $courseA->id]);

        Course::factory()->create(['status' => 'active', 'order_num' => 2, 'name' => 'Empty Area']);
        $courseLocked = Course::factory()->create(['status' => 'locked', 'order_num' => 3, 'name' => 'Locked']);
        $assessmentLocked = Assessment::factory()->create(['course_id' => $courseLocked->id]);
        $section = Section::factory()->create(['course_id' => $courseLocked->id, 'order_num' => 1]);
        Mission::factory()->create(['course_id' => $courseLocked->id, 'section_id' => $section->id, 'order_num' => 1]);

        $this->complete($user, $missionsA[0]);
        $this->attempt($user, $assessmentA, 'passed');

        $this->service->evaluateAssessmentPass($user);

        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_FULL_CLEAR),
        ]);
    }

    public function test_award_is_idempotent_and_the_unique_constraint_holds(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();

        $this->assertTrue($this->service->award($user, AchievementService::SLUG_FIRST_CHALLENGE));
        $this->assertFalse($this->service->award($user, AchievementService::SLUG_FIRST_CHALLENGE));
        $this->assertSame(1, $this->awardCount($user, AchievementService::SLUG_FIRST_CHALLENGE));

        $this->expectException(QueryException::class);

        UserAchievement::query()->create([
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_FIRST_CHALLENGE),
        ]);
    }

    public function test_award_throws_when_the_catalog_has_no_matching_slug(): void
    {
        $user = User::factory()->create();

        $this->expectException(\RuntimeException::class);

        $this->service->award($user, 'no_such_achievement');
    }

    public function test_catalog_annotates_awarded_state(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();

        $this->service->award($user, AchievementService::SLUG_FIRST_CHALLENGE);

        $rows = $this->service->catalog($user);

        $awarded = $rows->firstWhere('slug', AchievementService::SLUG_FIRST_CHALLENGE);
        $held = $rows->firstWhere('slug', AchievementService::SLUG_FULL_CLEAR);

        $this->assertSame('First Challenge', $awarded['name']);
        $this->assertTrue($awarded['awarded']);
        $this->assertNotNull($awarded['unlocked_at']);
        $this->assertFalse($held['awarded']);
        $this->assertNull($held['unlocked_at']);
    }

    private function seedCatalog(): void
    {
        $names = [
            AchievementService::SLUG_FIRST_CHALLENGE => 'First Challenge',
            AchievementService::SLUG_FIRST_COURSE => 'First Course Complete',
            AchievementService::SLUG_STREAK_3 => 'Operator Streak',
            AchievementService::SLUG_FULL_CLEAR => 'System Cleared',
        ];

        foreach ($names as $slug => $name) {
            Achievement::factory()->create(['slug' => $slug, 'name' => $name]);
        }
    }

    private function achievementId(string $slug): int
    {
        return (int) Achievement::query()->where('slug', $slug)->value('id');
    }

    /**
     * @return array{0: Course, 1: array<int, Mission>}
     */
    private function createCourseWithMissions(int $orderNum, string $type, int $missionCount = 1): array
    {
        $course = Course::factory()->create(['status' => 'active', 'type' => $type, 'order_num' => $orderNum]);
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

    private function complete(User $user, Mission $mission, string|Carbon $when = 'now'): void
    {
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => $when instanceof Carbon ? $when : Carbon::parse($when),
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

    private function awardCount(User $user, string $slug): int
    {
        $achievementId = $this->achievementId($slug);

        return UserAchievement::query()
            ->where('user_id', $user->id)
            ->where('achievement_id', $achievementId)
            ->count();
    }
}
