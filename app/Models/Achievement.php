<?php

namespace App\Models;

use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'description'])]
class Achievement extends Model
{
    /** @use HasFactory<AchievementFactory> */
    use HasFactory;

    protected $table = 'the404_achievements';

    /** @return HasMany<UserAchievement, $this> */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class, 'achievement_id');
    }
}
