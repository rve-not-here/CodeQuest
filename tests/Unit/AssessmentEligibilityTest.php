<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private AssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AssessmentService::class);
    }

    /**
     * @param  array<int, int>  $missionsPerSection  e.g. [2, 1] = section with 2 missions, section with 1
     * @return array{course: Course, missions: array<int, Mission>}
     */
    private function makeCourseWithSections(array $missionsPerSection): array
    {
        $course = Course::factory()->create();
        $missions = [];
        $order = 1;

        foreach ($missionsPerSection as $sectionIndex => $count) {
            $section = Section::factory()->create([
                'course_id' => $course->id,
                'order_num' => $sectionIndex + 1,
            ]);

            for ($i = 0; $i < $count; $i++) {
                $missions[] = Mission::factory()->create([
                    'course_id' => $course->id,
                    'section_id' => $section->id,
                    'order_num' => $order++,
                ]);
            }
        }

        return ['course' => $course, 'missions' => $missions];
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

    public function test_eligible_when_every_mission_in_every_section_is_complete(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'missions' => $missions] = $this->makeCourseWithSections([2, 1]);

        foreach ($missions as $mission) {
            $this->complete($user, $mission);
        }

        $this->assertTrue($this->service->isEligible($user, $course));
    }

    public function test_not_eligible_when_any_mission_is_incomplete(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'missions' => $missions] = $this->makeCourseWithSections([2, 2]);

        foreach ([$missions[0], $missions[1], $missions[2]] as $mission) {
            $this->complete($user, $mission);
        }

        $this->assertFalse($this->service->isEligible($user, $course));
    }

    public function test_not_eligible_when_no_mission_is_complete(): void
    {
        $user = User::factory()->create();
        ['course' => $course] = $this->makeCourseWithSections([1, 1]);

        $this->assertFalse($this->service->isEligible($user, $course));
    }

    public function test_not_eligible_for_course_with_zero_missions(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $this->assertFalse($this->service->isEligible($user, $course));
    }

    public function test_eligibility_is_scoped_to_that_students_progress(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        ['course' => $course, 'missions' => $missions] = $this->makeCourseWithSections([2]);

        foreach ($missions as $mission) {
            $this->complete($first, $mission);
        }

        $this->assertTrue($this->service->isEligible($first, $course));
        $this->assertFalse($this->service->isEligible($second, $course));
    }

    public function test_completing_an_unrelated_courses_missions_does_not_make_this_course_eligible(): void
    {
        $user = User::factory()->create();
        ['course' => $other, 'missions' => $otherMissions] = $this->makeCourseWithSections([1]);
        ['course' => $course] = $this->makeCourseWithSections([1]);

        foreach ($otherMissions as $mission) {
            $this->complete($user, $mission);
        }

        $this->assertFalse($this->service->isEligible($user, $course));
        $this->assertTrue($this->service->isEligible($user, $other));
    }

    public function test_empty_section_is_vacuously_satisfied_but_does_not_mask_an_incomplete_mission_elsewhere(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $emptySection = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1]);
        $fullSection = Section::factory()->create(['course_id' => $course->id, 'order_num' => 2]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $fullSection->id,
            'order_num' => 1,
        ]);

        $this->assertFalse($this->service->isEligible($user, $course));

        $this->complete($user, $mission);

        $this->assertTrue($this->service->isEligible($user, $course));
        $this->assertSame(0, $emptySection->missions()->count());
    }

    public function test_not_eligible_when_only_an_empty_section_exists_and_it_has_no_missions(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        Section::factory()->create(['course_id' => $course->id]);

        $this->assertFalse($this->service->isEligible($user, $course));
    }
}
