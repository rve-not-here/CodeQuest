<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\XpTransaction;
use App\Services\AchievementService;
use App\Services\CompetencyService;
use App\Services\RecommendationService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * US-510 chain-propagation anchor. The individual links (Learning, Progress,
 * XP, Assessment, Competency, Achievements, Recommendations) each have their
 * own service and tests; this file proves they are one connected system by
 * driving a single Boss Challenge pass through the real HTTP flow and reading
 * every downstream system straight after it returns — no page reload, no
 * separate catch-up step. Every system derives live from the event the pass
 * just wrote, so the assertion is that all of them reflect the same event at
 * the same instant.
 */
class ProgressIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    /**
     * A course with missions and an active Boss Challenge. Mirrors the helper
     * in AssessmentIntegrationTest so both journey tests share grading setup.
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

    /**
     * The live competency row for one course, exactly as the course overview
     * service derives it.
     *
     * @return array<string, mixed>
     */
    private function competency(User $user, Course $course): array
    {
        $row = app(CompetencyService::class)->overview($user)
            ->first(fn (array $row): bool => $row['course']->is($course));

        $this->assertNotNull($row, 'Expected a competency row for the course.');

        return $row;
    }

    /**
     * @return Collection<int, array{slot: int, title: string, subtitle: string, href: string, cta: string}>
     */
    private function recommendations(User $user): Collection
    {
        return app(RecommendationService::class)->recommendations($user);
    }

    private function assertAchievementAwarded(User $user, string $slug): void
    {
        $this->assertDatabaseHas('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId($slug),
        ]);
    }

    private function assertAchievementNotAwarded(User $user, string $slug): void
    {
        $this->assertDatabaseMissing('the404_user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $this->achievementId($slug),
        ]);
    }

    private function achievementId(string $slug): int
    {
        return Achievement::query()->where('slug', $slug)->firstOrFail()->id;
    }

    public function test_first_boss_challenge_pass_propagates_through_the_chain_in_one_action(): void
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

        // Learning -> Progress -> XP -> Achievements: completing the first
        // mission writes progress and XP, and awards first_challenge — the
        // first link of the chain.
        $this->passMission($user, $alphaMissions[0], '<h1>Intro</h1>');
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $user->id,
            'mission_id' => $alphaMissions[0]->id,
            'pts_earned' => 30,
        ]);
        $this->assertSame(30, $this->xp($user));
        $this->assertAchievementAwarded($user, AchievementService::SLUG_FIRST_CHALLENGE);

        $this->passMission($user, $alphaMissions[1], '<nav>Menu</nav>');
        $this->assertSame(70, $this->xp($user));

        // Competency reads the same completed missions live: not yet
        // demonstrated, and the recommendation is the outstanding challenge.
        $this->assertSame('practicing', $this->competency($user, $alpha)['state']);
        $this->assertSame('not_started', $this->competency($user, $beta)['state']);
        $this->assertFalse($this->competency($user, $alpha)['challengePassed']);
        $this->assertAchievementNotAwarded($user, AchievementService::SLUG_FIRST_COURSE);
        $this->assertAchievementNotAwarded($user, AchievementService::SLUG_FULL_CLEAR);

        $before = $this->recommendations($user);
        $this->assertCount(1, $before);
        $this->assertSame(2, $before->first()['slot']);
        $this->assertSame('Boss Challenge', $before->first()['title']);

        // The single triggering event: one submit POST runs evaluateAttempt,
        // which writes the verdict, the assessment XP, and the achievement
        // awards in one transaction. Everything below is read straight after
        // that POST returns — same instant, no reload, no catch-up step.
        $this->passChallenge($user, $alphaChallenge, 'SYSTEM_ONLINE');

        // XP: the assessment reward landed in the ledger with the verdict.
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'assessment_id' => $alphaChallenge->id,
            'type' => XpService::TYPE_ASSESSMENT_COMPLETED,
            'amount' => XpService::ASSESSMENT_PASSED_AMOUNT,
        ]);
        $this->assertSame(170, $this->xp($user));

        // Competency: alpha flipped to DEMONSTRATED from the same pass.
        $this->assertSame('demonstrated', $this->competency($user, $alpha)['state']);
        $this->assertTrue($this->competency($user, $alpha)['challengePassed']);
        $this->assertSame('not_started', $this->competency($user, $beta)['state']);

        // Achievements: the first pass records first_course, not yet full_clear.
        $this->assertAchievementAwarded($user, AchievementService::SLUG_FIRST_COURSE);
        $this->assertAchievementNotAwarded($user, AchievementService::SLUG_FULL_CLEAR);

        // Recommendations: the same pass re-derived the position — the finished
        // course's challenge is gone, replaced by the next course.
        $after = $this->recommendations($user);
        $this->assertCount(1, $after);
        $this->assertSame(4, $after->first()['slot']);
        $this->assertSame('Next Course', $after->first()['title']);
        $this->assertStringContainsString($beta->name, $after->first()['subtitle']);
    }

    public function test_final_boss_challenge_pass_awards_full_clear_and_clears_all_recommendations(): void
    {
        $user = User::factory()->create();

        ['course' => $alpha, 'assessment' => $alphaChallenge, 'missions' => $alphaMissions] =
            $this->courseWithAssessment('HTML Fundamentals', 1, [
                ['token' => '<h1>', 'points' => 30],
            ], 'SYSTEM_ONLINE');

        ['course' => $beta, 'assessment' => $betaChallenge, 'missions' => $betaMissions] =
            $this->courseWithAssessment('CSS Foundations', 2, [
                ['token' => '<p>', 'points' => 50],
            ], 'ALL_CLEAR');

        $this->passMission($user, $alphaMissions[0], '<h1>Intro</h1>');
        $this->passChallenge($user, $alphaChallenge, 'SYSTEM_ONLINE');

        // Mid-chain: the second course is live for the student.
        $this->assertSame('demonstrated', $this->competency($user, $alpha)['state']);
        $this->assertSame('not_started', $this->competency($user, $beta)['state']);
        $this->assertSame(4, $this->recommendations($user)->first()['slot']);

        $this->passMission($user, $betaMissions[0], '<p>Body</p>');
        $this->assertSame(180, $this->xp($user));
        $this->assertSame('practicing', $this->competency($user, $beta)['state']);

        // The final pass in the same single-action style.
        $this->passChallenge($user, $betaChallenge, 'ALL_CLEAR');

        $this->assertSame(280, $this->xp($user));

        // Competency: both courses demonstrate, from the same passes.
        $this->assertSame('demonstrated', $this->competency($user, $alpha)['state']);
        $this->assertSame('demonstrated', $this->competency($user, $beta)['state']);

        // Achievements: full_clear lands, and first_course was not re-awarded.
        $this->assertAchievementAwarded($user, AchievementService::SLUG_FULL_CLEAR);
        $this->assertSame(1, $this->achievementAwardCount($user, AchievementService::SLUG_FIRST_COURSE));

        // Recommendations: every active course cleared — nothing left to show.
        $this->assertTrue($this->recommendations($user)->isEmpty());
    }

    private function achievementAwardCount(User $user, string $slug): int
    {
        return UserAchievement::query()
            ->where('user_id', $user->id)
            ->where('achievement_id', $this->achievementId($slug))
            ->count();
    }
}
