<?php

namespace App\Models;

use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Canonical skill vocabulary (US-905/US-906). One shared table: the spec
 * uses "Skill / Concept" and "skills/concepts" interchangeably and never
 * distinguishes them, so a second entity would invent a distinction that
 * does not exist.
 *
 * Skill keys are globally unique stable machine names (e.g. html.headings).
 * Historical evidence snapshots store keys, never labels or ids, so a label
 * edit or id shift can never reinterpret a recorded row.
 *
 * @property string $key
 * @property string $label
 * @property string|null $description
 */
#[Fillable([
    'key',
    'label',
    'description',
])]
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    protected $table = 'the404_skills';

    /** @return BelongsToMany<Mission, $this> */
    public function missions(): BelongsToMany
    {
        return $this->belongsToMany(Mission::class, 'the404_mission_skill', 'skill_id', 'mission_id');
    }

    /** @return BelongsToMany<KnowledgeCheckQuestion, $this> */
    public function knowledgeCheckQuestions(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeCheckQuestion::class, 'the404_knowledge_check_question_skill', 'skill_id', 'question_id');
    }

    /** @return BelongsToMany<Assessment, $this> */
    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(Assessment::class, 'the404_assessment_skill', 'skill_id', 'assessment_id');
    }
}
