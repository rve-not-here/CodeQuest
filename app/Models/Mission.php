<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'course_id',
    'section_id',
    'order_num',
    'title',
    'difficulty',
    'description',
    'broken_code',
    'solution_code',
    'target_html',
    'validate_rule',
    'hints',
    'points',
])]
class Mission extends Model
{
    use HasFactory;

    protected $table = 'the404_missions';

    /**
     * Never serialize the reference solution or validation rules. A student
     * must not be able to read either through any JSON/array dump of a
     * mission, only ask for the solution through the XP-gated reveal flow.
     *
     * @var array<int, string>
     */
    protected $hidden = ['solution_code', 'validate_rule'];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(Progress::class, 'mission_id');
    }

    public function draft(): HasOne
    {
        return $this->hasOne(MissionDraft::class, 'mission_id');
    }
}
