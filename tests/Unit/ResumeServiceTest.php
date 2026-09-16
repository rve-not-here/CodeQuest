<?php

namespace Tests\Unit;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\ResumeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResumeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ResumeService::class);
    }

    public function test_resolve_returns_null_with_no_courses(): void
    {
        $this->assertNull($this->service->resolve(User::factory()->create()));
    }

    public function test_resolve_returns_first_unfinished_mission_in_course_order(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $missions = $this->missions($course, $section, 3);

        $this->complete($user, $missions[0]);

        $resume = $this->service->resolve($user);

        $this->assertSame('mission', $resume['type']);
        $this->assertSame($missions[1]->id, $resume['mission']->id);
        $this->assertSame($section->id, $resume['section']->id);
    }

    public function test_resolve_prefers_in_progress_draft_over_later_untouched_mission(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $missions = $this->missions($course, $section, 3);

        MissionDraft::query()->create([
            'user_id' => $user->id,
            'mission_id' => $missions[0]->id,
            'code' => '<h1>Draft</h1>',
        ]);

        $resume = $this->service->resolve($user);

        $this->assertSame('mission', $resume['type']);
        $this->assertSame($missions[0]->id, $resume['mission']->id);
    }

    public function test_resolve_returns_course_when_all_missions_done_and_challenge_outstanding(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $missions = $this->missions($course, $section, 2);
        Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $missions[0]);
        $this->complete($user, $missions[1]);

        $resume = $this->service->resolve($user);

        $this->assertSame('course', $resume['type']);
        $this->assertSame($course->id, $resume['course']->id);
        $this->assertArrayNotHasKey('mission', $resume);
    }

    public function test_resolve_returns_null_when_the_boss_challenge_has_been_passed(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $missions = $this->missions($course, $section, 1);
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $this->complete($user, $missions[0]);

        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'passed',
            'passed_at' => now(),
        ]);

        $this->assertNull($this->service->resolve($user));
    }

    /**
     * @return array<int, Mission>
     */
    private function missions(Course $course, Section $section, int $count): array
    {
        $missions = [];

        for ($order = 1; $order <= $count; $order++) {
            $missions[] = Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $order,
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
}
