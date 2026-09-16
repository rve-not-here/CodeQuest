<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\AdminMissionService;
use App\Services\MissionService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    public function test_missions_index_lists_missions_in_order_num_order(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        Mission::factory()->create(['course_id' => $course->id, 'title' => 'Zulu Mission', 'order_num' => 3]);
        Mission::factory()->create(['course_id' => $course->id, 'title' => 'Alpha Mission', 'order_num' => 1]);
        Mission::factory()->create(['course_id' => $course->id, 'title' => 'Bravo Mission', 'order_num' => 2]);

        $response = $this->actingAs($admin)->get(route('admin.courses.missions', $course));

        $response->assertOk();
        $content = $response->getContent();

        $alphaAt = strpos($content, 'Alpha Mission');
        $bravoAt = strpos($content, 'Bravo Mission');
        $zuluAt = strpos($content, 'Zulu Mission');

        $this->assertNotFalse($alphaAt);
        $this->assertNotFalse($bravoAt);
        $this->assertNotFalse($zuluAt);
        $this->assertLessThan($bravoAt, $alphaAt, 'order_num 1 must render before order_num 2');
        $this->assertLessThan($zuluAt, $bravoAt, 'order_num 2 must render before order_num 3');
    }

    public function test_missions_index_is_scoped_to_the_given_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['name' => 'Primary Course']);
        $other = Course::factory()->create(['name' => 'Other Course']);
        Mission::factory()->create(['course_id' => $course->id, 'title' => 'Owned Mission']);
        Mission::factory()->create(['course_id' => $other->id, 'title' => 'Foreign Mission']);

        $this->actingAs($admin)->get(route('admin.courses.missions', $course))
            ->assertOk()
            ->assertSee('Owned Mission')
            ->assertDontSee('Foreign Mission');
    }

    public function test_missions_index_shows_difficulty_points_and_section(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id, 'title' => 'Arrays Rig']);
        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'title' => 'HARD Mission',
            'difficulty' => 'HARD',
            'points' => 150,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.courses.missions', $course));
        $response->assertOk();

        $content = $response->getContent();
        $titleAt = strpos($content, 'HARD Mission');
        $rowEnd = strpos($content, '</tr>', $titleAt);

        $this->assertNotFalse($titleAt);
        $this->assertNotFalse($rowEnd);

        $row = substr($content, $titleAt, $rowEnd - $titleAt);

        $this->assertStringContainsString('data-label="Difficulty"', $row);
        $this->assertStringContainsString('data-label="Points"', $row);
        $this->assertStringContainsString('data-label="Section"', $row);
        $this->assertStringContainsString('HARD', $row, 'the difficulty value must render in its row');
        $this->assertMatchesRegularExpression('/data-label="Points">\s*150\s*</', $row);
        $this->assertStringContainsString('Arrays Rig', $row);
    }

    public function test_edit_renders_current_mission_values(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id, 'title' => 'Arrays Rig']);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'title' => 'Arrays Rig 3',
            'difficulty' => 'MEDIUM',
            'points' => 90,
            'order_num' => 4,
        ]);

        $this->actingAs($admin)->get(route('admin.courses.missions.edit', [$course, $mission]))
            ->assertOk()
            ->assertSee('Arrays Rig 3')
            ->assertSee('value="90"', false)
            ->assertSee('value="4"', false);
    }

    public function test_edit_404s_for_a_mission_of_a_different_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $other = Course::factory()->create();
        $foreign = Mission::factory()->create(['course_id' => $other->id]);

        $this->actingAs($admin)->get(route('admin.courses.missions.edit', [$course, $foreign]))
            ->assertNotFound();
    }

    public function test_update_applies_every_editable_mission_field(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'title' => 'Original Title',
            'difficulty' => 'EASY',
            'points' => 50,
            'order_num' => 1,
        ]);

        $this->actingAs($admin)->put(route('admin.courses.missions.update', [$course, $mission]), [
            'title' => 'Data Arrays',
            'description' => 'Iterate over array data.',
            'difficulty' => 'HARD',
            'points' => 120,
            'order_num' => 7,
            'section_id' => $section->id,
            'hints' => '["Hint one", "Hint two"]',
            'broken_code' => 'let x = ;',
            'target_html' => '<h1>Hello</h1>',
        ])->assertRedirect(route('admin.courses.missions', $course));

        $mission->refresh();
        $this->assertSame('Data Arrays', $mission->title);
        $this->assertSame('Iterate over array data.', $mission->description);
        $this->assertSame('HARD', $mission->difficulty);
        $this->assertSame(120, $mission->points);
        $this->assertSame(7, $mission->order_num);
        $this->assertSame($section->id, $mission->section_id);
        $this->assertSame('["Hint one", "Hint two"]', $mission->hints);
        $this->assertSame('let x = ;', $mission->broken_code);
        $this->assertSame('<h1>Hello</h1>', $mission->target_html);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'mission.update',
            'target_type' => 'mission',
            'target_id' => $mission->id,
            'result' => 'success',
            'summary' => "Mission updated: title → 'Data Arrays', description → 'Iterate over array data.', difficulty → 'HARD', points → 120, order_num → 7, section_id → {$section->id}, hints → '[\"Hint one\", \"Hint two\"]', broken_code → 'let x = ;', target_html → '<h1>Hello</h1>'",
        ]);
    }

    public function test_update_can_clear_section_id_to_null(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'title' => 'Wireframe Rig',
            'difficulty' => 'EASY',
            'points' => 50,
            'order_num' => 1,
        ]);

        $this->actingAs($admin)->put(route('admin.courses.missions.update', [$course, $mission]), [
            'title' => 'Wireframe Rig',
            'description' => $mission->description,
            'difficulty' => 'EASY',
            'points' => 50,
            'order_num' => 1,
            'section_id' => null,
            'hints' => null,
            'broken_code' => null,
            'target_html' => null,
        ])->assertRedirect(route('admin.courses.missions', $course));

        $this->assertNull($mission->refresh()->section_id);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'mission.update',
            'target_type' => 'mission',
            'target_id' => $mission->id,
            'result' => 'success',
            'summary' => 'Mission updated: section_id → null',
        ]);
    }

    public function test_update_never_accepts_solution_code_or_validate_rule_from_a_crafted_payload(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'title' => 'Original Title',
            'difficulty' => 'EASY',
            'points' => 50,
            'order_num' => 1,
            'solution_code' => 'const secret = true;',
            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'SECRET']]),
        ]);

        $this->actingAs($admin)->put(route('admin.courses.missions.update', [$course, $mission]), [
            'title' => 'Swapped Title',
            'description' => null,
            'difficulty' => 'EASY',
            'points' => 50,
            'order_num' => 1,
            'section_id' => null,
            'hints' => null,
            'broken_code' => null,
            'target_html' => null,
            'solution_code' => 'const hijacked = true;',
            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'HIJACKED']]),
        ])->assertRedirect(route('admin.courses.missions', $course));

        $mission->refresh();
        $this->assertSame('const secret = true;', $mission->solution_code);
        $this->assertSame('[{"type":"contains","value":"SECRET"}]', $mission->validate_rule);
    }

    public function test_editing_points_never_rewrites_recorded_progress_xp_or_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'title' => 'Points Anchor',
            'difficulty' => 'EASY',
            'points' => 50,
            'order_num' => 1,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
        ]);

        app(MissionService::class)->submit($student, $mission, '<h1>Hello</h1>');

        $this->assertSame(50, app(XpService::class)->balance($student));
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'pts_earned' => 50,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'amount' => 50,
        ]);
        $this->assertDatabaseHas('the404_activity', [
            'user_id' => $student->id,
            'type' => 'mission_completed',
            'pts' => 50,
        ]);

        $this->actingAs($admin)->put(route('admin.courses.missions.update', [$course, $mission]), [
            'title' => 'Points Anchor',
            'description' => null,
            'difficulty' => 'EASY',
            'points' => 200,
            'order_num' => 1,
            'section_id' => null,
            'hints' => null,
            'broken_code' => null,
            'target_html' => null,
        ])->assertRedirect(route('admin.courses.missions', $course));

        $this->assertSame(200, $mission->refresh()->points);

        $this->assertSame(50, app(XpService::class)->balance($student));
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'pts_earned' => 50,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'amount' => 50,
        ]);
        $this->assertDatabaseHas('the404_activity', [
            'user_id' => $student->id,
            'type' => 'mission_completed',
            'pts' => 50,
        ]);
    }

    public function test_a_new_completion_after_a_points_edit_earns_the_new_value(): void
    {
        $admin = User::factory()->admin()->create();
        $laterStudent = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'title' => 'Points Refresh',
            'difficulty' => 'EASY',
            'points' => 50,
            'order_num' => 1,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
        ]);

        $this->actingAs($admin)->put(route('admin.courses.missions.update', [$course, $mission]), [
            'title' => 'Points Refresh',
            'description' => null,
            'difficulty' => 'EASY',
            'points' => 175,
            'order_num' => 1,
            'section_id' => null,
            'hints' => null,
            'broken_code' => null,
            'target_html' => null,
        ])->assertRedirect(route('admin.courses.missions', $course));

        $mission->refresh();

        app(MissionService::class)->submit($laterStudent, $mission, '<h1>Hello</h1>');

        $this->assertSame(175, app(XpService::class)->balance($laterStudent));
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $laterStudent->id,
            'mission_id' => $mission->id,
            'pts_earned' => 175,
        ]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $laterStudent->id,
            'mission_id' => $mission->id,
            'amount' => 175,
        ]);
    }

    public function test_update_404s_for_a_mission_of_a_different_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $other = Course::factory()->create();
        $foreign = Mission::factory()->create(['course_id' => $other->id, 'title' => 'Foreign Rig']);

        $this->actingAs($admin)->put(route('admin.courses.missions.update', [$course, $foreign]), [
            'title' => 'Hijacked',
            'description' => null,
            'difficulty' => 'EASY',
            'points' => 50,
            'order_num' => 1,
            'section_id' => null,
            'hints' => null,
            'broken_code' => null,
            'target_html' => null,
        ])->assertNotFound();

        $this->assertSame('Foreign Rig', $foreign->refresh()->title);
        $this->assertSame(0, AdminAudit::count(), 'a 404 must not write an audit row');
    }

    public function test_update_refuses_a_section_of_a_different_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $other = Course::factory()->create();
        $foreign = Section::factory()->create(['course_id' => $other->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'title' => 'Wireframe Rig',
            'difficulty' => 'EASY',
            'points' => 50,
            'order_num' => 1,
        ]);

        $this->actingAs($admin)->put(route('admin.courses.missions.update', [$course, $mission]), [
            'title' => 'Wireframe Rig',
            'description' => null,
            'difficulty' => 'EASY',
            'points' => 50,
            'order_num' => 1,
            'section_id' => $foreign->id,
            'hints' => null,
            'broken_code' => null,
            'target_html' => null,
        ])->assertSessionHasNoErrors();

        $this->assertNull($mission->refresh()->section_id, 'a cross-course section must not be assigned');

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'mission.update',
            'target_type' => 'mission',
            'target_id' => $mission->id,
            'result' => 'failed',
            'summary' => 'Refused: Mission section must belong to the same course as the mission.',
        ]);
    }

    public function test_no_op_update_writes_no_audit_row(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin)->put(route('admin.courses.missions.update', [$course, $mission]), [
            'title' => $mission->title,
            'description' => $mission->description ?? '',
            'difficulty' => $mission->difficulty,
            'points' => $mission->points,
            'order_num' => $mission->order_num,
            'section_id' => $mission->section_id,
            'hints' => $mission->hints ?? '',
            'broken_code' => $mission->broken_code ?? '',
            'target_html' => $mission->target_html ?? '',
        ])->assertRedirect(route('admin.courses.missions', $course));

        $this->assertDatabaseMissing('the404_admin_audit', [
            'target_type' => 'mission',
            'target_id' => $mission->id,
        ]);
    }

    public function test_points_must_be_a_non_negative_integer(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        $pointsBefore = $mission->points;

        $this->actingAs($admin)->put(route('admin.courses.missions.update', [$course, $mission]), [
            'title' => $mission->title,
            'description' => $mission->description ?? '',
            'difficulty' => $mission->difficulty,
            'points' => -1,
            'order_num' => $mission->order_num,
            'section_id' => $mission->section_id,
            'hints' => $mission->hints ?? '',
            'broken_code' => $mission->broken_code ?? '',
            'target_html' => $mission->target_html ?? '',
        ])->assertSessionHasErrors('points');

        $this->assertSame($pointsBefore, $mission->refresh()->points);
    }

    public function test_difficulty_must_be_one_of_the_enum_values(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin)->put(route('admin.courses.missions.update', [$course, $mission]), [
            'title' => $mission->title,
            'description' => $mission->description ?? '',
            'difficulty' => 'IMPOSSIBLE',
            'points' => $mission->points,
            'order_num' => $mission->order_num,
            'section_id' => $mission->section_id,
            'hints' => $mission->hints ?? '',
            'broken_code' => $mission->broken_code ?? '',
            'target_html' => $mission->target_html ?? '',
        ])->assertSessionHasErrors('difficulty');

        $this->assertSame('EASY', $mission->refresh()->difficulty);
    }

    public function test_smuggled_solution_code_and_validate_rule_are_never_written_at_the_service_layer(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'points' => 50,
            'solution_code' => 'const secret = true;',
            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'SECRET']]),
        ]);
        $service = app(AdminMissionService::class);

        $service->update($admin, $course, $mission, [
            'title' => 'Smuggler',
            'description' => null,
            'difficulty' => 'EASY',
            'points' => 50,
            'order_num' => 1,
            'section_id' => null,
            'hints' => null,
            'broken_code' => null,
            'target_html' => null,
            'solution_code' => 'const hijacked = true;',
            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'HIJACKED']]),
        ]);

        $mission->refresh();
        $this->assertSame('Smuggler', $mission->title);
        $this->assertSame('const secret = true;', $mission->solution_code, 'solution_code must never be rewritten');
        $this->assertSame('[{"type":"contains","value":"SECRET"}]', $mission->validate_rule, 'validate_rule must never be rewritten');
    }

    public function test_update_refuses_a_mission_of_a_different_course_at_the_service_layer(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $other = Course::factory()->create();
        $foreign = Mission::factory()->create(['course_id' => $other->id, 'title' => 'Foreign Rig']);
        $service = app(AdminMissionService::class);

        try {
            $service->update($admin, $course, $foreign, [
                'title' => 'Hijacked',
                'description' => null,
                'difficulty' => 'EASY',
                'points' => 50,
                'order_num' => 1,
                'section_id' => null,
                'hints' => null,
                'broken_code' => null,
                'target_html' => null,
            ]);
            $this->fail('a cross-course update must throw at the service layer');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('This mission does not belong to the given course.', $e->getMessage());
        }

        $this->assertSame('Foreign Rig', $foreign->refresh()->title);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'mission.update',
            'target_type' => 'mission',
            'target_id' => $foreign->id,
            'result' => 'failed',
            'summary' => 'Refused: This mission does not belong to the given course.',
        ]);
    }

    public function test_non_admin_roles_are_forbidden_from_the_mission_update_path(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);

        $this->actingAs($teacher)->put(route('admin.courses.missions.update', [$course, $mission]), [
            'title' => 'Hijacked',
            'description' => null,
            'difficulty' => 'EASY',
            'points' => 50,
            'order_num' => 1,
            'section_id' => null,
            'hints' => null,
            'broken_code' => null,
            'target_html' => null,
        ])->assertForbidden();

        $this->assertNotSame('Hijacked', $mission->refresh()->title);
        $this->assertSame(0, AdminAudit::count(), 'a 403 must not write an audit row');
    }
}
