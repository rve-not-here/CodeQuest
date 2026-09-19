<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'mission_id',
    'mission_version',
    'skill_keys',
    'pts_earned',
    'completed_at',
])]
class Progress extends Model
{
    use HasFactory;

    protected $table = 'the404_progress';

    /**
     * the404_progress has no updated_at column.
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'pts_earned' => 'integer',
            'mission_version' => 'integer',
            'skill_keys' => 'array',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Mission, $this> */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }
}
