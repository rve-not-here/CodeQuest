<?php

namespace App\Models;

use Database\Factories\KnowledgeCheckAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['knowledge_check_id', 'knowledge_check_version', 'user_id', 'attempt_number', 'started_at'])]
class KnowledgeCheckAttempt extends Model
{
    /** @use HasFactory<KnowledgeCheckAttemptFactory> */
    use HasFactory;

    public const STATUS_STARTED = 'started';

    public const STATUS_SUBMITTED = 'submitted';

    protected $table = 'the404_knowledge_check_attempts';

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'knowledge_check_version' => 'integer',
            'score' => 'integer',
            'total_questions' => 'integer',
            'percentage' => 'integer',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<KnowledgeCheck, $this> */
    public function knowledgeCheck(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCheck::class, 'knowledge_check_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<KnowledgeCheckResponse, $this> */
    public function responses(): HasMany
    {
        return $this->hasMany(KnowledgeCheckResponse::class, 'knowledge_check_attempt_id');
    }
}
