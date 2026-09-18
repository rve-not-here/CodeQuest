<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every admin-gated route added across Phase 7 (US-701..US-713 plus the
     * Classroom / Enrollment Authorization surface). The gate is one
     * middleware group in routes/web.php, but the regression test must
     * enumerate the routes explicitly, mirroring the US-611 teacher audit:
     * a later admin route registered outside the group, or without the 'admin'
     * middleware, is exactly the drift this file exists to catch. Extend this
     * list in the same test method that registers the route. GET-only routes
     * are enumerated here; the admin.courses.store/update write routes are
     * exercised in AdminCourseManagementTest.
     *
     * @return array<int, array{name: string, needsUser?: bool, needsCourse?: bool, needsSection?: bool, needsMission?: bool, needsAnnouncement?: bool, needsClassroom?: bool}>
     */
    private function adminRoutes(): array
    {
        return [
            ['name' => 'admin.dashboard'],
            ['name' => 'admin.users'],
            ['name' => 'admin.users.create'],
            ['name' => 'admin.users.show', 'needsUser' => true],
            ['name' => 'admin.users.edit', 'needsUser' => true],
            ['name' => 'admin.courses'],
            ['name' => 'admin.courses.edit', 'needsCourse' => true],
            ['name' => 'admin.courses.sections', 'needsCourse' => true],
            ['name' => 'admin.courses.sections.edit', 'needsCourse' => true, 'needsSection' => true],
            ['name' => 'admin.courses.missions', 'needsCourse' => true],
            ['name' => 'admin.courses.missions.edit', 'needsCourse' => true, 'needsMission' => true],
            ['name' => 'admin.courses.assessment', 'needsCourse' => true],
            ['name' => 'admin.courses.assessment.edit', 'needsCourse' => true, 'needsAssessment' => true],
            ['name' => 'admin.classrooms'],
            ['name' => 'admin.classrooms.create'],
            ['name' => 'admin.classrooms.edit', 'needsClassroom' => true],
            ['name' => 'admin.announcements'],
            ['name' => 'admin.announcements.create'],
            ['name' => 'admin.announcements.edit', 'needsAnnouncement' => true],
            ['name' => 'admin.activity'],
            ['name' => 'admin.analytics'],
            ['name' => 'admin.system'],
        ];
    }

    /**
     * Implicit route model binding resolves before the role gate, so the bound
     * resources must really exist: a fabricated id 404s before the 403 under
     * test. Every id here comes from the rows the caller actually created (the
     * same discipline as test_admins_can_access_the_admin_routes).
     *
     * @param  array{user?: User, course?: Course, section?: Section, mission?: Mission, assessment?: Assessment, classroom?: Classroom, announcement?: Announcement}  $bound
     */
    private function uriFor(array $route, array $bound): string
    {
        return match (true) {
            isset($route['needsAssessment']) => route($route['name'], ['course' => $bound['course']->id, 'assessment' => $bound['assessment']->id]),
            isset($route['needsMission']) => route($route['name'], ['course' => $bound['course']->id, 'mission' => $bound['mission']->id]),
            isset($route['needsSection']) => route($route['name'], ['course' => $bound['course']->id, 'section' => $bound['section']->id]),
            isset($route['needsCourse']) => route($route['name'], ['course' => $bound['course']->id]),
            isset($route['needsUser']) => route($route['name'], ['user' => $bound['user']->id]),
            isset($route['needsAnnouncement']) => route($route['name'], ['announcement' => $bound['announcement']->id]),
            isset($route['needsClassroom']) => route($route['name'], ['classroom' => $bound['classroom']->id]),
            default => route($route['name']),
        };
    }

    public function test_guests_are_redirected_to_login_when_requesting_admin_routes(): void
    {
        $bound = $this->boundResources();

        foreach ($this->adminRoutes() as $route) {
            $this->get($this->uriFor($route, $bound))->assertRedirect(route('login'));
        }
    }

    public function test_students_are_forbidden_from_admin_routes(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $bound = $this->boundResources($student);

        foreach ($this->adminRoutes() as $route) {
            $this->actingAs($student)->get($this->uriFor($route, $bound))->assertForbidden();
        }
    }

    public function test_teachers_are_forbidden_from_admin_routes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $bound = $this->boundResources($teacher);

        foreach ($this->adminRoutes() as $route) {
            $this->actingAs($teacher)->get($this->uriFor($route, $bound))->assertForbidden();
        }
    }

    public function test_operator_role_is_forbidden_from_admin_routes(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $bound = $this->boundResources($operator);

        foreach ($this->adminRoutes() as $route) {
            $this->actingAs($operator)->get($this->uriFor($route, $bound))->assertForbidden();
        }
    }

    public function test_admins_can_access_the_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['username' => 'admin_route_target']);
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);
        $classroom = Classroom::factory()->create();
        $announcement = Announcement::factory()->create(['created_by' => $admin->id]);

        // Gate assertion only: content-level checks for the pages live in their
        // own feature tests (US-702..US-713). Routes are asserted Ok, not for
        // placeholder text, because later stories replace the stub. Bound
        // resources must really exist: implicit route model binding resolves
        // before the role gate, so a fabricated id 1 would 404 here.
        foreach ($this->adminRoutes() as $route) {
            $uri = match (true) {
                isset($route['needsAssessment']) => route($route['name'], ['course' => $course->id, 'assessment' => $assessment->id]),
                isset($route['needsMission']) => route($route['name'], ['course' => $course->id, 'mission' => $mission->id]),
                isset($route['needsSection']) => route($route['name'], ['course' => $course->id, 'section' => $section->id]),
                isset($route['needsCourse']) => route($route['name'], ['course' => $course->id]),
                isset($route['needsUser']) => route($route['name'], ['user' => $target->id]),
                isset($route['needsAnnouncement']) => route($route['name'], ['announcement' => $announcement->id]),
                isset($route['needsClassroom']) => route($route['name'], ['classroom' => $classroom->id]),
                default => route($route['name']),
            };

            $this->actingAs($admin)->get($uri)->assertOk();
        }
    }

    /**
     * US-712 fleet-wide drift guard: the matrix above is only as good as its
     * completeness, the exact failure shape the US-611 enumeration audit
     * taught. This asserts against the LIVE route table that (1) every route
     * named admin.* sits behind the admin middleware, and (2) the GET/HEAD
     * routes are exactly the enumerated matrix (write routes are exercised by
     * their own management tests and only earn an exception here because their
     * verbs are non-GET). A route registered outside the auth+admin group, or
     * a GET route added without a matrix row, fails this test.
     */
    public function test_every_admin_route_carries_the_admin_middleware_and_the_matrix_is_complete(): void
    {
        $adminRoutes = collect(RouteFacade::getRoutes()->getRoutesByName())
            ->filter(fn (Route $route): bool => is_string($route->getName()) && str_starts_with($route->getName(), 'admin.'));

        $this->assertGreaterThan(0, $adminRoutes->count(), 'there must be admin routes to audit');

        foreach ($adminRoutes as $name => $route) {
            $this->assertContains('admin', $route->gatherMiddleware(), "{$name} must sit behind the admin middleware");
        }

        $matrixNames = collect($this->adminRoutes())->pluck('name');

        foreach ($adminRoutes as $name => $route) {
            $verbs = $route->methods();
            $isWrite = in_array('POST', $verbs, true)
                || in_array('PUT', $verbs, true)
                || in_array('PATCH', $verbs, true)
                || in_array('DELETE', $verbs, true);

            if ($isWrite) {
                $this->assertNotContains($name, $matrixNames, "write routes stay out of the GET matrix: {$name}");

                continue;
            }

            $this->assertContains($name, $matrixNames, "every admin GET route must be enumerated in adminRoutes(): {$name}");
        }
    }

    /**
     * US-712: write routes carry the same gate as the GET pages; a store/update
     * call from any non-admin role must 403 before touching a controller. The
     * per-resource management tests only cast the teacher role at the update
     * endpoints, so every write route is probed here by all three rejected
     * roles to keep the guarantee fleet-wide.
     */
    public function test_students_teachers_and_operators_are_forbidden_from_every_admin_write_route(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->teacher()->create();
        $operator = User::factory()->create(['role' => 'operator']);
        $target = User::factory()->create(['username' => 'write_matrix_target']);
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);
        $classroom = Classroom::factory()->create();
        $announcement = Announcement::factory()->create();

        $calls = [
            ['method' => 'post', 'uri' => route('admin.users.store'), 'data' => ['username' => 'sneaky_matrix', 'name' => 'Sneaky', 'role' => 'student', 'password' => 'password-123']],
            ['method' => 'put', 'uri' => route('admin.users.update', $target), 'data' => ['username' => 'write_matrix_target', 'name' => 'Escalated', 'role' => 'admin']],
            ['method' => 'put', 'uri' => route('admin.courses.update', $course), 'data' => ['name' => 'Sneaky Course', 'slug' => $course->slug, 'type' => $course->type, 'status' => $course->status, 'order_num' => $course->order_num]],
            ['method' => 'put', 'uri' => route('admin.courses.sections.update', [$course, $section]), 'data' => ['title' => 'Sneaky Section']],
            ['method' => 'put', 'uri' => route('admin.courses.missions.update', [$course, $mission]), 'data' => ['title' => 'Sneaky Mission']],
            ['method' => 'put', 'uri' => route('admin.courses.assessment.update', [$course, $assessment]), 'data' => ['title' => 'Sneaky Assessment', 'passing_score' => 70, 'status' => 'active']],
            ['method' => 'post', 'uri' => route('admin.classrooms.store'), 'data' => ['name' => 'Sneaky Classroom', 'status' => 'active']],
            ['method' => 'put', 'uri' => route('admin.classrooms.update', $classroom), 'data' => ['name' => 'Sneaky Classroom', 'status' => 'inactive']],
            ['method' => 'post', 'uri' => route('admin.classrooms.teachers', $classroom), 'data' => ['teacher_ids' => [$teacher->id]]],
            ['method' => 'post', 'uri' => route('admin.classrooms.students', $classroom), 'data' => ['student_ids' => [$student->id]]],
            ['method' => 'post', 'uri' => route('admin.classrooms.courses', $classroom), 'data' => ['course_ids' => [$course->id]]],
            ['method' => 'post', 'uri' => route('admin.announcements.store'), 'data' => ['title' => 'Sneaky Announcement', 'message' => 'Sneaky', 'audience' => 'all']],
            ['method' => 'put', 'uri' => route('admin.announcements.update', $announcement), 'data' => ['title' => 'Sneaky Announcement', 'message' => 'Sneaky', 'audience' => 'all']],
            ['method' => 'post', 'uri' => route('admin.announcements.publish', $announcement), 'data' => []],
            ['method' => 'post', 'uri' => route('admin.announcements.archive', $announcement), 'data' => []],
        ];

        foreach ([$student, $teacher, $operator] as $requester) {
            foreach ($calls as $call) {
                $this->actingAs($requester)->{$call['method']}($call['uri'], $call['data'])->assertForbidden();
            }
        }

        $this->assertSame('student', User::find($target->id)->role, 'a role-escalation attempt must never mutate');
        $this->assertDatabaseMissing('the404_users', ['username' => 'sneaky_matrix']);
        $this->assertSame('draft', $announcement->refresh()->status, 'a publish/archive attempt from a rejected role must never mutate the announcement');
    }

    /**
     * @return array{user: User, course: Course, section: Section, mission: Mission, assessment: Assessment, classroom: Classroom, announcement: Announcement}
     */
    private function boundResources(?User $user = null): array
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);
        $classroom = Classroom::factory()->create();
        $announcement = Announcement::factory()->create();

        return [
            'user' => $user ?? User::factory()->create(),
            'course' => $course,
            'section' => $section,
            'mission' => $mission,
            'assessment' => $assessment,
            'classroom' => $classroom,
            'announcement' => $announcement,
        ];
    }
}
