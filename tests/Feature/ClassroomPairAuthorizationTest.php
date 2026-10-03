<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Services\ClassroomAccessService;
use App\Services\CourseAnalyticsService;
use App\Services\StudentService;
use App\Services\TeacherDashboardService;
use App\Services\TimelineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * Classroom / Enrollment Authorization — the pair constraint, end to end.
 *
 * The monitoring scope is NOT two independent sets ({S1,S2} students and
 * {C1,C2} courses). It is an exact set of (student, course) pairs: S1 may be
 * seen only in C1, S2 only in C2, even though both students and both courses
 * are inside the teacher's union. A flattened `student_id IN (...) AND
 * course_id IN (...)` filter would wrongly expose S1/C2 and S2/C1 — the
 * cross-product bug this test pins down.
 *
 * The fleet below deliberately cross-sows every pair (each student does work in
 * both courses). Only the two authorized pairs may contribute to course
 * analytics, dashboard metrics, recent activity, and the roster's Last activity
 * column.
 */
class ClassroomPairAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_services_confine_a_teacher_to_exact_student_course_pairs(): void
    {
        $fleet = $this->crossSownFleet();

        $access = app(ClassroomAccessService::class);
        $scope = $access->scopesFor($fleet['teacher']);

        $this->assertEqualsCanonicalizing(
            [$fleet['s1']->id, $fleet['s2']->id],
            $scope['studentIds']->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$fleet['c1']->id, $fleet['c2']->id],
            $scope['courseIds']->all(),
        );
        $this->assertSame([$fleet['c1']->id], $scope['byStudent'][$fleet['s1']->id]->all());
        $this->assertSame([$fleet['c2']->id], $scope['byStudent'][$fleet['s2']->id]->all());

        $this->assertCourseAnalyticsIsPairScoped($fleet, $scope);
        $this->assertDashboardIsPairScoped($fleet, $scope);
        $this->assertRecentActivityIsPairScoped($fleet, $scope);
        $this->assertRosterLastActivityIsPairScoped($fleet, $scope);
    }

    public function test_an_active_classroom_with_no_courses_yields_no_data(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();

        $this->classroomFor($teacher, [$student]);

        $scope = app(ClassroomAccessService::class)->scopesFor($teacher);
        $this->assertSame([], $scope['byStudent']);

        $dashboard = app(TeacherDashboardService::class)->overview(
            8,
            $scope['studentIds'],
            $scope['courseIds'],
            $scope['byStudent'],
        );

        $this->assertSame(0, $dashboard['metrics']['total_students']);
        $this->assertSame(0, $dashboard['metrics']['active_students']);
        $this->assertSame(0, $dashboard['metrics']['assessments_passed']);
        $this->assertSame(0, $dashboard['metrics']['needs_attention']);
        $this->assertTrue($dashboard['recent_activity']->isEmpty());
        $this->assertTrue($dashboard['assessment_summary']->isEmpty());

        $this->actingAs($teacher)
            ->get(route('activity'))
            ->assertOk()
            ->assertSee('NO ACTIVITY');
    }

    public function test_deactivating_a_classroom_removes_its_pairs_and_reactivating_restores_them(): void
    {
        $fleet = $this->crossSownFleet();

        $access = app(ClassroomAccessService::class);

        $before = $access->scopesFor($fleet['teacher']);
        $this->assertArrayHasKey($fleet['s2']->id, $before['byStudent']);
        $this->assertSame(2, app(TeacherDashboardService::class)->overview(
            8,
            $before['studentIds'],
            $before['courseIds'],
            $before['byStudent'],
        )['metrics']['assessments_passed']);

        $fleet['classroomB']->update(['status' => Classroom::STATUS_INACTIVE]);

        $during = $access->scopesFor($fleet['teacher']);
        $this->assertFalse($access->isAuthorizedForStudentCourse($fleet['teacher'], $fleet['s2'], $fleet['c2']));
        $this->assertArrayNotHasKey($fleet['s2']->id, $during['byStudent']);
        $this->assertSame([$fleet['c1']->id], $during['courseIds']->all());
        $this->assertSame(1, app(TeacherDashboardService::class)->overview(
            8,
            $during['studentIds'],
            $during['courseIds'],
            $during['byStudent'],
        )['metrics']['assessments_passed']);

        $fleet['classroomB']->update(['status' => Classroom::STATUS_ACTIVE]);

        $after = $access->scopesFor($fleet['teacher']);
        $this->assertArrayHasKey($fleet['s2']->id, $after['byStudent']);
        $this->assertSame(2, app(TeacherDashboardService::class)->overview(
            8,
            $after['studentIds'],
            $after['courseIds'],
            $after['byStudent'],
        )['metrics']['assessments_passed']);
    }

    /**
     * When active Classrooms overlap on the same student or the same course,
     * the monitoring scope is the EXACT union of their (student, course) pairs —
     * a course reachable through two classrooms must not widen authorization or
     * be counted twice.
     *
     * Teacher T:  Classroom A {S1: C1, C2}  Classroom B {S1: C2, C3}
     *             Classroom C {S2: C4}
     * Expected:   S1 -> C1, C2, C3 (C2 deduplicated)   S2 -> C4
     * Forbidden:  S1/C4   S2/C1   S2/C2   S2/C3
     */
    public function test_overlapping_active_classrooms_union_pairs_exactly_without_duplicating_shared_courses(): void
    {
        $fleet = $this->overlappingFleet();
        $access = app(ClassroomAccessService::class);

        $scope = $access->scopesFor($fleet['teacher']);

        $this->assertEqualsCanonicalizing(
            [$fleet['s1']->id, $fleet['s2']->id],
            $scope['studentIds']->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$fleet['c1']->id, $fleet['c2']->id, $fleet['c3']->id, $fleet['c4']->id],
            $scope['courseIds']->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$fleet['c1']->id, $fleet['c2']->id, $fleet['c3']->id],
            $scope['byStudent'][$fleet['s1']->id]->all(),
        );
        $this->assertSame([$fleet['c4']->id], $scope['byStudent'][$fleet['s2']->id]->all());
        $this->assertCount(3, $scope['byStudent'][$fleet['s1']->id], 'C2 is shared by classrooms A and B but must appear once.');
        $this->assertCount(4, $scope['courseIds'], 'The course union must be deduplicated.');

        $this->assertTrue($access->isAuthorizedForStudentCourse($fleet['teacher'], $fleet['s1'], $fleet['c1']));
        $this->assertTrue($access->isAuthorizedForStudentCourse($fleet['teacher'], $fleet['s1'], $fleet['c2']));
        $this->assertTrue($access->isAuthorizedForStudentCourse($fleet['teacher'], $fleet['s1'], $fleet['c3']));
        $this->assertTrue($access->isAuthorizedForStudentCourse($fleet['teacher'], $fleet['s2'], $fleet['c4']));

        $this->assertFalse($access->isAuthorizedForStudentCourse($fleet['teacher'], $fleet['s1'], $fleet['c4']));
        $this->assertFalse($access->isAuthorizedForStudentCourse($fleet['teacher'], $fleet['s2'], $fleet['c1']));
        $this->assertFalse($access->isAuthorizedForStudentCourse($fleet['teacher'], $fleet['s2'], $fleet['c2']));
        $this->assertFalse($access->isAuthorizedForStudentCourse($fleet['teacher'], $fleet['s2'], $fleet['c3']));

        // The shared course C2 must not be counted twice in analytics, and the
        // forbidden pairs must contribute nothing.
        $rows = app(CourseAnalyticsService::class)
            ->overview($scope['studentIds'], $scope['courseIds'], $scope['byStudent'])
            ->keyBy(fn (array $row): int => $row['course']->id);

        foreach ([$fleet['c1']->id, $fleet['c2']->id, $fleet['c3']->id, $fleet['c4']->id] as $courseId) {
            $this->assertSame(1, $rows[$courseId]['fleet'], 'Course '.$courseId.' must have exactly one authorized student.');
            $this->assertSame(1, $rows[$courseId]['engaged'], 'Course '.$courseId.' must count one student, not a duplicated one.');
            $this->assertSame(
                ['completed' => 1, 'in_progress' => 0, 'assessment_ready' => 0, 'not_started' => 0],
                $rows[$courseId]['buckets'],
            );
        }

        $dashboard = app(TeacherDashboardService::class)->overview(
            8,
            $scope['studentIds'],
            $scope['courseIds'],
            $scope['byStudent'],
        );
        $this->assertSame(2, $dashboard['metrics']['total_students']);
        $this->assertSame(2, $dashboard['metrics']['active_students']);
        $this->assertSame(4, $dashboard['metrics']['assessments_passed'], 'S1 passes 3 + S2 passes 1; the forbidden cross-pairs must not add any.');
    }

    /**
     * @param  array<string, mixed>  $fleet
     * @param  array{studentIds: mixed, courseIds: mixed, byStudent: mixed}  $scope
     */
    private function assertCourseAnalyticsIsPairScoped(array $fleet, array $scope): void
    {
        // Guard against a vacuously-green fixture: under the flattened scope a
        // combined student/course filter would compute for C1, the cross-product
        // bug doubles the engagement. The exact pairs below must halve it again.
        $flattened = [
            $fleet['s1']->id => collect([$fleet['c1']->id, $fleet['c2']->id]),
            $fleet['s2']->id => collect([$fleet['c1']->id, $fleet['c2']->id]),
        ];
        $crossProduct = app(CourseAnalyticsService::class)
            ->overview(
                collect([$fleet['s1']->id, $fleet['s2']->id]),
                collect([$fleet['c1']->id, $fleet['c2']->id]),
                $flattened,
            )
            ->keyBy(fn (array $row): int => $row['course']->id);
        $this->assertSame(2, $crossProduct[$fleet['c1']->id]['engaged']);

        $rows = app(CourseAnalyticsService::class)
            ->overview($scope['studentIds'], $scope['courseIds'], $scope['byStudent'])
            ->keyBy(fn (array $row): int => $row['course']->id);

        $alpha = $rows[$fleet['c1']->id];
        $this->assertSame(1, $alpha['fleet']);
        $this->assertSame(1, $alpha['engaged']);
        $this->assertSame(
            ['completed' => 1, 'in_progress' => 0, 'assessment_ready' => 0, 'not_started' => 0],
            $alpha['buckets'],
        );
        $this->assertSame(100, $alpha['pass_rate']);

        $bravo = $rows[$fleet['c2']->id];
        $this->assertSame(1, $bravo['fleet']);
        $this->assertSame(1, $bravo['engaged']);
        $this->assertSame(
            ['completed' => 1, 'in_progress' => 0, 'assessment_ready' => 0, 'not_started' => 0],
            $bravo['buckets'],
        );
        $this->assertSame(100, $bravo['pass_rate']);
    }

    /**
     * @param  array<string, mixed>  $fleet
     * @param  array{studentIds: mixed, courseIds: mixed, byStudent: mixed}  $scope
     */
    private function assertDashboardIsPairScoped(array $fleet, array $scope): void
    {
        $dashboard = app(TeacherDashboardService::class)->overview(
            8,
            $scope['studentIds'],
            $scope['courseIds'],
            $scope['byStudent'],
        );

        $this->assertSame(2, $dashboard['metrics']['total_students']);
        $this->assertSame(2, $dashboard['metrics']['active_students']);
        $this->assertSame(2, $dashboard['metrics']['assessments_passed']);
    }

    /**
     * @param  array<string, mixed>  $fleet
     * @param  array{studentIds: mixed, courseIds: mixed, byStudent: mixed}  $scope
     */
    private function assertRecentActivityIsPairScoped(array $fleet, array $scope): void
    {
        $beats = app(TeacherDashboardService::class)->overview(
            8,
            $scope['studentIds'],
            $scope['courseIds'],
            $scope['byStudent'],
        )['recent_activity']
            ->map(fn (array $beat): string => $beat['user']['username'].'|'.$beat['label'])
            ->all();

        $this->assertCount(4, $beats);
        $this->assertContains($fleet['s1']->username.'|Challenge completed: '.$fleet['m1']->title, $beats);
        $this->assertContains($fleet['s2']->username.'|Challenge completed: '.$fleet['m2']->title, $beats);
        $this->assertNotContains($fleet['s1']->username.'|Challenge completed: '.$fleet['m2']->title, $beats);
        $this->assertNotContains($fleet['s2']->username.'|Challenge completed: '.$fleet['m1']->title, $beats);
        $this->assertContains(
            $fleet['s1']->username.'|Boss Challenge passed: Alpha Boss (score 90)',
            $beats,
        );
        $this->assertContains(
            $fleet['s2']->username.'|Boss Challenge passed: Bravo Boss (score 90)',
            $beats,
        );
    }

    /**
     * @param  array<string, mixed>  $fleet
     * @param  array{studentIds: mixed, courseIds: mixed, byStudent: mixed}  $scope
     */
    private function assertRosterLastActivityIsPairScoped(array $fleet, array $scope): void
    {
        $rows = app(StudentService::class)
            ->index(null, null, null, [], $scope['byStudent'])
            ->items();
        $byUsername = collect($rows)->keyBy('username');

        $this->assertSame(
            'Boss Challenge passed: Alpha Boss (score 90)',
            $byUsername[$fleet['s1']->username]['lastActivity']['message'],
        );
        $this->assertSame(
            'Boss Challenge passed: Bravo Boss (score 90)',
            $byUsername[$fleet['s2']->username]['lastActivity']['message'],
        );

        // The unscoped single-student view shows the newest beat, which here is
        // the forbidden course — proof the scoped view is not the same query.
        $this->assertSame(
            'Boss Challenge passed: Bravo Boss (score 90)',
            app(TimelineService::class)->events($fleet['s1'], 1)->first()['label'],
        );
    }

    /**
     * Teacher T owns classroom A (student S1, course C1) and classroom B
     * (student S2, course C2). Both students do work in BOTH courses, and each
     * has a newer pass in the course that belongs to the OTHER classroom, so a
     * flattened student/course filter would surface the wrong beat and inflate
     * every count.
     *
     * @return array{
     *     teacher: User, s1: User, s2: User, c1: Course, c2: Course,
     *     m1: Mission, m2: Mission, a1: Assessment, a2: Assessment,
     *     classroomA: Classroom, classroomB: Classroom,
     * }
     */
    private function crossSownFleet(): array
    {
        $teacher = $this->teacher();
        $s1 = $this->student();
        $s2 = $this->student();

        $c1 = Course::factory()->create(['name' => 'Alpha Pair', 'order_num' => 1]);
        $c2 = Course::factory()->create(['name' => 'Bravo Pair', 'order_num' => 2]);

        $m1 = Mission::factory()->create(['course_id' => $c1->id]);
        $m2 = Mission::factory()->create(['course_id' => $c2->id]);

        $a1 = Assessment::factory()->create([
            'course_id' => $c1->id,
            'title' => 'Alpha Boss',
            'status' => 'active',
        ]);
        $a2 = Assessment::factory()->create([
            'course_id' => $c2->id,
            'title' => 'Bravo Boss',
            'status' => 'active',
        ]);

        $classroomA = $this->classroomFor($teacher, [$s1], [$c1]);
        $classroomB = $this->classroomFor($teacher, [$s2], [$c2]);

        // Each student completes both courses. The scoped timeline must emit
        // only the progress rows belonging to the teacher's exact pairs.
        foreach ([$s1, $s2] as $student) {
            foreach ([$m1, $m2] as $mission) {
                Progress::query()->create([
                    'user_id' => $student->id,
                    'mission_id' => $mission->id,
                    'pts_earned' => 10,
                    'completed_at' => now()->subHours(3),
                    'created_at' => now()->subHours(3),
                ]);
            }
        }

        // The authorized pass is OLDER than the forbidden one, so the newest
        // beat (and the roster's Last activity cell) can only be correct if the
        // pair filter really ran.
        $this->attempt($s1, $a1, now()->subHours(2));
        $this->attempt($s1, $a2, now()->subMinutes(2));
        $this->attempt($s2, $a2, now()->subHours(2));
        $this->attempt($s2, $a1, now()->subMinutes(2));

        return compact(
            'teacher', 's1', 's2', 'c1', 'c2',
            'm1', 'm2', 'a1', 'a2', 'classroomA', 'classroomB',
        );
    }

    /**
     * Teacher T owns three ACTIVE classrooms that overlap on course C2:
     *   A {S1: C1, C2}   B {S1: C2, C3}   C {S2: C4}
     * Every student does work in every course, so a scope that is not the exact
     * union of authorized pairs — or that double-counts the shared course —
     * will produce the wrong numbers.
     *
     * @return array{
     *     teacher: User, s1: User, s2: User,
     *     c1: Course, c2: Course, c3: Course, c4: Course,
     *     classroomA: Classroom, classroomB: Classroom, classroomC: Classroom,
     * }
     */
    private function overlappingFleet(): array
    {
        $teacher = $this->teacher();
        $s1 = $this->student();
        $s2 = $this->student();

        $c1 = Course::factory()->create(['name' => 'Alpha Overlap', 'order_num' => 1]);
        $c2 = Course::factory()->create(['name' => 'Bravo Overlap', 'order_num' => 2]);
        $c3 = Course::factory()->create(['name' => 'Charlie Overlap', 'order_num' => 3]);
        $c4 = Course::factory()->create(['name' => 'Delta Overlap', 'order_num' => 4]);
        $courses = [$c1, $c2, $c3, $c4];

        $classroomA = $this->classroomFor($teacher, [$s1], [$c1, $c2]);
        $classroomB = $this->classroomFor($teacher, [$s1], [$c2, $c3]);
        $classroomC = $this->classroomFor($teacher, [$s2], [$c4]);

        foreach ($courses as $index => $course) {
            $mission = Mission::factory()->create(['course_id' => $course->id]);
            $assessment = Assessment::factory()->create([
                'course_id' => $course->id,
                'title' => $course->name.' Boss',
                'status' => 'active',
            ]);

            foreach ([$s1, $s2] as $student) {
                Progress::query()->create([
                    'user_id' => $student->id,
                    'mission_id' => $mission->id,
                    'pts_earned' => 10,
                    'completed_at' => now()->subHours(5 - $index),
                    'created_at' => now()->subHours(5 - $index),
                ]);

                $this->attempt($student, $assessment, now()->subHours(5 - $index));
            }
        }

        return compact(
            'teacher', 's1', 's2', 'c1', 'c2', 'c3', 'c4',
            'classroomA', 'classroomB', 'classroomC',
        );
    }

    private function attempt(User $student, Assessment $assessment, Carbon $at): AssessmentAttempt
    {
        return AssessmentAttempt::factory()->create([
            'user_id' => $student->id,
            'assessment_id' => $assessment->id,
            'status' => 'passed',
            'score' => 90,
            'passed_at' => $at,
            'submitted_at' => $at,
            'created_at' => $at,
        ]);
    }

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function student(): User
    {
        return User::factory()->create();
    }
}
