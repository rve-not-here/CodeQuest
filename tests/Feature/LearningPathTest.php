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
            ->assertSee('CURRENT');
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
            ->assertSee('COMPLETED');
    }

    public function test_learning_path_empty_when_no_courses(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('NO COURSES');
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
            ->assertSee('COMPLETED')
            ->assertSee('CURRENT')
            ->assertSee('AVAILABLE');
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
            ->assertSee('LOCKED')
            ->assertSee('Course access is sealed. Return when Command restores this course.')
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
            ->assertSee('FINAL COURSE MILESTONE')
            ->assertSee('BOSS AVAILABLE')
            ->assertSee(route('assessment.show', $assessment));
    }
}
