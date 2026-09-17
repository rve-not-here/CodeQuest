<?php

namespace App\Models;

use Database\Factories\KnowledgeCheckQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['knowledge_check_id', 'order_num', 'type', 'prompt', 'code_snippet', 'explanation'])]
#[Hidden(['explanation'])]
class KnowledgeCheckQuestion extends Model
{
    /** @use HasFactory<KnowledgeCheckQuestionFactory> */
    use HasFactory;

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_CODE_READING = 'code_reading';

    public const TYPE_CONCEPT_IDENTIFICATION = 'concept_identification';

    protected $table = 'the404_knowledge_check_questions';

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
}
