<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Services\ReportAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-1001 report authorization contract. Every verdict is exercised
 * directly against the service with real models; tampered identifiers are
 * just models the viewer is not entitled to, resolved the same way whether
 * the id arrives from a route parameter or request data.
 */
class ReportAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    private ReportAuthorizationService $auth;

    private User $student;

    private User $otherStudent;

    private User $teacher;

    private User $outsider;

    private User $admin;

    private User $operator;

    private Course $courseA;

    private Course $courseB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = app(ReportAuthorizationService::class);

        $this->student = User::factory()->create();
        $this->otherStudent = User::factory()->create();
        $this->teacher = User::factory()->teacher()->create();
        $this->outsider = User::factory()->teacher()->create();
        $this->admin = User::factory()->admin()->create();
        $this->operator = User::factory()->create(['role' => 'operator']);

        $this->courseA = Course::factory()->create();
        $this->courseB = Course::factory()->create();

        $this->classroomFor($this->teacher, [$this->student], [$this->courseA]);
    }

    public function test_student_may_access_own_report_scope(): void
    {
        $this->assertTrue($this->auth->canViewStudentReport($this->student, $this->student));
        $this->assertNull($this->auth->reportCourseIds($this->student, $this->student));
    }

    public function test_student_cannot_access_another_students_report_scope(): void
    {
        $this->assertFalse($this->auth->canViewStudentReport($this->student, $this->otherStudent));
    }

    public function test_student_cannot_access_teacher_course_scope(): void
    {
        $this->assertFalse($this->auth->canViewCourseReport($this->student, $this->courseA));
        $this->assertFalse($this->auth->canViewSystemReport($this->student));
    }

    public function test_authorized_teacher_may_access_assigned_student_scope(): void
    {
        $this->assertTrue($this->auth->canViewStudentReport($this->teacher, $this->student));
        $this->assertSame(
            [$this->courseA->id],
            $this->auth->reportCourseIds($this->teacher, $this->student)->all()
        );
    }

    public function test_teacher_without_classroom_cannot_access_student(): void
    {
        $this->assertFalse($this->auth->canViewStudentReport($this->outsider, $this->student));
    }

    public function test_teacher_course_scope_covers_only_shared_courses(): void
    {
        $this->assertTrue($this->auth->canViewCourseReport($this->teacher, $this->courseA));
        $this->assertFalse($this->auth->canViewCourseReport($this->teacher, $this->courseB));
        $this->assertFalse($this->auth->canViewSystemReport($this->teacher));
    }

    public function test_tampered_course_model_is_refused(): void
    {
        $unassigned = Course::factory()->create();

        $this->assertFalse($this->auth->canViewCourseReport($this->teacher, $unassigned));
    }

    public function test_tampered_student_model_is_refused(): void
    {
        $unenrolled = User::factory()->create();

        $this->assertFalse($this->auth->canViewStudentReport($this->teacher, $unenrolled));
    }

    public function test_inactive_classroom_removes_teacher_scope(): void
    {
        $classroom = $this->classroomFor($this->outsider, [$this->otherStudent], [$this->courseB]);
        $classroom->update(['status' => 'inactive']);

        $this->assertFalse($this->auth->canViewStudentReport($this->outsider, $this->otherStudent));
        $this->assertFalse($this->auth->canViewCourseReport($this->outsider, $this->courseB));
    }

    public function test_admin_may_access_permitted_system_wide_scope(): void
    {
        $this->assertTrue($this->auth->canViewStudentReport($this->admin, $this->student));
        $this->assertNull($this->auth->reportCourseIds($this->admin, $this->student));
        $this->assertTrue($this->auth->canViewCourseReport($this->admin, $this->courseB));
        $this->assertTrue($this->auth->canViewSystemReport($this->admin));
    }

    public function test_unsupported_role_fails_closed(): void
    {
        $this->assertFalse($this->auth->canViewStudentReport($this->operator, $this->student));
        $this->assertFalse($this->auth->canViewCourseReport($this->operator, $this->courseA));
        $this->assertFalse($this->auth->canViewSystemReport($this->operator));
        $this->assertTrue($this->auth->canViewStudentReport($this->operator, $this->operator));
    }

    public function test_verdict_is_identical_for_route_and_request_sourced_ids(): void
    {
        $fromRoute = User::find($this->student->id);
        $fromRequest = User::where('id', request()->merge(['user_id' => $this->student->id])->input('user_id'))->first();

        $this->assertTrue($this->auth->canViewStudentReport($this->teacher, $fromRoute));
        $this->assertTrue($this->auth->canViewStudentReport($this->teacher, $fromRequest));

        $foreignFromRoute = User::find($this->otherStudent->id);

        $this->assertFalse($this->auth->canViewStudentReport($this->teacher, $foreignFromRoute));
    }

    public function test_guest_middleware_layer_still_guards_authenticated_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
