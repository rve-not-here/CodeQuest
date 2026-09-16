<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One append-only row per administrative action (US-702/US-709, §28). The
 * dedicated the404_admin_audit table is the ONLY source for "recent system
 * activity" on the admin console — never TimelineService, whose learning-beat
 * vocabulary deliberately excludes admin bookkeeping.
 *
 * @property int $admin_user_id
 * @property string $admin_username
 * @property string $action
 * @property string|null $target_type
 * @property int|null $target_id
 * @property string $summary
 * @property string $result
 * @property Carbon $created_at
 */
#[Fillable([
    'admin_user_id',
    'admin_username',
    'action',
    'target_type',
    'target_id',
    'summary',
    'result',
])]
class AdminAudit extends Model
{
    protected $table = 'the404_admin_audit';

    /**
     * the404_admin_audit is append-only and has no updated_at column.
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'admin_user_id' => 'integer',
            'target_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
