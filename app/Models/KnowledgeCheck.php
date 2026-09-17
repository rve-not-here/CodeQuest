<?php

namespace App\Models;

use Database\Factories\KnowledgeCheckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['mission_id', 'order_num', 'title', 'instructions', 'is_required', 'status'])]
class KnowledgeCheck extends Model
{
    /** @use HasFactory<KnowledgeCheckFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'the404_knowledge_checks';

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    /** @return BelongsTo<Mission, $this> */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }

    /** @return HasMany<KnowledgeCheckQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(KnowledgeCheckQuestion::class, 'knowledge_check_id')->orderBy('order_num');
    }

    /** @return HasMany<KnowledgeCheckAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(KnowledgeCheckAttempt::class, 'knowledge_check_id');
    }
}
