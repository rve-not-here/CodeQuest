<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;

class ActivityService
{
    /**
     * Record an activity event for a user.
     *
     * @param  array{type: string, message: string, pts?: int}  $data
     */
    public function record(User $user, array $data): Activity
    {
        return Activity::query()->create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'message' => $data['message'],
            'pts' => $data['pts'] ?? 0,
        ]);
    }
}
