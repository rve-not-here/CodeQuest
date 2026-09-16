<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every teacher-gated route added across the phase (US-602..US-610). The
     * gate is one middleware group in routes/web.php, but the regression test
     * must enumerate the routes explicitly: a later route registered outside
     * the group is exactly the drift US-511's audit was built to catch, and a
     * two-entry list would let the other four fall out of coverage silently.
     *
     * @return array<int, array{name: string, needsStudent?: bool}>
     */
    private function teacherRoutes(): array
    {
        return [
            ['name' => 'students'],
            ['name' => 'student-progress', 'needsStudent' => true],
            ['name' => 'activity'],
            ['name' => 'course-analytics'],
            ['name' => 'needs-attention'],
        ];
    }

    public function test_guests_are_redirected_to_login_when_requesting_teacher_routes(): void
    {
        foreach ($this->teacherRoutes() as $route) {
            $this->get(route($route['name']))->assertRedirect(route('login'));
        }
    }

    public function test_students_are_forbidden_from_teacher_routes(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        foreach ($this->teacherRoutes() as $route) {
            $this->actingAs($student)->get(route($route['name']))->assertForbidden();
        }
    }

    public function test_operator_role_is_forbidden_from_teacher_routes(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        foreach ($this->teacherRoutes() as $route) {
            $this->actingAs($operator)->get(route($route['name']))->assertForbidden();
        }
    }

    public function test_teachers_can_access_the_teacher_routes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);

        // Gate assertion only: content-level checks for the pages live in
        // their own feature tests (StudentOverviewTest US-602, StudentProgressTest
        // US-603, ActivityFeedTest US-606, CourseAnalyticsTest US-607, AttentionTest
        // US-608, TeacherDashboardTest US-609). Routes are asserted Ok, not for
        // standby text, because they are all real pages rather than shells.
        foreach ($this->teacherRoutes() as $route) {
            $url = ($route['needsStudent'] ?? false)
                ? route($route['name'], ['student' => $student->id])
                : route($route['name']);

            $this->actingAs($teacher)->get($url)->assertOk();
        }
    }

    public function test_admins_can_access_the_teacher_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        foreach ($this->teacherRoutes() as $route) {
            $url = ($route['needsStudent'] ?? false)
                ? route($route['name'], ['student' => $student->id])
                : route($route['name']);

            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
