<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;
use App\Services\ClassroomAccessService;

class ClassroomPolicy
{
    /**
     * The teacher-area classroom listing is open to any teacher or admin; the
     * rows themselves are scope-filtered by ClassroomAccessService.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['teacher', 'admin'], true);
    }

    /**
     * Viewing a classroom's page is membership-driven and status-driven: an
     * admin manages every classroom (active or inactive), a teacher only the
     * classrooms they teach AND that are still active — status is an
     * authorization visibility boundary, not housekeeping.
     */
    public function view(User $user, Classroom $classroom): bool
    {
        return $this->access()->teachesClassroom($user, $classroom);
    }

    /**
     * Creating classrooms is an admin-only surface (teacher monitors, admins
     * manage the academic structure).
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Editing a classroom's base fields is admin-only.
     */
    public function update(User $user, Classroom $classroom): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Teacher/student/course assignments are admin-only management operations.
     */
    public function manageMembers(User $user, Classroom $classroom): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Reading a classroom's monitoring data (roster, progress) is the teacher
     * view: the classroom's own teachers while it is active, plus the
     * fleet-wide admin.
     */
    public function monitor(User $user, Classroom $classroom): bool
    {
        return $this->access()->teachesClassroom($user, $classroom);
    }

    /**
     * Classrooms are academic records: there is deliberately no delete path
     * (§14 no-destructive posture applies here as it does to courses).
     */
    public function delete(User $user, Classroom $classroom): bool
    {
        return false;
    }

    private function access(): ClassroomAccessService
    {
        return app(ClassroomAccessService::class);
    }
}
