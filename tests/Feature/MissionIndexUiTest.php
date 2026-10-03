<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionIndexUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_later_course_missions_are_visible_but_not_linked_until_the_course_is_reached(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $first = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $later = Course::factory()->create(['status' => 'active', 'order_num' => 2]);
        $firstMission = $this->missionFor($first, 'First step');
        $laterMission = $this->missionFor($later, 'Later step');

        $response = $this->actingAs($student)->get(route('missions'))->assertOk();

        $response->assertSee('First step')
            ->assertSee('Later step')
            ->assertSee('You are here')
            ->assertSee('Complete earlier courses to unlock this challenge.')
            ->assertSee('href="'.route('mission.show', $firstMission).'"', false)
            ->assertDontSee('href="'.route('mission.show', $laterMission).'"', false);

        $this->get(route('mission.show', $laterMission))->assertForbidden();
    }

    public function test_sealed_course_keeps_mission_history_readable_without_an_action(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $sealed = Course::factory()->create(['status' => 'locked', 'order_num' => 1]);
        $mission = $this->missionFor($sealed, 'Archived concept');

        $this->actingAs($student)->get(route('missions'))
            ->assertOk()
            ->assertSee('Archived concept')
            ->assertSee('Course access is sealed. Return when Command restores this course.')
            ->assertDontSee('href="'.route('mission.show', $mission).'"', false);
    }

    private function missionFor(Course $course, string $title): Mission
    {
        $section = Section::factory()->create(['course_id' => $course->id]);

        return Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'title' => $title,
        ]);
    }
}
