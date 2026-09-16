<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StudentProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_progress_requires_teacher_authorization(): void
    {
        $student = User::factory()->create();

        $this->get(route('student-progress', ['student' => $student->id]))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertForbidden();
    }

    public function test_student_progress_without_a_student_bounces_to_the_roster(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(route('student-progress'))
            ->assertRedirect(route('students'));
    }

    public function test_student_progress_unknown_student_is_not_found(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => 99999]))
            ->assertNotFound();
    }

    public function test_student_progress_rejects_a_non_student_target(): void
    {
        $teacher = User::factory()->teacher()->create();
        $target = User::factory()->teacher()->create(['username' => 'target_teacher']);

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $target->id]))
            ->assertNotFound();
    }

    public function test_student_progress_shows_course_and_section_hierarchy(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$course, $sections, $missions] = $this->createCourseWithSections(1, [1 => 2, 2 => 1]);
        Assessment::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->create(['username' => 'cadet_inkling', 'name' => 'Inks Cadet']);
        $this->complete($student, $missions[0]);

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('cadet_inkling')
            ->assertSee($course->name)
            ->assertSee($sections[1]->title)
            ->assertSee($sections[2]->title)
            ->assertSee('1/3')
            ->assertSee('1/2')
            ->assertSee('0/1')
            ->assertSee('IN PROGRESS')
            ->assertSee('NOT STARTED')
            ->assertSee('BOSS CHALLENGE: LOCKED');
    }

    public function test_student_progress_shows_the_current_learning_position(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$course, $sections, $missions] = $this->createCourseWithSections(1, [1 => 2]);
        Assessment::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->create(['username' => 'cadet_inkling']);
        $this->complete($student, $missions[0]);

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('CURRENT POSITION')
            ->assertSee('Continue at')
            ->assertSee($course->name)
            ->assertSee($sections[1]->title)
            ->assertSee($missions[1]->title)
            ->assertSee('CURRENT');
    }

    public function test_student_progress_marks_a_ready_course_with_challenge_outstanding_position(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$course, $sections, $missions] = $this->createCourseWithSections(1, [1 => 2]);
        Assessment::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->create(['username' => 'cadet_ready']);
        $this->complete($student, $missions[0]);
        $this->complete($student, $missions[1]);

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('Boss Challenge outstanding')
            ->assertSee('2/2')
            ->assertSee('DONE')
            ->assertSee('READY')
            ->assertSee('BOSS CHALLENGE: READY');
    }

    public function test_student_progress_all_cleared_state(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$course, , $missions] = $this->createCourseWithSections(1, [1 => 1]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->create(['username' => 'cadet_cleared']);
        $this->complete($student, $missions[0]);
        $this->attempt($student, $assessment, 'passed');

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('ALL COURSES CLEARED')
            ->assertSee('COMPLETED')
            ->assertSee('BOSS CHALLENGE: PASSED');
    }

    public function test_student_progress_renders_with_the_teacher_layout(): void
    {
        $teacher = User::factory()->teacher()->create(['username' => 'cpu_teacher']);
        $student = User::factory()->create();

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('cpu_teacher')
            ->assertDontSee('XP Ledger');
    }

    public function test_roster_rows_deep_link_to_the_progress_detail(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['username' => 'cadet_inkling']);

        $this->actingAs($teacher)
            ->get(route('students'))
            ->assertOk()
            ->assertSee('student-progress/'.$student->id);
    }

    public function test_student_progress_shows_assessment_performance(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$course, , $missions] = $this->createCourseWithSections(1, [1 => 2]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->create(['username' => 'cadet_performance']);
        $this->complete($student, $missions[0]);
        $this->complete($student, $missions[1]);

        $failedAt = Carbon::create(2026, 9, 2, 10, 0);
        $passedAt = Carbon::create(2026, 9, 3, 14, 30);
        $this->attempt($student, $assessment, 'failed', score: 50, submittedAt: $failedAt, passedAt: null);
        $this->attempt($student, $assessment, 'passed', score: 90, submittedAt: $passedAt->subMinutes(10), passedAt: $passedAt);

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('Assessment Performance')
            ->assertSee($course->name)
            ->assertSee('2')
            ->assertSee('90%')
            ->assertSee('PASSED')
            ->assertSee('COMPLETED')
            ->assertSee($passedAt->format('Y-m-d H:i'));
    }

    public function test_student_progress_attempt_log_lists_every_attempt_newest_first(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$course, , $missions] = $this->createCourseWithSections(1, [1 => 2]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->create(['username' => 'cadet_logbook']);
        $this->complete($student, $missions[0]);
        $this->complete($student, $missions[1]);

        // Three attempts in history; the newest (last created) decides the
        // summary's Last score + Verdict columns.
        $this->attempt($student, $assessment, 'failed', score: 50, submittedAt: Carbon::create(2026, 9, 1, 9, 0));
        $this->attempt($student, $assessment, 'failed', score: 40, submittedAt: Carbon::create(2026, 9, 1, 15, 0));
        $this->attempt($student, $assessment, 'passed', score: 90, submittedAt: Carbon::create(2026, 9, 2, 11, 0), passedAt: Carbon::create(2026, 9, 2, 11, 20));

        $response = $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]));

        $response->assertOk()->assertSee('Attempt log');
        $panel = $this->panelBodyBetween($response->getContent(), 'panel-title">Assessment Performance');
        $this->assertSame(1, substr_count($panel, '50%'));
        $this->assertSame(1, substr_count($panel, '40%'));
        $this->assertSame(2, substr_count($panel, '90%'));
        $this->assertSame(2, substr_count($panel, 'FAILED'));
        $this->assertSame(2, substr_count($panel, 'PASSED'));
    }

    public function test_student_progress_performance_covers_only_assessed_courses(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$assessed, , $missions] = $this->createCourseWithSections(1, [1 => 1]);
        $challenge = Assessment::factory()->create(['course_id' => $assessed->id]);
        [$bare] = $this->createCourseWithSections(2, [1 => 1], 'locked');

        $student = User::factory()->create(['username' => 'cadet_assessed_only']);
        $this->complete($student, $missions[0]);
        $this->attempt($student, $challenge, 'passed', score: 85, passedAt: Carbon::create(2026, 9, 2, 11, 0));

        $response = $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]));

        $response->assertOk();
        $content = $response->getContent();
        $panel = $this->panelBodyBetween($content, 'panel-title">Assessment Performance');
        $this->assertSame(2, substr_count($panel, $assessed->name));
        $this->assertSame(0, substr_count($panel, $bare->name));
        $this->assertSame(1, substr_count($content, $bare->name));
        $this->assertStringContainsString('BOSS CHALLENGE: LOCKED', $content);
    }

    public function test_the_student_progress_route_is_read_only(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $this->actingAs($teacher)
            ->post(route('student-progress', ['student' => $student->id]))
            ->assertMethodNotAllowed();

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk();
    }

    public function test_student_progress_shows_competency_breakdown_from_the_shared_service(): void
    {
        $teacher = User::factory()->teacher()->create();

        [$demonstrated, , $demoMissions] = $this->createCourseWithSections(1, [1 => 2]);
        Assessment::factory()->create(['course_id' => $demonstrated->id]);
        [$developing, , $develMissions] = $this->createCourseWithSections(2, [1 => 3]);
        Assessment::factory()->create(['course_id' => $developing->id]);

        $student = User::factory()->create(['username' => 'cadet_skill']);
        $this->complete($student, $demoMissions[0]);
        $this->complete($student, $demoMissions[1]);
        $this->attempt($student, Assessment::where('course_id', $demonstrated->id)->firstOrFail(), 'passed', score: 85);
        $this->complete($student, $develMissions[0]);
        $this->wrongSubmission($student, $develMissions[0]);

        $response = $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]));

        $response->assertOk()->assertSee('Competency');

        $panel = $this->panelBodyBetween($response->getContent(), 'panel-title">Competency');
        $this->assertSame(1, substr_count($panel, 'DEMONSTRATED'));
        $this->assertSame(1, substr_count($panel, 'DEVELOPING'));
        $this->assertSame(1, substr_count($panel, '2/2'));
        $this->assertSame(1, substr_count($panel, '1/3'));
        $this->assertSame(1, substr_count($panel, 'PASSED'));
        $this->assertSame(1, substr_count($panel, 'NOT ATTEMPTED'));
    }

    /**
     * Slice the body of a panel: from its title marker to the next panel
     * section opening. Scope count-based content assertions here so a later
     * panel added to the page (e.g. US-605's Competency panel) can never
     * silently change a whole-page count.
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

    private function wrongSubmission(User $user, Mission $mission): void
    {
        XpTransaction::factory()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'type' => XpService::TYPE_WRONG_SUBMISSION,
        ]);
    }

    /**
     * @return array{0: Course, 1: array<int, Section>, 2: array<int, Mission>}
     */
    private function createCourseWithSections(int $orderNum, array $sectionMissionCounts, string $status = 'active'): array
    {
        $course = Course::factory()->create(['status' => $status, 'order_num' => $orderNum]);
        $sections = [];
        $missions = [];
        $missionOrder = 1;

        foreach ($sectionMissionCounts as $sectionOrder => $missionCount) {
            $section = Section::factory()->create([
                'course_id' => $course->id,
                'order_num' => $sectionOrder,
            ]);
            $sections[$sectionOrder] = $section;

            foreach (range(1, $missionCount) as $order) {
                $missions[] = Mission::factory()->create([
                    'course_id' => $course->id,
                    'section_id' => $section->id,
                    'order_num' => $missionOrder++,
                ]);
            }
        }

        return [$course, $sections, $missions];
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

    private function attempt(
        User $user,
        Assessment $assessment,
        string $status,
        ?int $score = null,
        ?Carbon $submittedAt = null,
        ?Carbon $passedAt = null,
    ): void {
        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => $status,
            'score' => $score,
            'submitted_at' => $submittedAt,
            'passed_at' => $status === 'passed' ? ($passedAt ?? now()) : null,
        ]);
    }
}
