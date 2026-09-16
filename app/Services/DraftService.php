<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\User;

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
        $draft = MissionDraft::query()
            ->updateOrCreate(
                ['user_id' => $user->id, 'mission_id' => $mission->id],
                ['code' => $code],
            );

        return $draft;
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
