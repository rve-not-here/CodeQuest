<?php

namespace App\Models;

use App\Models\Concerns\CurriculumVersioned;
use App\Models\Concerns\HasCurriculumVersion;
use Database\Factories\KnowledgeCheckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['mission_id', 'order_num', 'title', 'instructions', 'is_required', 'status', 'version'])]
class KnowledgeCheck extends Model implements CurriculumVersioned
{
    use HasCurriculumVersion;

    /** @use HasFactory<KnowledgeCheckFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'the404_knowledge_checks';

    /**
     * Check status is a lifecycle state (draft|published|archived), not
     * content: a status-only transition never bumps the version.
     *
     * @return array<int, string>
     */
    public function curriculumVersionExcludedFields(): array
    {
        return ['status'];
    }

    /**
     * Material check fields (§12): guidance content (instructions), the
     * required/optional gate (is_required — required checks gate local
     * progression, §4A), the check sequence (order_num), and mission
     * attribution (mission_id). The title is a display label, so it stays
     * version-silent. Question and option content versions through the
     * parent check via their own hook.
     *
     * @return array<int, string>
     */
    public function curriculumVersionMaterialFields(): array
    {
        return ['instructions', 'is_required', 'order_num', 'mission_id'];
    }

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'version' => 'integer',
        ];
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
