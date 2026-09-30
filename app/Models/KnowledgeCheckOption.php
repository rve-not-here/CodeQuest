<?php

namespace App\Models;

use App\Models\Concerns\CurriculumVersioned;
use App\Models\Concerns\HasCurriculumVersion;
use Database\Factories\KnowledgeCheckOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['knowledge_check_question_id', 'order_num', 'option_text', 'is_correct'])]
#[Hidden(['is_correct'])]
class KnowledgeCheckOption extends Model implements CurriculumVersioned
{
    use HasCurriculumVersion;

    /** @use HasFactory<KnowledgeCheckOptionFactory> */
    use HasFactory;

    protected $table = 'the404_knowledge_check_options';

    /**
     * Options carry no version of their own: their material edits — notably
     * an is_correct flip, which changes verdicts — advance the parent
     * check's version instead.
     */
    public function curriculumVersionTarget(): Model
    {
        return $this->question()->firstOrFail()->knowledgeCheck()->firstOrFail();
    }

    /**
     * Material option fields: answer text, correctness, and sequence.
     *
     * @return array<int, string>
     */
    public function curriculumVersionMaterialFields(): array
    {
        return ['order_num', 'option_text', 'is_correct'];
    }

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    /** @return BelongsTo<KnowledgeCheckQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCheckQuestion::class, 'knowledge_check_question_id');
    }
}
