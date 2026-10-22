<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'type',
    'mission_id',
    'message',
    'pts',
])]
class Activity extends Model
{
    use HasFactory;

    protected $table = 'the404_activity';

    /**
     * the404_activity has no updated_at column.
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'pts' => 'integer',
            'mission_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The mission an attributed event belongs to. Historical rows and
     * assessment-level events keep this null.
     *
     * @return BelongsTo<Mission, $this>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }
}
