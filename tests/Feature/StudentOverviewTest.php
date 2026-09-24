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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

class StudentOverviewTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_student_overview_requires_teacher_authorization(): void
    {
        $this->get(route('students'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('students'))
            ->assertForbidden();
    }

    public function test_student_overview_rejects_user_scoping_parameters(): void
    {
        $teacher = User::factory()->teacher()->create();

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $parameter) {
            $this->actingAs($teacher)
                ->get(route('students', [$parameter => 9999]))
                ->assertForbidden();
        }
    }

    public function test_student_overview_lists_students_only(): void
    {
        $pilotOne = User::factory()->create(['username' => 'pilot_one']);
        $pilotTwo = User::factory()->create(['username' => 'pilot_two']);
        User::factory()->create(['role' => 'admin', 'username' => 'admin_roster']);
        User::factory()->create(['role' => 'operator', 'username' => 'operator_roster']);
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create();

        $this->classroomFor($teacher, [$pilotOne, $pilotTwo], [$course]);

        $this->actingAs($teacher)
            ->get(route('students'))
            ->assertOk()
            ->assertSee('pilot_one')
            ->assertSee('pilot_two')
            ->assertDontSee('admin_roster')
            ->assertDontSee('operator_roster');
    }

    public function test_student_overview_shows_columns_derived_from_phase5_services(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, missionCount: 2);
        Assessment::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->create(['username' => 'cadet_inkling']);
        $this->complete($student, $missions[0]);
        $hint = new XpTransaction([
            'user_id' => $student->id,
            'mission_id' => $missions[0]->id,
            'type' => 'hint_used',
            'description' => 'Hint used on mission: alpha',
            'amount' => -5,
        ]);
        $hint->forceFill(['created_at' => now()])->save();

        $this->classroomFor($teacher, [$student], [$course]);

        $this->actingAs($teacher)
            ->get(route('students'))
            ->assertOk()
            ->assertSee('cadet_inkling')
            ->assertSee($course->name)
            ->assertSee('IN PROGRESS 1/2 · 50%')
            ->assertSee('LOCKED')
            ->assertSee('PRACTICING 1')
            ->assertSee('Hint used on mission: alpha')
            ->assertSee('ATTN');
    }

    public function test_student_overview_search_is_server_side_by_username_and_name(): void
    {
        $teacher = User::factory()->teacher()->create();
        $captain = User::factory()->create(['username' => 'captain_alpha', 'name' => 'Aaron Waters']);
        $lieutenant = User::factory()->create(['username' => 'lieutenant_bravo', 'name' => 'Bella Stone']);

        $this->classroomFor($teacher, [$captain, $lieutenant], [Course::factory()->create()]);

        $this->actingAs($teacher)
            ->get(route('students', ['q' => 'alpha']))
            ->assertOk()
            ->assertSee('captain_alpha')
            ->assertDontSee('lieutenant_bravo');

        $this->actingAs($teacher)
            ->get(route('students', ['q' => 'Stone']))
            ->assertOk()
            ->assertSee('lieutenant_bravo')
            ->assertSee('Bella Stone')
            ->assertDontSee('captain_alpha');

        $this->actingAs($teacher)
            ->get(route('students', ['q' => 'nobody_here']))
            ->assertOk()
            ->assertSee('NO TRAINEES');
    }

    public function test_student_overview_filters_by_current_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$first, $firstMissions] = $this->createCourseWithMissions(1, missionCount: 2);
        $firstAssessment = Assessment::factory()->create(['course_id' => $first->id]);
        [$second] = $this->createCourseWithMissions(2, missionCount: 2);
        Assessment::factory()->create(['course_id' => $second->id]);

        $learnerA = User::factory()->create(['username' => 'learner_a']);
        $this->complete($learnerA, $firstMissions[0]);

        $learnerB = User::factory()->create(['username' => 'learner_b']);
        $this->complete($learnerB, $firstMissions[0]);
        $this->complete($learnerB, $firstMissions[1]);
        $this->attempt($learnerB, $firstAssessment, 'passed');

        $this->classroomFor($teacher, [$learnerA, $learnerB], [$first, $second]);

        // The dashboard strips above the roster echo usernames system-wide, so
        // scope the roster-filter assertions to the roster region (feature.md).
        $rosterA = $this->rosterBody($this->actingAs($teacher)
            ->get(route('students', ['course' => $first->id]))->getContent());
        $this->assertStringContainsString('learner_a', $rosterA);
        $this->assertStringNotContainsString('learner_b', $rosterA);

        $rosterB = $this->rosterBody($this->actingAs($teacher)
            ->get(route('students', ['course' => $second->id]))->getContent());
        $this->assertStringContainsString('learner_b', $rosterB);
        $this->assertStringNotContainsString('learner_a', $rosterB);
    }

    public function test_course_filter_does_not_reveal_a_foreign_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $foreignCourse = Course::factory()->create();
        $this->classroomFor($teacher, [$student], [$course]);

        foreach ([$foreignCourse->id, $foreignCourse->id + 100000] as $courseId) {
            $this->actingAs($teacher)
                ->from(route('students'))
                ->get(route('students', ['course' => $courseId]))
                ->assertRedirect(route('students'))
                ->assertSessionHasErrors('course');
        }
    }

    public function test_student_overview_filters_by_progress_status(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, missionCount: 2);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $inProgress = User::factory()->create(['username' => 'status_building']);
        $this->complete($inProgress, $missions[0]);

        $ready = User::factory()->create(['username' => 'status_ready']);
        $this->complete($ready, $missions[0]);
        $this->complete($ready, $missions[1]);

        $cleared = User::factory()->create(['username' => 'status_cleared']);
        $this->complete($cleared, $missions[0]);
        $this->complete($cleared, $missions[1]);
        $this->attempt($cleared, $assessment, 'passed');

        $this->classroomFor($teacher, [$inProgress, $ready, $cleared], [$course]);

        $this->actingAs($teacher)
            ->get(route('students'))
            ->assertOk()
            ->assertSee('status_building')
            ->assertSee('IN PROGRESS 1/2 · 50%')
            ->assertSee('status_ready')
            ->assertSee('READY 2/2 · 100%')
            ->assertSee('status_cleared')
            ->assertSee('ALL CLEARED')
            ->assertSee('DEMONSTRATED 1');

        $inProgress = $this->rosterBody($this->actingAs($teacher)
            ->get(route('students', ['status' => 'in_progress']))->getContent());
        $this->assertStringContainsString('status_building', $inProgress);
        $this->assertStringNotContainsString('status_ready', $inProgress);
        $this->assertStringNotContainsString('status_cleared', $inProgress);

        $ready = $this->rosterBody($this->actingAs($teacher)
            ->get(route('students', ['status' => 'ready']))->getContent());
        $this->assertStringContainsString('status_ready', $ready);
        $this->assertStringNotContainsString('status_building', $ready);
        $this->assertStringNotContainsString('status_cleared', $ready);

        $cleared = $this->rosterBody($this->actingAs($teacher)
            ->get(route('students', ['status' => 'completed']))->getContent());
        $this->assertStringContainsString('status_cleared', $cleared);
        $this->assertStringNotContainsString('status_building', $cleared);
        $this->assertStringNotContainsString('status_ready', $cleared);
    }

    public function test_student_overview_paginates_results(): void
    {
        $teacher = User::factory()->teacher()->create();

        $cadets = [];
        foreach (range(1, 12) as $index) {
            $cadets[] = User::factory()->create(['username' => sprintf('cadet_%02d', $index)]);
        }

        $this->classroomFor($teacher, $cadets, [Course::factory()->create()]);

        $this->actingAs($teacher)
            ->get(route('students'))
            ->assertOk()
            ->assertSee('cadet_01')
            ->assertSee('cadet_10')
            ->assertDontSee('cadet_11')
            ->assertDontSee('cadet_12')
            ->assertSee('SHOWING PAGE 1 OF 2');

        $this->actingAs($teacher)
            ->get(route('students', ['page' => 2]))
            ->assertOk()
            ->assertSee('cadet_11')
            ->assertSee('cadet_12')
            ->assertDontSee('cadet_01')
            ->assertDontSee('cadet_02')
            ->assertSee('SHOWING PAGE 2 OF 2');
    }

    public function test_unfiltered_roster_query_count_stays_bounded_after_the_first_page(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$course] = $this->createCourseWithMissions(1);
        $students = User::factory()->count(12)->create(['role' => 'student']);
        $classroom = $this->classroomFor($teacher, $students->all(), [$course]);

        $small = $this->countQueries(fn (): mixed => $this->actingAs($teacher)->get(route('students'))->assertOk());

        $more = User::factory()->count(13)->create(['role' => 'student']);
        $classroom->students()->syncWithoutDetaching($more->pluck('id')->all());
        $large = $this->countQueries(fn (): mixed => $this->actingAs($teacher)->get(route('students'))->assertOk());

        $this->assertLessThanOrEqual($small + 10, $large, "Roster queries grew from {$small} to {$large} for 12 versus 25 students.");
    }

    public function test_displayed_roster_rows_do_not_multiply_queries(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$course] = $this->createCourseWithMissions(1);
        $students = User::factory()->count(1)->create(['role' => 'student']);
        $classroom = $this->classroomFor($teacher, $students->all(), [$course]);

        $one = $this->countQueries(fn (): mixed => $this->actingAs($teacher)->get(route('students'))->assertOk());

        $more = User::factory()->count(4)->create(['role' => 'student']);
        $classroom->students()->syncWithoutDetaching($more->pluck('id')->all());
        $five = $this->countQueries(fn (): mixed => $this->actingAs($teacher)->get(route('students'))->assertOk());

        $more = User::factory()->count(5)->create(['role' => 'student']);
        $classroom->students()->syncWithoutDetaching($more->pluck('id')->all());
        $ten = $this->countQueries(fn (): mixed => $this->actingAs($teacher)->get(route('students'))->assertOk());

        $this->assertLessThanOrEqual($one + 10, $five, "Roster queries grew {$one} → {$five} for 1 → 5 displayed students.");
        $this->assertLessThanOrEqual($one + 10, $ten, "Roster queries grew {$one} → {$ten} for 1 → 10 displayed students.");
    }

    public function test_roster_queries_stay_bounded_when_displayed_students_have_completed_sections(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$course, $missions] = $this->createCourseWithMissions(1);
        $students = User::factory()->count(1)->create(['role' => 'student']);
        $classroom = $this->classroomFor($teacher, $students->all(), [$course]);
        $this->complete($students->first(), $missions[0]);

        $one = $this->countQueries(fn (): mixed => $this->actingAs($teacher)->get(route('students'))->assertOk());

        $more = User::factory()->count(9)->create(['role' => 'student']);
        $classroom->students()->syncWithoutDetaching($more->pluck('id')->all());
        foreach ($more as $student) {
            $this->complete($student, $missions[0]);
        }

        $ten = $this->countQueries(fn (): mixed => $this->actingAs($teacher)->get(route('students'))->assertOk());

        $this->assertLessThanOrEqual($one + 10, $ten, "Completed-section roster queries grew {$one} → {$ten} for 1 → 10 students.");
    }

    private function countQueries(callable $operation): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $operation();

            return count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public function test_student_overview_validates_query_parameters(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->from(route('students'))
            ->get(route('students', ['course' => 9999]))
            ->assertRedirect(route('students'))
            ->assertSessionHasErrors('course');

        $this->actingAs($teacher)
            ->from(route('students'))
            ->get(route('students', ['status' => 'bogus']))
            ->assertRedirect(route('students'))
            ->assertSessionHasErrors('status');

        $this->actingAs($teacher)
            ->from(route('students'))
            ->get(route('students', ['q' => str_repeat('a', 65)]))
            ->assertRedirect(route('students'))
            ->assertSessionHasErrors('q');
    }

    /**
     * @return array{0: Course, 1: array<int, Mission>}
     */
    private function createCourseWithMissions(int $orderNum, string $status = 'active', int $missionCount = 1): array
    {
        $course = Course::factory()->create(['status' => $status, 'order_num' => $orderNum]);
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1]);

        $missions = [];
        foreach (range(1, $missionCount) as $order) {
            $missions[] = Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $order,
            ]);
        }

        return [$course, $missions];
    }

    /**
     * The /students page now carries system-wide dashboard strips above the
     * roster (US-609). Roster assertions must run against this slice, anchored
     * at the roster's filter form, so a username echoed by the recent-activity
     * strip can never bleed into a roster-filter assertion.
     */
    private function rosterBody(string $content): string
    {
        $from = strpos($content, '<form method="GET"');

        if ($from === false) {
            return '';
        }

        return substr($content, $from);
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

    private function attempt(User $user, Assessment $assessment, string $status): void
    {
        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => $status,
            'passed_at' => $status === 'passed' ? now() : null,
        ]);
    }
}
