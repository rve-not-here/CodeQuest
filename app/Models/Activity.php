<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'type',
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
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
