<?php

namespace App\Models;

use Database\Factories\UserAchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'achievement_id'])]
class UserAchievement extends Model
{
    /** @use HasFactory<UserAchievementFactory> */
    use HasFactory;

    protected $table = 'the404_user_achievements';

    /**
     * the404_user_achievements has no updated_at column: an award is written
     * once and never edited.
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Achievement, $this> */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'achievement_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
