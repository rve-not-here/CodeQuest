<?php

namespace App\Models;

use App\Models\Concerns\CurriculumVersioned;
use App\Models\Concerns\HasCurriculumVersion;
use Database\Factories\KnowledgeCheckQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['knowledge_check_id', 'order_num', 'type', 'prompt', 'code_snippet', 'explanation'])]
#[Hidden(['explanation'])]
class KnowledgeCheckQuestion extends Model implements CurriculumVersioned
{
    use HasCurriculumVersion;

    /** @use HasFactory<KnowledgeCheckQuestionFactory> */
    use HasFactory;

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_CODE_READING = 'code_reading';

    public const TYPE_CONCEPT_IDENTIFICATION = 'concept_identification';

    protected $table = 'the404_knowledge_check_questions';

    /**
     * Questions carry no version of their own: their material edits advance
     * the parent check, so the check version always reflects the definition
     * students actually face.
     */
    public function curriculumVersionTarget(): Model
    {
        return $this->knowledgeCheck()->firstOrFail();
    }

    /**
     * Material question fields: the prompt, supporting code, feedback, type,
     * and sequence. Every persistent content field is material here.
     *
     * @return array<int, string>
     */
    public function curriculumVersionMaterialFields(): array
    {
        return ['type', 'prompt', 'code_snippet', 'explanation', 'order_num'];
    }

    /** @return BelongsTo<KnowledgeCheck, $this> */
    public function knowledgeCheck(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCheck::class, 'knowledge_check_id');
    }

    /** @return HasMany<KnowledgeCheckOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(KnowledgeCheckOption::class, 'knowledge_check_question_id')->orderBy('order_num');
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'the404_knowledge_check_question_skill', 'question_id', 'skill_id');
    }
}
