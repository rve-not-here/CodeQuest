<?php

namespace Tests\Feature;

use App\Http\Controllers\AssessmentController;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    private const GRADING_RULE = [
        ['type' => 'contains', 'value' => 'A', 'label' => 'has A'],
        ['type' => 'contains', 'value' => 'B', 'label' => 'has B'],
        ['type' => 'contains', 'value' => 'C', 'label' => 'has C'],
    ];

    /**
     * A course whose Boss Challenge is unlocked for the given user: one
     * mission, already completed, and an active assessment that passes on
     * 'A B C' (3/3 rules) and fails on less.
     *
     * @return array{course: Course, assessment: Assessment, mission: Mission}
     */
    private function makeUnlockedChallenge(User $user, array $assessmentAttrs = []): array
    {
        ['course' => $course, 'assessment' => $assessment, 'mission' => $mission] =
            $this->makeChallenge($assessmentAttrs);

        $this->completeMission($user, $mission);

        return compact('course', 'assessment', 'mission');
    }

    /**
     * A course whose Boss Challenge exists but is still sealed (the mission
     * is incomplete, so the student is not eligible).
     *
     * @return array{course: Course, assessment: Assessment}
     */
    private function makeSealedChallenge(array $assessmentAttrs = []): array
    {
        return $this->makeChallenge($assessmentAttrs);
    }

    /**
     * @return array{course: Course, assessment: Assessment, mission: Mission}
     */
    private function makeChallenge(array $assessmentAttrs = []): array
    {
        $course = Course::factory()->create(['type' => 'html']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        $assessment = Assessment::factory()->create(array_merge([
            'course_id' => $course->id,
            'status' => 'active',
            'grading_rule' => json_encode(self::GRADING_RULE),
            'passing_score' => 70,
        ], $assessmentAttrs));

        return compact('course', 'assessment', 'mission');
    }

    private function completeMission(User $user, Mission $mission): void
    {
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 0,
            'completed_at' => now(),
        ]);
    }

    public function test_assessment_index_requires_authentication(): void
    {
        $this->get(route('assessments'))->assertRedirect(route('login'));
    }

    public function test_assessment_show_requires_authentication(): void
    {
        ['assessment' => $assessment] = $this->makeSealedChallenge();

        $this->get(route('assessment.show', $assessment))->assertRedirect(route('login'));
    }

    public function test_assessment_with_missing_course_is_not_found(): void
    {
        $this->actingAs(User::factory()->create());
        $assessment = Assessment::factory()->make()->setRelation('course', null);

        $this->expectException(NotFoundHttpException::class);

        app(AssessmentController::class)->show($assessment);
    }

    public function test_assessment_start_requires_authentication(): void
    {
        ['assessment' => $assessment] = $this->makeSealedChallenge();

        $this->post(route('assessment.start', $assessment))->assertRedirect(route('login'));
    }

    public function test_assessment_submit_requires_authentication(): void
    {
        ['assessment' => $assessment] = $this->makeSealedChallenge();

        $this->post(route('assessment.submit', $assessment), ['code' => 'test'])->assertRedirect(route('login'));
    }

    public function test_assessment_retry_requires_authentication(): void
    {
        ['assessment' => $assessment] = $this->makeSealedChallenge();

        $this->post(route('assessment.retry', $assessment))->assertRedirect(route('login'));
    }

    public function test_assessment_index_lists_courses_in_order(): void
    {
        $user = User::factory()->create();
        $alpha = $this->makeSealedChallenge(); // order_num picked by factory
        $beta = $this->makeSealedChallenge();

        $this->actingAs($user)
            ->get(route('assessments'))
            ->assertOk()
            ->assertSee($alpha['course']->name)
            ->assertSee($beta['course']->name);
    }

    public function test_assessment_hub_queries_stay_bounded_as_courses_grow(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 2) as $index) {
            $this->hubCourse($index);
        }

        $small = $this->assessmentIndexQueries($user);

        foreach (range(3, 8) as $index) {
            $this->hubCourse($index);
        }

        $large = $this->assessmentIndexQueries($user);

        $this->assertLessThanOrEqual($small + 5, $large, "Assessment hub queries grew from {$small} to {$large} for 2 versus 8 courses.");
    }

    private function hubCourse(int $index): void
    {
        $course = Course::query()->create([
            'slug' => "hub-{$index}",
            'name' => "Hub Course {$index}",
            'type' => 'html',
            'status' => 'active',
            'order_num' => $index,
        ]);
        Mission::query()->create([
            'course_id' => $course->id,
            'order_num' => 1,
            'title' => "Hub Mission {$index}",
            'difficulty' => 'EASY',
            'points' => 50,
        ]);
        Assessment::query()->create([
            'course_id' => $course->id,
            'title' => "Hub Boss {$index}",
            'status' => 'active',
            'passing_score' => 70,
        ]);
    }

    private function assessmentIndexQueries(User $user): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $this->actingAs($user)->get(route('assessments'))->assertOk();

            return count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public function test_assessment_index_marks_a_progress_incomplete_challenge_as_sealed(): void
    {
        $user = User::factory()->create();
        $this->makeSealedChallenge();

        $this->actingAs($user)
            ->get(route('assessments'))
            ->assertOk()
            ->assertSee('SEALED')
            ->assertDontSee('INITIATE CHALLENGE');
    }

    public function test_assessment_index_marks_an_unlocked_challenge_as_ready(): void
    {
        $user = User::factory()->create();
        $this->makeUnlockedChallenge($user);

        $this->actingAs($user)
            ->get(route('assessments'))
            ->assertOk()
            ->assertSee('READY')
            ->assertSee('INITIATE CHALLENGE');
    }

    public function test_assessment_index_marks_an_in_progress_challenge(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        AssessmentAttempt::factory()->started()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('assessments'))
            ->assertOk()
            ->assertSee('IN PROGRESS')
            ->assertSee('CONTINUE CHALLENGE');
    }

    public function test_assessment_index_marks_a_passed_challenge_as_cleared(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'passed',
            'score' => 100,
            'passed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('assessments'))
            ->assertOk()
            ->assertSee('CLEARED');
    }

    public function test_assessment_show_redirects_when_challenge_is_sealed(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeSealedChallenge();

        $this->actingAs($user)
            ->get(route('assessment.show', $assessment))
            ->assertRedirect(route('assessments'))
            ->assertSessionHas('assessment_locked');
    }

    public function test_assessment_show_renders_briefing_and_initiate_when_unlocked(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user, [
            'description' => 'Restore the damaged broadcast.',
        ]);

        $this->actingAs($user)
            ->get(route('assessment.show', $assessment))
            ->assertOk()
            ->assertSee($assessment->title)
            ->assertSee('Restore the damaged broadcast.')
            ->assertSee('INITIATE CHALLENGE')
            ->assertDontSee('SUBMIT');
    }

    public function test_assessment_show_renders_terminal_when_attempt_in_progress(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        $attempt = AssessmentAttempt::factory()->started()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'code' => 'A B',
        ]);

        $this->actingAs($user)
            ->get(route('assessment.show', $assessment))
            ->assertOk()
            ->assertSee('RUN')
            ->assertSee('SUBMIT')
            ->assertSee('A B');
    }

    public function test_assessment_preview_grants_scripts_only_for_javascript_courses(): void
    {
        $student = User::factory()->create();

        foreach (['html', 'css', 'js'] as $type) {
            ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($student);
            $course->update(['type' => $type, 'order_num' => 1]);

            AssessmentAttempt::factory()->started()->create([
                'assessment_id' => $assessment->id,
                'user_id' => $student->id,
            ]);

            $response = $this->actingAs($student)
                ->get(route('assessment.show', $assessment))
                ->assertOk();

            if ($type === 'js') {
                $response->assertSee('sandbox="allow-scripts"', false);
            } else {
                $response->assertSee('sandbox=""', false)
                    ->assertDontSee('sandbox="allow-scripts"', false);
            }
        }
    }

    public function test_assessment_show_restores_in_progress_code(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        AssessmentAttempt::factory()->started()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'code' => 'A B C',
        ]);

        $this->actingAs($user)
            ->get(route('assessment.show', $assessment))
            ->assertOk()
            ->assertSee('A B C');
    }

    public function test_assessment_start_creates_a_started_attempt(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        $this->actingAs($user)
            ->post(route('assessment.start', $assessment))
            ->assertRedirect(route('assessment.show', $assessment));

        $this->assertDatabaseHas('the404_assessment_attempts', [
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'started',
        ]);
    }

    public function test_assessment_start_is_refused_while_sealed(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeSealedChallenge();

        $this->actingAs($user)
            ->post(route('assessment.start', $assessment))
            ->assertRedirect(route('assessments'))
            ->assertSessionHas('assessment_error');

        $this->assertDatabaseMissing('the404_assessment_attempts', [
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_assessment_submit_requires_code(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        $this->actingAs($user)
            ->post(route('assessment.submit', $assessment), [])
            ->assertSessionHasErrors('code');
    }

    public function test_started_attempt_cannot_be_submitted_after_its_course_is_sealed(): void
    {
        $student = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($student);

        $this->actingAs($student)->post(route('assessment.start', $assessment));
        $attempt = AssessmentAttempt::query()->sole();
        $course->update(['status' => 'locked']);

        $this->actingAs($student)
            ->post(route('assessment.submit', $assessment), [
                'code' => 'A B C',
                'score' => 100,
                'passed' => true,
                'xp' => 999999,
            ])
            ->assertRedirect(route('assessments'))
            ->assertSessionHas('assessment_error');

        $this->assertSame('started', $attempt->fresh()->status);
        $this->assertNull($attempt->fresh()->score);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    public function test_started_attempt_cannot_be_submitted_after_its_assessment_is_sealed(): void
    {
        $student = User::factory()->create();
        ['assessment' => $assessment] = $this->makeUnlockedChallenge($student);

        $this->actingAs($student)->post(route('assessment.start', $assessment));
        $attempt = AssessmentAttempt::query()->sole();
        $assessment->update(['status' => 'locked']);

        $this->actingAs($student)
            ->post(route('assessment.submit', $assessment), ['code' => 'A B C'])
            ->assertRedirect(route('assessments'))
            ->assertSessionHas('assessment_error');

        $this->assertSame('started', $attempt->fresh()->status);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    public function test_later_course_boss_challenge_stays_sealed_until_earlier_course_passes(): void
    {
        $student = User::factory()->create();
        ['course' => $earlierCourse] = $this->makeSealedChallenge();
        $earlierCourse->update(['order_num' => 1]);
        ['course' => $laterCourse, 'assessment' => $laterAssessment] = $this->makeUnlockedChallenge($student);
        $laterCourse->update(['order_num' => 2]);

        $this->actingAs($student)
            ->post(route('assessment.start', $laterAssessment))
            ->assertRedirect(route('assessments'))
            ->assertSessionHas('assessment_error');

        $this->assertDatabaseCount('the404_assessment_attempts', 0);
    }

    public function test_assessment_submit_correct_code_passes_and_awards_xp_once(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        $this->actingAs($user)->post(route('assessment.start', $assessment));

        $this->actingAs($user)
            ->post(route('assessment.submit', $assessment), ['code' => 'A B C'])
            ->assertRedirect(route('assessment.show', $assessment))
            ->assertSessionHas('assessment_success');

        $this->assertStringContainsString('+100 XP', session('assessment_success')['message']);

        $attempt = AssessmentAttempt::query()->first();
        $this->assertSame('passed', $attempt->status);
        $this->assertSame(100, $attempt->score);

        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'mission_id' => null,
            'amount' => 100,
            'type' => XpService::TYPE_ASSESSMENT_COMPLETED,
        ]);
    }

    public function test_assessment_submit_wrong_code_fails_without_xp(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        $this->actingAs($user)->post(route('assessment.start', $assessment));

        $this->actingAs($user)
            ->post(route('assessment.submit', $assessment), ['code' => 'A B'])
            ->assertRedirect(route('assessment.show', $assessment))
            ->assertSessionHas('assessment_error');

        $attempt = AssessmentAttempt::query()->first();
        $this->assertSame('failed', $attempt->status);
        $this->assertSame(67, $attempt->score);

        $this->assertDatabaseMissing('the404_xp_transactions', [
            'user_id' => $user->id,
            'type' => XpService::TYPE_ASSESSMENT_COMPLETED,
        ]);
    }

    public function test_assessment_submit_without_an_attempt_is_refused(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        $this->actingAs($user)
            ->post(route('assessment.submit', $assessment), ['code' => 'A B C'])
            ->assertRedirect(route('assessment.show', $assessment))
            ->assertSessionHas('assessment_error');

        $this->assertDatabaseMissing('the404_assessment_attempts', [
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_assessment_submit_refuses_a_second_submission_without_retry(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        $this->actingAs($user)->post(route('assessment.start', $assessment));
        $this->actingAs($user)->post(route('assessment.submit', $assessment), ['code' => 'A B C']);

        $this->actingAs($user)
            ->post(route('assessment.submit', $assessment), ['code' => 'A B C'])
            ->assertRedirect(route('assessment.show', $assessment))
            ->assertSessionHas('assessment_error');

        $this->assertSame(1, AssessmentAttempt::query()->count());
    }

    public function test_assessment_retry_after_failure_opens_a_fresh_attempt(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        $this->actingAs($user)->post(route('assessment.start', $assessment));
        $this->actingAs($user)->post(route('assessment.submit', $assessment), ['code' => 'A B']);

        $this->actingAs($user)
            ->post(route('assessment.retry', $assessment))
            ->assertRedirect(route('assessment.show', $assessment))
            ->assertSessionHas('assessment_info');

        $rows = AssessmentAttempt::query()->orderBy('id')->get();
        $this->assertSame(2, $rows->count());
        $this->assertSame('failed', $rows[0]->status);
        $this->assertSame('started', $rows[1]->status);
        $this->assertNull($rows[1]->code);
    }

    public function test_assessment_retry_after_pass_does_not_re_award_xp(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        $this->actingAs($user)->post(route('assessment.start', $assessment));
        $this->actingAs($user)->post(route('assessment.submit', $assessment), ['code' => 'A B C']);
        $this->actingAs($user)->post(route('assessment.retry', $assessment));
        $this->actingAs($user)
            ->post(route('assessment.submit', $assessment), ['code' => 'A B C'])
            ->assertRedirect(route('assessment.show', $assessment))
            ->assertSessionHas('assessment_success');

        $this->assertStringContainsString('no additional XP', session('assessment_success')['message']);

        $this->assertSame(100, (int) XpTransaction::query()->where('user_id', $user->id)->sum('amount'));
        $this->assertSame(1, XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_ASSESSMENT_COMPLETED)
            ->count());
    }

    public function test_assessment_retry_unavailable_while_an_attempt_is_in_progress(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        $this->actingAs($user)->post(route('assessment.start', $assessment));

        $this->actingAs($user)
            ->post(route('assessment.retry', $assessment))
            ->assertRedirect(route('assessment.show', $assessment))
            ->assertSessionHas('assessment_error');

        $this->assertSame(1, AssessmentAttempt::query()->count());
    }

    public function test_assessment_show_never_exposes_the_grading_rule(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user, [
            'grading_rule' => json_encode([
                ['type' => 'contains', 'value' => 'SECRET_PATTERN', 'label' => 'secret'],
            ]),
        ]);

        AssessmentAttempt::factory()->started()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('assessment.show', $assessment))
            ->assertOk()
            ->assertDontSee('SECRET_PATTERN');
    }

    public function test_assessment_show_does_not_leak_another_students_code(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment, 'mission' => $mission] = $this->makeChallenge();
        $this->completeMission($alice, $mission);
        $this->completeMission($bob, $mission);

        AssessmentAttempt::factory()->started()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $alice->id,
            'code' => 'TOP_SECRET_420',
        ]);

        $this->actingAs($bob)
            ->get(route('assessment.show', $assessment))
            ->assertOk()
            ->assertSee('INITIATE CHALLENGE')
            ->assertDontSee('TOP_SECRET_420');
    }

    public function test_assessment_submit_is_scoped_to_the_authenticated_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment, 'mission' => $mission] = $this->makeChallenge();
        $this->completeMission($alice, $mission);
        $this->completeMission($bob, $mission);

        $this->actingAs($alice)->post(route('assessment.start', $assessment));
        $this->actingAs($alice)->post(route('assessment.submit', $assessment), ['code' => 'A B C']);

        $attemptId = AssessmentAttempt::query()->where('user_id', $alice->id)->value('id');

        $this->actingAs($bob)
            ->post(route('assessment.submit', $assessment), ['code' => 'A B C'])
            ->assertRedirect(route('assessment.show', $assessment))
            ->assertSessionHas('assessment_error');

        $this->assertDatabaseMissing('the404_assessment_attempts', [
            'user_id' => $bob->id,
        ]);
        $this->assertSame(100, (int) XpTransaction::query()->where('user_id', $alice->id)->sum('amount'));
        $this->assertSame('A B C', AssessmentAttempt::query()->find($attemptId)->code);
    }

    public function test_assessment_index_still_shows_cleared_after_a_failed_retry(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'assessment' => $assessment] = $this->makeUnlockedChallenge($user);

        $this->actingAs($user)->post(route('assessment.start', $assessment));
        $this->actingAs($user)->post(route('assessment.submit', $assessment), ['code' => 'A B C']);
        $this->actingAs($user)->post(route('assessment.retry', $assessment));
        $this->actingAs($user)->post(route('assessment.submit', $assessment), ['code' => 'A B']);

        $this->actingAs($user)
            ->get(route('assessments'))
            ->assertOk()
            ->assertSee('CLEARED')
            ->assertSee('your pass stands');
    }
}
