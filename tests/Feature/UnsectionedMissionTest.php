<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnsectionedMissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsectioned_required_work_is_discoverable_and_still_gates_the_boss(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $sectioned = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        $unsectioned = Mission::factory()->create(['course_id' => $course->id, 'section_id' => null, 'title' => 'Required unsectioned challenge', 'order_num' => 0]);
        Assessment::factory()->create(['course_id' => $course->id]);
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $sectioned->id]);

        $this->actingAs($student)->get(route('missions'))->assertSee('Required unsectioned challenge')->assertSee(route('mission.show', $unsectioned));
        $this->get(route('learning-path'))->assertSee('Additional missions')->assertSee('Required unsectioned challenge')->assertSee(route('mission.show', $unsectioned));
        $this->get(route('section-progress'))->assertSee('Additional missions')->assertSee('0/1 missions complete');
        $this->get(route('dashboard'))->assertSee('Required unsectioned challenge');
        $this->assertFalse(app(AssessmentService::class)->isUnlocked($student, $course));

        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $unsectioned->id]);
        $this->assertTrue(app(AssessmentService::class)->isUnlocked($student, $course));
        $this->get(route('section-progress'))->assertSee('Additional missions')->assertSee('1/1 missions complete');
        $this->assertDatabaseCount('the404_sections', 1);
        $this->assertNull($unsectioned->fresh()->section_id);
    }
}
