<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One admin-authored system announcement (Phase 8, §30/§31). status
 * (draft|published|archived) is the publication lifecycle gate; published_at
 * is set once on the first publish. audience (all|students|teachers|admins)
 * is server-determined and decides which users receive a
 * SYSTEM_ANNOUNCEMENT notification at publish — operator is deliberately
 * excluded from every audience.
 *
 * @property int $id
 * @property int $created_by
 * @property string $title
 * @property string $message
 * @property string $audience
 * @property string $status
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'created_by',
    'title',
    'message',
    'audience',
    'status',
    'published_at',
])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    protected $table = 'the404_announcements';

    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
