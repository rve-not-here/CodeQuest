<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\CourseService;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCourseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_courses_in_order_num_order(): void
    {
        $admin = User::factory()->admin()->create();
        Course::factory()->create(['name' => 'Zulu Course', 'order_num' => 3]);
        Course::factory()->create(['name' => 'Alpha Course', 'order_num' => 1]);
        Course::factory()->create(['name' => 'Bravo Course', 'order_num' => 2]);

        $response = $this->actingAs($admin)->get(route('admin.courses'));

        $response->assertOk();
        $content = $response->getContent();

        $alphaAt = strpos($content, 'Alpha Course');
        $bravoAt = strpos($content, 'Bravo Course');
        $zuluAt = strpos($content, 'Zulu Course');

        $this->assertNotFalse($alphaAt);
        $this->assertNotFalse($bravoAt);
        $this->assertNotFalse($zuluAt);
        $this->assertLessThan($bravoAt, $alphaAt, 'order_num 1 must render before order_num 2');
        $this->assertLessThan($zuluAt, $bravoAt, 'order_num 2 must render before order_num 3');
    }

    public function test_edit_renders_the_current_course_values(): void
    {
        $admin = User::factory()->admin()->create();
        Course::factory()->locked()->create(['name' => 'Locked Rig', 'slug' => 'locked-rig']);

        $course = Course::where('slug', 'locked-rig')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.courses.edit', $course))
            ->assertOk()
            ->assertSee('Locked Rig')
            ->assertSee('value="locked-rig"', false);
    }

    public function test_update_applies_every_catalog_field(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['order_num' => 3]);

        $this->actingAs($admin)->put(route('admin.courses.update', $course), [
            'name' => 'Data Arrays',
            'slug' => 'data-arrays',
            'type' => 'js',
            'description' => 'Iterate over array data.',
            'status' => 'active',
            'order_num' => 7,
        ])->assertRedirect(route('admin.courses'));

        $course->refresh();
        $this->assertSame('Data Arrays', $course->name);
        $this->assertSame('data-arrays', $course->slug);
        $this->assertSame('js', $course->type);
        $this->assertSame('Iterate over array data.', $course->description);
        $this->assertSame(7, $course->order_num);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'course.update',
            'target_type' => 'course',
            'target_id' => $course->id,
            'result' => 'success',
            'summary' => 'Course updated: name → \'Data Arrays\', slug → \'data-arrays\', type → \'js\', description → \'Iterate over array data.\', order_num → 7',
        ]);
    }

    public function test_status_change_applies_and_records_a_status_audit_row(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)->put(route('admin.courses.update', $course), [
            'name' => $course->name,
            'slug' => $course->slug,
            'type' => $course->type,
            'description' => $course->description ?? '',
            'status' => 'locked',
            'order_num' => $course->order_num,
        ])->assertRedirect(route('admin.courses'));

        $this->assertSame('locked', $course->refresh()->status);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'course.status.change',
            'target_type' => 'course',
            'target_id' => $course->id,
            'result' => 'success',
            'summary' => 'Status changed: active → locked',
        ]);
    }

    public function test_status_change_never_rewrites_recorded_student_progress(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        Progress::factory()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
        ]);

        $progressCountBefore = Progress::where('user_id', $student->id)->count();

        $this->actingAs($admin)->put(route('admin.courses.update', $course), [
            'name' => $course->name,
            'slug' => $course->slug,
            'type' => $course->type,
            'description' => $course->description ?? '',
            'status' => 'locked',
            'order_num' => $course->order_num,
        ])->assertRedirect(route('admin.courses'));

        $this->assertSame($progressCountBefore, Progress::query()->count(), 'a status change must not delete or add progress rows');
        $this->assertDatabaseHas('the404_progress', [
            'user_id' => $student->id,
            'mission_id' => $mission->id,
        ]);
    }

    public function test_locking_a_course_removes_it_from_current_course_resolution(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $next = Course::factory()->create(['name' => 'Next Up', 'order_num' => 2]);
        $current = Course::factory()->create(['name' => 'Current Rig', 'order_num' => 1]);
        Mission::factory()->create(['course_id' => $next->id]);
        Mission::factory()->create(['course_id' => $current->id]);

        $dashboard = app(DashboardService::class);
        $this->assertSame('Current Rig', $dashboard->currentCourse($student)->name);

        $this->actingAs($admin)->put(route('admin.courses.update', $current), [
            'name' => $current->name,
            'slug' => $current->slug,
            'type' => $current->type,
            'description' => $current->description ?? '',
            'status' => 'locked',
            'order_num' => $current->order_num,
        ])->assertRedirect(route('admin.courses'));

        $this->assertSame('Next Up', $dashboard->currentCourse($student)->name,
            'locking the current course must move the student to the next active course');
    }

    public function test_locked_and_draft_courses_seal_mission_access_and_block_progress(): void
    {
        $student = User::factory()->create();

        foreach (['locked', 'draft'] as $status) {
            $course = Course::factory()->create();
            $mission = Mission::factory()->create(['course_id' => $course->id]);
            $course->update(['status' => $status]);

            $this->actingAs($student)->get(route('mission.show', $mission))->assertForbidden();
            $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => 'x'])->assertForbidden();
            $this->actingAs($student)->post(route('mission.draft', $mission), ['code' => 'x'])->assertForbidden();
            $this->actingAs($student)->post(route('mission.hint', $mission))->assertForbidden();
            $this->actingAs($student)->post(route('mission.reveal', $mission))->assertForbidden();

            $this->assertDatabaseMissing('the404_progress', ['mission_id' => $mission->id]);
        }

        $this->assertSame(0, XpTransaction::query()->count(),
            'a sealed mission must never earn XP');
    }

    public function test_locked_and_draft_courses_seal_the_boss_challenge(): void
    {
        $student = User::factory()->create();

        foreach (['locked', 'draft'] as $status) {
            $course = Course::factory()->create();
            $mission = Mission::factory()->create(['course_id' => $course->id]);
            $assessment = Assessment::factory()->create(['course_id' => $course->id]);
            Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);

            $course->update(['status' => $status]);

            $this->actingAs($student)->get(route('assessment.show', $assessment))
                ->assertRedirect(route('assessments'))
                ->assertSessionHas('assessment_locked');

            $this->actingAs($student)->post(route('assessment.start', $assessment))
                ->assertRedirect(route('assessments'))
                ->assertSessionHas('assessment_error');

            $this->assertDatabaseMissing('the404_assessment_attempts', [
                'assessment_id' => $assessment->id,
                'user_id' => $student->id,
            ]);
        }
    }

    public function test_status_change_rejects_values_outside_the_catalog_set(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)->put(route('admin.courses.update', $course), [
            'name' => $course->name,
            'slug' => $course->slug,
            'type' => $course->type,
            'description' => $course->description ?? '',
            'status' => 'retired',
            'order_num' => $course->order_num,
        ])->assertSessionHasErrors('status');

        $this->assertSame('active', $course->refresh()->status);
    }

    public function test_slug_must_be_unique_across_courses(): void
    {
        $admin = User::factory()->admin()->create();
        Course::factory()->create(['slug' => 'taken-slug']);
        $course = Course::factory()->create();

        $this->actingAs($admin)->put(route('admin.courses.update', $course), [
            'name' => $course->name,
            'slug' => 'taken-slug',
            'type' => $course->type,
            'description' => $course->description ?? '',
            'status' => 'active',
            'order_num' => $course->order_num,
        ])->assertSessionHasErrors('slug');

        $this->assertNotSame('taken-slug', $course->refresh()->slug);
    }

    public function test_slug_must_be_kebab_case(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)->put(route('admin.courses.update', $course), [
            'name' => $course->name,
            'slug' => 'Bad Slug!',
            'type' => $course->type,
            'description' => $course->description ?? '',
            'status' => 'active',
            'order_num' => $course->order_num,
        ])->assertSessionHasErrors('slug');

        $this->assertNotSame('Bad Slug!', $course->refresh()->slug);
    }

    public function test_order_num_must_be_a_non_negative_integer(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)->put(route('admin.courses.update', $course), [
            'name' => $course->name,
            'slug' => $course->slug,
            'type' => $course->type,
            'description' => $course->description ?? '',
            'status' => 'active',
            'order_num' => -1,
        ])->assertSessionHasErrors('order_num');

        $this->assertNotSame(-1, $course->refresh()->order_num);
    }

    public function test_no_op_update_writes_no_audit_row(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)->put(route('admin.courses.update', $course), [
            'name' => $course->name,
            'slug' => $course->slug,
            'type' => $course->type,
            'description' => $course->description ?? '',
            'status' => 'active',
            'order_num' => $course->order_num,
        ])->assertRedirect(route('admin.courses'));

        $this->assertDatabaseMissing('the404_admin_audit', [
            'target_type' => 'course',
            'target_id' => $course->id,
        ]);
    }

    public function test_out_of_allow_list_value_at_the_service_layer_records_a_failed_row(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $service = app(CourseService::class);

        try {
            $service->update($admin, $course, [
                'name' => $course->name,
                'slug' => $course->slug,
                'type' => $course->type,
                'description' => $course->description ?? '',
                'status' => 'archived',
                'order_num' => $course->order_num,
            ]);
            $this->fail('an out-of-allow-list status must throw');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame("Unknown status 'archived'.", $e->getMessage());
        }

        $this->assertSame('active', $course->refresh()->status);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'course.update',
            'target_type' => 'course',
            'target_id' => $course->id,
            'result' => 'failed',
            'summary' => "Refused: Unknown status 'archived'.",
        ]);
    }

    public function test_non_admin_roles_are_forbidden_from_the_course_update_path(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create();

        $this->actingAs($teacher)->put(route('admin.courses.update', $course), [
            'name' => 'Hijacked',
            'slug' => 'hijacked',
            'type' => 'html',
            'description' => null,
            'status' => 'active',
            'order_num' => 1,
        ])->assertForbidden();

        $this->assertNotSame('Hijacked', $course->refresh()->name);
        $this->assertSame(0, AdminAudit::count(), 'a 403 must not write an audit row');
    }
}
