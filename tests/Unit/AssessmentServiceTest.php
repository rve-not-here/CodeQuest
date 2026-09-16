<?php

namespace Tests\Unit;

use App\Exceptions\AssessmentAlreadyExistsException;
use App\Models\Assessment;
use App\Models\Course;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AssessmentService::class);
    }

    public function test_creates_the_first_assessment_for_a_course(): void
    {
        $course = Course::factory()->create();

        $assessment = $this->service->createForCourse($course, [
            'title' => 'HTML Boss Challenge',
            'passing_score' => 80,
            'status' => 'active',
        ]);

        $this->assertInstanceOf(Assessment::class, $assessment);
        $this->assertSame($course->id, $assessment->course_id);
        $this->assertDatabaseHas('the404_assessments', [
            'id' => $assessment->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_second_assessment_for_same_course_throws_application_error(): void
    {
        $course = Course::factory()->create();
        $this->service->createForCourse($course, ['title' => 'First']);

        $this->expectException(AssessmentAlreadyExistsException::class);

        $this->service->createForCourse($course, ['title' => 'Second']);
    }

    public function test_duplicate_assessment_does_not_write_to_database(): void
    {
        $course = Course::factory()->create();
        $this->service->createForCourse($course, ['title' => 'First']);

        try {
            $this->service->createForCourse($course, ['title' => 'Second']);
        } catch (AssessmentAlreadyExistsException) {
            // expected
        }

        $this->assertSame(1, Assessment::query()->where('course_id', $course->id)->count());
    }

    public function test_distinct_courses_each_get_their_own_assessment(): void
    {
        $first = Course::factory()->create();
        $second = Course::factory()->create();

        $this->service->createForCourse($first, ['title' => 'First']);
        $this->service->createForCourse($second, ['title' => 'Second']);

        $this->assertSame(2, Assessment::query()->count());
        $this->assertSame('First', $this->service->forCourse($first)->title);
        $this->assertSame('Second', $this->service->forCourse($second)->title);
    }
}
