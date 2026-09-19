<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\SectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_sections_index_lists_sections_in_order_num_order(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        Section::factory()->create(['course_id' => $course->id, 'title' => 'Zulu Section', 'order_num' => 3]);
        Section::factory()->create(['course_id' => $course->id, 'title' => 'Alpha Section', 'order_num' => 1]);
        Section::factory()->create(['course_id' => $course->id, 'title' => 'Bravo Section', 'order_num' => 2]);

        $response = $this->actingAs($admin)->get(route('admin.courses.sections', $course));

        $response->assertOk();
        $content = $response->getContent();

        $alphaAt = strpos($content, 'Alpha Section');
        $bravoAt = strpos($content, 'Bravo Section');
        $zuluAt = strpos($content, 'Zulu Section');

        $this->assertNotFalse($alphaAt);
        $this->assertNotFalse($bravoAt);
        $this->assertNotFalse($zuluAt);
        $this->assertLessThan($bravoAt, $alphaAt, 'order_num 1 must render before order_num 2');
        $this->assertLessThan($zuluAt, $bravoAt, 'order_num 2 must render before order_num 3');
    }

    public function test_sections_index_is_scoped_to_the_given_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['name' => 'Primary Course']);
        $other = Course::factory()->create(['name' => 'Other Course']);
        Section::factory()->create(['course_id' => $course->id, 'title' => 'Owned Section']);
        Section::factory()->create(['course_id' => $other->id, 'title' => 'Foreign Section']);

        $this->actingAs($admin)->get(route('admin.courses.sections', $course))
            ->assertOk()
            ->assertSee('Owned Section')
            ->assertDontSee('Foreign Section');
    }

    public function test_sections_index_shows_the_mission_count_per_section(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id, 'title' => 'Counting Section']);
        Mission::factory()->count(3)->create(['course_id' => $course->id, 'section_id' => $section->id]);

        $response = $this->actingAs($admin)->get(route('admin.courses.sections', $course));
        $response->assertOk();

        $content = $response->getContent();
        $titleAt = strpos($content, 'Counting Section');
        $rowEnd = strpos($content, '</tr>', $titleAt);

        $this->assertNotFalse($titleAt);
        $this->assertNotFalse($rowEnd);

        $row = substr($content, $titleAt, $rowEnd - $titleAt);

        $this->assertStringContainsString('data-label="Missions"', $row);
        $this->assertMatchesRegularExpression('/data-label="Missions">\s*3\s*</', $row, 'the section row must show its mission count');
    }

    public function test_edit_renders_the_current_section_values(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create([
            'course_id' => $course->id,
            'title' => 'Arrays Rig',
            'order_num' => 4,
        ]);

        $this->actingAs($admin)->get(route('admin.courses.sections.edit', [$course, $section]))
            ->assertOk()
            ->assertSee('Arrays Rig')
            ->assertSee('value="4"', false);
    }

    public function test_edit_404s_for_a_section_of_a_different_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $other = Course::factory()->create();
        $foreign = Section::factory()->create(['course_id' => $other->id]);

        $this->actingAs($admin)->get(route('admin.courses.sections.edit', [$course, $foreign]))
            ->assertNotFound();
    }

    public function test_update_applies_every_section_field(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => 3]);

        $this->actingAs($admin)->put(route('admin.courses.sections.update', [$course, $section]), [
            'title' => 'Data Arrays',
            'description' => 'Iterate over array data.',
            'order_num' => 7,
        ])->assertRedirect(route('admin.courses.sections', $course));

        $section->refresh();
        $this->assertSame('Data Arrays', $section->title);
        $this->assertSame('Iterate over array data.', $section->description);
        $this->assertSame(7, $section->order_num);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'section.update',
            'target_type' => 'section',
            'target_id' => $section->id,
            'result' => 'success',
            'summary' => "Section updated: title → 'Data Arrays', description → 'Iterate over array data.', order_num → 7, version 1 → 2",
        ]);
    }

    public function test_update_never_touches_the_sections_missions(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id, 'title' => 'Original Title']);
        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'title' => 'Kept Mission',
        ]);

        $missionBefore = Mission::firstOrFail();

        $this->actingAs($admin)->put(route('admin.courses.sections.update', [$course, $section]), [
            'title' => 'Renamed Section',
            'description' => 'New blurb.',
            'order_num' => 9,
        ])->assertRedirect(route('admin.courses.sections', $course));

        $missionAfter = Mission::firstOrFail();
        $this->assertSame($missionBefore->id, $missionAfter->id, 'a section edit must not delete or recreate missions');
        $this->assertSame('Kept Mission', $missionAfter->title);
        $this->assertSame($section->id, $missionAfter->section_id, 'missions must stay bound to their section');
        $this->assertSame(1, Mission::count(), 'a section edit must not add or remove mission rows');
    }

    public function test_update_404s_for_a_section_of_a_different_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $other = Course::factory()->create();
        $foreign = Section::factory()->create(['course_id' => $other->id, 'title' => 'Foreign Rig']);

        $this->actingAs($admin)->put(route('admin.courses.sections.update', [$course, $foreign]), [
            'title' => 'Hijacked',
            'description' => null,
            'order_num' => 1,
        ])->assertNotFound();

        $this->assertSame('Foreign Rig', $foreign->refresh()->title);
        $this->assertSame(0, AdminAudit::count(), 'a 404 must not write an audit row');
    }

    public function test_no_op_update_writes_no_audit_row(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin)->put(route('admin.courses.sections.update', [$course, $section]), [
            'title' => $section->title,
            'description' => $section->description ?? '',
            'order_num' => $section->order_num,
        ])->assertRedirect(route('admin.courses.sections', $course));

        $this->assertDatabaseMissing('the404_admin_audit', [
            'target_type' => 'section',
            'target_id' => $section->id,
        ]);
    }

    public function test_order_num_must_be_a_non_negative_integer(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin)->put(route('admin.courses.sections.update', [$course, $section]), [
            'title' => $section->title,
            'description' => $section->description ?? '',
            'order_num' => -1,
        ])->assertSessionHasErrors('order_num');

        $this->assertNotSame(-1, $section->refresh()->order_num);
    }

    public function test_title_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin)->put(route('admin.courses.sections.update', [$course, $section]), [
            'title' => '',
            'description' => $section->description ?? '',
            'order_num' => $section->order_num,
        ])->assertSessionHasErrors('title');

        $this->assertNotEmpty($section->refresh()->title);
    }

    public function test_out_of_allow_list_value_at_the_service_layer_records_a_failed_row(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $service = app(SectionService::class);

        try {
            $service->update($admin, $course, $section, [
                'title' => '',
                'description' => $section->description ?? '',
                'order_num' => $section->order_num,
            ]);
            $this->fail('an empty title must throw at the service layer');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Section title must be a non-empty string of at most 128 characters.', $e->getMessage());
        }

        $this->assertNotEmpty($section->refresh()->title);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'section.update',
            'target_type' => 'section',
            'target_id' => $section->id,
            'result' => 'failed',
            'summary' => 'Refused: Section title must be a non-empty string of at most 128 characters.',
        ]);
    }

    public function test_update_refuses_a_section_of_a_different_course_at_the_service_layer(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $other = Course::factory()->create();
        $foreign = Section::factory()->create(['course_id' => $other->id, 'title' => 'Foreign Rig']);
        $service = app(SectionService::class);

        try {
            $service->update($admin, $course, $foreign, [
                'title' => 'Hijacked',
                'description' => null,
                'order_num' => 1,
            ]);
            $this->fail('a cross-course update must throw at the service layer');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('This section does not belong to the given course.', $e->getMessage());
        }

        $this->assertSame('Foreign Rig', $foreign->refresh()->title);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'section.update',
            'target_type' => 'section',
            'target_id' => $foreign->id,
            'result' => 'failed',
            'summary' => 'Refused: This section does not belong to the given course.',
        ]);
    }

    public function test_non_admin_roles_are_forbidden_from_the_section_update_path(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);

        $this->actingAs($teacher)->put(route('admin.courses.sections.update', [$course, $section]), [
            'title' => 'Hijacked',
            'description' => null,
            'order_num' => 1,
        ])->assertForbidden();

        $this->assertNotSame('Hijacked', $section->refresh()->title);
        $this->assertSame(0, AdminAudit::count(), 'a 403 must not write an audit row');
    }
}
