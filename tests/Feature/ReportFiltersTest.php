<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\User;
use App\Services\ReportAuthorizationService;
use App\Support\ReportFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-1009 report filtering contract. Validation proves a filter is
 * well-formed; it never grants visibility. Every authorization interaction
 * below pairs a valid filter with the US-1001 contract to prove the two
 * stay separate.
 */
class ReportFiltersTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    /** @var array<int, string> */
    private array $statuses = ['active', 'locked', 'draft'];

    public function test_empty_filters_are_accepted(): void
    {
        $filters = ReportFilters::fromArray([], $this->statuses);

        $this->assertNull($filters->from);
        $this->assertNull($filters->toExclusive);
        $this->assertNull($filters->studentId);
        $this->assertNull($filters->courseId);
        $this->assertNull($filters->status);
    }

    public function test_valid_start_end_range_is_accepted(): void
    {
        $filters = ReportFilters::fromArray(['from' => '2026-01-01', 'to' => '2026-01-31'], $this->statuses);

        $this->assertSame('2026-01-01 00:00:00', $filters->from->toDateTimeString());
        $this->assertSame('2026-02-01 00:00:00', $filters->toExclusive->toDateTimeString());
    }

    public function test_same_day_range_is_accepted(): void
    {
        $filters = ReportFilters::fromArray(['from' => '2026-03-15', 'to' => '2026-03-15'], $this->statuses);

        $this->assertSame('2026-03-15 00:00:00', $filters->from->toDateTimeString());
        $this->assertSame('2026-03-16 00:00:00', $filters->toExclusive->toDateTimeString());
    }

    public function test_start_after_end_is_rejected(): void
    {
        try {
            ReportFilters::fromArray(['from' => '2026-02-01', 'to' => '2026-01-01'], $this->statuses);
            $this->fail('Start after end must throw.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('from', $e->errors());
        }
    }

    public function test_invalid_start_date_is_rejected(): void
    {
        try {
            ReportFilters::fromArray(['from' => 'not-a-date'], $this->statuses);
            $this->fail('Malformed start must throw.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('from', $e->errors());
        }
    }

    public function test_impossible_end_date_is_rejected(): void
    {
        try {
            ReportFilters::fromArray(['to' => '2026-02-30'], $this->statuses);
            $this->fail('Impossible end date must throw.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('to', $e->errors());
        }
    }

    public function test_non_leap_february_29_is_rejected(): void
    {
        try {
            ReportFilters::fromArray(['from' => '2026-02-29'], $this->statuses);
            $this->fail('Non-leap Feb 29 must throw.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('from', $e->errors());
        }
    }

    public function test_leap_february_29_is_accepted(): void
    {
        $filters = ReportFilters::fromArray(['from' => '2028-02-29', 'to' => '2028-02-29'], $this->statuses);

        $this->assertSame('2028-02-29 00:00:00', $filters->from->toDateTimeString());
        $this->assertSame('2028-03-01 00:00:00', $filters->toExclusive->toDateTimeString());
    }

    public function test_boundary_timestamps_are_included_and_excluded(): void
    {
        $user = User::factory()->create();
        $missions = Mission::factory()->count(3)->create();

        $at = function (int $mission, string $when) use ($user): void {
            DB::table('the404_progress')->insert([
                'user_id' => $user->id,
                'mission_id' => $mission,
                'pts_earned' => 0,
                'completed_at' => $when,
            ]);
        };

        $at($missions[0]->id, '2026-05-01 00:00:00');
        $at($missions[1]->id, '2026-05-15 12:30:45');
        $at($missions[2]->id, '2026-06-01 00:00:00');

        $filters = ReportFilters::fromArray(['from' => '2026-05-01', 'to' => '2026-05-31'], $this->statuses);

        $found = $filters->applyDateRange(
            DB::table('the404_progress')->where('user_id', $user->id),
            'completed_at'
        )->pluck('mission_id')->all();

        $this->assertEqualsCanonicalizing([$missions[0]->id, $missions[1]->id], $found);
    }

    public function test_existing_student_is_accepted(): void
    {
        $student = User::factory()->create();

        $filters = ReportFilters::fromArray(['student_id' => $student->id], $this->statuses);

        $this->assertSame($student->id, $filters->studentId);
    }

    public function test_nonexistent_student_is_rejected(): void
    {
        try {
            ReportFilters::fromArray(['student_id' => 999999], $this->statuses);
            $this->fail('Unknown student must throw.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('student_id', $e->errors());
        }
    }

    public function test_non_student_user_is_rejected_for_student_filter(): void
    {
        $teacher = User::factory()->teacher()->create();

        try {
            ReportFilters::fromArray(['student_id' => $teacher->id], $this->statuses);
            $this->fail('Non-student must throw.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('student_id', $e->errors());
        }
    }

    public function test_existing_course_is_accepted(): void
    {
        $course = Course::factory()->create();

        $filters = ReportFilters::fromArray(['course_id' => $course->id], $this->statuses);

        $this->assertSame($course->id, $filters->courseId);
    }

    public function test_nonexistent_course_is_rejected(): void
    {
        try {
            ReportFilters::fromArray(['course_id' => 999999], $this->statuses);
            $this->fail('Unknown course must throw.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('course_id', $e->errors());
        }
    }

    public function test_valid_status_is_accepted(): void
    {
        $filters = ReportFilters::fromArray(['status' => 'locked'], $this->statuses);

        $this->assertSame('locked', $filters->status);
    }

    public function test_invalid_status_is_rejected(): void
    {
        try {
            ReportFilters::fromArray(['status' => 'archived'], $this->statuses);
            $this->fail('Out-of-vocabulary status must throw.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
    }

    public function test_status_without_vocabulary_is_rejected(): void
    {
        try {
            ReportFilters::fromArray(['status' => 'active'], []);
            $this->fail('Status with no vocabulary must throw.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
    }

    public function test_normalized_boundaries_are_deterministic(): void
    {
        $first = ReportFilters::fromArray(['from' => '2026-05-01', 'to' => '2026-05-31'], $this->statuses);
        $second = ReportFilters::fromArray(['from' => '2026-05-01', 'to' => '2026-05-31'], $this->statuses);

        $this->assertTrue($first->from->equalTo($second->from));
        $this->assertTrue($first->toExclusive->equalTo($second->toExclusive));
    }

    public function test_date_range_applies_half_open_predicates(): void
    {
        $filters = ReportFilters::fromArray(['from' => '2026-05-01', 'to' => '2026-05-31'], $this->statuses);

        $sql = $filters->applyDateRange(User::query(), 'created_at')->toSql();
        $bare = str_replace(['"', '`', '[', ']'], '', $sql);

        $this->assertStringContainsString('created_at >= ?', $bare);
        $this->assertStringContainsString('created_at < ?', $bare);
    }

    public function test_teacher_cannot_turn_unauthorized_student_filter_into_access(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $this->classroomFor($teacher, [], [$course]);

        $filters = ReportFilters::fromArray(['student_id' => $student->id], $this->statuses);
        $auth = app(ReportAuthorizationService::class);

        $this->assertSame($student->id, $filters->studentId);
        $this->assertFalse($auth->canViewStudentReport($teacher, $student));
    }

    public function test_teacher_cannot_turn_unauthorized_course_filter_into_access(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $mine = Course::factory()->create();
        $theirs = Course::factory()->create();
        $this->classroomFor($teacher, [$student], [$mine]);

        $filters = ReportFilters::fromArray(['course_id' => $theirs->id], $this->statuses);
        $auth = app(ReportAuthorizationService::class);

        $this->assertSame($theirs->id, $filters->courseId);
        $this->assertFalse($auth->canViewCourseReport($teacher, $theirs));
    }

    public function test_admin_valid_filters_remain_valid_under_fleet_scope(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create();

        $filters = ReportFilters::fromArray([
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
        ], $this->statuses);
        $auth = app(ReportAuthorizationService::class);

        $this->assertTrue($auth->canViewStudentReport($admin, $student));
        $this->assertTrue($auth->canViewCourseReport($admin, $course));
        $this->assertTrue($auth->canViewSystemReport($admin));
        $this->assertNull($auth->reportCourseIds($admin, $student));
    }

    public function test_student_filter_does_not_permit_another_students_scope(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $auth = app(ReportAuthorizationService::class);

        $filters = ReportFilters::fromArray(['student_id' => $other->id], $this->statuses);

        $this->assertSame($other->id, $filters->studentId);
        $this->assertFalse($auth->canViewStudentReport($student, $other));
    }
}
