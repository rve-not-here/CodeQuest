<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id',
    'title',
    'description',
    'instructions',
    'grading_rule',
    'passing_score',
    'status',
])]
#[Hidden(['grading_rule'])]
class Assessment extends Model
{
    use HasFactory;

    protected $table = 'the404_assessments';

    protected function casts(): array
    {
        return [
            'passing_score' => 'integer',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class, 'assessment_id');
    }
}
