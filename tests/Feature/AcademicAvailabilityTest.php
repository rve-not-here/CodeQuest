<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Notification;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\NotificationService;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademicAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_boss_is_not_advertised_as_ready_or_recommended(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
        $boss = Assessment::factory()->create(['course_id' => $course->id, 'status' => 'locked']);

        $this->actingAs($student)->get(route('assessments'))->assertViewHas('rows', function ($rows) use ($course): bool {
            $row = $rows->firstWhere('course.id', $course->id);

            return $row['state'] === 'sealed' && ! $row['unlocked'];
        })->assertDontSee(route('assessment.show', $boss));
        $cards = app(RecommendationService::class)->recommendations($student);
        $this->assertFalse($cards->contains('href', route('assessment.show', $boss)));
        $this->get(route('assessment.show', $boss))->assertRedirect(route('assessments'));
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    public function test_completed_successor_evidence_cannot_advertise_prerequisite_bypass(): void
    {
        $student = User::factory()->create();
        $first = Course::factory()->create(['order_num' => 1]);
        Mission::factory()->create(['course_id' => $first->id]);
        Assessment::factory()->create(['course_id' => $first->id]);
        $second = Course::factory()->create(['order_num' => 2]);
        $section = Section::factory()->create(['course_id' => $second->id]);
        $mission = Mission::factory()->create(['course_id' => $second->id, 'section_id' => $section->id]);
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
        $boss = Assessment::factory()->create(['course_id' => $second->id]);

        $this->actingAs($student)->get(route('learning-path'))
            ->assertDontSee(route('mission.show', $mission))->assertDontSee(route('assessment.show', $boss));
        $this->get(route('assessments'))->assertViewHas('rows', function ($rows) use ($second): bool {
            return ! $rows->firstWhere('course.id', $second->id)['unlocked'];
        });
        $this->get(route('mission.show', $mission))->assertForbidden();
    }

    public function test_historical_pass_is_preserved_when_access_is_sealed(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
        $boss = Assessment::factory()->create(['course_id' => $course->id, 'status' => 'locked']);
        AssessmentAttempt::factory()->create(['user_id' => $student->id, 'assessment_id' => $boss->id, 'status' => 'passed', 'score' => 100, 'created_at' => now(), 'passed_at' => now()]);

        $this->assertTrue(app(AssessmentService::class)->hasPassed($student, $course));
        $this->actingAs($student)->get(route('assessments'))->assertDontSee(route('assessment.show', $boss));
        $this->get(route('learning-path'))->assertDontSee(route('assessment.show', $boss));
        $this->assertDatabaseHas('the404_assessment_attempts', ['user_id' => $student->id, 'status' => 'passed']);
    }

    public function test_notification_links_follow_current_course_access_without_rewriting_history(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $student->id,
            'type' => NotificationService::TYPE_MISSION_COMPLETED,
            'data' => ['route' => 'mission.show', 'params' => ['mission' => $mission->id]],
        ]);
        $service = app(NotificationService::class);

        $this->assertSame(route('mission.show', $mission), $service->linkFor($notification));
        $mission->course->update(['status' => 'locked']);
        $this->assertNull($service->linkFor($notification->fresh()));
        $this->assertDatabaseCount('the404_notifications', 1);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    public function test_notification_destination_checks_are_batched_and_reject_foreign_ownership(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create();
        $rows = Notification::factory()->count(20)->create([
            'user_id' => $student->id,
            'type' => NotificationService::TYPE_MISSION_COMPLETED,
            'data' => ['route' => 'mission.show', 'params' => ['mission' => (string) $mission->id]],
        ]);
        $foreign = Notification::factory()->create();
        $service = app(NotificationService::class);
        DB::enableQueryLog();

        try {
            DB::flushQueryLog();
            $service->linksFor($student, collect([$rows->first()]));
            $small = count(DB::getQueryLog());
            DB::flushQueryLog();
            $links = $service->linksFor($student, $rows->push($foreign));
            $large = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }

        $this->assertLessThanOrEqual($small, $large);
        $this->assertSame(route('mission.show', $mission), $links[$rows->first()->id]);
        $this->assertNull($links[$foreign->id]);
    }
}
