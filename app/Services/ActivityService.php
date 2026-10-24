<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use App\Support\SchemaAvailability;

class ActivityService
{
    /**
     * Record an activity event for a user.
     *
     * `mission_id` attributes mission-scoped events (completion, wrong
     * submission, ungradable grading) so analytics can count attempts without
     * parsing message text. Assessment-level events leave it null.
     *
     * The attribution column is optional: while its migration is still
     * pending the attribute is simply omitted, so an Activity write never
     * fails on a database that has not received the DDL yet. Unattributed
     * rows stay uncounted by mission-scoped analytics rather than guessed.
     *
     * @param  array{type: string, message: string, pts?: int, mission_id?: int|null}  $data
     */
    public function record(User $user, array $data): Activity
    {
        $attributes = [
            'user_id' => $user->id,
            'type' => $data['type'],
            'message' => $data['message'],
            // the404_activity.pts is an unsigned points-earned column in the
            // deployed schema. A debit belongs in the XP ledger (the source of
            // truth for balances), never in this column: a negative value is a
            // MySQL out-of-range error, and SQLite would hide it in tests.
            'pts' => max(0, (int) ($data['pts'] ?? 0)),
        ];

        if (SchemaAvailability::activityMissionId()) {
            $attributes['mission_id'] = $data['mission_id'] ?? null;
        }

        return Activity::query()->create($attributes);
    }
}
