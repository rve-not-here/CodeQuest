<?php

namespace Tests\Unit;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentUnlockTest extends TestCase
{
    use RefreshDatabase;

    private AssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AssessmentService::class);
    }

    private function makeEligibleCourse(): array
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
        ]);

        return compact('course', 'mission');
    }

    private function complete(User $user, Mission $mission): void
    {
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 0,
            'completed_at' => now(),
        ]);
    }

    public function test_unlocked_when_assessment_active_and_student_is_eligible(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'mission' => $mission] = $this->makeEligibleCourse();
        $this->complete($user, $mission);
        Assessment::factory()->create(['course_id' => $course->id, 'status' => 'active']);

        $this->assertTrue($this->service->isUnlocked($user, $course));
    }

    public function test_not_unlocked_when_course_has_no_assessment(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'mission' => $mission] = $this->makeEligibleCourse();
        $this->complete($user, $mission);

        $this->assertFalse($this->service->isUnlocked($user, $course));
    }

    public function test_not_unlocked_when_assessment_is_locked_even_if_eligible(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'mission' => $mission] = $this->makeEligibleCourse();
        $this->complete($user, $mission);
        Assessment::factory()->create(['course_id' => $course->id, 'status' => 'locked']);

        $this->assertFalse($this->service->isUnlocked($user, $course));
    }

    public function test_not_unlocked_when_assessment_is_draft_even_if_eligible(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'mission' => $mission] = $this->makeEligibleCourse();
        $this->complete($user, $mission);
        Assessment::factory()->create(['course_id' => $course->id, 'status' => 'draft']);

        $this->assertFalse($this->service->isUnlocked($user, $course));
    }

    public function test_not_unlocked_when_student_is_not_eligible(): void
    {
        $user = User::factory()->create();
        ['course' => $course] = $this->makeEligibleCourse();
        Assessment::factory()->create(['course_id' => $course->id, 'status' => 'active']);

        $this->assertFalse($this->service->isUnlocked($user, $course));
    }

    public function test_unlock_is_server_authoritative_and_scoped_per_student(): void
    {
        $eligible = User::factory()->create();
        $other = User::factory()->create();
        ['course' => $course, 'mission' => $mission] = $this->makeEligibleCourse();
        $this->complete($eligible, $mission);
        Assessment::factory()->create(['course_id' => $course->id, 'status' => 'active']);

        $this->assertTrue($this->service->isUnlocked($eligible, $course));
        $this->assertFalse($this->service->isUnlocked($other, $course));
    }
}
