<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Notification;
use App\Models\Section;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\DashboardService;
use App\Services\NotificationService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Student learning-event notifications (US-804): mission_completed,
 * course_completed, next_course_unlocked, and achievement_earned all land as
 * notifications created inside the same transaction as the underlying state
 * change (§19), and every payload carries a safe internal route name.
 */
class LearningNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    /**
     * A course with missions and an active Boss Challenge.
     *
     * @param  array<int, array{token: string, points: int}>  $missions
     * @return array{course: Course, assessment: Assessment, missions: array<int, Mission>}
     */
    private function courseWithAssessment(string $name, int $order, array $missions, string $challengeToken): array
    {
        $course = Course::factory()->create([
            'name' => $name,
            'order_num' => $order,
            'status' => 'active',
        ]);

        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1]);

        $built = [];
        foreach ($missions as $index => $mission) {
            $built[] = Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $index + 1,
                'validate_rule' => json_encode([
                    ['type' => 'contains', 'value' => $mission['token'], 'label' => 'has '.$mission['token']],
                ]),
                'points' => $mission['points'],
            ]);
        }

        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'passing_score' => 70,
            'grading_rule' => json_encode([
                ['type' => 'contains', 'value' => $challengeToken, 'label' => 'has '.$challengeToken],
            ]),
        ]);

        return ['course' => $course, 'assessment' => $assessment, 'missions' => $built];
    }

    private function passMission(User $user, Mission $mission, string $code): void
    {
        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => $code])
            ->assertRedirect()
            ->assertSessionHas('mission_success');
    }

    private function passChallenge(User $user, Assessment $assessment, string $code): void
    {
        $this->actingAs($user)->post(route('assessment.start', $assessment));
        $this->actingAs($user)
            ->post(route('assessment.submit', $assessment), ['code' => $code])
            ->assertRedirect()
            ->assertSessionHas('assessment_success');
    }

    private function countNotifications(User $user, string $type, ?string $dedupeKey = null): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->when($dedupeKey !== null, fn ($q): mixed => $q->where('dedupe_key', $dedupeKey))
            ->count();
    }

    public function test_mission_completed_notification_lands_with_a_safe_route_link(): void
    {
        $user = User::factory()->create();

        ['course' => $course, 'missions' => $missions] = $this->courseWithAssessment('HTML Fundamentals', 1, [
            ['token' => '<h1>', 'points' => 30],
        ], 'SYSTEM_ONLINE');

        $this->passMission($user, $missions[0], '<h1>Intro</h1>');

        $notification = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', NotificationService::TYPE_MISSION_COMPLETED)
            ->firstOrFail();

        $this->assertSame("mission_completed:{$missions[0]->id}", $notification->dedupe_key);
        $this->assertSame('CHALLENGE COMPLETED', $notification->title);
        $this->assertSame("Challenge completed: {$missions[0]->title}", $notification->message);
        $this->assertSame(['route' => 'mission.show', 'params' => ['mission' => $missions[0]->id]], $notification->data);
        $this->assertSame(
            route('mission.show', ['mission' => $missions[0]->id]),
            app(NotificationService::class)->linkFor($notification)
        );
    }

    public function test_a_duplicate_mission_completion_request_never_duplicates_the_notification(): void
    {
        $user = User::factory()->create();

        ['missions' => $missions] = $this->courseWithAssessment('HTML Fundamentals', 1, [
            ['token' => '<h1>', 'points' => 30],
        ], 'SYSTEM_ONLINE');

        $this->passMission($user, $missions[0], '<h1>First attempt</h1>');

        $this->actingAs($user)
            ->post(route('mission.submit', $missions[0]), ['code' => '<h1>Duplicate request</h1>'])
            ->assertRedirect();

        $this->assertSame(1, $this->countNotifications(
            $user,
            NotificationService::TYPE_MISSION_COMPLETED,
            "mission_completed:{$missions[0]->id}"
        ));
        $this->assertSame(1, $this->countNotifications($user, NotificationService::TYPE_MISSION_COMPLETED));
    }

    public function test_a_notification_created_inside_a_failed_transaction_rolls_back_with_it(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'order_num' => 1]);

        try {
            DB::transaction(function () use ($user, $mission): void {
                app(NotificationService::class)->create(
                    $user,
                    NotificationService::TYPE_MISSION_COMPLETED,
                    'CHALLENGE COMPLETED',
                    'Mission completed.',
                    "mission_completed:{$mission->id}",
                    NotificationService::payload('mission.show', ['mission' => $mission->id]),
                );

                throw new RuntimeException('boom');
            });
        } catch (RuntimeException) {
            // Expected: the exception forces the transaction to roll back.
        }

        $this->assertSame(0, Notification::query()->where('user_id', $user->id)->count());
    }

    public function test_first_pass_fires_course_completed_and_next_course_unlocked_tied_to_current_course(): void
    {
        $user = User::factory()->create();

        ['course' => $alpha, 'assessment' => $alphaChallenge, 'missions' => $alphaMissions] = $this->courseWithAssessment('HTML Fundamentals', 1, [
            ['token' => '<h1>', 'points' => 30],
        ], 'SYSTEM_ONLINE');

        ['course' => $beta, 'assessment' => $betaChallenge] = $this->courseWithAssessment('CSS Foundations', 2, [
            ['token' => '<p>', 'points' => 50],
        ], 'ALL_CLEAR');

        $this->passMission($user, $alphaMissions[0], '<h1>Intro</h1>');
        $this->passChallenge($user, $alphaChallenge, 'SYSTEM_ONLINE');

        $completed = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', NotificationService::TYPE_COURSE_COMPLETED)
            ->firstOrFail();

        $this->assertSame("course_completed:{$alpha->id}", $completed->dedupe_key);
        $this->assertSame('COURSE COMPLETED', $completed->title);
        $this->assertSame(['route' => 'learning-path', 'params' => ['course' => $alpha->id]], $completed->data);

        $next = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', NotificationService::TYPE_NEXT_COURSE_UNLOCKED)
            ->firstOrFail();

        $this->assertSame("next_course_unlocked:{$beta->id}", $next->dedupe_key);
        $this->assertSame('NEXT COURSE UNLOCKED', $next->title);
        $this->assertSame(['route' => 'learning-path', 'params' => ['course' => $beta->id]], $next->data);
        $this->assertSame(
            route('learning-path', ['course' => $beta->id]),
            app(NotificationService::class)->linkFor($next)
        );

        // The notified "next course" is exactly the dashboard's current course:
        // the two derived-predicate consumers agree.
        $this->assertSame($beta->id, app(DashboardService::class)->currentCourse($user)->id);
    }

    public function test_passing_the_terminal_course_has_no_next_course_notification(): void
    {
        $user = User::factory()->create();

        ['course' => $solo, 'assessment' => $challenge, 'missions' => $missions] = $this->courseWithAssessment('Single Course', 1, [
            ['token' => '<h1>', 'points' => 30],
        ], 'SYSTEM_ONLINE');

        $this->passMission($user, $missions[0], '<h1>Intro</h1>');
        $this->passChallenge($user, $challenge, 'SYSTEM_ONLINE');

        $this->assertSame(1, $this->countNotifications($user, NotificationService::TYPE_COURSE_COMPLETED));
        $this->assertSame(0, $this->countNotifications($user, NotificationService::TYPE_NEXT_COURSE_UNLOCKED));
        $this->assertNull(app(DashboardService::class)->currentCourse($user));
    }

    public function test_a_retry_pass_never_refires_course_completed_or_next_course_unlocked(): void
    {
        $user = User::factory()->create();

        ['course' => $alpha, 'assessment' => $alphaChallenge, 'missions' => $alphaMissions] = $this->courseWithAssessment('HTML Fundamentals', 1, [
            ['token' => '<h1>', 'points' => 30],
        ], 'SYSTEM_ONLINE');

        ['course' => $beta] = $this->courseWithAssessment('CSS Foundations', 2, [
            ['token' => '<p>', 'points' => 50],
        ], 'ALL_CLEAR');

        $this->passMission($user, $alphaMissions[0], '<h1>Intro</h1>');
        $this->passChallenge($user, $alphaChallenge, 'SYSTEM_ONLINE');

        $this->actingAs($user)->post(route('assessment.retry', $alphaChallenge));
        $this->actingAs($user)
            ->post(route('assessment.submit', $alphaChallenge), ['code' => 'SYSTEM_ONLINE'])
            ->assertRedirect()
            ->assertSessionHas('assessment_success');

        $this->assertSame(1, $this->countNotifications(
            $user,
            NotificationService::TYPE_COURSE_COMPLETED,
            "course_completed:{$alpha->id}"
        ));
        $this->assertSame(1, $this->countNotifications(
            $user,
            NotificationService::TYPE_NEXT_COURSE_UNLOCKED,
            "next_course_unlocked:{$beta->id}"
        ));
        $this->assertSame($beta->id, app(DashboardService::class)->currentCourse($user)->id);
    }

    public function test_achievement_earned_notification_is_created_once_per_award(): void
    {
        $user = User::factory()->create();

        ['course' => $course, 'assessment' => $challenge, 'missions' => $missions] = $this->courseWithAssessment('HTML Fundamentals', 1, [
            ['token' => '<h1>', 'points' => 30],
        ], 'SYSTEM_ONLINE');

        $firstChallenge = Achievement::query()->where('slug', AchievementService::SLUG_FIRST_CHALLENGE)->firstOrFail();

        $this->passMission($user, $missions[0], '<h1>Intro</h1>');

        $notification = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', NotificationService::TYPE_ACHIEVEMENT_EARNED)
            ->firstOrFail();

        $this->assertSame("achievement_earned:{$firstChallenge->id}", $notification->dedupe_key);
        $this->assertSame('ACHIEVEMENT EARNED', $notification->title);
        $this->assertSame("Achievement unlocked: {$firstChallenge->name}", $notification->message);
        $this->assertSame(['route' => 'achievements', 'params' => []], $notification->data);

        // The award is idempotent, so a second award attempt leaves one row.
        $this->assertFalse(app(AchievementService::class)->award($user, AchievementService::SLUG_FIRST_CHALLENGE));
        $this->assertSame(1, $this->countNotifications($user, NotificationService::TYPE_ACHIEVEMENT_EARNED));

        // Passing the Boss Challenge earns the course-completion achievements,
        // each announcing itself with its own documented dedupe key.
        $this->passChallenge($user, $challenge, 'SYSTEM_ONLINE');

        $firstCourse = Achievement::query()->where('slug', AchievementService::SLUG_FIRST_COURSE)->firstOrFail();
        $fullClear = Achievement::query()->where('slug', AchievementService::SLUG_FULL_CLEAR)->firstOrFail();

        $this->assertDatabaseHas('the404_notifications', [
            'user_id' => $user->id,
            'type' => NotificationService::TYPE_ACHIEVEMENT_EARNED,
            'dedupe_key' => "achievement_earned:{$firstCourse->id}",
        ]);
        $this->assertDatabaseHas('the404_notifications', [
            'user_id' => $user->id,
            'type' => NotificationService::TYPE_ACHIEVEMENT_EARNED,
            'dedupe_key' => "achievement_earned:{$fullClear->id}",
        ]);
    }

    public function test_notification_center_renders_the_safe_internal_link_for_a_mission_completion(): void
    {
        $user = User::factory()->create();

        ['course' => $course, 'missions' => $missions] = $this->courseWithAssessment('HTML Fundamentals', 1, [
            ['token' => '<h1>', 'points' => 30],
        ], 'SYSTEM_ONLINE');

        $this->passMission($user, $missions[0], '<h1>Intro</h1>');

        $this->actingAs($user)->get(route('notifications'))
            ->assertOk()
            ->assertSee(route('mission.show', ['mission' => $missions[0]->id]));

        // The link resolves: it lands on the very mission that was completed.
        $this->actingAs($user)->get(route('mission.show', ['mission' => $missions[0]->id]))->assertOk();
    }
}
