<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\KnowledgeCheckResponse;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\KnowledgeCheckService;
use App\Services\TimelineService;
use App\Services\XpService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

class ScopedLearningEvidenceTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_scoped_timeline_uses_course_linked_records_without_leaking_unattributed_activity(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create(['name' => 'Visible course', 'order_num' => 1]);
        $foreign = Course::factory()->create(['name' => 'Private course', 'order_num' => 2]);
        foreach ([$course, $foreign] as $item) {
            $mission = Mission::factory()->create(['course_id' => $item->id, 'title' => $item->name.' mission']);
            Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
            XpTransaction::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id, 'type' => XpService::TYPE_WRONG_SUBMISSION, 'amount' => -10, 'description' => $item->name.' failure']);
            $check = KnowledgeCheck::factory()->create(['mission_id' => $mission->id, 'title' => $item->name.' check']);
            KnowledgeCheckAttempt::factory()->submitted()->create(['user_id' => $student->id, 'knowledge_check_id' => $check->id]);
        }
        Activity::query()->create(['user_id' => $student->id, 'type' => 'mission_completed', 'message' => 'UNATTRIBUTED PRIVATE ACTIVITY']);
        $timeline = app(TimelineService::class);
        $events = $timeline->events($student, 50, collect([$course->id]));
        $this->assertEqualsCanonicalizing(['mission_completed', 'wrong_submission', 'knowledge_check_completed'], $events->pluck('type')->all());
        $this->assertStringNotContainsString('Private', $events->pluck('label')->implode(' '));
        $this->assertStringNotContainsString('UNATTRIBUTED', $events->pluck('label')->implode(' '));
        foreach (['mission_completed', 'wrong_submission', 'knowledge_check_completed'] as $type) {
            $feed = $timeline->feed(collect([$student->id]), $course->id, $type, now()->subDay(), now()->addDay(), [], [$student->id => collect([$course->id])]);
            $this->assertSame(1, $feed->total());
        }
        $this->assertEmpty($timeline->events($student, 50, collect()));
        $this->assertEmpty($timeline->latestForUsers(collect([$student]), []));
    }

    public function test_teacher_evidence_uses_submitted_snapshots_and_current_scope_and_role(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create();
        $visible = Course::factory()->create(['name' => 'Shared evidence', 'order_num' => 1]);
        $private = Course::factory()->create(['name' => 'Private evidence', 'order_num' => 2]);
        $classroom = $this->classroomFor($teacher, [$student], [$visible]);
        foreach ([$visible, $private] as $course) {
            $mission = Mission::factory()->create(['course_id' => $course->id]);
            $check = KnowledgeCheck::factory()->create(['mission_id' => $mission->id]);
            $attempt = KnowledgeCheckAttempt::factory()->submitted(0)->create(['user_id' => $student->id, 'knowledge_check_id' => $check->id]);
            $question = KnowledgeCheckQuestion::factory()->create(['knowledge_check_id' => $check->id]);
            $selected = KnowledgeCheckOption::factory()->create(['knowledge_check_question_id' => $question->id]);
            $correct = KnowledgeCheckOption::factory()->correct()->create(['knowledge_check_question_id' => $question->id, 'order_num' => 2]);
            KnowledgeCheckResponse::factory()->create(['knowledge_check_attempt_id' => $attempt->id, 'knowledge_check_question_id' => $question->id, 'selected_option_id' => $selected->id, 'correct_option_id' => $correct->id, 'prompt_snapshot' => $course->name.' recorded question']);
            KnowledgeCheckAttempt::factory()->create(['user_id' => $student->id, 'knowledge_check_id' => $check->id, 'attempt_number' => 2]);
        }
        $this->actingAs($teacher)->get(route('student-progress', $student))->assertOk()
            ->assertSee('Shared evidence recorded question')->assertSee('Needs review')->assertDontSee('Private evidence recorded question');
        $this->assertSame(1, app(KnowledgeCheckService::class)->monitoringAttempts($teacher, $student)->total());
        $misconceptions = app(KnowledgeCheckService::class)->monitoringMisconceptions($teacher, $student);
        $this->assertSame([['prompt' => 'Shared evidence recorded question', 'responses' => 1, 'incorrect' => 1]], $misconceptions->all());
        $this->get(route('student-progress', User::factory()->create()))->assertForbidden();
        $classroom->update(['status' => 'inactive']);
        $this->get(route('student-progress', $student))->assertForbidden();
        $classroom->update(['status' => 'active']);
        $teacher->update(['role' => 'student']);
        $this->get(route('student-progress', $student))->assertForbidden();
        $this->expectException(AuthorizationException::class);
        app(KnowledgeCheckService::class)->monitoringAttempts($teacher->fresh(), $student);
    }
}
