<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class RecommendationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_recommendations_page_requires_authentication(): void
    {
        $this->get(route('recommendations'))->assertRedirect(route('login'));
    }

    public function test_the_recommendations_page_shows_an_empty_state_for_a_fresh_student(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'student']))
            ->get(route('recommendations'))
            ->assertOk()
            ->assertSee('NO RECOMMENDATIONS');
    }

    public function test_the_page_suggests_the_current_unfinished_mission(): void
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
            ->get(route('recommendations'))
            ->assertOk()
            ->assertSee('PRIORITY 1')
            ->assertSee('Continue Learning')
            ->assertSee('Rusty Lockbox')
            ->assertSee(route('mission.show', $mission));
    }

    public function test_the_page_suggests_a_review_for_a_completed_struggled_mission(): void
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
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $mission);
        $this->pass($user, $assessment);
        $this->wrongSubmission($user, $mission);
        $this->wrongSubmission($user, $mission);

        $this->actingAs($user)
            ->get(route('recommendations'))
            ->assertOk()
            ->assertSee('Review')
            ->assertSee('Hostile Takeover')
            ->assertSee(route('mission.show', $mission));
    }

    public function test_recommendations_are_scoped_to_the_authenticated_student(): void
    {
        $viewer = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $course = $this->course('HTML');
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
            'title' => 'Someone Elses Struggle',
        ]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($viewer, $mission);
        $this->pass($viewer, $assessment);
        $this->complete($other, $mission);
        $this->pass($other, $assessment);
        $this->wrongSubmission($other, $mission);
        $this->wrongSubmission($other, $mission);

        $this->actingAs($viewer)
            ->get(route('recommendations'))
            ->assertOk()
            ->assertDontSee('Review')
            ->assertDontSee('Someone Elses Struggle');
    }

    public function test_recommendations_reject_user_scoping_parameters(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $param) {
            $this->actingAs($user)
                ->get(route('recommendations').'?'.$param.'=999')
                ->assertForbidden();
        }
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
