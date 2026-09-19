<?php

namespace App\Models;

use App\Models\Concerns\CurriculumVersioned;
use App\Models\Concerns\HasCurriculumVersion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
    'version',
])]
class Mission extends Model implements CurriculumVersioned
{
    use HasCurriculumVersion;
    use HasFactory;

    protected $table = 'the404_missions';

    /**
     * Material mission fields (§12): task instructions (description),
     * starter code and challenge content (broken_code, target_html),
     * validation rules and reference solution (validate_rule,
     * solution_code), hint content (hints), the completion reward (points),
     * and progression structure (order_num, section_id). Title and
     * difficulty are display labels consumed by no domain logic, so they
     * stay version-silent.
     *
     * @return array<int, string>
     */
    public function curriculumVersionMaterialFields(): array
    {
        return [
            'description',
            'broken_code',
            'target_html',
            'validate_rule',
            'solution_code',
            'hints',
            'points',
            'order_num',
            'section_id',
        ];
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

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

    /** @return HasMany<KnowledgeCheck, $this> */
    public function knowledgeChecks(): HasMany
    {
        return $this->hasMany(KnowledgeCheck::class, 'mission_id')->orderBy('order_num');
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'the404_mission_skill', 'mission_id', 'skill_id');
    }
}
