<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\CourseService;
use App\Services\DashboardService;
use App\Services\LearningPathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSequenceSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tied_course_cannot_bypass_its_displayed_predecessor(): void
    {
        $student = User::factory()->create();
        $first = Course::factory()->create(['order_num' => 1]);
        Mission::factory()->create(['course_id' => $first->id]);
        $second = Course::factory()->create(['order_num' => 1]);
        $mission = Mission::factory()->create(['course_id' => $second->id]);
        $assessment = Assessment::factory()->create(['course_id' => $second->id]);

        $this->assertSame($first->id, app(DashboardService::class)->currentCourse($student)->id);
        $this->assertFalse(app(AssessmentService::class)->isCourseReached($student, $second));
        $this->actingAs($student)->get(route('mission.show', $mission))->assertForbidden();
        $this->get(route('mission.challenge', $mission))->assertForbidden();
        foreach (['mission.submit', 'mission.draft', 'mission.hint', 'mission.reveal'] as $route) {
            $this->post(route($route, $mission), ['code' => 'valid', 'passed' => true])->assertForbidden();
        }
        $this->post(route('assessment.start', $assessment))->assertSessionHas('assessment_error');
        $this->assertDatabaseCount('the404_progress', 0);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
        $this->assertDatabaseCount('the404_assessment_attempts', 0);
        $this->assertDatabaseCount('the404_mission_drafts', 0);
    }

    public function test_historical_pass_reaches_tied_successor_even_after_failed_retry(): void
    {
        $student = User::factory()->create();
        $first = Course::factory()->create(['order_num' => 1]);
        Mission::factory()->create(['course_id' => $first->id]);
        $boss = Assessment::factory()->create(['course_id' => $first->id]);
        $second = Course::factory()->create(['order_num' => 1]);
        $mission = Mission::factory()->create(['course_id' => $second->id]);
        AssessmentAttempt::factory()->create(['assessment_id' => $boss->id, 'user_id' => $student->id, 'status' => 'passed', 'score' => 100]);
        AssessmentAttempt::factory()->create(['assessment_id' => $boss->id, 'user_id' => $student->id, 'status' => 'failed', 'score' => 0]);

        $this->assertTrue(app(AssessmentService::class)->isCourseReached($student, $second));
        $this->assertSame($second->id, app(DashboardService::class)->currentCourse($student)->id);
        $this->actingAs($student)->get(route('mission.challenge', $mission))->assertOk();
        $this->assertSame([$first->id, $second->id], app(CourseService::class)->ordered()->pluck('id')->all());
        $this->assertSame([$first->id, $second->id], app(LearningPathService::class)->build($student)->pluck('course.id')->all());
    }

    public function test_inactive_and_empty_courses_do_not_block_but_reordering_does(): void
    {
        $student = User::factory()->create();
        Course::factory()->create(['order_num' => 0]);
        $sealed = Course::factory()->locked()->create(['order_num' => 0]);
        Mission::factory()->create(['course_id' => $sealed->id]);
        $target = Course::factory()->create(['order_num' => 1]);
        Mission::factory()->create(['course_id' => $target->id]);
        $later = Course::factory()->create(['order_num' => 2]);
        Mission::factory()->create(['course_id' => $later->id]);

        $this->assertTrue(app(AssessmentService::class)->isCourseReached($student, $target));
        $admin = User::factory()->admin()->create();
        app(CourseService::class)->update($admin, $later, ['name' => $later->name, 'slug' => $later->slug, 'type' => $later->type, 'status' => 'active', 'order_num' => 0]);
        $this->assertFalse(app(AssessmentService::class)->isCourseReached($student, $target));
        $this->assertDatabaseHas('the404_admin_audit', ['target_id' => $later->id, 'action' => 'course.update']);
        $this->assertDatabaseCount('the404_progress', 0);
    }
}
