<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Notification;
use App\Models\Section;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Assessment-event notifications (US-805): assessment_unlocked,
 * assessment_passed, and assessment_failed land as notifications created
 * inside the same transaction as the evaluation (§19), and every payload
 * carries the safe internal assessment.show route name.
 */
class AssessmentNotificationTest extends TestCase
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

    private function countNotifications(User $user, string $type, ?string $dedupeKey = null): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->when($dedupeKey !== null, fn ($q): mixed => $q->where('dedupe_key', $dedupeKey))
            ->count();
    }

    public function test_first_course_pass_fires_assessment_unlocked_once_with_the_assessment_route_link(): void
    {
        $user = User::factory()->create();

        $built = $this->courseWithAssessment('HTML Fundamentals', 1, [
            ['token' => '<h1>', 'points' => 30],
            ['token' => '<p>', 'points' => 40],
        ], 'SYSTEM_ONLINE');

        $this->passMission($user, $built['missions'][0], '<h1>Intro</h1>');
        $this->passMission($user, $built['missions'][1], '<p>Para</p>');

        $notification = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', NotificationService::TYPE_ASSESSMENT_UNLOCKED)
            ->firstOrFail();

        $this->assertSame("assessment_unlocked:{$built['assessment']->id}", $notification->dedupe_key);
        $this->assertSame('CHALLENGE UNLOCKED', $notification->title);
        $this->assertSame([
            'route' => 'assessment.show',
            'params' => ['assessment' => $built['assessment']->id],
        ], $notification->data);
        $this->assertSame(
            route('assessment.show', ['assessment' => $built['assessment']->id]),
            app(NotificationService::class)->linkFor($notification)
        );

        // Re-submitting the final mission replays the event, which dedupe
        // swallows: one unlock notification either way.
        $this->actingAs($user)
            ->post(route('mission.submit', $built['missions'][1]), ['code' => '<p>Duplicate</p>'])
            ->assertRedirect();

        $this->assertSame(1, $this->countNotifications($user, NotificationService::TYPE_ASSESSMENT_UNLOCKED));
    }

    public function test_failed_and_passed_verdicts_fire_once_each_per_attempt(): void
    {
        $user = User::factory()->create();

        $built = $this->courseWithAssessment('HTML Fundamentals', 1, [
            ['token' => '<h1>', 'points' => 30],
        ], 'SYSTEM_ONLINE');

        $this->passMission($user, $built['missions'][0], '<h1>Intro</h1>');

        $this->actingAs($user)->post(route('assessment.start', $built['assessment']));

        $this->actingAs($user)
            ->post(route('assessment.submit', $built['assessment']), ['code' => 'not the key'])
            ->assertRedirect()
            ->assertSessionHas('assessment_error');

        $failedAttempt = AssessmentAttempt::query()
            ->where('assessment_id', $built['assessment']->id)
            ->firstOrFail();

        $failed = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', NotificationService::TYPE_ASSESSMENT_FAILED)
            ->firstOrFail();

        $this->assertSame("assessment_failed:{$failedAttempt->id}", $failed->dedupe_key);
        $this->assertSame('CHALLENGE FAILED', $failed->title);
        $this->assertSame([
            'route' => 'assessment.show',
            'params' => ['assessment' => $built['assessment']->id],
        ], $failed->data);

        // A retry opens a fresh attempt; the verdict for it keys off its own
        // attempt id, so both verdict rows coexist.
        $this->actingAs($user)->post(route('assessment.retry', $built['assessment']));

        $this->actingAs($user)
            ->post(route('assessment.submit', $built['assessment']), ['code' => 'SYSTEM_ONLINE'])
            ->assertRedirect()
            ->assertSessionHas('assessment_success');

        $passedAttempt = AssessmentAttempt::query()
            ->where('assessment_id', $built['assessment']->id)
            ->where('id', '!=', $failedAttempt->id)
            ->firstOrFail();

        $passed = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', NotificationService::TYPE_ASSESSMENT_PASSED)
            ->firstOrFail();

        $this->assertSame("assessment_passed:{$passedAttempt->id}", $passed->dedupe_key);
        $this->assertSame('CHALLENGE PASSED', $passed->title);
        $this->assertSame([
            'route' => 'assessment.show',
            'params' => ['assessment' => $built['assessment']->id],
        ], $passed->data);
        $this->assertSame(1, $this->countNotifications($user, NotificationService::TYPE_ASSESSMENT_FAILED));
        $this->assertSame(1, $this->countNotifications($user, NotificationService::TYPE_ASSESSMENT_PASSED));
    }

    public function test_a_duplicate_evaluation_request_leaves_a_single_verdict_notification(): void
    {
        $user = User::factory()->create();

        $built = $this->courseWithAssessment('HTML Fundamentals', 1, [
            ['token' => '<h1>', 'points' => 30],
        ], 'SYSTEM_ONLINE');

        $this->passMission($user, $built['missions'][0], '<h1>Intro</h1>');
        $this->actingAs($user)->post(route('assessment.start', $built['assessment']));

        $this->actingAs($user)
            ->post(route('assessment.submit', $built['assessment']), ['code' => 'SYSTEM_ONLINE'])
            ->assertRedirect()
            ->assertSessionHas('assessment_success');

        $attempt = AssessmentAttempt::query()
            ->where('assessment_id', $built['assessment']->id)
            ->firstOrFail();

        // RESEND the same POST for the now-passed attempt: the controller
        // rejects it, so no evaluation runs and no second verdict appears.
        $this->actingAs($user)
            ->post(route('assessment.submit', $built['assessment']), ['code' => 'SYSTEM_ONLINE'])
            ->assertRedirect();

        $this->assertSame(1, $this->countNotifications(
            $user,
            NotificationService::TYPE_ASSESSMENT_PASSED,
            "assessment_passed:{$attempt->id}"
        ));
        $this->assertSame(1, $this->countNotifications($user, NotificationService::TYPE_ASSESSMENT_PASSED));
    }

    public function test_direct_create_with_a_taken_dedupe_key_returns_null(): void
    {
        $user = User::factory()->create();
        $notifications = app(NotificationService::class);
        $data = NotificationService::payload('assessment.show', ['attempt' => $user->id]);

        $first = $notifications->create(
            $user,
            NotificationService::TYPE_ASSESSMENT_PASSED,
            'CHALLENGE PASSED',
            'Challenge passed.',
            "assessment_passed:{$user->id}",
            $data,
        );

        $second = $notifications->create(
            $user,
            NotificationService::TYPE_ASSESSMENT_PASSED,
            'CHALLENGE PASSED',
            'Challenge passed.',
            "assessment_passed:{$user->id}",
            $data,
        );

        $this->assertInstanceOf(Notification::class, $first);
        $this->assertNull($second);
        $this->assertSame(1, Notification::query()->where('user_id', $user->id)->count());
    }

    public function test_link_for_renders_no_link_for_payloads_outside_the_safe_contract(): void
    {
        $user = User::factory()->create();
        $notifications = app(NotificationService::class);

        $tampered = [
            ['route' => 'https://www.example.com/auth', 'params' => ['user' => $user->id]],
            ['route' => 'dreamland', 'params' => ['user' => $user->id]],
            ['route' => 'achievements', 'params' => []],
            ['route' => 'mission.show', 'params' => ['mission' => 'abc']],
            ['route' => 'mission.show', 'params' => ['mission' => ['nested' => 1]]],
            ['route' => 'mission.show', 'data' => null],
        ];

        foreach ($tampered as $data) {
            $notification = Notification::factory()->create([
                'user_id' => $user->id,
                'type' => NotificationService::TYPE_MISSION_COMPLETED,
                'data' => $data,
            ]);

            $this->assertNull($notifications->linkFor($notification));
        }
    }

    public function test_create_rejects_a_raw_url_payload_before_writing_any_row(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(NotificationService::class)->create(
            $user,
            NotificationService::TYPE_MISSION_COMPLETED,
            'MISSION COMPLETED',
            'Mission completed.',
            "mission_completed:{$user->id}",
            NotificationService::payload('https://www.example.com/auth', ['user' => $user->id]),
        );
    }
}
