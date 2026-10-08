<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AttentionService;
use App\Services\ClassroomAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

class ScopedAttentionTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    #[DataProvider('privateEventSources')]
    public function test_private_learning_events_do_not_change_teacher_attention(string $source): void
    {
        [$teacher, $student, $shared, $private] = $this->scopedStudent();
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $shared->id, 'completed_at' => now()->subDays(30)]);
        $this->recordEvent($source, $student, $private);

        $row = $this->attentionFor($teacher)->firstWhere('student.id', $student->id);

        $this->assertNotNull($row);
        $this->assertSame(['stalled', 'inactive'], $row['signals']);
        $this->assertSame('No activity in 30 days', $row['reasons']['inactive']);
        $this->assertEquals(now()->subDays(30), $row['last_activity_at']);
        $this->actingAs($teacher)->get(route('needs-attention'))->assertOk()->assertSee('No activity in 30 days');
    }

    /** @return array<string, array{string}> */
    public static function privateEventSources(): array
    {
        return array_combine($sources = ['progress', 'assessment', 'mission-xp', 'assessment-xp', 'activity', 'knowledge-check'], array_map(fn (string $source): array => [$source], $sources));
    }

    public function test_private_progress_does_not_suppress_a_shared_course_never_started_signal(): void
    {
        [$teacher, $student, , $private] = $this->scopedStudent();
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $private->id]);

        $row = $this->attentionFor($teacher)->firstWhere('student.id', $student->id);

        $this->assertNotNull($row);
        $this->assertSame(['never_started'], $row['signals']);
        $this->assertNull($row['last_activity_at']);
    }

    public function test_submitted_shared_knowledge_check_prevents_false_inactivity(): void
    {
        [$teacher, $student, $shared] = $this->scopedStudent();
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $shared->id, 'completed_at' => now()->subDays(30)]);
        $this->recordEvent('knowledge-check', $student, $shared);

        $row = $this->attentionFor($teacher)->firstWhere('student.id', $student->id);

        $this->assertNotNull($row);
        $this->assertSame(['stalled'], $row['signals']);
        $this->assertEquals(now(), $row['last_activity_at']);
    }

    public function test_started_shared_knowledge_check_does_not_count_as_submitted_learning_evidence(): void
    {
        [$teacher, $student, $shared] = $this->scopedStudent();
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $shared->id, 'completed_at' => now()->subDays(30)]);
        $check = KnowledgeCheck::factory()->create(['mission_id' => $shared->id]);
        KnowledgeCheckAttempt::factory()->create(['user_id' => $student->id, 'knowledge_check_id' => $check->id]);

        $this->assertSame(['stalled', 'inactive'], $this->attentionFor($teacher)->firstWhere('student.id', $student->id)['signals']);
    }

    #[DataProvider('sharedXpSources')]
    public function test_authorized_xp_events_prevent_false_inactivity(string $source): void
    {
        [$teacher, $student, $shared] = $this->scopedStudent();
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $shared->id, 'completed_at' => now()->subDays(30)]);
        $this->recordEvent($source, $student, $shared);

        $row = $this->attentionFor($teacher)->firstWhere('student.id', $student->id);

        $this->assertNotNull($row);
        $this->assertSame(['stalled'], $row['signals']);
        $this->assertEquals(now(), $row['last_activity_at']);
    }

    /** @return array<string, array{string}> */
    public static function sharedXpSources(): array
    {
        return ['mission' => ['mission-xp'], 'assessment' => ['assessment-xp']];
    }

    public function test_classroom_without_assigned_courses_has_no_attention_data(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['created_at' => now()->subDays(30)]);
        $this->classroomFor($teacher, [$student]);
        Mission::factory()->create();

        $this->assertEmpty($this->attentionFor($teacher));
        $this->assertEmpty(app(AttentionService::class)->list(collect([$student->id]), [$student->id => collect()]));
    }

    public function test_admin_attention_retains_unattributed_fleet_activity(): void
    {
        [, $student, $shared] = $this->scopedStudent();
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $shared->id, 'completed_at' => now()->subDays(30)]);
        $this->recordEvent('activity', $student, $shared);

        $row = app(AttentionService::class)->list()->firstWhere('student.id', $student->id);

        $this->assertNotNull($row);
        $this->assertSame(['stalled'], $row['signals']);
        $this->assertEquals(now(), $row['last_activity_at']);
    }

    /** @return array{User, User, Mission, Mission} */
    private function scopedStudent(): array
    {
        $this->travelTo(now()->startOfDay()->addHours(6));
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['created_at' => now()->subDays(40)]);
        $sharedCourse = Course::factory()->create(['order_num' => 1]);
        $privateCourse = Course::factory()->create(['order_num' => 2]);
        $shared = Mission::factory()->create(['course_id' => $sharedCourse->id]);
        Mission::factory()->create(['course_id' => $sharedCourse->id]);
        $private = Mission::factory()->create(['course_id' => $privateCourse->id]);
        $this->classroomFor($teacher, [$student], [$sharedCourse]);
        $otherStudent = User::factory()->create();
        $this->classroomFor($teacher, [$otherStudent], [$privateCourse]);

        return [$teacher, $student, $shared, $private];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function attentionFor(User $teacher): Collection
    {
        $scope = app(ClassroomAccessService::class)->scopesFor($teacher);

        return app(AttentionService::class)->list($scope['studentIds'], $scope['byStudent']);
    }

    private function recordEvent(string $source, User $student, Mission $mission): void
    {
        if ($source === 'progress') {
            Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
        } elseif ($source === 'assessment') {
            $assessment = Assessment::factory()->create(['course_id' => $mission->course_id]);
            AssessmentAttempt::factory()->create(['user_id' => $student->id, 'assessment_id' => $assessment->id, 'status' => 'failed', 'score' => 20, 'submitted_at' => now(), 'created_at' => now()]);
        } elseif ($source === 'mission-xp') {
            XpTransaction::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id, 'created_at' => now()]);
        } elseif ($source === 'assessment-xp') {
            $assessment = Assessment::factory()->create(['course_id' => $mission->course_id]);
            XpTransaction::factory()->create(['user_id' => $student->id, 'mission_id' => null, 'assessment_id' => $assessment->id, 'created_at' => now()]);
        } elseif ($source === 'activity') {
            $activity = new Activity;
            $activity->forceFill(['user_id' => $student->id, 'type' => 'mission_completed', 'message' => 'Unattributed activity', 'created_at' => now()])->save();
        } elseif ($source === 'knowledge-check') {
            $check = KnowledgeCheck::factory()->create(['mission_id' => $mission->id]);
            KnowledgeCheckAttempt::factory()->submitted()->create(['user_id' => $student->id, 'knowledge_check_id' => $check->id]);
        }
    }
}
