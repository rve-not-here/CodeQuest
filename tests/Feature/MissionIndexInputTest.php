<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MissionIndexInputTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('invalidFilters')]
    public function test_invalid_filter_shapes_return_validation_errors(string $field, mixed $value): void
    {
        $this->actingAs(User::factory()->create())->getJson(route('missions', [$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('the404_progress', 0);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidFilters(): array
    {
        return [
            'search array' => ['q', ['nested' => ['text']]],
            'status array' => ['status', ['COMPLETED']],
            'course array' => ['course', ['1']],
            'unknown status' => ['status', 'passed'],
            'fractional course' => ['course', '1.5'],
            'missing course' => ['course', '999999'],
            'oversized search' => ['q', str_repeat('x', 201)],
        ];
    }

    public function test_valid_filters_render_only_matching_authoritative_rows(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id, 'title' => 'Unique needle']);
        Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id, 'title' => 'Excluded title']);
        $this->actingAs($student)->get(route('missions', ['q' => 'needle', 'course' => (string) $course->id, 'status' => 'NOT STARTED']))
            ->assertOk()->assertSee($mission->title)->assertDontSee('Excluded title');
    }

    public function test_ownership_probe_is_rejected_before_malformed_filter_validation(): void
    {
        $this->actingAs(User::factory()->create())->getJson(route('missions', ['user_id' => 999999, 'q' => ['bad']]))->assertForbidden();
    }

    public function test_invalid_browser_filter_redirects_to_safe_index_with_visible_error(): void
    {
        $this->actingAs(User::factory()->create())->followingRedirects()->get(route('missions', ['q' => ['bad']]))
            ->assertOk()->assertSee('Invalid challenge filters')->assertSee('The q field must be a string.');
    }
}
