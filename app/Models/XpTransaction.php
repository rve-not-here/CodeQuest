<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'mission_id', 'assessment_id', 'amount', 'type', 'description'])]
class XpTransaction extends Model
{
    use HasFactory;

    protected $table = 'the404_xp_transactions';

    /**
     * the404_xp_transactions has no updated_at column.
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }
}
