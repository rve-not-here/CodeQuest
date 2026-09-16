<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AssessmentService;
use App\Services\DashboardService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end journey tests (US-414). These exercise the real HTTP flow only:
 * dashboard and assessment screens plus the mission submit route, so the
 * whole progression chain is proven as one coherent system. A mission passes
 * when its validation token appears (a single contains rule), and a Boss
 * Challenge passes when its token appears (single rule, passing_score 70).
 */
class AssessmentIntegrationTest extends TestCase
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

    private function xp(User $user): int
    {
        return (int) XpTransaction::query()->where('user_id', $user->id)->sum('amount');
    }

    private function currentCourse(User $user): ?Course
    {
        return app(DashboardService::class)->currentCourse($user);
    }

    public function test_full_journey_flows_through_two_courses_to_completion(): void
    {
        $user = User::factory()->create();

        ['course' => $alpha, 'assessment' => $alphaChallenge, 'missions' => $alphaMissions] =
            $this->courseWithAssessment('HTML Fundamentals', 1, [
                ['token' => '<h1>', 'points' => 30],
                ['token' => '<nav>', 'points' => 40],
            ], 'SYSTEM_ONLINE');

        ['course' => $beta, 'assessment' => $betaChallenge, 'missions' => $betaMissions] =
            $this->courseWithAssessment('CSS Foundations', 2, [
                ['token' => '<p>', 'points' => 50],
            ], 'ALL_CLEAR');

        // The journey starts on the first course; its challenge is sealed.
        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('HTML Fundamentals')
            ->assertDontSee('CSS Foundations');

        $this->actingAs($user)->get(route('assessment.show', $alphaChallenge))
            ->assertRedirect(route('assessments'))
            ->assertSessionHas('assessment_locked');

        $this->actingAs($user)->get(route('assessments'))
            ->assertOk()
            ->assertSee('SEALED');

        // Required missions first.
        $this->passMission($user, $alphaMissions[0], '<h1>Intro</h1>');
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $user->id,
            'mission_id' => $alphaMissions[0]->id,
            'pts_earned' => 30,
        ]);

        $this->passMission($user, $alphaMissions[1], '<nav>Menu</nav>');
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $user->id,
            'mission_id' => $alphaMissions[1]->id,
            'pts_earned' => 40,
        ]);

        $this->assertSame(70, $this->xp($user));
        $this->assertSame(0, AssessmentAttempt::query()->where('user_id', $user->id)->count());

        // All missions done: the Boss Challenge is now unlocked.
        $this->actingAs($user)->get(route('assessment.show', $alphaChallenge))
            ->assertOk()
            ->assertSee('INITIATE CHALLENGE');

        $this->actingAs($user)->get(route('assessments'))
            ->assertOk()
            ->assertSee('READY');

        // Pass the Boss Challenge.
        $this->passChallenge($user, $alphaChallenge, 'SYSTEM_ONLINE');

        $this->assertDatabaseHas('the404_assessment_attempts', [
            'user_id' => $user->id,
            'assessment_id' => $alphaChallenge->id,
            'status' => 'passed',
            'score' => 100,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'mission_id' => null,
            'assessment_id' => $alphaChallenge->id,
            'amount' => 100,
            'type' => XpService::TYPE_ASSESSMENT_COMPLETED,
        ]);
        $this->assertSame(170, $this->xp($user));

        // Course one is complete: the dashboard and the derived next course
        // both hand over to course two, and course two stays sealed until its
        // own missions are done.
        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('CSS Foundations')
            ->assertDontSee('Course complete');

        $this->assertSame($beta->id, $this->currentCourse($user)->id);
        $this->assertTrue(app(AssessmentService::class)->hasPassed($user, $alpha));

        $this->actingAs($user)->get(route('assessment.show', $betaChallenge))
            ->assertRedirect(route('assessments'))
            ->assertSessionHas('assessment_locked');

        // Clear the second course.
        $this->passMission($user, $betaMissions[0], '<p>Body</p>');
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $user->id,
            'mission_id' => $betaMissions[0]->id,
            'pts_earned' => 50,
        ]);

        $this->passChallenge($user, $betaChallenge, 'ALL_CLEAR');

        $this->assertSame(320, $this->xp($user));
        $this->assertSame(2, AssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'passed')
            ->count());

        // Every active course is cleared: no current course, completion banner.
        $this->assertNull($this->currentCourse($user));

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Course complete');

        $this->actingAs($user)->get(route('assessments'))
            ->assertOk()
            ->assertSee('CLEARED')
            ->assertDontSee('SEALED');
    }

    public function test_finishing_missions_unlocks_but_does_not_complete_a_course(): void
    {
        $user = User::factory()->create();

        ['course' => $course, 'assessment' => $challenge, 'missions' => $missions] =
            $this->courseWithAssessment('HTML Fundamentals', 1, [
                ['token' => '<h1>', 'points' => 30],
            ], 'SYSTEM_ONLINE');

        $this->passMission($user, $missions[0], '<h1>Intro</h1>');

        // Unlocked, so the student can view and initiate the challenge.
        $this->actingAs($user)->get(route('assessment.show', $challenge))
            ->assertOk()
            ->assertSee('INITIATE CHALLENGE');

        // But no attempt exists, completion has not happened, and the same
        // course is still current on the dashboard.
        $this->assertSame(0, AssessmentAttempt::query()->where('user_id', $user->id)->count());
        $this->assertSame($course->id, $this->currentCourse($user)->id);
        $this->assertFalse(app(AssessmentService::class)->hasPassed($user, $course));

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('HTML Fundamentals')
            ->assertDontSee('Course complete');
    }

    public function test_failed_challenge_keeps_next_course_locked_until_a_retry_passes(): void
    {
        $user = User::factory()->create();

        ['course' => $alpha, 'assessment' => $alphaChallenge, 'missions' => $alphaMissions] =
            $this->courseWithAssessment('HTML Fundamentals', 1, [
                ['token' => '<h1>', 'points' => 30],
            ], 'SYSTEM_ONLINE');

        ['course' => $beta, 'assessment' => $betaChallenge] =
            $this->courseWithAssessment('CSS Foundations', 2, [
                ['token' => '<p>', 'points' => 50],
            ], 'ALL_CLEAR');

        $this->passMission($user, $alphaMissions[0], '<h1>Intro</h1>');

        // Start and fail the challenge.
        $this->actingAs($user)->post(route('assessment.start', $alphaChallenge));
        $this->actingAs($user)
            ->post(route('assessment.submit', $alphaChallenge), ['code' => 'GARBAGE'])
            ->assertRedirect()
            ->assertSessionHas('assessment_error');

        $this->assertDatabaseHas('the404_assessment_attempts', [
            'user_id' => $user->id,
            'assessment_id' => $alphaChallenge->id,
            'status' => 'failed',
            'score' => 0,
            'passed_at' => null,
        ]);
        $this->assertDatabaseMissing('the404_xp_transactions', [
            'user_id' => $user->id,
            'type' => XpService::TYPE_ASSESSMENT_COMPLETED,
        ]);

        // The next course is still locked and the current course unchanged.
        $this->assertSame($alpha->id, $this->currentCourse($user)->id);

        $this->actingAs($user)->get(route('assessment.show', $betaChallenge))
            ->assertRedirect(route('assessments'))
            ->assertSessionHas('assessment_locked');

        // A retry opens a fresh attempt, and the pass completes the course.
        $this->actingAs($user)->post(route('assessment.retry', $alphaChallenge));
        $this->assertSame(2, AssessmentAttempt::query()->where('user_id', $user->id)->count());

        $this->actingAs($user)
            ->post(route('assessment.submit', $alphaChallenge), ['code' => 'SYSTEM_ONLINE'])
            ->assertRedirect()
            ->assertSessionHas('assessment_success');

        $this->assertTrue(app(AssessmentService::class)->hasPassed($user, $alpha));
        $this->assertSame($beta->id, $this->currentCourse($user)->id);
        $this->assertSame(1, XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_ASSESSMENT_COMPLETED)
            ->count());
        $this->assertSame(130, $this->xp($user));
    }
}
