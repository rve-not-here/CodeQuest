<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Notification;
use App\Models\Section;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\NotificationService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US-812 cross-module notification anchor. The story tests the seam between
 * student actions, notification delivery, read state, progression, teacher
 * monitoring, and admin statistics. Individual notification rules remain in
 * their focused feature tests.
 */
class NotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    public function test_learning_events_flow_through_notifications_and_live_monitoring(): void
    {
        $student = User::factory()->create(['username' => 'notification_cadet']);
        $teacher = User::factory()->teacher()->create(['username' => 'notification_teacher']);
        $admin = User::factory()->admin()->create(['username' => 'notification_admin']);

        ['course' => $alpha, 'assessment' => $alphaChallenge, 'missions' => $alphaMissions] =
            $this->courseWithAssessment('HTML Fundamentals', 1, [
                ['token' => '<h1>', 'points' => 30],
                ['token' => '<nav>', 'points' => 40],
            ], 'SYSTEM_ONLINE');

        ['course' => $beta] = $this->courseWithAssessment('CSS Foundations', 2, [
            ['token' => '<p>', 'points' => 50],
        ], 'ALL_CLEAR');

        $this->passMission($student, $alphaMissions[0], '<h1>Intro</h1>');
        $this->passMission($student, $alphaMissions[1], '<nav>Menu</nav>');

        $unlocked = Notification::query()
            ->where('user_id', $student->id)
            ->where('type', NotificationService::TYPE_ASSESSMENT_UNLOCKED)
            ->firstOrFail();

        $this->assertDatabaseHas('the404_notifications', [
            'id' => $unlocked->id,
            'user_id' => $student->id,
            'dedupe_key' => "assessment_unlocked:{$alphaChallenge->id}",
            'read_at' => null,
        ]);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('CHALLENGE UNLOCKED')
            ->assertSee(route('assessment.show', $alphaChallenge));

        $this->actingAs($student)->get(route('notifications'))
            ->assertOk()
            ->assertSee('CHALLENGE UNLOCKED')
            ->assertSee(route('assessment.show', $alphaChallenge));

        $this->actingAs($student)
            ->post(route('notifications.read', $unlocked))
            ->assertRedirect(route('notifications'));

        $this->assertNotNull($unlocked->refresh()->read_at);

        $this->passChallenge($student, $alphaChallenge, 'SYSTEM_ONLINE');

        $this->assertNotificationCount($student, NotificationService::TYPE_ASSESSMENT_PASSED, 1);
        $this->assertNotificationCount($student, NotificationService::TYPE_COURSE_COMPLETED, 1);
        $this->assertNotificationCount($student, NotificationService::TYPE_NEXT_COURSE_UNLOCKED, 1);
        $this->assertDatabaseHas('the404_notifications', [
            'user_id' => $student->id,
            'type' => NotificationService::TYPE_NEXT_COURSE_UNLOCKED,
            'dedupe_key' => "next_course_unlocked:{$beta->id}",
        ]);
        $this->assertSame($beta->id, app(DashboardService::class)->currentCourse($student)?->id);

        $this->actingAs($student)->get(route('notifications'))
            ->assertOk()
            ->assertSee('CHALLENGE PASSED')
            ->assertSee('COURSE COMPLETED')
            ->assertSee('NEXT COURSE UNLOCKED')
            ->assertSee(route('learning-path', ['course' => $beta->id]));

        $teacherDashboard = $this->actingAs($teacher)->get(route('students'));
        $teacherDashboard->assertOk()
            ->assertSee('Boss Challenge completed: '.$alphaChallenge->title);

        $roster = $this->rosterBody($teacherDashboard->getContent());
        $this->assertStringContainsString('notification_cadet', $roster);
        $this->assertStringContainsString($beta->name, $roster);
        $this->assertStringContainsString('DEMONSTRATED 1', $roster);

        $adminDashboard = $this->actingAs($admin)->get(route('admin.dashboard'));
        $adminDashboard->assertOk();
        $this->assertMetric($adminDashboard->getContent(), 'attempts', '1');
        $this->assertMetric($adminDashboard->getContent(), 'passed_attempts', '1');
        $this->assertMetric($adminDashboard->getContent(), 'active_students', '1');
        $this->assertMetric($adminDashboard->getContent(), 'course_completions', '1');

        $this->actingAs($student)
            ->post(route('notifications.read-all'))
            ->assertRedirect(route('notifications'));

        $this->assertSame(
            0,
            Notification::query()->where('user_id', $student->id)->whereNull('read_at')->count(),
        );
    }

    /**
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

        $builtMissions = [];

        foreach ($missions as $index => $mission) {
            $builtMissions[] = Mission::factory()->create([
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

        return ['course' => $course, 'assessment' => $assessment, 'missions' => $builtMissions];
    }

    private function passMission(User $student, Mission $mission, string $code): void
    {
        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => $code])
            ->assertRedirect()
            ->assertSessionHas('mission_success');
    }

    private function passChallenge(User $student, Assessment $assessment, string $code): void
    {
        $this->actingAs($student)
            ->post(route('assessment.start', $assessment))
            ->assertRedirect();

        $this->actingAs($student)
            ->post(route('assessment.submit', $assessment), ['code' => $code])
            ->assertRedirect()
            ->assertSessionHas('assessment_success');
    }

    private function assertNotificationCount(User $user, string $type, int $expected): void
    {
        $this->assertSame(
            $expected,
            Notification::query()->where('user_id', $user->id)->where('type', $type)->count(),
        );
    }

    private function rosterBody(string $content): string
    {
        $from = strpos($content, '<form method="GET"');

        return $from === false ? '' : substr($content, $from);
    }

    private function assertMetric(string $content, string $metric, string $expected): void
    {
        $matches = [];
        $matched = preg_match(
            '/data-metric="'.preg_quote($metric, '/').'"[^>]*>(\d+)</',
            $content,
            $matches,
        );

        $this->assertSame(1, $matched, "Metric {$metric} was not rendered.");
        $this->assertSame($expected, $matches[1]);
    }
}
