<?php

namespace App\Models;

use Database\Factories\KnowledgeCheckResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'knowledge_check_attempt_id',
    'knowledge_check_question_id',
    'selected_option_id',
    'correct_option_id',
    'is_correct',
    'prompt_snapshot',
    'selected_option_snapshot',
    'correct_option_snapshot',
    'explanation_snapshot',
])]
#[Hidden(['correct_option_id', 'correct_option_snapshot'])]
class KnowledgeCheckResponse extends Model
{
    /** @use HasFactory<KnowledgeCheckResponseFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'the404_knowledge_check_responses';

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<KnowledgeCheckAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCheckAttempt::class, 'knowledge_check_attempt_id');
    }

    /** @return BelongsTo<KnowledgeCheckQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCheckQuestion::class, 'knowledge_check_question_id');
    }

    /** @return BelongsTo<KnowledgeCheckOption, $this> */
    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCheckOption::class, 'selected_option_id');
    }

    /** @return BelongsTo<KnowledgeCheckOption, $this> */
    public function correctOption(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCheckOption::class, 'correct_option_id');
    }
}
