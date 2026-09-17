<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'slug',
    'name',
    'type',
    'description',
    'status',
    'order_num',
])]
class Course extends Model
{
    use HasFactory;

    protected $table = 'the404_courses';

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class, 'course_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'course_id')->orderBy('order_num');
    }

    /**
     * @return HasOne<Assessment, $this>
     */
    public function assessment(): HasOne
    {
        return $this->hasOne(Assessment::class, 'course_id');
    }

    /** @return BelongsToMany<Classroom, $this> */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'the404_classroom_courses', 'course_id', 'classroom_id')
            ->withTimestamps();
    }
}
