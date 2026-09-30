<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Notification;
use App\Models\Progress;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * Teacher attention notifications (US-806): the teacher dashboard (/students)
 * syncs the requesting teacher's notification center from AttentionService::list()
 * — change-driven with an independent 24-hour cooldown, strictly scoped to the
 * requesting user, and drawn only from the needs-attention page vocabulary. A
 * student never sees these rows, about anyone.
 */
class TeacherAttentionNotificationTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    public function test_a_second_dashboard_visit_with_no_attention_change_creates_no_new_rows(): void
    {
        $course = $this->course('Boss', 1, 2, 100);
        $student = $this->student('alice');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 90, $this->daysAgo(1));

        $teacher = $this->teacher();
        $this->classroomFor($teacher, [$student], [$course]);

        $this->visitDashboard($teacher);
        $this->assertSame(1, $this->countTeacherRows($teacher));

        $this->visitDashboard($teacher);
        $this->assertSame(1, $this->countTeacherRows($teacher));
    }

    public function test_a_signal_change_inside_the_cooldown_stays_pending_and_emits_once_it_lifts(): void
    {
        $course = $this->course('Boss', 1, 2, 100);
        $student = $this->student('alice');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 90, $this->daysAgo(2));

        $teacher = $this->teacher();
        $this->classroomFor($teacher, [$student], [$course]);

        $this->visitDashboard($teacher);
        $this->assertSame(1, $this->countTeacherRows($teacher));
        $this->assertSame(['boss_fail'], $this->latestRow($teacher, $student)->data['signals']);

        // The student fails again right now: a genuine change, still within
        // the 24-hour cooldown. The state differs, but the cooldown suppresses
        // the emission (independent gates — see the report).
        $this->attempt($student, $course->assessment, 'failed', 90, now());

        $this->visitDashboard($teacher);
        $this->assertSame(1, $this->countTeacherRows($teacher));

        // The cooldown expires while the changed state still stands; the
        // pending change emits now — delayed, not lost.
        $this->backdateTeacherRows($teacher);

        $this->visitDashboard($teacher);
        $this->assertSame(2, $this->countTeacherRows($teacher));
        $this->assertSame(['boss_fail', 'repeat_fail'], $this->latestRow($teacher, $student)->data['signals']);
    }

    public function test_cooldown_expiry_alone_never_creates_a_row_for_an_unchanged_student(): void
    {
        $course = $this->course('Boss', 1, 2, 100);
        $student = $this->student('alice');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 90, $this->daysAgo(1));

        $teacher = $this->teacher();
        $this->classroomFor($teacher, [$student], [$course]);

        $this->visitDashboard($teacher);
        $this->assertSame(1, $this->countTeacherRows($teacher));

        $this->backdateTeacherRows($teacher);

        $this->visitDashboard($teacher);
        $this->assertSame(1, $this->countTeacherRows($teacher));
    }

    public function test_no_row_is_written_by_a_plain_needs_attention_page_visit(): void
    {
        $course = $this->course('Boss', 1, 2, 100);
        $student = $this->student('alice');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 90, $this->daysAgo(1));

        $teacher = $this->teacher();
        $this->classroomFor($teacher, [$student], [$course]);

        $this->actingAs($teacher)->get(route('needs-attention'))->assertOk();

        $this->assertSame(0, $this->countTeacherRows($teacher));
    }

    public function test_each_teacher_receives_only_their_own_attention_notifications(): void
    {
        $course = $this->course('Boss', 1, 2, 100);
        $student = $this->student('alice');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 90, $this->daysAgo(1));

        $first = $this->teacher();
        $second = $this->teacher();
        $this->classroomFor($first, [$student], [$course]);
        $this->classroomFor($second, [$student], [$course]);

        $this->visitDashboard($first);

        $this->assertSame(1, $this->countTeacherRows($first));
        $this->assertSame(0, $this->countTeacherRows($second));

        $this->visitDashboard($second);

        $this->assertSame(1, $this->countTeacherRows($first));
        $this->assertSame(1, $this->countTeacherRows($second));
    }

    public function test_the_notified_title_message_and_link_mirror_the_needs_attention_vocabulary(): void
    {
        $course = $this->course('Boss', 1, 2, 100);
        $student = $this->student('alice');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 40, $this->daysAgo(1));

        $teacher = $this->teacher();
        $this->classroomFor($teacher, [$student], [$course]);

        $this->visitDashboard($teacher);

        $row = $this->latestRow($teacher, $student);

        $this->assertSame('teacher_attention', $row->type);
        $this->assertNull($row->dedupe_key);
        $this->assertSame('ALICE NEEDS ATTENTION', $row->title);
        $this->assertSame('BOSS CHALLENGE FAILED: Boss Challenge failed (score 40/100, attempt 1)', $row->message);
        $this->assertSame([
            'route' => 'needs-attention',
            'params' => [],
            'student' => $student->id,
            'signals' => ['boss_fail'],
        ], $row->data);
        $this->assertSame(route('needs-attention'), app(NotificationService::class)->linkFor($row));

        // The teacher's center renders the row with its link, which opens the
        // needs-attention page (no per-student query parameter is carried).
        $this->actingAs($teacher)
            ->get(route('notifications'))
            ->assertOk()
            ->assertSee('ALICE NEEDS ATTENTION')
            ->assertSee('Boss Challenge failed (score 40/100, attempt 1)')
            ->assertSee(route('needs-attention'));

        $this->actingAs($teacher)->get(route('needs-attention'))->assertOk();
    }

    public function test_a_student_never_sees_teacher_attention_notifications_about_anyone(): void
    {
        $course = $this->course('Boss', 1, 2, 100);
        $subject = $this->student('alice');
        $bystander = $this->student('bob');

        $this->completeAll($subject, $this->missions($course));
        $this->attempt($subject, $course->assessment, 'failed', 90, $this->daysAgo(1));

        $teacher = $this->teacher();
        $this->classroomFor($teacher, [$subject], [$course]);

        $this->visitDashboard($teacher);
        $this->assertSame(1, $this->countTeacherRows($teacher));

        // Neither the student the row is about nor a bystander sees it.
        foreach ([$subject, $bystander] as $student) {
            $this->actingAs($student)
                ->get(route('notifications'))
                ->assertOk()
                ->assertDontSee('NEEDS ATTENTION')
                ->assertDontSee('boss_fail')
                ->assertSee('INBOX EMPTY');
        }
    }

    // ── Fixture helpers ───────────────────────────────────────────────

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function student(string $username): User
    {
        return User::factory()->create(['username' => $username]);
    }

    private function course(string $name, int $order, int $missionCount, int $passingScore): Course
    {
        $course = Course::factory()->create([
            'slug' => Str::slug($name).'-'.$order,
            'name' => $name,
            'order_num' => $order,
            'status' => 'active',
        ]);

        for ($i = 1; $i <= $missionCount; $i++) {
            Mission::factory()->create([
                'course_id' => $course->id,
                'order_num' => $i,
                'title' => "Mission {$name} {$i}",
            ]);
        }

        Assessment::factory()->create([
            'course_id' => $course->id,
            'title' => "{$name} Boss Challenge",
            'passing_score' => $passingScore,
            'status' => 'active',
        ]);

        return $course->load('assessment');
    }

    /**
     * @return array<int, Mission>
     */
    private function missions(Course $course): array
    {
        return $course->missions()->orderBy('order_num')->get()->all();
    }

    /**
     * @param  array<int, Mission>  $missions
     */
    private function completeAll(User $user, array $missions): void
    {
        foreach ($missions as $mission) {
            Progress::query()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'pts_earned' => 10,
                'completed_at' => now(),
            ]);
        }
    }

    private function attempt(User $user, ?Assessment $assessment, string $status, int $score, ?Carbon $at = null): void
    {
        $this->assertNotNull($assessment);

        AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => $status,
            'score' => $score,
            'passed_at' => $status === 'passed' ? ($at ?? now()) : null,
            'submitted_at' => $at ?? now(),
            'created_at' => $at,
        ]);
    }

    /**
     * A deterministic "N full calendar days ago" timestamp six hours past
     * start-of-day, the same convention AttentionTest uses.
     */
    private function daysAgo(int $days): Carbon
    {
        return now()->startOfDay()->subDays($days)->addHours(6);
    }

    private function visitDashboard(User $teacher): void
    {
        $this->actingAs($teacher)->get(route('students'))->assertOk();
    }

    private function countTeacherRows(User $teacher): int
    {
        return Notification::query()
            ->where('user_id', $teacher->id)
            ->where('type', NotificationService::TYPE_TEACHER_ATTENTION)
            ->count();
    }

    private function latestRow(User $teacher, User $student): Notification
    {
        return Notification::query()
            ->where('user_id', $teacher->id)
            ->where('type', NotificationService::TYPE_TEACHER_ATTENTION)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->firstOrFail();
    }

    /**
     * Move every one of the teacher's teacher_attention rows outside the
     * 24-hour cooldown so a subsequent sync re-evaluates the change gate.
     */
    private function backdateTeacherRows(User $teacher): void
    {
        Notification::query()
            ->where('user_id', $teacher->id)
            ->where('type', NotificationService::TYPE_TEACHER_ATTENTION)
            ->update(['created_at' => now()->subHours(25)]);
    }
}
