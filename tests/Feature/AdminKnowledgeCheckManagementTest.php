<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\KnowledgeCheckGate;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P1-1: Knowledge Check metadata is operable without a deployment.
 *
 * The required gate is the load-bearing field: flipping it here is what makes
 * the KnowledgeCheckGate prerequisite real for existing content. Writes are
 * audited, versioned, and scoped to the Course → Mission → Check hierarchy.
 */
class AdminKnowledgeCheckManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    /** @return array{course: Course, mission: Mission, check: KnowledgeCheck} */
    private function curriculum(): array
    {
        $course = Course::factory()->create(['status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
        ]);
        $check = KnowledgeCheck::factory()->create([
            'mission_id' => $mission->id,
            'order_num' => 1,
            'is_required' => false,
            'status' => KnowledgeCheck::STATUS_PUBLISHED,
            'version' => 1,
        ]);

        return compact('course', 'mission', 'check');
    }

    public function test_admin_sees_the_mission_checks(): void
    {
        $admin = User::factory()->admin()->create();
        ['course' => $course, 'mission' => $mission, 'check' => $check] = $this->curriculum();

        $response = $this->actingAs($admin)
            ->get(route('admin.courses.missions.checks', [$course, $mission]));

        $response->assertOk();
        $response->assertSee($check->title);
        $response->assertSee('Optional');
    }

    public function test_admin_can_make_a_check_required_and_the_version_bumps(): void
    {
        $admin = User::factory()->admin()->create();
        ['course' => $course, 'mission' => $mission, 'check' => $check] = $this->curriculum();

        $this->actingAs($admin)->put(
            route('admin.courses.missions.checks.update', [$course, $mission, $check]),
            [
                'title' => $check->title,
                'instructions' => $check->instructions ?? '',
                'is_required' => '1',
                'status' => KnowledgeCheck::STATUS_PUBLISHED,
            ],
        )->assertRedirect(route('admin.courses.missions.checks', [$course, $mission]));

        $check->refresh();
        $this->assertTrue($check->is_required);
        $this->assertSame(2, $check->version);

        $audit = AdminAudit::query()
            ->where('action', 'knowledge_check.update')
            ->where('target_type', 'knowledge_check')
            ->where('target_id', $check->id)
            ->where('result', 'success')
            ->sole();
        $this->assertStringContainsString('is_required → true', $audit->summary);
        $this->assertStringContainsString('version 1 → 2', $audit->summary);
    }

    public function test_requiring_a_check_makes_the_gate_outstanding_until_an_attempt_is_submitted(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['role' => 'student']);
        ['course' => $course, 'mission' => $mission, 'check' => $check] = $this->curriculum();

        $this->actingAs($admin)->put(
            route('admin.courses.missions.checks.update', [$course, $mission, $check]),
            [
                'title' => $check->title,
                'instructions' => $check->instructions ?? '',
                'is_required' => '1',
                'status' => KnowledgeCheck::STATUS_PUBLISHED,
            ],
        );

        $gate = app(KnowledgeCheckGate::class);
        $this->assertNotNull($gate->outstanding($student, $mission));

        KnowledgeCheckAttempt::factory()->submitted(0, 1)->create([
            'knowledge_check_id' => $check->id,
            'user_id' => $student->id,
        ]);

        $this->assertNull($gate->outstanding($student, $mission));
    }

    public function test_non_admin_cannot_manage_knowledge_checks(): void
    {
        $teacher = User::factory()->teacher()->create();
        ['course' => $course, 'mission' => $mission, 'check' => $check] = $this->curriculum();

        $this->actingAs($teacher)
            ->get(route('admin.courses.missions.checks', [$course, $mission]))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->put(route('admin.courses.missions.checks.update', [$course, $mission, $check]), [
                'title' => 'Hijacked',
                'is_required' => '1',
                'status' => KnowledgeCheck::STATUS_PUBLISHED,
            ])
            ->assertForbidden();
    }

    public function test_a_check_cannot_be_edited_through_another_mission(): void
    {
        $admin = User::factory()->admin()->create();
        ['course' => $course, 'mission' => $mission, 'check' => $check] = $this->curriculum();
        $otherMission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $mission->section_id,
            'order_num' => 2,
        ]);
        $originalTitle = $check->title;

        $this->actingAs($admin)
            ->get(route('admin.courses.missions.checks.edit', [$course, $otherMission, $check]))
            ->assertNotFound();

        $this->actingAs($admin)
            ->put(route('admin.courses.missions.checks.update', [$course, $otherMission, $check]), [
                'title' => 'Hijacked',
                'is_required' => '1',
                'status' => KnowledgeCheck::STATUS_PUBLISHED,
            ])
            ->assertNotFound();

        $this->assertSame($originalTitle, $check->fresh()->title);
    }

    public function test_a_check_cannot_be_edited_through_another_course(): void
    {
        $admin = User::factory()->admin()->create();
        ['course' => $course, 'mission' => $mission, 'check' => $check] = $this->curriculum();
        $otherCourse = Course::factory()->create(['status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.courses.missions.checks.edit', [$otherCourse, $mission, $check]))
            ->assertNotFound();
    }

    public function test_invalid_status_is_rejected_without_writing(): void
    {
        $admin = User::factory()->admin()->create();
        ['course' => $course, 'mission' => $mission, 'check' => $check] = $this->curriculum();
        $originalTitle = $check->title;

        $this->actingAs($admin)->put(
            route('admin.courses.missions.checks.update', [$course, $mission, $check]),
            [
                'title' => 'Changed',
                'instructions' => $check->instructions ?? '',
                'is_required' => '0',
                'status' => 'bogus',
            ],
        )->assertSessionHasErrors('status');

        $check->refresh();
        $this->assertSame($originalTitle, $check->title);
        $this->assertSame(1, $check->version);
    }
}
