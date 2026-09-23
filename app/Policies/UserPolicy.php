<?php

namespace App\Policies;

use App\Models\User;
use App\Services\ClassroomAccessService;

class UserPolicy
{
    /**
     * Who may view a user's learning data (the per-student monitor page).
     * Admins are fleet-wide; a teacher may view exactly the students their
     * classroom membership authorizes; a student may always view their own
     * data through the student-facing pages, never another account's.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return $user->role === 'student' || $user->role === 'admin';
        }

        return app(ClassroomAccessService::class)->isAuthorizedForStudent($user, $model);
    }

    /**
     * Nobody creates or administers accounts through the monitor surface;
     * account lifecycle is the admin directory's (UserService) alone.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $model): bool
    {
        return false;
    }
}
