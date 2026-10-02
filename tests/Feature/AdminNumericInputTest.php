<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\AdminAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminNumericInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_numeric_strings_update_course_section_mission_and_assessment(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);
        $this->actingAs($admin);
        $this->put(route('admin.courses.update', $course), ['name' => $course->name, 'slug' => $course->slug, 'type' => $course->type, 'status' => $course->status, 'order_num' => '7'])->assertSessionHas('status');
        $this->put(route('admin.courses.sections.update', [$course, $section]), ['title' => $section->title, 'order_num' => '8'])->assertSessionHas('status');
        $this->put(route('admin.courses.missions.update', [$course, $mission]), ['title' => $mission->title, 'difficulty' => $mission->difficulty, 'points' => '50', 'order_num' => '9', 'section_id' => (string) $section->id])->assertSessionHas('status');
        $this->put(route('admin.courses.assessment.update', [$course, $assessment]), ['title' => $assessment->title, 'passing_score' => '100', 'status' => $assessment->status])->assertSessionHas('status');
        $this->assertSame(7, $course->refresh()->order_num);
        $this->assertSame(8, $section->refresh()->order_num);
        $this->assertSame(9, $mission->refresh()->order_num);
        $this->assertSame(50, $mission->points);
        $this->assertSame(100, $assessment->refresh()->passing_score);
    }

    #[DataProvider('invalidNumbers')]
    public function test_invalid_numeric_input_does_not_change_academic_configuration(mixed $input): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['order_num' => 2]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'points' => 40]);
        $this->actingAs($admin)->put(route('admin.courses.update', $course), ['name' => $course->name, 'slug' => $course->slug, 'type' => $course->type, 'status' => $course->status, 'order_num' => $input])->assertSessionHasErrors('order_num');
        $this->put(route('admin.courses.missions.update', [$course, $mission]), ['title' => $mission->title, 'difficulty' => $mission->difficulty, 'points' => $input, 'order_num' => 1, 'section_id' => null])->assertSessionHasErrors('points');
        $this->assertSame(2, $course->refresh()->order_num);
        $this->assertSame(40, $mission->refresh()->points);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidNumbers(): array
    {
        return ['fraction' => ['1.5'], 'negative' => ['-1'], 'array' => [['7']], 'overflow' => ['9999999999999999999999999'], 'text' => ['seven']];
    }

    public function test_impossible_boss_threshold_is_refused_by_request_and_service(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create(['course_id' => $course->id, 'passing_score' => 70]);
        foreach (['101', 101, '-1', -1] as $score) {
            $this->actingAs($admin)->put(route('admin.courses.assessment.update', [$course, $assessment]), ['title' => $assessment->title, 'passing_score' => $score, 'status' => $assessment->status])->assertSessionHasErrors('passing_score');
        }
        try {
            app(AdminAssessmentService::class)->update($admin, $course, $assessment, ['title' => $assessment->title, 'passing_score' => 101, 'status' => $assessment->status]);
            $this->fail('The domain boundary accepted an impossible threshold.');
        } catch (InvalidArgumentException) {
            $this->assertSame(70, $assessment->refresh()->passing_score);
        }
        $this->assertDatabaseCount('the404_assessment_attempts', 0);
    }

    public function test_boss_threshold_accepts_both_percentage_boundaries(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);
        foreach (['0', 0, '100', 100] as $score) {
            $this->actingAs($admin)->put(route('admin.courses.assessment.update', [$course, $assessment]), ['title' => $assessment->title, 'passing_score' => $score, 'status' => $assessment->status])->assertSessionHasNoErrors()->assertSessionHas('status');
            $this->assertSame((int) $score, $assessment->refresh()->passing_score);
        }
    }
}
