<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\KnowledgeCheckService;
use App\Services\PerformanceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The student Performance Summary is one register for the three result
 * types. Its figures are pinned to the authoritative services:
 * CourseProgressService (challenges), KnowledgeCheckService (submitted
 * checks), and AssessmentService (attempt history, never the latest verdict).
 */
class PerformanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_summary_requires_authentication(): void
    {
        $this->get(route('performance'))->assertRedirect(route('login'));
    }

    public function test_the_summary_rejects_attempts_to_name_another_student(): void
    {
        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $parameter) {
            $this->actingAs(User::factory()->create(['role' => 'student']))
                ->get(route('performance', [$parameter => 1]))
                ->assertForbidden();
        }
    }

    public function test_all_three_result_types_appear_in_one_register(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->courseWithMissions('Register One', 2);

        $this->complete($student, $course->missions()->orderBy('order_num')->firstOrFail());

        $check = KnowledgeCheck::factory()->create([
            'mission_id' => $course->missions()->orderBy('order_num')->firstOrFail()->id,
        ]);
        KnowledgeCheckAttempt::factory()->submitted(score: 3, total: 4)->create([
            'knowledge_check_id' => $check->id,
            'user_id' => $student->id,
        ]);

        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'passing_score' => 70,
        ]);
        $this->attempt($student, $assessment, 'passed', 80);

        $this->actingAs($student)
            ->get(route('performance'))
            ->assertOk()
            ->assertSee('Performance summary')
            ->assertSee('1/2 complete')
            ->assertSee('1/1 submitted')
            ->assertSee('3/4 correct')
            ->assertSee('Passed')
            ->assertSee('80%')
            ->assertSee('Boss attempts')
            ->assertSee('1');
    }

    public function test_a_failed_retry_never_erases_a_pass_or_the_best_score(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->courseWithMissions('Register Two', 1);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'passing_score' => 70,
        ]);

        $this->attempt($student, $assessment, 'passed', 100);
        $this->attempt($student, $assessment, 'failed', 40);

        $rows = app(PerformanceSummaryService::class)->forStudent($student);
        $boss = $rows->firstWhere('course.id', $course->id)['boss'];

        $this->assertTrue($boss['passed']);
        $this->assertSame(100, $boss['best']);
        $this->assertSame(2, $boss['attempts']);
        $this->assertSame('failed', $boss['latest']);
    }

    public function test_the_latest_submitted_knowledge_check_attempt_supplies_the_denominator(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->courseWithMissions('Register Three', 1);
        $check = KnowledgeCheck::factory()->create([
            'mission_id' => $course->missions()->firstOrFail()->id,
        ]);

        // An earlier 1/4 and a later 4/4: retries replace evidence, never stack.
        KnowledgeCheckAttempt::factory()->submitted(score: 1, total: 4)->create([
            'knowledge_check_id' => $check->id,
            'user_id' => $student->id,
            'attempt_number' => 1,
        ]);
        KnowledgeCheckAttempt::factory()->submitted(score: 4, total: 4)->create([
            'knowledge_check_id' => $check->id,
            'user_id' => $student->id,
            'attempt_number' => 2,
        ]);

        $register = app(KnowledgeCheckService::class)
            ->courseRegister($student, collect([$course->id]))
            ->get($course->id);

        $this->assertSame(1, $register['submitted']);
        $this->assertSame(4, $register['correct']);
        $this->assertSame(4, $register['questions']);
    }

    public function test_another_students_evidence_is_not_counted(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $course = $this->courseWithMissions('Register Four', 1);
        $check = KnowledgeCheck::factory()->create([
            'mission_id' => $course->missions()->firstOrFail()->id,
        ]);

        KnowledgeCheckAttempt::factory()->submitted(score: 1, total: 1)->create([
            'knowledge_check_id' => $check->id,
            'user_id' => $other->id,
        ]);

        $register = app(KnowledgeCheckService::class)
            ->courseRegister($student, collect([$course->id]))
            ->get($course->id);

        $this->assertSame(0, $register['submitted']);
        $this->assertSame(0, $register['correct']);
    }

    private function courseWithMissions(string $name, int $missionCount): Course
    {
        $course = Course::factory()->create([
            'name' => $name,
            'status' => 'active',
            'order_num' => 1,
        ]);
        $section = Section::factory()->create(['course_id' => $course->id]);

        foreach (range(1, $missionCount) as $order) {
            Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $order,
            ]);
        }

        return $course;
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

    private function attempt(User $user, Assessment $assessment, string $status, int $score): void
    {
        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => $status,
            'score' => $score,
            'passed_at' => $status === 'passed' ? now() : null,
            'submitted_at' => now(),
        ]);
    }
}
