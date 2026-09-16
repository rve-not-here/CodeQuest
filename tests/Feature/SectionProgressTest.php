<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_section_progress_requires_authentication(): void
    {
        $this->get(route('section-progress'))->assertRedirect(route('login'));
    }

    public function test_attempts_to_view_another_students_section_progress_fail_via_any_parameter(): void
    {
        $attacker = User::factory()->create();
        $victim = User::factory()->create();

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $parameter) {
            $this->actingAs($attacker)
                ->get(route('section-progress', [$parameter => $victim->id]))
                ->assertForbidden();
        }
    }

    public function test_section_progress_empty_when_no_courses(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('section-progress'))
            ->assertOk()
            ->assertSee('NO COURSES');
    }

    public function test_section_progress_untouched_section_is_not_started(): void
    {
        $user = User::factory()->create();
        $this->createCourseWithSections(1, [2]);

        $this->actingAs($user)
            ->get(route('section-progress'))
            ->assertOk()
            ->assertSee('0/2')
            ->assertSee('NOT STARTED');
    }

    public function test_section_progress_partial_section_is_in_progress(): void
    {
        $user = User::factory()->create();
        [, $sections] = $this->createCourseWithSections(1, [2]);

        $this->complete($user, $sections[0]['missions'][0]);

        $this->actingAs($user)
            ->get(route('section-progress'))
            ->assertOk()
            ->assertSee('1/2')
            ->assertSee('IN PROGRESS');
    }

    public function test_section_progress_full_section_is_done(): void
    {
        $user = User::factory()->create();
        [, $sections] = $this->createCourseWithSections(1, [2]);

        $this->complete($user, $sections[0]['missions'][0]);
        $this->complete($user, $sections[0]['missions'][1]);

        $this->actingAs($user)
            ->get(route('section-progress'))
            ->assertOk()
            ->assertSee('2/2')
            ->assertSee('DONE');
    }

    public function test_section_progress_empty_section_shows_em_empty_and_does_not_mask_other_sections(): void
    {
        $user = User::factory()->create();
        $this->createCourseWithSections(1, [0, 1]);

        $this->actingAs($user)
            ->get(route('section-progress'))
            ->assertOk()
            ->assertSee('0/0')
            ->assertSee('EMPTY')
            ->assertSee('0/1')
            ->assertSee('NOT STARTED');
    }

    public function test_section_progress_is_not_a_flat_course_percentage_repeated_per_section(): void
    {
        $user = User::factory()->create();
        [, $sections] = $this->createCourseWithSections(1, [2, 1]);

        $this->complete($user, $sections[0]['missions'][0]);

        $this->actingAs($user)
            ->get(route('section-progress'))
            ->assertOk()
            ->assertSee('1/3')
            ->assertSee('1/2')
            ->assertSee('0/1')
            ->assertSee('IN PROGRESS')
            ->assertSee('NOT STARTED');
    }

    public function test_section_progress_in_locked_course_still_derives_from_real_mission_state(): void
    {
        $user = User::factory()->create();
        [, $sections] = $this->createCourseWithSections(1, [2], status: 'locked');

        $this->complete($user, $sections[0]['missions'][0]);
        $this->complete($user, $sections[0]['missions'][1]);

        $this->actingAs($user)
            ->get(route('section-progress'))
            ->assertOk()
            ->assertSee('2/2')
            ->assertSee('DONE');
    }

    /**
     * @param  array<int, int>  $missionsPerSection
     * @return array{0: Course, 1: array<int, array{section: Section, missions: array<int, Mission>}>}
     */
    private function createCourseWithSections(int $courseOrder, array $missionsPerSection, string $status = 'active'): array
    {
        $course = Course::factory()->create(['status' => $status, 'order_num' => $courseOrder]);

        $sections = [];
        foreach ($missionsPerSection as $index => $missionCount) {
            $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => $index + 1]);

            $missions = [];
            for ($order = 1; $order <= $missionCount; $order++) {
                $missions[] = Mission::factory()->create([
                    'course_id' => $course->id,
                    'section_id' => $section->id,
                    'order_num' => $order,
                ]);
            }

            $sections[] = ['section' => $section, 'missions' => $missions];
        }

        return [$course, $sections];
    }

    private function complete(User $user, Mission $mission): void
    {
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => now(),
        ]);
    }
}
