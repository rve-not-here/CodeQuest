<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Services\ReportAuthorizationService;
use App\Services\ReportPdfExporter;
use App\Services\TeacherCourseReportService;
use App\Services\TeacherStudentReportService;
use App\Support\ReportFilters;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

class ReportSecurityTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    /** @return array<string, array{string}> */
    public static function revokedRoles(): array
    {
        return ['student' => ['student'], 'operator' => ['operator'], 'unknown' => ['auditor']];
    }

    #[DataProvider('revokedRoles')]
    public function test_retained_assignments_do_not_grant_report_authority_after_role_change(string $role): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $this->classroomFor($teacher, [$student], [$course]);
        $authorization = app(ReportAuthorizationService::class);
        $this->assertTrue($authorization->canViewStudentReport($teacher, $student));
        $this->assertTrue($authorization->canViewCourseReport($teacher, $course));

        $teacher->role = $role;

        if ($role !== 'auditor') {
            $teacher->save();
            $teacher->refresh();
        }

        $this->assertFalse($authorization->canViewStudentReport($teacher, $student));
        $this->assertFalse($authorization->canViewCourseReport($teacher, $course));
        $this->assertSame([], $authorization->reportCourseIds($teacher, $student)?->all());
        $this->assertFalse($authorization->canViewSystemReport($teacher));
    }

    /** @return array<string, array{string}> */
    public static function formulaPrefixes(): array
    {
        return ['tab' => ["\t"], 'carriage return' => ["\r"], 'line feed' => ["\n"]];
    }

    #[DataProvider('formulaPrefixes')]
    public function test_csv_download_neutralizes_control_character_formula_prefixes(string $prefix): void
    {
        $admin = User::factory()->admin()->create();
        $title = $prefix.'=1+1';
        $mission = Mission::factory()->create(['title' => $title]);
        Progress::factory()->create(['mission_id' => $mission->id]);

        $response = $this->actingAs($admin)->get(route('export.admin-system', ['dataset' => 'challenges']));

        $response->assertOk()->assertDownload();
        $this->assertStringContainsString("\"'".$title.'"', $response->streamedContent());
    }

    /** @return array<string, array{string, string, string, int}> */
    public static function exportAccess(): array
    {
        $cases = [];

        foreach (['csv', 'pdf'] as $format) {
            foreach (['guest', 'student', 'teacher', 'admin', 'operator'] as $role) {
                foreach (['progress', 'teacher-student', 'teacher-course', 'admin-system'] as $report) {
                    $allowed = ($report === 'progress' && $role === 'student')
                        || (str_starts_with($report, 'teacher-') && $role === 'teacher')
                        || ($report === 'admin-system' && $role === 'admin');
                    $cases["{$role} {$report} {$format}"] = [$role, $report, $format, $role === 'guest' ? 302 : ($allowed ? 200 : 403)];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('exportAccess')]
    public function test_export_access_matrix(string $role, string $report, string $format, int $status): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create(['name' => 'Protected course sentinel']);
        Mission::factory()->create(['course_id' => $course->id]);

        if ($role !== 'guest') {
            $viewer = $role === 'student' ? $student : User::factory()->create(['role' => $role]);
            $this->classroomFor($viewer, [$student], [$course]);
            $this->actingAs($viewer);
        }

        $params = match ($report) {
            'teacher-student' => ['student' => $student->id],
            'teacher-course' => ['course' => $course->id],
            default => [],
        };
        $response = $this->get(route('export.'.$report, $params + ['format' => $format]));

        $response->assertStatus($status);

        if ($status === 200) {
            $response->assertDownload();
            $this->assertStringStartsWith($format === 'pdf' ? '%PDF-' : "\xEF\xBB\xBF", $response->streamedContent());
        } else {
            $response->assertHeaderMissing('Content-Disposition')->assertDontSee('Protected course sentinel');

            if ($role === 'guest') {
                $response->assertRedirect(route('login'));
            }
        }
    }

    public function test_export_route_inventory_retains_authentication_and_role_middleware(): void
    {
        $expected = [
            'export.progress' => ['web', 'auth'],
            'export.teacher-student' => ['web', 'auth', 'teacher'],
            'export.teacher-course' => ['web', 'auth', 'teacher'],
            'export.admin-system' => ['web', 'auth', 'admin'],
        ];
        $actual = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->getActionName(), 'App\\Http\\Controllers\\ReportExportController@')) {
                continue;
            }

            $actual[] = $route->getName();
            $this->assertArrayHasKey($route->getName(), $expected);
            $this->assertSame(['GET', 'HEAD'], $route->methods());

            foreach ($expected[$route->getName()] as $middleware) {
                $this->assertContains($middleware, $route->gatherMiddleware());
            }
        }

        $this->assertEqualsCanonicalizing(array_keys($expected), $actual);
    }

    /** @return array<string, array{string, string}> */
    public static function revokedScopes(): array
    {
        $cases = [];

        foreach (['csv', 'pdf'] as $format) {
            foreach (['inactive classroom', 'teacher removed', 'student removed', 'course removed'] as $change) {
                $cases["{$change} {$format}"] = [$change, $format];
            }
        }

        return $cases;
    }

    #[DataProvider('revokedScopes')]
    public function test_exports_follow_live_classroom_scope(string $change, string $format): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['type' => 'private-scope']);
        Mission::factory()->create(['course_id' => $course->id]);
        $classroom = $this->classroomFor($teacher, [$student], [$course]);
        $params = ['student' => $student->id, 'format' => $format];
        $this->actingAs($teacher);
        $this->assertSame([$course->id], app(ReportAuthorizationService::class)->reportCourseIds($teacher, $student)?->all());

        match ($change) {
            'inactive classroom' => $classroom->update(['status' => 'inactive']),
            'teacher removed' => $classroom->teachers()->detach($teacher),
            'student removed' => $classroom->students()->detach($student),
            'course removed' => $classroom->courses()->detach($course),
        };

        if ($change === 'course removed' && $format === 'pdf') {
            $this->expectPdfWithout('PRIVATE-SCOPE');
        }

        $response = $this->get(route('export.teacher-student', $params));

        if ($change === 'course removed') {
            $response->assertOk();
            $this->assertSame([], app(TeacherStudentReportService::class)->forTeacherStudent($teacher, $student)['progress']['courses']);

            if ($format === 'csv') {
                $this->assertSame("\xEF\xBB\xBFcourse_id,name,state,percent,completed_missions,total_missions,challenge_passed\n", $response->streamedContent());
            }
        } else {
            $response->assertForbidden()->assertHeaderMissing('Content-Disposition')->assertDontSee('PRIVATE-SCOPE');
        }

        if ($change !== 'student removed') {
            $this->get(route('export.teacher-course', ['course' => $course->id, 'format' => $format]))
                ->assertForbidden()->assertHeaderMissing('Content-Disposition');
        }
    }

    /** @return array<string, array{string}> */
    public static function formats(): array
    {
        return ['csv' => ['csv'], 'pdf' => ['pdf']];
    }

    #[DataProvider('formats')]
    public function test_inactive_accounts_cannot_download_reports(string $format): void
    {
        $student = User::factory()->deactivated()->create();

        $this->actingAs($student)->get(route('export.progress', ['format' => $format]))
            ->assertRedirect(route('login'))->assertHeaderMissing('Content-Disposition');
        $this->assertGuest();
    }

    #[DataProvider('formats')]
    public function test_teacher_filters_cannot_combine_students_and_courses_from_different_classrooms(string $format): void
    {
        $teacher = User::factory()->teacher()->create();
        $studentA = User::factory()->create();
        $studentB = User::factory()->create();
        $courseA = Course::factory()->create(['order_num' => 1]);
        $courseB = Course::factory()->create(['order_num' => 2]);
        $mission = Mission::factory()->create(['course_id' => $courseA->id]);
        Progress::factory()->create(['user_id' => $studentB->id, 'mission_id' => $mission->id]);
        $this->classroomFor($teacher, [$studentA], [$courseA]);
        $this->classroomFor($teacher, [$studentB], [$courseB]);
        $params = ['course' => $courseA->id, 'student_id' => $studentB->id, 'format' => $format, 'dataset' => 'students'];

        if ($format === 'pdf') {
            $this->expectPdfWithout('<td>'.$studentB->id.'</td>');
        }

        $response = $this->actingAs($teacher)->get(route('export.teacher-course', $params));

        $response->assertOk()->assertDownload();
        $filters = ReportFilters::fromArray(['student_id' => $studentB->id]);
        $report = app(TeacherCourseReportService::class)->forTeacherCourse($teacher, $courseA, $filters);
        $this->assertSame([], $report['competency']['students']);
        $this->assertSame(0, $report['period']['completions']);

        if ($format === 'csv') {
            $this->assertSame("\xEF\xBB\xBFstudent_id,state,percent\n", $response->streamedContent());
        }
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidInputs(): array
    {
        return [
            'student array' => [['student_id' => ['1']], 'student_id'],
            'course SQL' => [['course_id' => '1 OR 1=1'], 'course_id'],
            'date array' => [['from' => ['2026-01-01']], 'from'],
            'reverse dates' => [['from' => '2026-02-01', 'to' => '2026-01-01'], 'from'],
            'invalid status' => [['status' => 'passed'], 'status'],
            'format array' => [['format' => ['pdf']], 'format'],
            'unknown format' => [['format' => 'html'], 'format'],
            'protected dataset' => [['dataset' => 'solution_code'], 'dataset'],
        ];
    }

    /** @param array<string, mixed> $input */
    #[DataProvider('invalidInputs')]
    public function test_invalid_export_input_returns_validation_errors_without_a_download(array $input, string $field): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->getJson(route('export.progress', $input))
            ->assertUnprocessable()->assertJsonValidationErrors($field)->assertHeaderMissing('Content-Disposition');
    }

    #[DataProvider('revokedRoles')]
    public function test_teacher_report_composition_refuses_retained_assignments_for_non_teachers(string $role): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $this->classroomFor($teacher, [$student], [$course]);
        $teacher->role = $role;

        $this->expectException(AuthorizationException::class);

        app(TeacherStudentReportService::class)->forTeacherStudent($teacher, $student);
    }

    private function expectPdfWithout(string $protectedContent): void
    {
        $renderer = new ReportPdfExporter;

        $this->partialMock(ReportPdfExporter::class)
            ->shouldReceive('pdf')->once()
            ->andReturnUsing(function (string $html) use ($renderer, $protectedContent): string {
                $this->assertStringContainsString('No data.', $html);
                $this->assertStringNotContainsString($protectedContent, $html);

                return $renderer->pdf($html);
            });
    }

    #[DataProvider('formats')]
    public function test_export_projections_exclude_credentials_solutions_grading_rules_and_submitted_code(string $format): void
    {
        $student = User::factory()->create();
        $teacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'title' => 'Visible challenge sentinel',
            'solution_code' => 'PRIVATE_SOLUTION_SENTINEL',
            'validate_rule' => '[{"type":"contains","value":"PRIVATE_VALIDATION_SENTINEL"}]',
        ]);
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'grading_rule' => '[{"type":"contains","value":"PRIVATE_GRADING_SENTINEL"}]',
        ]);
        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'status' => 'failed',
            'score' => 0,
            'code' => 'PRIVATE_SUBMISSION_SENTINEL',
            'created_at' => now(),
            'submitted_at' => now(),
        ]);
        $this->classroomFor($teacher, [$student], [$course]);
        $secrets = [$student->password, 'PRIVATE_SOLUTION_SENTINEL', 'PRIVATE_VALIDATION_SENTINEL', 'PRIVATE_GRADING_SENTINEL', 'PRIVATE_SUBMISSION_SENTINEL'];
        $renderer = new ReportPdfExporter;

        if ($format === 'pdf') {
            $this->partialMock(ReportPdfExporter::class)
                ->shouldReceive('pdf')->times(4)
                ->andReturnUsing(function (string $html) use ($renderer, $secrets): string {
                    foreach ($secrets as $secret) {
                        $this->assertStringNotContainsString($secret, $html);
                    }

                    return $renderer->pdf($html);
                });
        }

        foreach ([
            [$student, 'export.progress', []],
            [$teacher, 'export.teacher-student', ['student' => $student->id]],
            [$teacher, 'export.teacher-course', ['course' => $course->id]],
            [$admin, 'export.admin-system', ['dataset' => 'challenges']],
        ] as [$viewer, $route, $params]) {
            $response = $this->actingAs($viewer)->get(route($route, $params + ['format' => $format]));
            $response->assertOk()->assertDownload();

            if ($format === 'csv') {
                foreach ($secrets as $secret) {
                    $this->assertStringNotContainsString($secret, $response->streamedContent());
                }

                if ($route === 'export.admin-system') {
                    $this->assertStringContainsString('Visible challenge sentinel', $response->streamedContent());
                }
            } else {
                $this->assertStringStartsWith('%PDF-', $response->streamedContent());
            }
        }
    }
}
