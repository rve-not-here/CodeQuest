<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\DevelopmentDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class DevelopmentDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        putenv('CODEQUEST_DEMO_PASSWORD');
        parent::tearDown();
    }

    public function test_demo_fixtures_are_repeatable_and_preserve_student_work_and_credentials(): void
    {
        putenv('CODEQUEST_DEMO_PASSWORD=Local-only-test-password');
        $this->seed(DevelopmentDemoSeeder::class);
        $student = User::query()->where('username', 'demo.student')->firstOrFail();
        $passwordHash = $student->password;
        $this->seed(DevelopmentDemoSeeder::class);
        $this->assertSame(6, User::query()->count());
        $this->assertSame($passwordHash, $student->fresh()->password);
        $this->assertTrue(Hash::check('Local-only-test-password', $passwordHash));
        $classroom = Classroom::query()->where('code', 'DEMO-01')->firstOrFail();
        $this->assertTrue($classroom->students()->whereKey($student->id)->exists());
        $this->assertSame(Course::query()->where('status', 'active')->count(), $classroom->courses()->count());
        $this->assertDatabaseCount('the404_progress', 0);
        $this->assertDatabaseCount('the404_assessment_attempts', 0);

        $teacher = User::query()->where('username', 'demo.teacher')->firstOrFail();
        $unrelatedStudent = User::query()->where('username', 'demo.unrelated.student')->firstOrFail();
        $unrelatedClassroom = Classroom::query()->where('code', 'DEMO-02')->firstOrFail();
        $this->actingAs($teacher)->get(route('students'))->assertOk()->assertSee($student->username)->assertDontSee($unrelatedStudent->username);
        $this->get(route('student-progress', $unrelatedStudent))->assertForbidden();
        $this->get(route('classrooms.show', $unrelatedClassroom))->assertForbidden();
        $admin = User::query()->where('username', 'demo.admin')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->get(route('learning-path'))->assertForbidden();
        $this->actingAs($student)->get(route('dashboard'))->assertOk();
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_demo_seeding_refuses_production_before_writing(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        putenv('CODEQUEST_DEMO_PASSWORD=Local-only-test-password');
        try {
            app(DevelopmentDemoSeeder::class)->run();
            $this->fail('Production must be refused.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('disposable', $exception->getMessage());
        }
        $this->assertDatabaseCount('the404_users', 0);
        $this->assertDatabaseCount('the404_courses', 0);
    }

    public function test_demo_seeding_refuses_database_url_overrides_before_writing(): void
    {
        config()->set('database.connections.'.config('database.default').'.url', 'mysql://localhost/system404');
        putenv('CODEQUEST_DEMO_PASSWORD=Local-only-test-password');
        try {
            app(DevelopmentDemoSeeder::class)->run();
            $this->fail('A connection URL override must be refused.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('disposable', $exception->getMessage());
        }
        $this->assertDatabaseCount('the404_users', 0);
        $this->assertDatabaseCount('the404_courses', 0);
    }
}
