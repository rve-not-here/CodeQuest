<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'mission_id', 'code'])]
class MissionDraft extends Model
{
    use HasFactory;

    protected $table = 'the404_mission_drafts';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }
}
