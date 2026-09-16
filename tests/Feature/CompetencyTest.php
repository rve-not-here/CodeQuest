<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\AssessmentService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    public function test_competency_requires_authentication(): void
    {
        $this->get(route('competency'))->assertRedirect(route('login'));
    }

    public function test_competency_shows_an_empty_state_when_no_active_courses(): void
    {
        $user = User::factory()->create();
        Course::factory()->create(['status' => 'active', 'order_num' => 1, 'name' => 'Empty Area']);

        $this->actingAs($user)
            ->get(route('competency'))
            ->assertOk()
            ->assertSee('NO COMPETENCIES')
            ->assertDontSee('Empty Area');
    }

    public function test_competency_lists_untouched_courses_as_not_started(): void
    {
        $user = User::factory()->create();
        $this->createCourseWithMissions(1, 'html', 3);

        $this->actingAs($user)
            ->get(route('competency'))
            ->assertOk()
            ->assertSee('HTML')
            ->assertSee('NOT STARTED')
            ->assertSee('0/3');
    }

    public function test_competency_shows_developing_for_the_first_completed_mission(): void
    {
        $user = User::factory()->create();
        [, $missions] = $this->createCourseWithMissions(1, 'html', 3);

        $this->complete($user, $missions[0]);

        $this->actingAs($user)
            ->get(route('competency'))
            ->assertOk()
            ->assertSee('DEVELOPING')
            ->assertSee('1/3')
            ->assertSee('Work in progress');
    }

    public function test_competency_shows_practicing_once_half_the_missions_are_done(): void
    {
        $user = User::factory()->create();
        [, $missions] = $this->createCourseWithMissions(1, 'html', 4);

        $this->complete($user, $missions[0]);
        $this->complete($user, $missions[1]);

        $this->actingAs($user)
            ->get(route('competency'))
            ->assertOk()
            ->assertSee('PRACTICING')
            ->assertSee('2/4')
            ->assertSee('Boss Challenge: NOT ATTEMPTED');
    }

    public function test_competency_shows_demonstrated_with_the_visible_reasoning(): void
    {
        $user = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, 'html', 2);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $missions[0]);
        $this->complete($user, $missions[1]);
        $this->attempt($user, $assessment, 'passed');

        $this->actingAs($user)
            ->get(route('competency'))
            ->assertOk()
            ->assertSee('DEMONSTRATED')
            ->assertSee('2/2')
            ->assertSee('Demonstrated through')
            ->assertSee('Boss Challenge: PASSED');
    }

    public function test_competency_does_not_render_a_zero_mission_course(): void
    {
        $user = User::factory()->create();
        Course::factory()->create(['status' => 'active', 'order_num' => 1, 'name' => 'Empty Area']);

        $this->actingAs($user)
            ->get(route('competency'))
            ->assertOk()
            ->assertDontSee('Empty Area')
            ->assertDontSee('NOT STARTED');
    }

    public function test_competency_excludes_locked_and_draft_courses(): void
    {
        $user = User::factory()->create();
        $this->createCourseWithMissions(1, 'html', status: 'locked');
        $this->createCourseWithMissions(2, 'js', status: 'draft');

        $this->actingAs($user)
            ->get(route('competency'))
            ->assertOk()
            ->assertSee('NO COMPETENCIES');
    }

    public function test_demonstrated_competency_survives_a_failed_retry_on_the_page(): void
    {
        $user = User::factory()->create();
        [$course, $missions] = $this->createCourseWithMissions(1, 'html', 1);
        Assessment::factory()->create([
            'course_id' => $course->id,
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'ok', 'label' => 'Token']]),
            'passing_score' => 70,
        ]);
        $this->complete($user, $missions[0]);

        $assessments = app(AssessmentService::class);

        $attempt = $assessments->beginAttempt($user, $course);
        $assessments->submitAttempt($user, $attempt, 'restore ok');
        $assessments->evaluateAttempt($user, $attempt);

        $retry = $assessments->retryAttempt($user, $course);
        $assessments->submitAttempt($user, $retry, 'broken');
        $assessments->evaluateAttempt($user, $retry);

        $this->actingAs($user)
            ->get(route('competency'))
            ->assertOk()
            ->assertSee('DEMONSTRATED')
            ->assertDontSee('PRACTICING');
    }

    public function test_competency_rejects_user_scoping_parameters(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $param) {
            $this->actingAs($user)
                ->get(route('competency').'?'.$param.'=999')
                ->assertForbidden();
        }
    }

    /**
     * @return array{0: Course, 1: array<int, Mission>}
     */
    private function createCourseWithMissions(int $orderNum, string $type, int $missionCount = 1, string $status = 'active'): array
    {
        $course = Course::factory()->create(['status' => $status, 'type' => $type, 'order_num' => $orderNum]);
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1]);

        $missions = [];
        foreach (range(1, $missionCount) as $order) {
            $missions[] = Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $order,
            ]);
        }

        return [$course, $missions];
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

    private function attempt(User $user, Assessment $assessment, string $status): void
    {
        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => $status,
            'passed_at' => $status === 'passed' ? now() : null,
        ]);
    }
}
