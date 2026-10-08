<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use App\Services\ClassroomService;
use App\Support\DisposableDatabase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DevelopmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $connection = config('database.default');
        $disposable = DisposableDatabase::allows(
            $connection,
            config("database.connections.{$connection}"),
            getenv('CODEQUEST_ALLOW_DISPOSABLE_DATABASE') === '1',
        );

        if (! app()->environment(['local', 'testing']) || ! $disposable) {
            throw new RuntimeException('Demo seeding requires local/testing and an explicitly disposable database.');
        }

        $password = getenv('CODEQUEST_DEMO_PASSWORD');
        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set CODEQUEST_DEMO_PASSWORD to a local-only password of at least 12 characters.');
        }

        DB::transaction(function () use ($password): void {
            $this->call(DatabaseSeeder::class);
            $roles = [
                'demo.admin' => 'admin',
                'demo.teacher' => 'teacher',
                'demo.student' => 'student',
                'demo.unrelated.teacher' => 'teacher',
                'demo.unrelated.student' => 'student',
                'demo.operator' => 'operator',
            ];
            $users = [];

            foreach ($roles as $username => $role) {
                $user = User::query()->where('username', $username)->first();
                if ($user !== null && ($user->role !== $role || $user->status !== 'active')) {
                    throw new RuntimeException("Demo account collision: {$username}. Existing accounts were not changed.");
                }
                $users[$username] = $user ?? User::factory()->create([
                    'username' => $username,
                    'name' => ucwords(str_replace('.', ' ', $username)),
                    'role' => $role,
                    'password' => $password,
                ]);
            }

            $service = app(ClassroomService::class);
            $admin = $users['demo.admin'];
            $courseIds = Course::query()->where('status', 'active')->orderBy('id')->pluck('id')->all();

            foreach (['DEMO-01' => ['demo.teacher', 'demo.student'], 'DEMO-02' => ['demo.unrelated.teacher', 'demo.unrelated.student']] as $code => [$teacher, $student]) {
                $classroom = Classroom::query()->where('code', $code)->first()
                    ?? $service->store($admin, ['name' => "Development classroom {$code}", 'code' => $code, 'status' => Classroom::STATUS_ACTIVE]);
                if ($classroom->status !== Classroom::STATUS_ACTIVE) {
                    throw new RuntimeException("Demo classroom collision: {$code}.");
                }
                if ($classroom->teachers()->pluck('the404_users.id')->all() !== [$users[$teacher]->id]) {
                    $service->assignTeachers($admin, $classroom, [$users[$teacher]->id]);
                }
                if ($classroom->students()->pluck('the404_users.id')->all() !== [$users[$student]->id]) {
                    $service->enrollStudents($admin, $classroom, [$users[$student]->id]);
                }
                if ($classroom->courses()->orderBy('the404_courses.id')->pluck('the404_courses.id')->all() !== $courseIds) {
                    $service->assignCourses($admin, $classroom, $courseIds);
                }
            }
        });
    }
}
