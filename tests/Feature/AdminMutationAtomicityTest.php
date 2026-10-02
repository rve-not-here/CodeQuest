<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\AdminAssessmentService;
use App\Services\AdminAuditService;
use App\Services\AdminMissionService;
use App\Services\ClassroomService;
use App\Services\CourseService;
use App\Services\SectionService;
use App\Services\UserService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class AdminMutationAtomicityTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('mutationKinds')]
    public function test_audit_failure_rolls_back_accepted_mutation(string $kind): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        [$record, $service, $args] = match ($kind) {
            'course' => [$course, CourseService::class, [$admin, $course, ['name' => 'Changed', 'slug' => $course->slug, 'type' => $course->type, 'status' => $course->status, 'order_num' => $course->order_num]]],
            'section' => $this->sectionMutation($admin, $course),
            'mission' => $this->missionMutation($admin, $course),
            'assessment' => $this->assessmentMutation($admin, $course),
            'user' => $this->userMutation($admin),
            'classroom' => $this->classroomMutation($admin),
        };
        $before = $record->fresh()->getRawOriginal();
        $this->mock(AdminAuditService::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('audit storage unavailable'));
        try {
            app($service)->update(...$args);
            $this->fail('Audit failure must prevent the mutation from committing.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit storage unavailable', $exception->getMessage());
        }
        $this->assertSame($before, $record->fresh()->getRawOriginal());
        $this->assertDatabaseCount('the404_admin_audit', 0);
    }

    /** @return array<string, array{string}> */
    public static function mutationKinds(): array
    {
        return array_combine($keys = ['course', 'section', 'mission', 'assessment', 'user', 'classroom'], array_map(fn ($kind) => [$kind], $keys));
    }

    private function sectionMutation(User $admin, Course $course): array
    {
        $section = Section::factory()->create(['course_id' => $course->id]);

        return [$section, SectionService::class, [$admin, $course, $section, ['title' => 'Changed', 'order_num' => $section->order_num]]];
    }

    private function missionMutation(User $admin, Course $course): array
    {
        $mission = Mission::factory()->create(['course_id' => $course->id]);

        return [$mission, AdminMissionService::class, [$admin, $course, $mission, ['title' => 'Changed', 'difficulty' => $mission->difficulty, 'points' => $mission->points, 'order_num' => $mission->order_num, 'section_id' => null]]];
    }

    private function assessmentMutation(User $admin, Course $course): array
    {
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        return [$assessment, AdminAssessmentService::class, [$admin, $course, $assessment, ['title' => 'Changed', 'passing_score' => $assessment->passing_score, 'status' => $assessment->status]]];
    }

    private function userMutation(User $admin): array
    {
        $target = User::factory()->create();

        return [$target, UserService::class, [$admin, $target, ['username' => $target->username, 'name' => 'Changed']]];
    }

    private function classroomMutation(User $admin): array
    {
        $classroom = Classroom::factory()->create();

        return [$classroom, ClassroomService::class, [$admin, $classroom, ['name' => 'Changed', 'status' => $classroom->status]]];
    }

    public function test_create_account_and_classroom_roll_back_when_audit_fails(): void
    {
        $admin = User::factory()->admin()->create();
        $this->mock(AdminAuditService::class)->shouldReceive('record')->twice()->andThrow(new RuntimeException('audit unavailable'));
        foreach ([fn () => app(UserService::class)->create($admin, ['username' => 'new-user', 'name' => 'New', 'role' => 'student', 'password' => 'test-password']), fn () => app(ClassroomService::class)->store($admin, ['name' => 'New classroom', 'status' => 'active'])] as $create) {
            try {
                $create();
                $this->fail('Expected audit failure.');
            } catch (RuntimeException) {
            }
        }
        $this->assertDatabaseMissing('the404_users', ['username' => 'new-user']);
        $this->assertDatabaseCount('the404_classrooms', 0);
    }

    public function test_assignment_audit_failure_preserves_previous_membership(): void
    {
        $admin = User::factory()->admin()->create();
        $classroom = Classroom::factory()->create();
        $original = User::factory()->teacher()->create();
        $replacement = User::factory()->teacher()->create();
        $classroom->teachers()->attach($original);
        $this->mock(AdminAuditService::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('audit unavailable'));
        try {
            app(ClassroomService::class)->assignTeachers($admin, $classroom, [$replacement->id]);
            $this->fail('Expected audit failure.');
        } catch (RuntimeException) {
        }
        $this->assertSame([$original->id], $classroom->teachers()->pluck('the404_users.id')->all());
    }

    public function test_second_course_save_failure_rolls_back_first_save_and_accepted_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $originalName = $course->name;
        $saves = 0;
        Course::saving(function (Course $saved) use ($course, &$saves): void {
            if ($saved->is($course) && ++$saves === 2) {
                throw new RuntimeException('second save failed');
            }
        });

        try {
            app(CourseService::class)->update($admin, $course, ['name' => 'Changed', 'slug' => $course->slug, 'type' => $course->type, 'status' => 'locked', 'order_num' => $course->order_num]);
            $this->fail('Expected second-save failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('second save failed', $exception->getMessage());
        }

        $this->assertSame($originalName, $course->fresh()->name);
        $this->assertSame('active', $course->fresh()->status);
        $this->assertSame(1, $course->fresh()->version);
        $this->assertDatabaseCount('the404_admin_audit', 0);
    }

    public function test_sync_failure_rolls_back_detached_memberships(): void
    {
        $admin = User::factory()->admin()->create();
        $classroom = Classroom::factory()->create();
        $original = User::factory()->teacher()->create();
        $replacement = User::factory()->teacher()->create();
        $classroom->teachers()->attach($original);
        DB::listen(function (QueryExecuted $query): void {
            if (str_starts_with(strtolower($query->sql), 'delete') && str_contains($query->sql, 'the404_classroom_teachers')) {
                throw new RuntimeException('sync failed after detach');
            }
        });

        try {
            app(ClassroomService::class)->assignTeachers($admin, $classroom, [$replacement->id]);
            $this->fail('Expected sync failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('sync failed after detach', $exception->getMessage());
        }

        $this->assertSame([$original->id], $classroom->teachers()->pluck('the404_users.id')->all());
        $this->assertDatabaseCount('the404_admin_audit', 0);
    }
}
