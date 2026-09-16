<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\TimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    private TimelineService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TimelineService::class);
    }

    public function test_events_are_ordered_newest_first_across_sources(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['order_num' => 1]);
        $section = $this->section($course, 1, 2);

        $this->complete($user, $section->missions[0], Carbon::parse('2026-01-01 10:00:00'));
        $this->complete($user, $section->missions[1], Carbon::parse('2026-01-01 10:00:00'));

        $activity = Activity::query()->create([
            'user_id' => $user->id,
            'type' => 'mission_completed',
            'message' => 'Mission completed: Alpha',
            'pts' => 10,
        ]);
        $this->retimestamp('the404_activity', $activity->id, '2026-01-01 10:01:00');

        $hint = XpTransaction::query()->create([
            'user_id' => $user->id,
            'mission_id' => $section->missions[1]->id,
            'amount' => -5,
            'type' => 'hint_used',
            'description' => 'Hint 1 on mission: Alpha',
        ]);
        $this->retimestamp('the404_xp_transactions', $hint->id, '2026-01-01 10:02:00');

        $assessment = $this->assessment($course, 'The Final Directive');
        AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => 'passed',
            'score' => 90,
            'passed_at' => Carbon::parse('2026-01-01 10:03:00'),
            'submitted_at' => Carbon::parse('2026-01-01 10:03:00'),
        ]);

        $types = $this->service->events($user)->pluck('type')->all();

        $this->assertSame(['assessment_passed', 'hint_used', 'mission_completed', 'section_completed'], $types);
    }

    public function test_login_and_logout_activity_is_filtered_out(): void
    {
        $user = User::factory()->create();

        Activity::query()->create(['user_id' => $user->id, 'type' => 'login', 'message' => 'Operator x logged in']);
        Activity::query()->create(['user_id' => $user->id, 'type' => 'logout', 'message' => 'Operator x logged out']);

        $this->assertTrue($this->service->events($user)->isEmpty());
    }

    public function test_mission_completion_beat_appears_once_even_with_a_duplicate_ledger_row(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        Activity::query()->create([
            'user_id' => $user->id,
            'type' => 'mission_completed',
            'message' => 'Mission completed: Beta',
            'pts' => 10,
        ]);
        XpTransaction::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => 10,
            'type' => 'mission_completed',
            'description' => 'Mission completed: Beta',
        ]);

        $beats = $this->service->events($user)->where('type', 'mission_completed');

        $this->assertCount(1, $beats);
        $this->assertSame('Mission completed: Beta', $beats->first()['label']);
    }

    public function test_assessment_results_include_score_in_the_label(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $assessment = $this->assessment($course, 'The Directive');

        AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => 'passed',
            'score' => 80,
            'passed_at' => Carbon::parse('2026-01-01 10:05:00'),
            'submitted_at' => Carbon::parse('2026-01-01 10:05:00'),
        ]);
        AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => 'failed',
            'score' => 40,
            'submitted_at' => Carbon::parse('2026-01-01 10:06:00'),
        ]);

        $labels = $this->service->events($user)->pluck('label')->all();

        $this->assertContains('Boss Challenge passed: The Directive (score 80)', $labels);
        $this->assertContains('Boss Challenge failed: The Directive (score 40)', $labels);
    }

    public function test_section_completion_beat_is_timed_at_the_last_mission_finish(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $section = $this->section($course, 1, 2);

        $this->complete($user, $section->missions[0], Carbon::parse('2026-01-01 09:00:00'));
        $this->complete($user, $section->missions[1], Carbon::parse('2026-01-01 11:00:00'));

        $beat = $this->service->events($user)->firstWhere('type', 'section_completed');

        $this->assertNotNull($beat);
        $this->assertSame('Section complete: '.$section->title, $beat['label']);
        $this->assertSame('2026-01-01 11:00:00', $beat['at']->format('Y-m-d H:i:s'));
    }

    public function test_partially_completed_section_produces_no_beat(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $section = $this->section($course, 1, 2);

        $this->complete($user, $section->missions[0], Carbon::parse('2026-01-01 09:00:00'));

        $this->assertNull($this->service->events($user)->firstWhere('type', 'section_completed'));
    }

    private function section(Course $course, int $orderNum, int $missionCount): Section
    {
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => $orderNum]);

        for ($order = 1; $order <= $missionCount; $order++) {
            Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $order,
            ]);
        }

        $section->load('missions');

        return $section;
    }

    private function assessment(Course $course, string $title): Assessment
    {
        return Assessment::factory()->create(['course_id' => $course->id, 'title' => $title]);
    }

    private function complete(User $user, Mission $mission, Carbon $at): void
    {
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => $at,
        ]);
    }

    private function retimestamp(string $table, int $id, string $at): void
    {
        DB::table($table)->where('id', $id)->update(['created_at' => Carbon::parse($at)]);
    }
}
