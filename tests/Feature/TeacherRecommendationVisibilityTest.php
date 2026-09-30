<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\ClassroomAccessService;
use App\Services\RecommendationService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-909 Teacher Recommendation Visibility. Teachers observe a student's
 * recommendations — title and reason only, never action links — scoped to
 * courses shared through authorized active classrooms. All authorization
 * flows through the existing route/policy/service chain; nothing here
 * re-derives recommendation eligibility.
 */
class TeacherRecommendationVisibilityTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_authorized_teacher_sees_the_students_recommendation_without_links(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('Course A', 1);
        $mission = $this->mission($course, 'Shared Signal');

        $this->classroomFor($teacher, [$student], [$course]);

        $content = $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('RECOMMENDATIONS')
            ->assertSee('Continue Learning')
            ->assertSee('Shared Signal')
            ->getContent();

        $this->assertStringNotContainsString(route('mission.show', $mission), $content);

        $panel = $this->recommendationsPanel($content);

        $this->assertStringNotContainsString('<a ', $panel);
        $this->assertStringNotContainsString('<form', $panel);
        $this->assertStringNotContainsString('href=', $panel);
    }

    public function test_unauthorized_teacher_is_refused(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('Course A', 1);
        $this->mission($course, 'Shared Signal');

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertForbidden();
    }

    public function test_cross_classroom_teacher_sees_only_the_shared_course(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('Course A', 1);
        $courseB = $this->course('Course B', 2);
        $missionA = $this->mission($courseA, 'Alpha Signal');
        $missionB = $this->mission($courseB, 'Beta Struggle');

        $this->classroomFor($teacherA, [$student], [$courseA]);
        $this->classroomFor($teacherB, [$student], [$courseB]);

        $this->complete($student, $missionB);
        $this->wrongSubmission($student, $missionB);
        $this->wrongSubmission($student, $missionB);

        $content = $this->actingAs($teacherA)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('Alpha Signal')
            ->getContent();

        $this->assertStringNotContainsString('Beta Struggle', $content);
        $this->assertStringNotContainsString(route('mission.show', $missionB), $content);
        $this->assertSame(1, substr_count($content, 'PRIORITY '));

        $this->actingAs($teacherB)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('Beta Struggle');
    }

    public function test_tampered_student_identifier_stays_forbidden(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $course = $this->course('Course A', 1);
        $this->mission($course, 'Shared Signal');

        $this->classroomFor($teacher, [$student], [$course]);

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $other->id]))
            ->assertForbidden();
    }

    public function test_out_of_scope_only_recommendations_render_no_panel(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('Course A', 1);
        $courseB = $this->course('Course B', 2);
        $missionA = $this->mission($courseA, 'Cleared Signal');
        $missionB = $this->mission($courseB, 'Distant Work');
        $assessmentA = Assessment::factory()->create(['course_id' => $courseA->id]);

        $this->classroomFor($teacher, [$student], [$courseA]);

        $this->complete($student, $missionA);
        $this->pass($student, $assessmentA);

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertDontSee('RECOMMENDATIONS')
            ->assertDontSee('Distant Work');
    }

    public function test_student_cannot_open_the_teacher_monitoring_surface(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('student-progress', ['student' => $other->id]))
            ->assertForbidden();
    }

    public function test_teacher_panel_exposes_no_protected_internals(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('Course A', 1);
        $this->mission($course, 'Shared Signal');

        $this->classroomFor($teacher, [$student], [$course]);

        $content = $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->getContent();

        foreach (['validate_rule', 'solution_code', 'grading_rule', 'is_correct', 'answer_key'] as $internal) {
            $this->assertStringNotContainsString($internal, $content);
        }
    }

    public function test_teacher_panel_matches_the_scoped_service_result(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('Course A', 1);
        $this->mission($course, 'Shared Signal');

        $this->classroomFor($teacher, [$student], [$course]);

        $scope = app(ClassroomAccessService::class)->courseIdsForStudent($teacher, $student);
        $cards = app(RecommendationService::class)->recommendations($student, $scope);

        $this->assertNotEmpty($cards);

        $content = $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->getContent();

        foreach ($cards as $card) {
            $this->assertStringContainsString($card['title'], $content);
            $this->assertStringContainsString($card['subtitle'], $content);
        }

        $this->assertSame($cards->count(), substr_count($content, 'PRIORITY '));
    }

    public function test_viewing_recommendations_mutates_no_learning_state(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('Course A', 1);
        $mission = $this->mission($course, 'Shared Signal');
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->classroomFor($teacher, [$student], [$course]);
        $this->complete($student, $mission);

        $xp = app(XpService::class);
        $assessments = app(AssessmentService::class);
        $before = [$xp->balance($student), $this->progressCount($student), $assessments->hasPassed($student, $course)];

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk();

        $this->assertSame($before, [$xp->balance($student), $this->progressCount($student), $assessments->hasPassed($student, $course)]);
    }

    public function test_admin_sees_unscoped_recommendations(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('Course A', 1);
        $courseB = $this->course('Course B', 2);
        $this->mission($courseA, 'Alpha Signal');
        $missionB = $this->mission($courseB, 'Beta Struggle');

        $this->complete($student, $missionB);
        $this->wrongSubmission($student, $missionB);
        $this->wrongSubmission($student, $missionB);

        $this->actingAs($admin)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('RECOMMENDATIONS')
            ->assertSee('Beta Struggle');
    }

    private function course(string $name, int $order): Course
    {
        return Course::factory()->create([
            'name' => $name,
            'status' => 'active',
            'order_num' => $order,
        ]);
    }

    private function mission(Course $course, string $title): Mission
    {
        $section = Section::factory()->create(['course_id' => $course->id]);

        return Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'title' => $title,
        ]);
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

    private function pass(User $user, Assessment $assessment): void
    {
        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'passed',
            'passed_at' => now(),
            'submitted_at' => now(),
        ]);
    }

    private function wrongSubmission(User $user, Mission $mission): void
    {
        DB::table('the404_xp_transactions')->insert([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => -10,
            'type' => XpService::TYPE_WRONG_SUBMISSION,
            'description' => 'Wrong submission on mission: '.$mission->title,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    private function progressCount(User $user): int
    {
        return Progress::query()->where('user_id', $user->id)->count();
    }

    /**
     * The rendered RECOMMENDATIONS panel slice, so read-only assertions stay
     * scoped to teacher recommendation output instead of the whole layout
     * (which legitimately carries its own navigation and forms).
     */
    private function recommendationsPanel(string $content): string
    {
        $start = strpos($content, 'RECOMMENDATIONS');

        $this->assertNotFalse($start);

        $rest = substr($content, (int) $start);
        $end = strpos($rest, '</section>');

        return $end === false ? $rest : substr($rest, 0, $end);
    }
}
