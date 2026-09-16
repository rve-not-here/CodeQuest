<?php

namespace Tests\Unit;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\RecommendationService;
use App\Services\XpService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RecommendationService::class);
    }

    public function test_recommendations_is_empty_when_there_are_no_active_courses(): void
    {
        $this->assertTrue($this->service->recommendations(User::factory()->create())->isEmpty());
    }

    public function test_slot_1_suggests_the_current_unfinished_mission(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $missions = $this->missions($course, 2);

        $this->complete($user, $missions[0]);

        [$card] = $this->service->recommendations($user)->all();

        $this->assertSame(1, $card['slot']);
        $this->assertSame('Continue Learning', $card['title']);
        $this->assertStringContainsString($missions[1]->title, $card['subtitle']);
        $this->assertSame(route('mission.show', $missions[1]), $card['href']);
    }

    public function test_slot_1_fires_for_an_untouched_course_when_nothing_was_completed_yet(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $first = $this->missions($course, 1)[0];

        [$card] = $this->service->recommendations($user)->all();

        $this->assertSame(1, $card['slot']);
        $this->assertSame('Continue Learning', $card['title']);
        $this->assertSame(route('mission.show', $first), $card['href']);
    }

    public function test_slot_4_is_used_when_the_previous_course_is_completed_and_the_next_is_untouched(): void
    {
        $user = User::factory()->create();
        $completed = $this->course('HTML', 1);
        $next = $this->course('CSS', 2);

        $this->complete($user, $this->missions($completed, 1)[0]);
        $this->passChallenge($user, $completed);
        $this->missions($next, 1);

        [$card] = $this->service->recommendations($user)->all();

        $this->assertSame(4, $card['slot']);
        $this->assertSame('Next Course', $card['title']);
        $this->assertStringContainsString($next->name, $card['subtitle']);
        $this->assertSame(route('learning-path'), $card['href']);
    }

    public function test_slot_1_takes_over_once_the_student_starts_the_next_course(): void
    {
        $user = User::factory()->create();
        $completed = $this->course('HTML', 1);
        $next = $this->course('CSS', 2);

        $this->complete($user, $this->missions($completed, 1)[0]);
        $this->passChallenge($user, $completed);

        $nextMissions = $this->missions($next, 2);
        $this->complete($user, $nextMissions[0]);

        [$card] = $this->service->recommendations($user)->all();

        $this->assertSame(1, $card['slot']);
        $this->assertSame(route('mission.show', $nextMissions[1]), $card['href']);
    }

    public function test_slot_2_is_used_when_every_mission_is_done_and_the_challenge_is_outstanding(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $missions = $this->missions($course, 2);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $missions[0]);
        $this->complete($user, $missions[1]);

        [$card] = $this->service->recommendations($user)->all();

        $this->assertSame(2, $card['slot']);
        $this->assertSame('Boss Challenge', $card['title']);
        $this->assertSame(route('assessment.show', $assessment), $card['href']);
    }

    public function test_slot_2_falls_back_to_the_learning_surface_when_the_course_has_no_assessment(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $missions = $this->missions($course, 1);

        $this->complete($user, $missions[0]);

        [$card] = $this->service->recommendations($user)->all();

        $this->assertSame(2, $card['slot']);
        $this->assertSame(route('learning-path'), $card['href']);
    }

    public function test_slot_3_lists_a_completed_mission_with_two_wrong_submissions_in_the_window(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $mission = $this->missions($course, 1)[0];

        $this->complete($user, $mission);
        $this->passChallenge($user, $course);
        $this->wrongSubmission($user, $mission, Carbon::now()->subDays(2));
        $this->wrongSubmission($user, $mission, Carbon::now()->subDay());

        [$card] = $this->service->recommendations($user)->all();

        $this->assertSame(3, $card['slot']);
        $this->assertSame('Review', $card['title']);
        $this->assertStringContainsString($mission->title, $card['subtitle']);
        $this->assertSame(route('mission.show', $mission), $card['href']);
    }

    public function test_slot_3_does_not_list_a_mission_with_only_one_wrong_submission(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $mission = $this->missions($course, 1)[0];

        $this->complete($user, $mission);
        $this->passChallenge($user, $course);
        $this->wrongSubmission($user, $mission);

        $this->assertTrue($this->service->recommendations($user)->isEmpty());
    }

    public function test_slot_3_includes_wrong_submissions_just_inside_the_14_day_window(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $mission = $this->missions($course, 1)[0];

        $this->complete($user, $mission);
        $this->passChallenge($user, $course);
        $this->wrongSubmission($user, $mission, Carbon::now()->subDays(14)->addSecond());
        $this->wrongSubmission($user, $mission, Carbon::now()->subDays(14)->addSecond());

        [$card] = $this->service->recommendations($user)->all();

        $this->assertSame(3, $card['slot']);
    }

    public function test_slot_3_excludes_wrong_submissions_just_outside_the_14_day_window(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $mission = $this->missions($course, 1)[0];

        $this->complete($user, $mission);
        $this->passChallenge($user, $course);
        $this->wrongSubmission($user, $mission, Carbon::now()->subDays(14)->subSecond());
        $this->wrongSubmission($user, $mission, Carbon::now()->subDays(14)->subSecond());

        $this->assertTrue($this->service->recommendations($user)->isEmpty());
    }

    public function test_slot_3_excludes_in_progress_missions_under_restriction_c(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $missions = $this->missions($course, 2);

        $this->complete($user, $missions[0]);
        $this->passChallenge($user, $course);
        $this->wrongSubmission($user, $missions[0], Carbon::now()->subDay());
        $this->wrongSubmission($user, $missions[0], Carbon::now());
        $this->wrongSubmission($user, $missions[1], Carbon::now()->subDays(2));
        $this->wrongSubmission($user, $missions[1], Carbon::now()->subDay());

        [$card] = $this->service->recommendations($user)->all();

        $this->assertSame(3, $card['slot']);
        $this->assertStringContainsString($missions[0]->title, $card['subtitle']);
        $this->assertStringNotContainsString($missions[1]->title, $card['subtitle']);
    }

    public function test_review_queue_orders_by_most_recent_wrong_submission(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $missions = $this->missions($course, 2);

        $this->complete($user, $missions[0]);
        $this->complete($user, $missions[1]);
        $this->passChallenge($user, $course);
        $this->wrongSubmission($user, $missions[0], Carbon::now()->subDays(3));
        $this->wrongSubmission($user, $missions[0], Carbon::now()->subDays(3)->addMinute());
        $this->wrongSubmission($user, $missions[1], Carbon::now()->subDay());
        $this->wrongSubmission($user, $missions[1], Carbon::now());

        [$first, $second] = $this->service->recommendations($user)->all();

        $this->assertStringContainsString($missions[1]->title, $first['subtitle']);
        $this->assertStringContainsString($missions[0]->title, $second['subtitle']);
    }

    public function test_review_queue_is_limited_to_three_missions(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $missions = $this->missions($course, 4);

        foreach ($missions as $mission) {
            $this->complete($user, $mission);
        }

        $this->passChallenge($user, $course);

        foreach (range(0, 3) as $index) {
            $at = Carbon::now()->subDays($index + 1);
            $this->wrongSubmission($user, $missions[$index], $at);
            $this->wrongSubmission($user, $missions[$index], $at->addMinute());
        }

        $cards = $this->service->recommendations($user)->all();

        $this->assertCount(3, $cards);
        $this->assertStringContainsString($missions[0]->title, $cards[0]['subtitle']);
        $this->assertStringContainsString($missions[1]->title, $cards[1]['subtitle']);
        $this->assertStringContainsString($missions[2]->title, $cards[2]['subtitle']);
        $this->assertStringNotContainsString($missions[3]->title, collect($cards)->pluck('subtitle')->implode(' '));
    }

    public function test_priority_slot_1_renders_before_review_slot_3(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $missions = $this->missions($course, 2);

        $this->complete($user, $missions[0]);
        $this->wrongSubmission($user, $missions[0], Carbon::now()->subDay());
        $this->wrongSubmission($user, $missions[0], Carbon::now());

        $cards = $this->service->recommendations($user)->all();

        $this->assertSame([1, 3], collect($cards)->pluck('slot')->all());
        $this->assertStringContainsString($missions[1]->title, $cards[0]['subtitle']);
        $this->assertStringContainsString($missions[0]->title, $cards[1]['subtitle']);
    }

    public function test_priority_slot_2_renders_before_review_slot_3(): void
    {
        $user = User::factory()->create();
        $course = $this->course('HTML', 1);
        $missions = $this->missions($course, 1);

        $this->complete($user, $missions[0]);
        Assessment::factory()->create(['course_id' => $course->id]);
        $this->wrongSubmission($user, $missions[0], Carbon::now()->subDay());
        $this->wrongSubmission($user, $missions[0], Carbon::now());

        $cards = $this->service->recommendations($user)->all();

        $this->assertSame([2, 3], collect($cards)->pluck('slot')->all());
    }

    public function test_priority_review_renders_before_slot_4(): void
    {
        $user = User::factory()->create();
        $completed = $this->course('HTML', 1);
        $next = $this->course('CSS', 2);

        $mission = $this->missions($completed, 1)[0];
        $this->complete($user, $mission);
        $this->passChallenge($user, $completed);
        $this->wrongSubmission($user, $mission, Carbon::now()->subDay());
        $this->wrongSubmission($user, $mission, Carbon::now());
        $this->missions($next, 1);

        $cards = $this->service->recommendations($user)->all();

        $this->assertSame([3, 4], collect($cards)->pluck('slot')->all());
        $this->assertSame('Review', $cards[0]['title']);
        $this->assertSame('Next Course', $cards[1]['title']);
        $this->assertStringContainsString($next->name, $cards[1]['subtitle']);
    }

    private function course(string $name, int $order): Course
    {
        return Course::factory()->create([
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => 'active',
            'order_num' => $order,
        ]);
    }

    /**
     * @return array<int, Mission>
     */
    private function missions(Course $course, int $count): array
    {
        $section = Section::factory()->create(['course_id' => $course->id]);
        $missions = [];

        for ($order = 1; $order <= $count; $order++) {
            $missions[] = Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $order,
                'title' => $course->name.' Mission '.$order,
            ]);
        }

        return $missions;
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

    private function passChallenge(User $user, Course $course): void
    {
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'passed',
            'passed_at' => now(),
            'submitted_at' => now(),
        ]);
    }

    private function wrongSubmission(User $user, Mission $mission, ?Carbon $at = null): void
    {
        DB::table('the404_xp_transactions')->insert([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => -10,
            'type' => XpService::TYPE_WRONG_SUBMISSION,
            'description' => 'Wrong submission on mission: '.$mission->title,
            'created_at' => ($at ?? Carbon::now())->toDateTimeString(),
        ]);
    }
}
