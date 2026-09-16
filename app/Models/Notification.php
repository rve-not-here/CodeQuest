<?php

namespace App\Models;

use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One notification row per recipient (Phase 8, §10/§39). A notification
 * belongs to exactly one user; ownership is enforced at the service layer
 * (NotificationService), never in the view. Append-only: there is no
 * updated_at column, so $timestamps is off. dedupe_key carries the
 * duplicate-prevention identity for one-shot events (unique per user); it is
 * null for frequency-governed recurring notifications.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property array<array-key, mixed>|null $data
 * @property string|null $dedupe_key
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 */
#[Fillable([
    'user_id',
    'type',
    'title',
    'message',
    'data',
    'dedupe_key',
])]
class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    protected $table = 'the404_notifications';

    /**
     * the404_notifications is append-only and has no updated_at column.
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'data' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
