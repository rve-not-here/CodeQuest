<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    public function test_non_students_cannot_submit_missions_or_create_academic_records(): void
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'validate_rule' => null,
        ]);

        foreach (['teacher', 'admin', 'operator'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->post(route('mission.submit', $mission), ['code' => 'valid'])
                ->assertForbidden();

            $this->assertDatabaseMissing('the404_progress', ['user_id' => $user->id]);
            $this->assertDatabaseMissing('the404_xp_transactions', ['user_id' => $user->id]);
        }
    }

    public function test_non_students_cannot_open_student_academic_pages_or_exports(): void
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $pages = [
            route('dashboard'), route('learning-path'), route('progress'), route('section-progress'),
            route('missions'), route('mission.show', $mission), route('mission.challenge', $mission),
            route('assessments'), route('assessment.show', $assessment), route('timeline'),
            route('xp-ledger'), route('competency'), route('recommendations'), route('achievements'),
            route('export.progress', ['format' => 'csv']),
        ];

        foreach (['teacher', 'admin', 'operator'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            foreach ($pages as $page) {
                $this->actingAs($user)->get($page)->assertForbidden();
            }
        }
    }

    public function test_non_students_cannot_start_assessments_or_knowledge_checks_even_with_forged_student_role(): void
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);
        $check = KnowledgeCheck::factory()->create(['mission_id' => $mission->id]);

        foreach (['teacher', 'admin', 'operator'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->post(route('assessment.start', $assessment), ['role' => 'student'])
                ->assertForbidden();
            $this->actingAs($user)
                ->post(route('knowledge-check.start', [$mission, $check]), ['role' => 'student'])
                ->assertForbidden();

            $this->assertDatabaseMissing('the404_assessment_attempts', ['user_id' => $user->id]);
            $this->assertDatabaseMissing('the404_knowledge_check_attempts', ['user_id' => $user->id]);
        }
    }

    public function test_role_denial_precedes_model_binding_for_student_teacher_and_admin_areas(): void
    {
        $student = User::factory()->create();
        $teacher = User::factory()->teacher()->create();
        $operator = User::factory()->create(['role' => 'operator']);
        $nonexistentId = 999999999;

        $this->get(route('mission.show', $nonexistentId))->assertRedirect(route('login'));
        $this->get(route('classrooms.show', $nonexistentId))->assertRedirect(route('login'));
        $this->get(route('admin.users.show', $nonexistentId))->assertRedirect(route('login'));

        $this->actingAs($operator)
            ->get(route('mission.show', $nonexistentId))
            ->assertForbidden();
        $this->actingAs($student)
            ->get(route('classrooms.show', $nonexistentId))
            ->assertForbidden();
        $this->actingAs($teacher)
            ->get(route('admin.users.show', $nonexistentId))
            ->assertForbidden();
    }

    public function test_role_changes_revoke_the_prior_area_without_a_new_login(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $student->update(['role' => 'operator']);

        $this->get(route('dashboard'))->assertForbidden();
        $this->get(route('notifications'))->assertOk();
    }
}
