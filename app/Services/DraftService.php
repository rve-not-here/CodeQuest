<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Persists and restores a student's in-progress mission code.
 * One draft per (user, mission), guaranteed by a unique constraint.
 */
class DraftService
{
    /**
     * Load the draft for a user and mission, if one exists.
     */
    public function find(User $user, Mission $mission): ?MissionDraft
    {
        return MissionDraft::query()
            ->where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->first();
    }

    /**
     * Insert or update the student's in-progress code for a mission.
     */
    public function save(User $user, Mission $mission, string $code): MissionDraft
    {
        return DB::transaction(function () use ($user, $mission, $code): MissionDraft {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $mission = Mission::query()->whereKey($mission->id)->sharedLock()->firstOrFail();
            $course = Course::query()->whereKey($mission->course_id)->sharedLock()->firstOrFail();
            abort_unless($course->status === 'active', 403);

            if (Progress::query()->where('user_id', $user->id)->where('mission_id', $mission->id)->exists()) {
                throw ValidationException::withMessages(['code' => 'This challenge is complete. Your draft was not saved.']);
            }

            return MissionDraft::query()->updateOrCreate(
                ['user_id' => $user->id, 'mission_id' => $mission->id],
                ['code' => $code],
            );
        }, attempts: 3);
    }

    /**
     * Remove a draft. A successful submission clears it.
     */
    public function delete(User $user, Mission $mission): void
    {
        MissionDraft::query()
            ->where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->delete();
    }
}
