<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-612 end-to-end instructor monitoring anchor. Phase 5 proved (ProgressIntegrationTest,
 * US-510) that after one triggering event every downstream system reads the same state at
 * the same instant on the STUDENT side. This test proves the identical property crossing
 * into the teacher area: a student's real HTTP actions (mission completions, a Boss
 * Challenge pass) must appear on the teacher dashboard, per-student progress page, and
 * course analytics immediately after the POST that caused them — no reload, no recompute,
 * no catch-up step. The teacher pages read live state through the shared Phase 5 services
 * and the analytics service, so every assertion below reads them straight after the student
 * POST returns, exactly as ProgressIntegrationTest reads the student services.
 *
 * The per-page behavior is already covered story-by-story (US-602..US-610). The risk this
 * test owns is whether the teacher side sees the same instant a student acts — the
 * cross-domain seam. It shares the courseWithAssessment/passMission/passChallenge harness
 * with the other journey anchors so grading setup stays in sync (recorded feature rule).
 */
class InstructorMonitoringIntegrationTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    public function test_student_actions_appear_on_the_teacher_side_in_the_same_instant(): void
    {
        $teacher = $this->teacher();
        $student = User::factory()->create(['username' => 'cadet_live']);

        ['course' => $alpha, 'assessment' => $alphaChallenge, 'missions' => $alphaMissions] =
            $this->courseWithAssessment('HTML Fundamentals', 1, [
                ['token' => '<h1>', 'points' => 30],
                ['token' => '<nav>', 'points' => 40],
            ], 'SYSTEM_ONLINE');

        ['course' => $beta, 'assessment' => $betaChallenge, 'missions' => $betaMissions] =
            $this->courseWithAssessment('CSS Foundations', 2, [
                ['token' => '<p>', 'points' => 50],
            ], 'ALL_CLEAR');

        $this->classroomFor($teacher, [$student], [$alpha, $beta]);

        // Stage 1 — a completed challenge writes progress and XP through the real
        // student route, and the teacher dashboard shows both in the same instant.
        $this->passMission($student, $alphaMissions[0], '<h1>Intro</h1>');

        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $alphaMissions[0]->id,
            'pts_earned' => 30,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'mission_id' => $alphaMissions[0]->id,
            'type' => XpService::TYPE_MISSION_COMPLETED,
            'amount' => 30,
        ]);
        $this->assertSame(30, $this->xp($student));

        $dashboard = $this->actingAs($teacher)->get(route('students'));
        $roster = $this->rosterBody($dashboard->getContent());
        $this->assertStringContainsString('cadet_live', $roster);
        $this->assertStringContainsString('1/2 · 50%', $roster);
        $this->assertStringContainsString($alpha->name, $roster);

        // Stage 2 — every mission done: the roster flips to READY before the Boss.
        $this->passMission($student, $alphaMissions[1], '<nav>Menu</nav>');
        $this->assertSame(70, $this->xp($student));

        $roster = $this->rosterBody($this->actingAs($teacher)
            ->get(route('students'))->getContent());
        $this->assertStringContainsString('2/2 · 100%', $roster);

        // Stage 3 — the single triggering event: one Boss Challenge pass writes the
        // verdict and the assessment XP. Every teacher-facing reader below reflects
        // it straight after this POST returns.
        $this->passChallenge($student, $alphaChallenge, 'SYSTEM_ONLINE');

        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'assessment_id' => $alphaChallenge->id,
            'type' => XpService::TYPE_ASSESSMENT_COMPLETED,
            'amount' => XpService::ASSESSMENT_PASSED_AMOUNT,
        ]);
        $this->assertSame(170, $this->xp($student));

        // Dashboard: the activity strip and assessment summary echo the pass, and the
        // roster has advanced the student into the next course as COMPLETED territory.
        $dashboard = $this->actingAs($teacher)->get(route('students'));
        $dashboard->assertSee('Boss Challenge completed: '.$alphaChallenge->title);
        $dashboard->assertSee('Pass rate 100%');
        $content = $dashboard->getContent();
        $from = strpos($content, '<h3 class="atlas-record-entry-title">'.$alpha->name.'</h3>');
        $this->assertNotFalse($from);
        $to = strpos($content, '</article>', $from);
        $this->assertNotFalse($to);
        $summary = substr($content, $from, $to - $from);
        $this->assertStringContainsString('<span>Completed 1</span>', $summary);
        $this->assertStringContainsString('<span>In progress 0</span>', $summary);
        $this->assertStringContainsString('<span>Boss ready 0</span>', $summary);
        $this->assertStringContainsString('<span>Not started 0</span>', $summary);
        $this->assertStringContainsString($alpha->name, $summary);

        $roster = $this->rosterBody($dashboard->getContent());
        $this->assertStringContainsString($beta->name, $roster);
        $this->assertStringContainsString('0/1 · 0%', $roster);
        $this->assertStringContainsString('Demonstrated 1', $roster);
        $this->assertStringNotContainsString('2/2 · 100%', $roster);

        // Per-student progress page (teacher): the assessment result and the
        // competency breakdown read live records, not a recomputed copy.
        $detail = $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]));

        $performance = $this->evidenceSection($detail->getContent(), 'id="monitoring-assessment"');
        $this->assertStringContainsString('Passed', $performance);
        $this->assertStringContainsString('100%', $performance);
        $this->assertStringContainsString($alpha->name, $performance);

        $competency = $this->evidenceSection($detail->getContent(), 'id="monitoring-competency"');
        $this->assertStringContainsString('Demonstrated', $competency);
        $this->assertStringContainsString('2/2', $competency);
        $this->assertStringContainsString('Passed', $competency);

        // Course analytics: alpha's panel counts the completed student and a 100% pass
        // rate, all derived from the same attempt and progress records.
        $alphaPanel = $this->evidenceSection(
            $this->actingAs($teacher)->get(route('course-analytics'))->getContent(),
            'id="analytics-course-'.$alpha->id.'"',
        );
        $this->assertStringContainsString('<dt>Completed</dt><dd class="is-numeric">1</dd>', $alphaPanel);
        $this->assertStringContainsString('100%', $alphaPanel);
    }

    /**
     * A course with missions and an active Boss Challenge. Mirrors the helper in
     * AssessmentIntegrationTest, ProgressIntegrationTest and JourneyIntegrationTest so
     * all journey anchors share grading setup.
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
        return (int) XpTransaction::query()
            ->where('user_id', $user->id)
            ->sum('amount');
    }

    private function teacher(): User
    {
        return User::factory()->teacher()->create(['username' => 'cpu_teacher']);
    }

    private function rosterBody(string $content): string
    {
        $from = strpos($content, '<tbody');
        $to = strpos($content, '</tbody>', $from === false ? 0 : $from);

        return $from === false || $to === false ? '' : substr($content, $from, $to - $from);
    }

    private function evidenceSection(string $content, string $titleMarker): string
    {
        $from = strpos($content, $titleMarker);

        if ($from === false) {
            return '';
        }

        $to = strpos($content, '</section>', $from + strlen($titleMarker));

        return $to === false ? substr($content, $from) : substr($content, $from, $to - $from);
    }
}
