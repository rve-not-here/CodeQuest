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
use App\Services\LearningPathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningPathServiceTest extends TestCase
{
    use RefreshDatabase;

    private LearningPathService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LearningPathService::class);
    }

    public function test_build_returns_tree_with_courses_and_sections(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['order_num' => 1]);
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1]);
        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
        ]);

        $tree = $this->service->build($user);

        $this->assertCount(1, $tree);
        $this->assertSame($course->id, $tree->first()['course']->id);
        $this->assertCount(1, $tree->first()['sections']);
        $this->assertSame($section->id, $tree->first()['sections']->first()['section']->id);
    }

    public function test_mission_state_completed_when_progress_exists(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
        ]);

        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => now(),
        ]);

        $tree = $this->service->build($user);
        $missionView = $tree->first()['sections']->first()['missions']->first();

        $this->assertSame('COMPLETED', $missionView['state']);
    }

    public function test_mission_state_in_progress_when_draft_exists(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
        ]);

        MissionDraft::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'code' => '<h1>Draft</h1>',
        ]);

        $tree = $this->service->build($user);
        $missionView = $tree->first()['sections']->first()['missions']->first();

        $this->assertSame('IN PROGRESS', $missionView['state']);
    }

    public function test_mission_state_not_started_when_no_draft_and_no_progress(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
        ]);

        $tree = $this->service->build($user);
        $missionView = $tree->first()['sections']->first()['missions']->first();

        $this->assertSame('NOT STARTED', $missionView['state']);
    }

    public function test_next_mission_returns_first_uncompleted(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $m1 = Mission::factory()->create(['course_id' => $course->id, 'order_num' => 1]);
        $m2 = Mission::factory()->create(['course_id' => $course->id, 'order_num' => 2]);

        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $m1->id,
            'pts_earned' => 10,
            'completed_at' => now(),
        ]);

        $next = $this->service->nextMission($user, $course);

        $this->assertNotNull($next);
        $this->assertSame($m2->id, $next->id);
    }

    public function test_next_mission_prefers_draft_over_earlier_uncompleted(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $m1 = Mission::factory()->create(['course_id' => $course->id, 'order_num' => 1]);
        Mission::factory()->create(['course_id' => $course->id, 'order_num' => 2]);

        MissionDraft::query()->create([
            'user_id' => $user->id,
            'mission_id' => $m1->id,
            'code' => 'code',
        ]);

        $next = $this->service->nextMission($user, $course);

        $this->assertNotNull($next);
        $this->assertSame($m1->id, $next->id);
    }

    public function test_next_mission_returns_null_when_all_completed(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'order_num' => 1]);

        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => now(),
        ]);

        $this->assertNull($this->service->nextMission($user, $course));
    }

    public function test_next_mission_is_null_when_there_is_no_current_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        Mission::factory()->create(['course_id' => $course->id, 'order_num' => 1]);

        $assessment = Assessment::factory()->create(['course_id' => $course->id, 'passing_score' => 70]);
        AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'passed',
            'score' => 100,
            'passed_at' => now(),
        ]);

        // End of sequence (§19 "no invalid next-course dependency"): with the
        // only course passed, currentCourse() is null and nextMission()
        // resolves through it, so navigation has no course to point at.
        $this->assertNull($this->service->nextMission($user));
    }

    public function test_section_progress_counts_correctly(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $m1 = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
        ]);
        $m2 = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 2,
        ]);

        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $m1->id,
            'pts_earned' => 10,
            'completed_at' => now(),
        ]);

        $tree = $this->service->build($user);
        $sectionProgress = $tree->first()['sections']->first()['progress'];

        $this->assertSame(1, $sectionProgress['completed']);
        $this->assertSame(2, $sectionProgress['total']);
        $this->assertSame(50, $sectionProgress['percent']);
    }

    public function test_course_progress_includes_correct_stats(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $m1 = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => 1,
        ]);

        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $m1->id,
            'pts_earned' => 10,
            'completed_at' => now(),
        ]);

        $tree = $this->service->build($user);
        $courseProgress = $tree->first()['progress'];

        $this->assertSame(1, $courseProgress['completed']);
        $this->assertSame(1, $courseProgress['total']);
        $this->assertSame(100, $courseProgress['percent']);
    }
}
