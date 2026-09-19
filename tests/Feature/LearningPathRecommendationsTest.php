<?php

namespace Tests\Feature;

use App\Http\Controllers\LearningPathController;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * US-908 Learning Path Integration. Recommendation cards render inside the
 * student's own learning-path page, composed display-only from the existing
 * RecommendationService output. Rendering a link never grants access: every
 * href targets an existing route that enforces its own authorization.
 */
class LearningPathRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_learning_path_shows_the_current_unfinished_mission_recommendation(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $course = $this->course('HTML');
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'title' => 'Rusty Lockbox',
        ]);

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('RECOMMENDED NEXT ACTIONS')
            ->assertSee('PRIORITY 1')
            ->assertSee('Continue Learning')
            ->assertSee('Rusty Lockbox')
            ->assertSee(route('mission.show', $mission));
    }

    public function test_learning_path_recommendation_carries_the_reason_and_no_internals(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $course = $this->course('HTML');
        $section = Section::factory()->create(['course_id' => $course->id]);
        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'title' => 'Rusty Lockbox',
        ]);

        $content = $this->actingAs($user)->get(route('learning-path'))->assertOk()->getContent();

        $this->assertStringContainsString('Rusty Lockbox', $content);
        $this->assertStringNotContainsString('grading_rule', $content);
        $this->assertStringNotContainsString('validate_rule', $content);
        $this->assertStringNotContainsString('solution_code', $content);
        $this->assertStringNotContainsString('is_correct', $content);
    }

    public function test_learning_path_never_renders_another_students_recommendations(): void
    {
        $viewer = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $course = $this->course('HTML');
        $section = Section::factory()->create(['course_id' => $course->id]);
        $shared = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'title' => 'Shared Struggle',
        ]);
        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 2,
            'title' => 'Viewers Next',
        ]);
        Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($viewer, $shared);
        $this->complete($other, $shared);
        $this->wrongSubmission($other, $shared);
        $this->wrongSubmission($other, $shared);

        $content = $this->actingAs($viewer)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('RECOMMENDED NEXT ACTIONS')
            ->assertSee('Viewers Next')
            ->getContent();

        // The viewer earns exactly one card (their own unfinished mission).
        // The other student's review card for the shared mission must not
        // render: PRIORITY badges appear only inside recommendation cards.
        $this->assertSame(1, substr_count($content, 'PRIORITY '));
    }

    public function test_recommendation_into_a_sealed_course_shows_no_actionable_link(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $course = $this->course('HTML');
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'title' => 'Hostile Takeover',
        ]);
        Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $mission);
        $this->wrongSubmission($user, $mission);
        $this->wrongSubmission($user, $mission);

        $course->status = 'locked';
        $course->save();

        $content = $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('RECOMMENDED NEXT ACTIONS')
            ->assertSee('Hostile Takeover')
            ->assertSee('LOCKED')
            ->assertSee('Course access is sealed.')
            ->assertDontSee(route('mission.show', $mission))
            ->getContent();

        $this->assertStringNotContainsString(
            'href="'.route('mission.show', $mission).'"',
            $content,
            'A sealed target must not render as a link anywhere on the page.'
        );

        $this->actingAs($user)
            ->get(route('mission.show', $mission))
            ->assertForbidden();
    }

    public function test_recommendation_into_a_locked_boss_challenge_shows_no_actionable_link(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $course = $this->course('HTML');
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'title' => 'Cleared Run',
        ]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $mission);

        $assessment->status = 'locked';
        $assessment->save();

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('RECOMMENDED NEXT ACTIONS')
            ->assertSee('Boss Challenge')
            ->assertSee('LOCKED')
            ->assertDontSee(route('assessment.show', $assessment));
    }

    public function test_recommendation_into_an_unlocked_boss_challenge_keeps_its_link(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $course = $this->course('HTML');
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'title' => 'Cleared Run',
        ]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $mission);

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('START CHALLENGE')
            ->assertSee(route('assessment.show', $assessment));

        $this->actingAs($user)
            ->get(route('assessment.show', $assessment))
            ->assertOk();
    }

    public function test_recommendation_cards_render_without_duplicated_authorization_logic(): void
    {
        $component = file_get_contents(resource_path('views/components/recommendation-cards.blade.php'));

        foreach (['isUnlocked', 'hasPassed', 'ensureCourseActive'] as $formula) {
            $this->assertStringNotContainsString($formula, $component);
        }

        // The new annotation reads already-computed tree/boss state; it must
        // not call the domain predicates itself (bossNode's pre-existing
        // service calls are the single home for those rules).
        $method = new ReflectionMethod(LearningPathController::class, 'accessibleRecommendations');
        $lines = file(app_path('Http/Controllers/LearningPathController.php'));
        $source = implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));

        foreach (['isUnlocked', 'hasPassed', 'ensureCourseActive'] as $formula) {
            $this->assertStringNotContainsString($formula, $source);
        }
    }

    public function test_learning_path_renders_cleanly_with_no_recommendations(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $course = $this->course('HTML');
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'title' => 'Cleared Signal',
        ]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $mission);
        $this->pass($user, $assessment);

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertDontSee('RECOMMENDED NEXT ACTIONS')
            ->assertSee('Cleared Signal')
            ->assertSee('BOSS PASSED');
    }

    public function test_learning_path_states_survive_the_recommendation_panel(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $course = $this->course('HTML');
        $section = Section::factory()->create(['course_id' => $course->id]);
        $done = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'title' => 'Done Deal',
        ]);
        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 2,
            'title' => 'Next Up',
        ]);

        $this->complete($user, $done);

        $this->actingAs($user)
            ->get(route('learning-path'))
            ->assertOk()
            ->assertSee('CURRENT SIGNAL')
            ->assertSee('Next Up')
            ->assertSee('Validated and recorded.');
    }

    private function course(string $name): Course
    {
        return Course::factory()->create([
            'name' => $name,
            'status' => 'active',
            'order_num' => 1,
        ]);
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

    private function pass(User $user, Assessment $assessment): void
    {
        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'passed',
            'passed_at' => now(),
            'submitted_at' => now(),
        ]);
    }

    private function wrongSubmission(User $user, Mission $mission): void
    {
        DB::table('the404_xp_transactions')->insert([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => -10,
            'type' => XpService::TYPE_WRONG_SUBMISSION,
            'description' => 'Wrong submission on mission: '.$mission->title,
            'created_at' => now()->toDateTimeString(),
        ]);
    }
}
