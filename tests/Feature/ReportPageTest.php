<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Services\ReportPdfExporter;
use App\Services\StudentProgressReportService;
use App\Support\ReportFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

class ReportPageTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_student_page_reuses_export_presentation_and_preserves_filters_and_own_scope(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create(['name' => 'Evidence course']);
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
        $filters = ['from' => now()->subDay()->toDateString(), 'to' => now()->toDateString()];
        $expected = app(ReportPdfExporter::class)->studentProgressView(app(StudentProgressReportService::class)->forStudent($student, null, ReportFilters::fromArray($filters)));
        $this->actingAs($student)->get(route('reports.progress', $filters))->assertOk()
            ->assertViewHas('document', $expected)->assertSee('DOWNLOAD CSV')->assertSee('DOWNLOAD PDF')
            ->assertSee(route('export.progress'))->assertSee($filters['from']);
        $csv = $this->get(route('export.progress', $filters))->assertOk()->streamedContent();
        $lines = preg_split('/\R/', trim($csv));
        $csvRow = str_getcsv($lines[1]);
        $this->assertSame(array_slice($expected['sections'][0]['rows'][0], 0, 6), array_slice($csvRow, 0, 6));
        $this->assertSame('No', $expected['sections'][0]['rows'][0][6]);
        $this->assertSame('false', $csvRow[6]);
        $this->getJson(route('reports.progress', ['from' => '2026-02-30']))->assertUnprocessable();
        $other = User::factory()->create();
        $this->get(route('reports.progress', ['student_id' => $other->id]))->assertOk()
            ->assertViewHas('document', fn (array $document): bool => $document['sections'][0]['rows'] === []);
    }

    public function test_teacher_pages_and_drill_down_share_current_classroom_authorization_with_exports(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['name' => 'Visible report course']);
        Mission::factory()->create(['course_id' => $course->id]);
        $classroom = $this->classroomFor($teacher, [$student], [$course]);
        $private = Course::factory()->create(['name' => 'PRIVATE REPORT COURSE']);
        Mission::factory()->create(['course_id' => $private->id]);
        $this->actingAs($teacher)->get(route('reports.teacher'))->assertOk()->assertSee(route('students'))->assertSee(route('course-analytics'));
        $this->get(route('student-progress', $student))->assertSee(route('reports.teacher-student', $student));
        $this->get(route('course-analytics'))->assertSee(route('reports.teacher-course', $course));
        $this->get(route('reports.teacher-student', $student))->assertOk()->assertSee('DOWNLOAD CSV')->assertDontSee('PRIVATE REPORT COURSE');
        $this->get(route('reports.teacher-course', $course))->assertOk()->assertSee('DOWNLOAD PDF');
        $this->get(route('reports.teacher-course', $private))->assertForbidden();
        $this->get(route('reports.teacher-student', User::factory()->create()))->assertForbidden();
        $classroom->update(['status' => 'inactive']);
        $this->get(route('reports.teacher-student', $student))->assertForbidden();
        $this->get(route('reports.teacher-course', $course))->assertForbidden();
    }

    public function test_report_role_gates_and_admin_entry_point(): void
    {
        $this->get(route('reports.progress'))->assertRedirect(route('login'));
        foreach (['student', 'teacher', 'operator'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route('reports.admin-system'))->assertForbidden();
        }
        $this->actingAs(User::factory()->admin()->create())->get(route('reports.admin-system'))->assertOk()->assertSee(route('export.admin-system'))->assertSee('DOWNLOAD PDF');
        $this->get(route('reports.progress'))->assertForbidden();
    }
}
