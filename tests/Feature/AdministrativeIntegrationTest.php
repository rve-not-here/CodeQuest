<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AdminAuditService;
use App\Services\AssessmentService;
use App\Services\DashboardService;
use App\Services\SectionService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-713 end-to-end administrative integration anchor — the fifth and final
 * anchor in the harness, required by §48.0 to run against REAL services and
 * REAL database state, never mocks. Phase 4/5/6 proved the student and teacher
 * chains each read live state at the same instant; this test proves the admin
 * write seam (Phases 7's own addition) cannot silently corrupt or break the
 * flows Phases 3–6 already built.
 *
 * The chain: an admin edits curriculum through the real PUT routes — rename a
 * course, reorder a section, change a mission's points, adjust an assessment's
 * passing_score — then a student continues a real HTTP journey against the
 * changed catalog (learning path, mission completion, Boss Challenge pass,
 * course completion, next-course unlock) and the teacher side (Phase 6)
 * reflects the result. The course-locking fix from US-705 is revisited with a
 * separate lock -> sealed-out -> unlock flow, proving the seal blocks new
 * use without rewriting recorded progress and that unlocking restores access
 * cleanly.
 *
 * Shares the courseWithAssessment/passMission/passChallenge harness with
 * AssessmentIntegrationTest, ProgressIntegrationTest, JourneyIntegrationTest
 * and InstructorMonitoringIntegrationTest so grading setup stays in sync
 * (recorded feature rule). Admin writes go through the whitelisted
 * $request->safe([...]) controllers and the guarded services, so an audit row
 * must accompany every applied edit.
 */
class AdministrativeIntegrationTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    public function test_admin_curriculum_edits_flow_through_to_student_completion_and_teacher_monitoring(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = $this->teacher();
        $student = User::factory()->create(['username' => 'cadet_admin']);

        ['course' => $alpha, 'assessment' => $alphaChallenge, 'missions' => $alphaMissions] =
            $this->courseWithAssessment('HTML Fundamentals', 1, [
                ['token' => '<h1>', 'points' => 30],
                ['token' => '<nav>', 'points' => 40],
            ], 'SYSTEM_ONLINE');

        ['course' => $beta, 'assessment' => $betaChallenge] =
            $this->courseWithAssessment('CSS Foundations', 2, [
                ['token' => '<p>', 'points' => 50],
            ], 'ALL_CLEAR');

        $this->classroomFor($teacher, [$student], [$alpha, $beta]);

        // The student completes the first mission BEFORE the admin edits, so a
        // baseline of recorded progress exists to prove the edits never touch
        // rows already on the books.
        $this->passMission($student, $alphaMissions[0], '<h1>Intro</h1>');
        $this->assertSame(30, $this->xp($student));

        // A second section under alpha, so the section reorder has two sections
        // to move. The harnessed missions stay on the original section.
        $secondSection = Section::factory()->create(['course_id' => $alpha->id, 'order_num' => 50]);
        $missionsSection = $alphaMissions[0]->section;

        // ADMIN EDIT 1 — rename the course. CourseService::update writes only
        // the course row; the slug is kept stable so id-bound model routing
        // still resolves.
        $this->actingAs($admin)
            ->put(route('admin.courses.update', $alpha), $this->coursePayload($alpha, [
                'name' => 'The 404 Challenge',
            ]))
            ->assertRedirect(route('admin.courses'));

        // ADMIN EDIT 2 — reorder the two sections (missions section drops back).
        $this->actingAs($admin)
            ->put(route('admin.courses.sections.update', [$alpha, $missionsSection]), $this->sectionPayload($missionsSection, [
                'order_num' => 2,
            ]))
            ->assertRedirect(route('admin.courses.sections', $alpha));

        $this->actingAs($admin)
            ->put(route('admin.courses.sections.update', [$alpha, $secondSection]), $this->sectionPayload($secondSection, [
                'order_num' => 1,
            ]))
            ->assertRedirect(route('admin.courses.sections', $alpha));

        // ADMIN EDIT 3 — raise the second mission's points 40 -> 60 before the
        // student has attempted it. Existing progress is never rewritten, only
        // future completions carry the new award.
        $alphaMissions[1]->refresh();
        $this->actingAs($admin)
            ->put(route('admin.courses.missions.update', [$alpha, $alphaMissions[1]]), $this->missionPayload($alphaMissions[1], [
                'points' => 60,
            ]))
            ->assertRedirect(route('admin.courses.missions', $alpha));

        // ADMIN EDIT 4 — raise the passing threshold 70 -> 100. The student has
        // not attempted the challenge, so the verdict on the next submission is
        // judged against the new threshold.
        $this->actingAs($admin)
            ->put(route('admin.courses.assessment.update', [$alpha, $alphaChallenge]), $this->assessmentPayload($alphaChallenge, [
                'passing_score' => 100,
            ]))
            ->assertRedirect(route('admin.courses.assessment', $alpha));

        // Every applied edit wrote a success audit row through the guarded
        // service seam, and the catalog rows really changed.
        $this->assertDatabaseHas('the404_admin_audit', ['action' => AdminAuditService::ACTION_COURSE_UPDATE]);
        $this->assertDatabaseHas('the404_admin_audit', ['action' => AdminAuditService::ACTION_SECTION_UPDATE]);
        $this->assertDatabaseHas('the404_admin_audit', ['action' => AdminAuditService::ACTION_MISSION_UPDATE]);
        $this->assertDatabaseHas('the404_admin_audit', ['action' => AdminAuditService::ACTION_ASSESSMENT_UPDATE]);
        $this->assertDatabaseHas('the404_courses', ['id' => $alpha->id, 'name' => 'The 404 Challenge']);
        $this->assertDatabaseHas('the404_missions', ['id' => $alphaMissions[1]->id, 'points' => 60]);
        $this->assertDatabaseHas('the404_assessments', ['id' => $alphaChallenge->id, 'passing_score' => 100]);

        // The pre-edit mission keeps its recorded award; nothing rewritten.
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $alphaMissions[0]->id,
            'pts_earned' => 30,
        ]);
        $this->assertSame(30, $this->xp($student));

        // STUDENT VIEWS: the renamed course is everywhere the student looks, the
        // reordered sections read in the new order, and the mission page shows
        // the edited +60 XP award.
        $alpha->refresh();
        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('The 404 Challenge')
            ->assertSee($alphaMissions[1]->title);

        $this->actingAs($student)->get(route('learning-path'))
            ->assertOk()
            ->assertSee('The 404 Challenge');

        $sectionOrder = app(SectionService::class)->forCourse($alpha)->pluck('id')->all();
        $this->assertSame([$secondSection->id, $missionsSection->id], $sectionOrder);

        $this->actingAs($student)->get(route('mission.show', $alphaMissions[1]))
            ->assertOk()
            ->assertSee('+60 XP');

        // MISSION ACCESS STILL VALID: the student completes the edited mission
        // and XP accrues at the new rate.
        $this->passMission($student, $alphaMissions[1], '<nav>Menu</nav>');
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $alphaMissions[1]->id,
            'pts_earned' => 60,
        ]);
        $this->assertSame(90, $this->xp($student));

        // BOSS ELIGIBILITY INTACT: every mission done, the dashboard hands the
        // student to the challenge of the renamed course.
        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Boss Challenge ready');

        // BOSS PASS: the threshold edit judged this future submission — score
        // 100 clears the new passing_score of 100.
        $this->passChallenge($student, $alphaChallenge, 'SYSTEM_ONLINE');
        $this->assertDatabaseHas('the404_assessment_attempts', [
            'user_id' => $student->id,
            'assessment_id' => $alphaChallenge->id,
            'status' => 'passed',
            'score' => 100,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'assessment_id' => $alphaChallenge->id,
            'type' => XpService::TYPE_ASSESSMENT_COMPLETED,
            'amount' => XpService::ASSESSMENT_PASSED_AMOUNT,
        ]);
        $this->assertSame(190, $this->xp($student));

        $this->assertTrue(app(AssessmentService::class)->hasPassed($student, $alpha));
        $this->assertSame($beta->id, app(DashboardService::class)->currentCourse($student)->id);

        // NEXT-COURSE UNLOCK: the dashboard hands the student to course two.
        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee($beta->name);

        // TEACHER MONITORING (Phase 6) reflects the same live state, keyed off
        // the renamed course, straight after the student's pass POST.
        $dashboard = $this->actingAs($teacher)->get(route('students'));
        $dashboard->assertSee('Boss Challenge passed: '.$alphaChallenge->title);

        $roster = $this->rosterBody($dashboard->getContent());
        $this->assertStringContainsString('The 404 Challenge', $roster);
        $this->assertStringContainsString($beta->name, $roster);
        $this->assertStringContainsString('IN PROGRESS 0/1 · 0%', $roster);
        $this->assertStringContainsString('DEMONSTRATED 1', $roster);

        $alphaPanel = $this->panelBodyBetween(
            $this->actingAs($teacher)->get(route('course-analytics'))->getContent(),
            'panel-title truncate">'.strtoupper($alpha->name),
        );
        $this->assertStringContainsString('✔ COMPLETED 1', $alphaPanel);
        $this->assertStringContainsString('100%', $alphaPanel);

        $detail = $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]));
        $performance = $this->panelBodyBetween($detail->getContent(), 'panel-title">Assessment Performance');
        $this->assertStringContainsString('PASSED', $performance);
        $this->assertStringContainsString('The 404 Challenge', $performance);
    }

    public function test_locking_a_course_mid_journey_seals_the_student_and_unlocking_restores_access(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['username' => 'cadet_sealed']);

        ['course' => $alpha, 'assessment' => $alphaChallenge, 'missions' => $alphaMissions] =
            $this->courseWithAssessment('HTML Fundamentals', 1, [
                ['token' => '<h1>', 'points' => 30],
                ['token' => '<nav>', 'points' => 40],
            ], 'SYSTEM_ONLINE');

        ['course' => $beta] = $this->courseWithAssessment('CSS Foundations', 2, [
            ['token' => '<p>', 'points' => 50],
        ], 'ALL_CLEAR');

        // The student is mid-journey: mission one done, mission two open.
        $this->passMission($student, $alphaMissions[0], '<h1>Intro</h1>');
        $this->assertSame(30, $this->xp($student));

        // LOCK the course (US-705 revisit).
        $this->actingAs($admin)
            ->put(route('admin.courses.update', $alpha), $this->coursePayload($alpha, [
                'status' => 'locked',
            ]))
            ->assertRedirect(route('admin.courses'));

        $this->assertDatabaseHas('the404_admin_audit', ['action' => AdminAuditService::ACTION_COURSE_STATUS_CHANGE]);
        $alpha->refresh();
        $this->assertSame('locked', $alpha->status);

        // SEALED OUT: the mission pages and submit route 403 before any user
        // resolution or write, and the challenge reads sealed.
        $this->actingAs($student)->get(route('mission.show', $alphaMissions[1]))->assertForbidden();
        $this->actingAs($student)->post(route('mission.submit', $alphaMissions[1]), ['code' => '<nav>Menu</nav>'])
            ->assertForbidden();

        $this->actingAs($student)->get(route('assessment.show', $alphaChallenge))
            ->assertRedirect(route('assessments'))
            ->assertSessionHas('assessment_locked');

        // No new progress or XP landed while sealed, and the pre-seal
        // progress was never rewritten.
        $this->assertDatabaseMissing('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $alphaMissions[1]->id,
        ]);
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $alphaMissions[0]->id,
            'pts_earned' => 30,
        ]);
        $this->assertSame(30, $this->xp($student));

        // The sealed course leaves the student's active progression: the
        // dashboard points at the next active course, exactly as the lock
        // removes the course from every status='active' consumer.
        $this->assertSame($beta->id, app(DashboardService::class)->currentCourse($student)->id);

        // UNLOCK the course cleaned: access is restored without any repair
        // pass or re-seed.
        $this->actingAs($admin)
            ->put(route('admin.courses.update', $alpha), $this->coursePayload($alpha, [
                'status' => 'active',
            ]))
            ->assertRedirect(route('admin.courses'));

        $alpha->refresh();
        $this->assertSame('active', $alpha->status);

        $this->actingAs($student)->get(route('mission.show', $alphaMissions[1]))->assertOk();

        // The student finishes mission two and the whole remaining chain now
        // proceeds normally against the restored course.
        $this->passMission($student, $alphaMissions[1], '<nav>Menu</nav>');
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $alphaMissions[1]->id,
            'pts_earned' => 40,
        ]);
        $this->assertSame(70, $this->xp($student));

        // Unlocking put alpha back as the current course (order 1, not
        // passed) — the seal was transient, not a completion shortcut.
        $this->assertSame($alpha->id, app(DashboardService::class)->currentCourse($student)->id);

        $this->passChallenge($student, $alphaChallenge, 'SYSTEM_ONLINE');
        $this->assertSame(170, $this->xp($student));
        $this->assertTrue(app(AssessmentService::class)->hasPassed($student, $alpha));
        $this->assertSame($beta->id, app(DashboardService::class)->currentCourse($student)->id);
    }

    /**
     * A course with missions and an active Boss Challenge. Mirrors the helper
     * in AssessmentIntegrationTest, ProgressIntegrationTest, JourneyIntegrationTest
     * and InstructorMonitoringIntegrationTest so all journey anchors share
     * grading setup.
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
        return User::factory()->teacher()->create(['username' => 'cpu_teacher_713']);
    }

    /**
     * A complete, valid PUT payload for the course catalog update route. The
     * whitelisted controller and guarded service both re-read these default
     * values, so an override here is the only edit.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function coursePayload(Course $course, array $overrides = []): array
    {
        return array_merge([
            'name' => $course->name,
            'slug' => $course->slug,
            'type' => $course->type,
            'description' => $course->description,
            'status' => $course->status,
            'order_num' => $course->order_num,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function sectionPayload(Section $section, array $overrides = []): array
    {
        return array_merge([
            'title' => $section->title,
            'description' => $section->description,
            'order_num' => $section->order_num,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function missionPayload(Mission $mission, array $overrides = []): array
    {
        return array_merge([
            'title' => $mission->title,
            'description' => $mission->description,
            'difficulty' => $mission->difficulty,
            'points' => $mission->points,
            'order_num' => $mission->order_num,
            'section_id' => $mission->section_id,
            'hints' => $mission->hints,
            'broken_code' => $mission->broken_code,
            'target_html' => $mission->target_html,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function assessmentPayload(Assessment $assessment, array $overrides = []): array
    {
        return array_merge([
            'title' => $assessment->title,
            'description' => $assessment->description,
            'instructions' => $assessment->instructions,
            'passing_score' => $assessment->passing_score,
            'status' => $assessment->status,
        ], $overrides);
    }

    /**
     * The /students page carries system-wide dashboard strips above the roster
     * (US-609). Roster assertions run against this slice so a username or label
     * echoed by the recent-activity or assessment-summary strips can never
     * bleed into a roster assertion.
     */
    private function rosterBody(string $content): string
    {
        $from = strpos($content, '<form method="GET"');

        if ($from === false) {
            return '';
        }

        return substr($content, $from);
    }

    /**
     * Slice the body of a panel: from its title marker to the next panel section
     * opening. Scope count-based content assertions here so a later panel added
     * to the page can never silently change a whole-page count.
     */
    private function panelBodyBetween(string $content, string $titleMarker): string
    {
        $from = strpos($content, $titleMarker);

        if ($from === false) {
            return '';
        }

        $to = strpos($content, '<section class="panel', $from + strlen($titleMarker));

        return $to === false ? substr($content, $from) : substr($content, $from, $to - $from);
    }
}
