<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\CourseAnalyticsService;
use App\Services\StudentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

class AssessmentReadinessParityTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_teacher_ready_states_match_authoritative_tied_course_prerequisites_without_exposing_private_course(): void
    {
        $student = User::factory()->create();
        $teacher = User::factory()->teacher()->create();
        $earlier = Course::factory()->create(['order_num' => 1, 'name' => 'Private prerequisite']);
        Mission::factory()->create(['course_id' => $earlier->id]);
        $earlierAssessment = Assessment::factory()->create(['course_id' => $earlier->id]);
        $course = Course::factory()->create(['order_num' => 1]);
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        Assessment::factory()->create(['course_id' => $course->id]);
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->classroomFor($teacher, [$student], [$course]);
        $scope = [$student->id => collect([$course->id])];

        $this->assertFalse(app(AssessmentService::class)->isUnlocked($student, $course));
        $row = app(StudentService::class)->index(null, null, null, [], $scope)->items()[0];
        $this->assertSame('LOCKED', $row['assessment']);
        $this->assertSame('in_progress', $row['state']);
        $this->assertSame(0, app(StudentService::class)->index(null, null, 'ready', [], $scope)->total());
        $analytics = app(CourseAnalyticsService::class)->overview(collect([$student->id]), collect([$course->id]), $scope)->first();
        $this->assertSame(0, $analytics['buckets']['assessment_ready']);
        $this->actingAs($teacher)->get(route('students'))->assertOk()->assertDontSee('Private prerequisite');

        AssessmentAttempt::factory()->create(['user_id' => $student->id, 'assessment_id' => $earlierAssessment->id, 'status' => 'passed', 'passed_at' => now()]);
        $this->assertTrue(app(AssessmentService::class)->isUnlocked($student, $course));
        $row = app(StudentService::class)->index(null, null, null, [], $scope)->items()[0];
        $this->assertSame('READY', $row['assessment']);
        $this->assertSame('ready', $row['state']);
        $analytics = app(CourseAnalyticsService::class)->overview(collect([$student->id]), collect([$course->id]), $scope)->first();
        $this->assertSame(1, $analytics['buckets']['assessment_ready']);
    }
}
