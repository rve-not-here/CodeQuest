<?php

namespace Tests\Feature;

use App\Exceptions\AssessmentNotUnlockedException;
use App\Models\AdminAudit;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\User;
use App\Services\AdminAssessmentService;
use App\Services\AssessmentService;
use App\Services\MissionService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAssessmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    public function test_assessment_index_lists_the_single_assessment_for_the_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create(['course_id' => $course->id, 'title' => 'Dawn Star Challenge', 'passing_score' => 80, 'status' => 'active']);

        $response = $this->actingAs($admin)->get(route('admin.courses.assessment', $course));

        $response->assertOk()->assertSee('Dawn Star Challenge')->assertSee('80%');
    }

    public function test_assessment_index_shows_empty_state_when_no_assessment_exists(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)->get(route('admin.courses.assessment', $course))
            ->assertOk()
            ->assertSee('NO BOSS CHALLENGE');
    }

    public function test_assessment_index_is_scoped_to_the_given_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['name' => 'Primary']);
        $other = Course::factory()->create(['name' => 'Other']);
        Assessment::factory()->create(['course_id' => $course->id, 'title' => 'Owned Assessment']);
        Assessment::factory()->create(['course_id' => $other->id, 'title' => 'Foreign Assessment']);

        $this->actingAs($admin)->get(route('admin.courses.assessment', $course))
            ->assertOk()
            ->assertSee('Owned Assessment')
            ->assertDontSee('Foreign Assessment');
    }

    public function test_edit_renders_current_assessment_values(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Sigma Core',
            'passing_score' => 65,
            'status' => 'active',
            'description' => 'A hard challenge.',
        ]);

        $this->actingAs($admin)->get(route('admin.courses.assessment.edit', [$course, $assessment]))
            ->assertOk()
            ->assertSee('Sigma Core')
            ->assertSee('value="65"', false);
    }

    public function test_edit_404s_for_an_assessment_of_a_different_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $other = Course::factory()->create();
        $foreign = Assessment::factory()->create(['course_id' => $other->id]);

        $this->actingAs($admin)->get(route('admin.courses.assessment.edit', [$course, $foreign]))
            ->assertNotFound();
    }

    public function test_update_applies_every_editable_assessment_field(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Old Title',
            'description' => 'Old description',
            'instructions' => 'Old instructions',
            'passing_score' => 50,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->put(route('admin.courses.assessment.update', [$course, $assessment]), [
            'title' => 'New Title',
            'description' => 'New description',
            'instructions' => 'New instructions',
            'passing_score' => 80,
            'status' => 'locked',
        ])->assertRedirect(route('admin.courses.assessment', $course));

        $assessment->refresh();
        $this->assertSame('New Title', $assessment->title);
        $this->assertSame('New description', $assessment->description);
        $this->assertSame('New instructions', $assessment->instructions);
        $this->assertSame(80, $assessment->passing_score);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'assessment.update',
            'target_type' => 'assessment',
            'target_id' => $assessment->id,
            'result' => 'success',
            'summary' => "Assessment updated: title → 'New Title', description → 'New description', instructions → 'New instructions', passing_score → 80",
        ]);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'assessment.status.change',
            'target_type' => 'assessment',
            'target_id' => $assessment->id,
            'result' => 'success',
            'summary' => 'Assessment status changed: active → locked',
        ]);
    }

    public function test_update_never_accepts_grading_rule_from_a_crafted_payload(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Original',
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'SECRET']]),
        ]);

        $this->actingAs($admin)->put(route('admin.courses.assessment.update', [$course, $assessment]), [
            'title' => 'Hi-Jacked',
            'description' => $assessment->description ?? '',
            'instructions' => $assessment->instructions ?? '',
            'passing_score' => $assessment->passing_score,
            'status' => $assessment->status,
            'grading_rule' => 'HACKED',
        ])->assertRedirect(route('admin.courses.assessment', $course));

        $assessment->refresh();
        $this->assertSame('Hi-Jacked', $assessment->title);
        $this->assertSame('[{"type":"contains","value":"SECRET"}]', $assessment->grading_rule);
    }

    public function test_editing_passing_score_never_rewrites_recorded_attempts(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
        ]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'passing_score' => 70,
            'grading_rule' => json_encode([
                ['type' => 'contains', 'value' => 'SIGNAL'],
                ['type' => 'contains', 'value' => 'BOOT'],
            ]),
        ]);

        // Complete the mission to become eligible.
        app(MissionService::class)->submit($student, $mission, '<h1>Hello</h1>');

        // Begin and submit a partial attempt (matches 1 of 2 rules → score 50).
        $assessService = app(AssessmentService::class);
        $attempt = $assessService->beginAttempt($student, $course);
        $assessService->submitAttempt($student, $attempt, 'SIGNAL');
        $attempt = $assessService->evaluateAttempt($student, $attempt);

        $this->assertSame(50, $attempt->score);
        $this->assertSame('failed', $attempt->status);
        $this->assertNull($attempt->passed_at);

        // Admin lowers passing_score from 70 to 40.
        $this->actingAs($admin)->put(route('admin.courses.assessment.update', [$course, $assessment]), [
            'title' => $assessment->title,
            'description' => $assessment->description ?? '',
            'instructions' => $assessment->instructions ?? '',
            'passing_score' => 40,
            'status' => $assessment->status,
        ])->assertRedirect(route('admin.courses.assessment', $course));

        // Old verdict is never rewritten: score stays 50, status stays failed,
        // passed_at stays null — even though 50 ≥ 40 now.
        $attempt->refresh();
        $this->assertSame(50, $attempt->score);
        $this->assertSame('failed', $attempt->status);
        $this->assertNull($attempt->passed_at);
        $this->assertSame(40, $assessment->refresh()->passing_score);
    }

    public function test_a_new_submission_after_passing_score_change_uses_new_threshold(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
        ]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'passing_score' => 70,
            'grading_rule' => json_encode([
                ['type' => 'contains', 'value' => 'SIGNAL'],
                ['type' => 'contains', 'value' => 'BOOT'],
            ]),
        ]);

        app(MissionService::class)->submit($student, $mission, '<h1>Hello</h1>');

        $assessService = app(AssessmentService::class);
        $attempt = $assessService->beginAttempt($student, $course);
        $assessService->submitAttempt($student, $attempt, 'SIGNAL');
        $assessService->evaluateAttempt($student, $attempt);

        // Admin lowers passing_score from 70 to 40.
        $this->actingAs($admin)->put(route('admin.courses.assessment.update', [$course, $assessment]), [
            'title' => $assessment->title,
            'description' => $assessment->description ?? '',
            'instructions' => $assessment->instructions ?? '',
            'passing_score' => 40,
            'status' => $assessment->status,
        ]);

        // Student retries: same partial code (50%) now passes the new threshold.
        $retry = $assessService->retryAttempt($student, $course);
        $assessService->submitAttempt($student, $retry, 'SIGNAL');
        $retry = $assessService->evaluateAttempt($student, $retry);

        $this->assertSame(50, $retry->score);
        $this->assertSame('passed', $retry->status);
        $this->assertNotNull($retry->passed_at);

        // Old attempt is still failed.
        $attempt->refresh();
        $this->assertSame('failed', $attempt->status);
    }

    public function test_sealing_the_challenge_never_rewrites_recorded_attempts(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
        ]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'passing_score' => 70,
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'MISS']]),
        ]);

        app(MissionService::class)->submit($student, $mission, '<h1>Hello</h1>');

        $assessService = app(AssessmentService::class);
        $attempt = $assessService->beginAttempt($student, $course);
        $assessService->submitAttempt($student, $attempt, 'no match');
        $attempt = $assessService->evaluateAttempt($student, $attempt);

        $this->assertSame('failed', $attempt->status);

        // Admin seals the challenge.
        $this->actingAs($admin)->put(route('admin.courses.assessment.update', [$course, $assessment]), [
            'title' => $assessment->title,
            'description' => $assessment->description ?? '',
            'instructions' => $assessment->instructions ?? '',
            'passing_score' => $assessment->passing_score,
            'status' => 'locked',
        ]);

        // Attempt rows are untouched.
        $attempt->refresh();
        $this->assertSame('failed', $attempt->status);
        $this->assertNull($attempt->passed_at);

        // isUnlocked now returns false — challenge is sealed.
        $this->assertFalse($assessService->isUnlocked($student, $course));

        // Old passing verdict (none) is never rewritten — new attempt is blocked.
        $this->expectException(AssessmentNotUnlockedException::class);
        $assessService->beginAttempt($student, $course);
    }

    public function test_update_path_never_creates_assessment_attempt_rows(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin)->put(route('admin.courses.assessment.update', [$course, $assessment]), [
            'title' => 'Updated',
            'description' => $assessment->description ?? '',
            'instructions' => $assessment->instructions ?? '',
            'passing_score' => 90,
            'status' => 'active',
        ]);

        $this->assertDatabaseEmpty('the404_assessment_attempts');
    }

    public function test_no_op_update_writes_no_audit_row(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin)->put(route('admin.courses.assessment.update', [$course, $assessment]), [
            'title' => $assessment->title,
            'description' => $assessment->description ?? '',
            'instructions' => $assessment->instructions ?? '',
            'passing_score' => $assessment->passing_score,
            'status' => $assessment->status,
        ])->assertRedirect(route('admin.courses.assessment', $course));

        $this->assertDatabaseMissing('the404_admin_audit', [
            'target_type' => 'assessment',
            'target_id' => $assessment->id,
        ]);
    }

    public function test_passing_score_must_be_a_non_negative_integer(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);
        $originalPassingScore = $assessment->passing_score;

        $this->actingAs($admin)->put(route('admin.courses.assessment.update', [$course, $assessment]), [
            'title' => $assessment->title,
            'description' => $assessment->description ?? '',
            'instructions' => $assessment->instructions ?? '',
            'passing_score' => -1,
            'status' => $assessment->status,
        ])->assertSessionHasErrors('passing_score');

        $this->assertSame($originalPassingScore, $assessment->refresh()->passing_score);
    }

    public function test_status_must_be_one_of_the_enum_values(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create(['course_id' => $course->id, 'status' => 'active']);

        $this->actingAs($admin)->put(route('admin.courses.assessment.update', [$course, $assessment]), [
            'title' => $assessment->title,
            'description' => $assessment->description ?? '',
            'instructions' => $assessment->instructions ?? '',
            'passing_score' => $assessment->passing_score,
            'status' => 'INVALID',
        ])->assertSessionHasErrors('status');

        $this->assertSame('active', $assessment->refresh()->status);
    }

    public function test_update_404s_for_an_assessment_of_a_different_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $other = Course::factory()->create();
        $foreign = Assessment::factory()->create(['course_id' => $other->id, 'title' => 'Foreign Boss']);

        $this->actingAs($admin)->put(route('admin.courses.assessment.update', [$course, $foreign]), [
            'title' => 'Hijacked',
            'description' => '',
            'instructions' => '',
            'passing_score' => 50,
            'status' => 'active',
        ])->assertNotFound();

        $this->assertSame('Foreign Boss', $foreign->refresh()->title);
        $this->assertSame(0, AdminAudit::count(), 'a 404 must not write an audit row');
    }

    public function test_update_refuses_cross_course_assessment_at_the_service_layer(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $other = Course::factory()->create();
        $foreign = Assessment::factory()->create(['course_id' => $other->id, 'title' => 'Foreign Boss']);

        try {
            app(AdminAssessmentService::class)->update($admin, $course, $foreign, [
                'title' => 'Hijacked',
                'description' => null,
                'instructions' => null,
                'passing_score' => 50,
                'status' => 'active',
            ]);
            $this->fail('a cross-course update must throw at the service layer');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('This assessment does not belong to the given course.', $e->getMessage());
        }

        $this->assertSame('Foreign Boss', $foreign->refresh()->title);

        $this->assertDatabaseHas('the404_admin_audit', [
            'action' => 'assessment.update',
            'target_type' => 'assessment',
            'target_id' => $foreign->id,
            'result' => 'failed',
            'summary' => 'Refused: This assessment does not belong to the given course.',
        ]);
    }

    public function test_non_admin_roles_are_forbidden_from_the_assessment_update_path(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->actingAs($teacher)->put(route('admin.courses.assessment.update', [$course, $assessment]), [
            'title' => 'Hijacked',
            'description' => '',
            'instructions' => '',
            'passing_score' => 50,
            'status' => 'active',
        ])->assertForbidden();

        $this->assertNotSame('Hijacked', $assessment->refresh()->title);
        $this->assertSame(0, AdminAudit::count(), 'a 403 must not write an audit row');
    }
}
