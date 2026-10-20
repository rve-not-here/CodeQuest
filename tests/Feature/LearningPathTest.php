<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_lock_reasons_are_independent_of_boss_eligibility(): void
    {
        $user = User::factory()->create();
        $current = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $mission = Mission::factory()->create(['course_id' => $current->id]);
        Assessment::factory()->create(['course_id' => $current->id, 'status' => 'active']);
        $later = Course::factory()->create(['status' => 'active', 'order_num' => 2]);
        Mission::factory()->create(['course_id' => $later->id]);
        $inactive = Course::factory()->create(['status' => 'locked', 'order_num' => 3]);

        $tree = $this->actingAs($user)->get(route('learning-path'))->assertOk()->viewData('tree')->keyBy('course.id');

        $this->assertArrayHasKey('locked_reason', $tree[$current->id]);
        $this->assertTrue($tree[$current->id]['accessible']);
        $this->assertNull($tree[$current->id]['locked_reason']);
        $this->assertFalse($tree[$current->id]['boss']['accessible']);
        $this->assertSame('LOCKED', $tree[$current->id]['boss']['state']);
        $this->assertFalse($tree[$later->id]['accessible']);
        $this->assertSame('Pass earlier courses before opening this course.', $tree[$later->id]['locked_reason']);
        $this->assertSame('No Boss Challenge is configured for this course.', $tree[$later->id]['boss']['reason']);
        $this->assertFalse($tree[$inactive->id]['accessible']);
        $this->assertSame('This course is not currently active.', $tree[$inactive->id]['locked_reason']);

        Progress::factory()->create(['user_id' => $user->id, 'mission_id' => $mission->id]);
        $tree = $this->get(route('learning-path'))->assertOk()->viewData('tree')->keyBy('course.id');
        $this->assertNull($tree[$current->id]['locked_reason']);
        $this->assertTrue($tree[$current->id]['boss']['accessible']);
        $this->assertSame('AVAILABLE', $tree[$current->id]['boss']['state']);
        $this->assertFalse($tree[$later->id]['accessible']);
        $this->assertSame('Pass earlier courses before opening this course.', $tree[$later->id]['locked_reason']);
    }

    public function test_sections_and_lock_reasons_remain_visible_in_learning_order(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['name' => 'JavaScript Foundations', 'status' => 'active', 'order_num' => 1]);
        $first = Section::factory()->create(['course_id' => $course->id, 'title' => 'Values and expressions', 'order_num' => 1]);
        $second = Section::factory()->create(['course_id' => $course->id, 'title' => 'Functions and events', 'order_num' => 2]);
        $complete = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $first->id, 'title' => 'Declare a variable', 'order_num' => 1]);
        Mission::factory()->create(['course_id' => $course->id, 'section_id' => $second->id, 'title' => 'Handle user interaction with accessible event listeners', 'order_num' => 2]);
        Mission::factory()->create(['course_id' => $course->id, 'section_id' => $second->id, 'title' => 'Update the document', 'order_num' => 3]);
        Mission::factory()->create(['course_id' => $course->id, 'section_id' => null, 'title' => 'Additional practice', 'order_num' => 4]);
        Progress::factory()->create(['user_id' => $user->id, 'mission_id' => $complete->id]);
        Assessment::factory()->create(['course_id' => $course->id, 'title' => 'JavaScript Boss Challenge', 'status' => 'active']);
        $later = Course::factory()->create(['name' => 'Next course', 'status' => 'active', 'order_num' => 2]);
        Mission::factory()->create(['course_id' => $later->id, 'title' => 'Next course mission']);

        $this->actingAs($user)->get(route('learning-path'))->assertOk()
            ->assertDontSee('<details id="section-', false)
            ->assertSeeInOrder(['Values and expressions', 'Declare a variable', 'Functions and events', 'Update the document', 'Additional practice', 'JavaScript Boss Challenge'])
            ->assertSee('Pass earlier courses before opening this course.');
    }

    public function test_learning_path_requires_authentication(): void
    {
        $this->get(route('learning-path'))->assertRedirect(route('login'));
    }

    public function test_learning_path_renders_with_courses_and_sections(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['order_num' => 1, 'status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1]);
        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee($course->name)
            ->assertSee($section->title)
            ->assertSee('Learning Path');
    }

    public function test_learning_path_shows_xp_balance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('XP 0');
    }

    public function test_learning_path_shows_mission_states(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('Current');
    }

    public function test_learning_path_shows_completed_state(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
        ]);

        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('Completed');
    }

    public function test_learning_path_empty_when_no_courses(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('No courses');
    }

    public function test_learning_path_shows_difficulty_badges(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'difficulty' => 'HARD',
        ]);

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('HARD');
    }

    public function test_learning_path_distinguishes_completed_current_and_available_nodes(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $completed = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
        ]);
        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 2,
        ]);
        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 3,
        ]);
        Progress::factory()->create([
            'user_id' => $user->id,
            'mission_id' => $completed->id,
        ]);

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('Completed')
            ->assertSee('Current')
            ->assertSee('Available');
    }

    public function test_learning_path_explains_server_sealed_course_nodes_without_linking_them(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'locked']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
        ]);

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('Locked')
            ->assertSee('This course is not currently active.')
            ->assertDontSee(route('mission.show', $mission));
    }

    public function test_learning_path_presents_the_boss_challenge_as_an_authoritative_milestone(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
        ]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);
        Progress::factory()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('Final course milestone')
            ->assertSee('atlas-stamp atlas-stamp--ink', false)
            ->assertSee('Available')
            ->assertSee(route('assessment.show', $assessment));
    }
}
