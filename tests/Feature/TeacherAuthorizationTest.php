<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

class TeacherAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    /**
     * Every teacher-gated route added across the phase (US-602..US-610 plus
     * the Classroom / Enrollment Authorization surface). The gate is one
     * middleware group in routes/web.php, but the regression test must
     * enumerate the routes explicitly: a later route registered outside the
     * group is exactly the drift US-511's audit was built to catch, and a
     * two-entry list would let the other four fall out of coverage silently.
     *
     * @return array<int, array{name: string, needsStudent?: bool, needsClassroom?: bool, needsCourse?: bool, adminForbidden?: bool}>
     */
    private function teacherRoutes(): array
    {
        return [
            ['name' => 'reports.teacher'],
            ['name' => 'reports.teacher-student', 'needsStudent' => true, 'adminForbidden' => true],
            ['name' => 'reports.teacher-course', 'needsCourse' => true, 'adminForbidden' => true],
            ['name' => 'students'],
            ['name' => 'student-progress', 'needsStudent' => true],
            ['name' => 'activity'],
            ['name' => 'course-analytics'],
            ['name' => 'needs-attention'],
            ['name' => 'classrooms'],
            ['name' => 'classrooms.show', 'needsClassroom' => true],
        ];
    }

    public function test_guests_are_redirected_to_login_when_requesting_teacher_routes(): void
    {
        $student = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $course = Course::factory()->create();

        foreach ($this->teacherRoutes() as $route) {
            $url = $this->teacherUrl($route, $student, $classroom, $course);

            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_students_are_forbidden_from_teacher_routes(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $classroom = Classroom::factory()->create();
        $course = Course::factory()->create();

        foreach ($this->teacherRoutes() as $route) {
            $url = $this->teacherUrl($route, $student, $classroom, $course);

            $this->actingAs($student)->get($url)->assertForbidden();
        }
    }

    public function test_operator_role_is_forbidden_from_teacher_routes(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $student = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $course = Course::factory()->create();

        foreach ($this->teacherRoutes() as $route) {
            $url = $this->teacherUrl($route, $student, $classroom, $course);

            $this->actingAs($operator)->get($url)->assertForbidden();
        }
    }

    public function test_teachers_can_access_the_teacher_routes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $classroom = $this->classroomFor($teacher, [$student], [$course]);

        // Gate assertion only: content-level checks for the pages live in
        // their own feature tests (StudentOverviewTest US-602, StudentProgressTest
        // US-603, ActivityFeedTest US-606, CourseAnalyticsTest US-607, AttentionTest
        // US-608, TeacherDashboardTest US-609). Routes are asserted Ok, not for
        // standby text, because they are all real pages rather than shells. The
        // student-progress and classroom detail routes resolve their bound
        // models, and the teacher's classroom scope grants the student.
        foreach ($this->teacherRoutes() as $route) {
            $url = $this->teacherUrl($route, $student, $classroom, $course);

            $this->actingAs($teacher)->get($url)->assertOk();
        }
    }

    public function test_admins_can_access_the_teacher_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $classroom = $this->classroomFor($admin, [$student], [$course]);

        foreach ($this->teacherRoutes() as $route) {
            $url = $this->teacherUrl($route, $student, $classroom, $course);

            $this->actingAs($admin)->get($url)->assertStatus(($route['adminForbidden'] ?? false) ? 403 : 200);
        }
    }

    /** @param array{name: string, needsStudent?: bool, needsClassroom?: bool, needsCourse?: bool, adminForbidden?: bool} $route */
    private function teacherUrl(array $route, User $student, Classroom $classroom, Course $course): string
    {
        $params = match (true) {
            ($route['needsClassroom'] ?? false) => ['classroom' => $classroom->id],
            ($route['needsStudent'] ?? false) => ['student' => $student->id],
            ($route['needsCourse'] ?? false) => ['course' => $course->id],
            default => [],
        };

        return route($route['name'], $params);
    }
}
