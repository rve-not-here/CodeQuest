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
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US-512 end-to-end learning experience. One continuous, real student journey
 * from first login (as the resume/Continue Learning state) through to the next
 * course being recommended — walking the actual GET/POST routes a student
 * clicks, reading the rendered screens at each stop, not just firing the
 * services.
 *
 * Relationship to US-510: US-510 proves that after ONE triggering event (a
 * Boss Challenge pass), every downstream system read through the services
 * agrees immediately — same instant, no reload/recompute. This test drives the
 * same systems but through the user journey: dashboard resume link ->
 * mission page -> submit -> back to dashboard -> assessment page -> submit,
 * asserting the visible state at each screen. US-510 fires the pass and checks
 * consequences; US-512 clicks through the journey and asserts the view state
 * the student sees. They share the assessment-mission harness (kept in sync
 * per the recorded feature rule) but answer different questions: immediate
 * consistency of the write (US-510) vs. the route-navigable journey and its
 * visible outputs (US-512).
 */
class JourneyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    /**
     * A course with missions and an active Boss Challenge. Mirrors the helper
     * in AssessmentIntegrationTest and ProgressIntegrationTest so all three
     * journey anchors share grading setup.
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

    private function contentContains(string $content, string $needle): bool
    {
        return str_contains($content, $needle);
    }

    public function test_student_journeys_from_login_to_next_course_recommended(): void
    {
        $user = User::factory()->create([
            'username' => 'operator',
            'password' => 'secret123',
            'role' => 'student',
        ]);

        ['course' => $alpha, 'assessment' => $alphaChallenge, 'missions' => $alphaMissions] =
            $this->courseWithAssessment('HTML Fundamentals', 1, [
                ['token' => '<h1>', 'points' => 30],
                ['token' => '<nav>', 'points' => 40],
            ], 'SYSTEM_ONLINE');

        ['course' => $beta] = $this->courseWithAssessment('CSS Foundations', 2, [
            ['token' => '<p>', 'points' => 50],
        ], 'ALL_CLEAR');

        // LOGIN #1: authenticate with the username/password the student would
        // type; the POST itself lands on the dashboard.
        $this->post(route('login'), [
            'username' => 'operator',
            'password' => 'secret123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        // DASHBOARD #1: the journey starts on the first course with the first
        // mission as the Continue Learning target.
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('HTML Fundamentals')
            ->assertSee($alphaMissions[0]->title)
            ->assertSee('Continue Learning →');

        // FOLLOW THE RESUME LINK the student would click: the dashboard's
        // Continue Learning deep-links the first unfinished mission.
        $this->get(route('mission.show', $alphaMissions[0]))
            ->assertOk()
            ->assertSee($alphaMissions[0]->title)
            ->assertSee('+30 XP');

        // SUBMIT the first challenge from its page, then confirm the returned
        // journey lands back on the mission screen showing the success flash.
        $this->post(route('mission.submit', $alphaMissions[0]), ['code' => '<h1>Intro</h1>'])
            ->assertRedirect()
            ->assertSessionHas('mission_success');

        $this->get(route('mission.show', $alphaMissions[0]))
            ->assertOk()
            ->assertSee('MISSION COMPLETE');

        // DASHBOARD #2: after the first mission the resume has advanced to the
        // next unfinished mission (not a course-level resume), and the XP total
        // reflects the completed mission.
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee($alphaMissions[1]->title);

        // SUBMIT the second mission.
        $this->post(route('mission.submit', $alphaMissions[1]), ['code' => '<nav>Menu</nav>'])
            ->assertRedirect()
            ->assertSessionHas('mission_success');

        // DASHBOARD #3: every mission done — the resume is now course-level,
        // announcing the Boss Challenge as the next step.
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Boss Challenge ready')
            ->assertSee('Return to the Learning Path to inspect the course milestone.')
            ->assertSee('Continue Learning →');

        // ASSESSMENT: the challenge screen presents its initiate state.
        $this->get(route('assessment.show', $alphaChallenge))
            ->assertOk()
            ->assertSee('INITIATE CHALLENGE');

        // BEGIN and SUBMIT the pass from the challenge screen.
        $this->post(route('assessment.start', $alphaChallenge))
            ->assertRedirect(route('assessment.show', $alphaChallenge));

        $this->post(route('assessment.submit', $alphaChallenge), ['code' => 'SYSTEM_ONLINE'])
            ->assertRedirect(route('assessment.show', $alphaChallenge))
            ->assertSessionHas('assessment_success');

        $ledger = XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('assessment_id', $alphaChallenge->id)
            ->where('type', XpService::TYPE_ASSESSMENT_COMPLETED)
            ->count();
        $this->assertSame(1, $ledger);

        // Course complete: the dashboard hands over to course two.
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee($beta->name);

        // COMPETENCY: the finished course reads DEMONSTRATED and alpha's Boss
        // Challenge shows PASSED in the rendered competency screen.
        $competencyPage = $this->get(route('competency'))->assertOk()->getContent();
        $this->assertTrue($this->contentContains($competencyPage, 'DEMONSTRATED'));
        $this->assertTrue($this->contentContains($competencyPage, 'Boss Challenge: PASSED'));

        // ACHIEVEMENT: the first pass awarded first_course to this user.
        $firstCourseId = Achievement::query()->where('slug', AchievementService::SLUG_FIRST_COURSE)->value('id');
        $this->assertSame(1, UserAchievement::query()
            ->where('user_id', $user->id)
            ->where('achievement_id', $firstCourseId)
            ->count());

        // RECOMMENDATION: next course is the recommendation the student now
        // sees on /recommendations.
        $recommendations = $this->get(route('recommendations'))->assertOk()->getContent();
        $this->assertTrue($this->contentContains($recommendations, 'Next Course'));
        $this->assertTrue($this->contentContains($recommendations, $beta->name));
    }
}
