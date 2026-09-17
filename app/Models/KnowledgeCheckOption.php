<?php

namespace App\Models;

use Database\Factories\KnowledgeCheckOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['knowledge_check_question_id', 'order_num', 'option_text', 'is_correct'])]
#[Hidden(['is_correct'])]
class KnowledgeCheckOption extends Model
{
    /** @use HasFactory<KnowledgeCheckOptionFactory> */
    use HasFactory;

    protected $table = 'the404_knowledge_check_options';

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
