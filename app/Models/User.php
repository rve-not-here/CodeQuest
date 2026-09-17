<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'password', 'name', 'role'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'the404_users';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function progress(): HasMany
    {
        return $this->hasMany(Progress::class, 'user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'user_id');
    }

    public function missionDrafts(): HasMany
    {
        return $this->hasMany(MissionDraft::class, 'user_id');
    }

    public function xpTransactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class, 'user_id');
    }

    /** @return HasMany<KnowledgeCheckAttempt, $this> */
    public function knowledgeCheckAttempts(): HasMany
    {
        return $this->hasMany(KnowledgeCheckAttempt::class, 'user_id');
    }

    /** @return BelongsToMany<Classroom, $this> */
    public function teachingClassrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'the404_classroom_teachers', 'teacher_id', 'classroom_id')
            ->withTimestamps();
    }

    /** @return BelongsToMany<Classroom, $this> */
    public function enrolledClassrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'the404_classroom_students', 'student_id', 'classroom_id')
            ->withTimestamps();
    }
}
