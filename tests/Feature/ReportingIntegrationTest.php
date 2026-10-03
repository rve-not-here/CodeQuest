<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

class ReportingIntegrationTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_completed_learning_journey_reaches_authorized_filtered_csv_and_pdf_reports(): void
    {
        $this->seed(AchievementSeeder::class);
        $this->travelTo('2026-09-20 12:00:00');

        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['name' => 'Reporting Journey', 'order_num' => 1, 'status' => 'active']);
        $foreignCourse = Course::factory()->create(['name' => 'Outside Classroom', 'order_num' => 2, 'status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1]);
        $foreignSection = Section::factory()->create(['course_id' => $foreignCourse->id, 'order_num' => 1]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'points' => 30,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
        ]);
        $skill = Skill::factory()->create(['key' => 'html.headings']);
        $mission->skills()->sync([$skill->id]);
        $foreignMission = Mission::factory()->create([
            'course_id' => $foreignCourse->id,
            'section_id' => $foreignSection->id,
            'order_num' => 1,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<p>']]),
        ]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'passing_score' => 70,
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'SIGNAL']]),
        ]);
        $this->classroomFor($teacher, [$student], [$course]);

        $this->get(route('export.progress'))->assertRedirect(route('login'));

        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => '<h1>Ready</h1>'])
            ->assertRedirect()->assertSessionHas('mission_success');

        $this->travelTo('2026-09-23 12:00:00');
        $this->actingAs($student)->post(route('assessment.start', $assessment))
            ->assertRedirect(route('assessment.show', $assessment));
        $this->actingAs($student)->post(route('assessment.submit', $assessment), ['code' => 'SIGNAL'])
            ->assertRedirect()->assertSessionHas('assessment_success');

        $this->travelTo('2026-09-25 12:00:00');
        $this->actingAs($student)->post(route('mission.submit', $foreignMission), ['code' => '<p>Next</p>'])
            ->assertRedirect()->assertSessionHas('mission_success');

        $window = ['dataset' => 'period', 'from' => '2026-09-22', 'to' => '2026-09-23'];

        $studentPeriod = $this->metrics($this->actingAs($student)
            ->get(route('export.progress', $window))->assertOk()->streamedContent());
        $this->assertSame('0', $studentPeriod['completions']);
        $this->assertSame('1', $studentPeriod['assessment_attempts']);
        $this->assertSame('1', $studentPeriod['assessment_passes']);

        $studentCourses = $this->csvRows($this->actingAs($student)
            ->get(route('export.progress'))->assertOk()->streamedContent());
        $this->assertSame((string) $course->id, $studentCourses[1][0]);
        $this->assertSame('true', $studentCourses[1][6]);
        $this->assertSame((string) $foreignCourse->id, $studentCourses[2][0]);

        $studentSkills = $this->csvRows($this->actingAs($student)
            ->get(route('export.progress', ['dataset' => 'skills']))->assertOk()->streamedContent());
        $this->assertSame('html.headings', $studentSkills[1][0]);

        $teacherPeriod = $this->metrics($this->actingAs($teacher)
            ->get(route('export.teacher-student', [$student, ...$window]))->assertOk()->streamedContent());
        $this->assertSame($studentPeriod, $teacherPeriod);

        $teacherCourses = $this->csvRows($this->actingAs($teacher)
            ->get(route('export.teacher-student', $student))->assertOk()->streamedContent());
        $this->assertCount(2, $teacherCourses);
        $this->assertSame((string) $course->id, $teacherCourses[1][0]);

        $teacherSkills = $this->csvRows($this->actingAs($teacher)
            ->get(route('export.teacher-student', [$student, 'dataset' => 'skills']))
            ->assertOk()->streamedContent());
        $this->assertSame($studentSkills, $teacherSkills);

        $coursePeriod = $this->metrics($this->actingAs($teacher)
            ->get(route('export.teacher-course', [$course, ...$window]))->assertOk()->streamedContent());
        $this->assertSame($studentPeriod, $coursePeriod);

        $lifecycle = $this->metrics($this->actingAs($teacher)
            ->get(route('export.teacher-course', $course))->assertOk()->streamedContent());
        $this->assertSame('1', $lifecycle['participating']);
        $this->assertSame('1', $lifecycle['completed']);

        $courseStudents = $this->csvRows($this->actingAs($teacher)
            ->get(route('export.teacher-course', [$course, 'dataset' => 'students']))
            ->assertOk()->streamedContent());
        $this->assertSame((string) $student->id, $courseStudents[1][0]);

        $adminPeriod = $this->metrics($this->actingAs($admin)
            ->get(route('export.admin-system', [...$window, 'student_id' => $student->id, 'course_id' => $course->id]))
            ->assertOk()->streamedContent());
        $this->assertSame($studentPeriod, $adminPeriod);

        $assessments = $this->csvRows($this->actingAs($admin)
            ->get(route('export.admin-system', ['dataset' => 'assessments', 'course_id' => $course->id]))
            ->assertOk()->streamedContent());
        $this->assertSame((string) $assessment->id, $assessments[1][0]);
        $this->assertSame('1', $assessments[1][3]);
        $this->assertSame('1', $assessments[1][4]);

        $challenges = $this->csvRows($this->actingAs($admin)
            ->get(route('export.admin-system', ['dataset' => 'challenges', 'course_id' => $course->id]))
            ->assertOk()->streamedContent());
        $this->assertSame((string) $mission->id, $challenges[1][0]);
        $this->assertSame('1', $challenges[1][5]);

        $summary = $this->metrics($this->actingAs($admin)
            ->get(route('export.admin-system'))->assertOk()->streamedContent());
        $this->assertSame('1', $summary['learning.course_completions']);

        $this->actingAs($teacher)
            ->get(route('export.teacher-student', [$student, 'course_id' => $foreignCourse->id]))
            ->assertRedirect()->assertSessionHasErrors(['course_id']);
        $this->actingAs($teacher)->get(route('export.teacher-course', $foreignCourse))->assertForbidden();
        $this->actingAs($teacher)->get(route('export.teacher-course', [$foreignCourse, 'format' => 'pdf']))
            ->assertForbidden();

        foreach ([
            [$student, route('export.progress', ['format' => 'pdf'])],
            [$teacher, route('export.teacher-student', [$student, 'format' => 'pdf'])],
            [$teacher, route('export.teacher-course', [$course, 'format' => 'pdf'])],
            [$admin, route('export.admin-system', ['format' => 'pdf'])],
        ] as [$viewer, $url]) {
            $response = $this->actingAs($viewer)->get($url)->assertOk()
                ->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->streamedContent());
        }

        $this->actingAs($student)->get(route('export.teacher-student', $student))->assertForbidden();
        $this->actingAs($teacher)->get(route('export.admin-system'))->assertForbidden();
    }

    /**
     * @return list<list<string>>
     */
    private function csvRows(string $body): array
    {
        $handle = fopen('php://memory', 'r+b');
        fwrite($handle, preg_replace('/^\xEF\xBB\xBF/', '', $body));
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(static fn ($cell): string => (string) $cell, $row);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    private function metrics(string $body): array
    {
        $rows = $this->csvRows($body);
        $this->assertSame(['metric', 'value'], array_shift($rows));

        return collect($rows)->mapWithKeys(static fn (array $row): array => [$row[0] => $row[1]])->all();
    }
}
