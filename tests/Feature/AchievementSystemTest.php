<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_challenge_awards_through_the_real_submission_route(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [, $missions] = $this->createCourseWithMissions(1, 'html', 1, $this->passingRule('<h1>'));

        $this->actingAs($user)
            ->post(route('mission.submit', $missions[0]), ['code' => '<h1>Title</h1>'])
            ->assertRedirect();

        $this->assertSame(1, UserAchievement::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_FIRST_CHALLENGE),
        ]);
    }

    public function test_streak_awards_across_three_real_submission_days(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [, $missions] = $this->createCourseWithMissions(1, 'html', 3, $this->passingRule('<h1>'));

        try {
            Carbon::setTestNow('2026-09-01 09:00:00');
            $this->actingAs($user)
                ->post(route('mission.submit', $missions[0]), ['code' => '<h1>Day one</h1>'])
                ->assertRedirect();
            $this->assertSame(0, $this->awardCount($user, AchievementService::SLUG_STREAK_3));

            Carbon::setTestNow('2026-09-02 09:00:00');
            $this->actingAs($user)
                ->post(route('mission.submit', $missions[1]), ['code' => '<h1>Day two</h1>'])
                ->assertRedirect();
            $this->assertSame(0, $this->awardCount($user, AchievementService::SLUG_STREAK_3));

            Carbon::setTestNow('2026-09-03 09:00:00');
            $this->actingAs($user)
                ->post(route('mission.submit', $missions[2]), ['code' => '<h1>Day three</h1>'])
                ->assertRedirect();
        } finally {
            Carbon::setTestNow();
        }

        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_STREAK_3),
        ]);
        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_FIRST_CHALLENGE),
        ]);
        $this->assertSame(2, UserAchievement::query()->where('user_id', $user->id)->count());
    }

    public function test_assessment_pass_awards_first_course_and_full_clear_through_the_real_route(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();
        [$course, $missions] = $this->createCourseWithMissions(1, 'html', 1);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'ok', 'label' => 'Token']]),
            'passing_score' => 70,
        ]);
        $this->complete($user, $missions[0]);

        $this->actingAs($user)
            ->post(route('assessment.start', $assessment))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('assessment.submit', $assessment), ['code' => 'restore ok'])
            ->assertRedirect();

        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_FIRST_COURSE),
        ]);
        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_FULL_CLEAR),
        ]);
        $this->assertSame(2, UserAchievement::query()->where('user_id', $user->id)->count());
    }

    public function test_client_input_cannot_unlock_an_achievement(): void
    {
        $user = User::factory()->create();
        $this->seedCatalog();

        $this->post('/achievements')->assertMethodNotAllowed();

        $this->actingAs($user)
            ->get(route('achievements'))
            ->assertOk();

        [, $missions] = $this->createCourseWithMissions(1, 'html', 1, $this->passingRule('<h1>'));

        $this->actingAs($user)
            ->post(route('mission.submit', $missions[0]), [
                'code' => 'broken',
                'achievement_unlocked' => ['full_clear' => true],
                'achievement' => ['first_challenge' => true],
            ])
            ->assertRedirect();

        $this->assertSame(0, UserAchievement::query()->where('user_id', $user->id)->count());

        $this->actingAs($user)
            ->post(route('mission.submit', $missions[0]), [
                'code' => '<h1>Title</h1>',
                'achievement_unlocked' => ['full_clear' => true],
                'achievement' => ['first_challenge' => true],
            ])
            ->assertRedirect();

        $this->assertSame(1, UserAchievement::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_FIRST_CHALLENGE),
        ]);
        $this->assertDatabaseMissing('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId(AchievementService::SLUG_FULL_CLEAR),
        ]);
    }

    private function seedCatalog(): void
    {
        foreach (AchievementService::slugs() as $slug) {
            Achievement::factory()->create(['slug' => $slug]);
        }
    }

    private function passingRule(string $value): string
    {
        return json_encode([['type' => 'contains', 'value' => $value]]);
    }

    private function achievementId(string $slug): int
    {
        return (int) Achievement::query()->where('slug', $slug)->value('id');
    }

    private function awardCount(User $user, string $slug): int
    {
        return UserAchievement::query()
            ->where('user_id', $user->id)
            ->where('achievement_id', $this->achievementId($slug))
            ->count();
    }

    /**
     * @return array{0: Course, 1: array<int, Mission>}
     */
    private function createCourseWithMissions(int $orderNum, string $type, int $missionCount = 1, ?string $validateRule = null): array
    {
        $course = Course::factory()->create(['status' => 'active', 'type' => $type, 'order_num' => $orderNum]);
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1]);

        $missions = [];
        foreach (range(1, $missionCount) as $order) {
            $missions[] = Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $order,
                'validate_rule' => $validateRule,
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
}
