<?php

namespace App\Models;

use App\Models\Concerns\CurriculumVersioned;
use App\Models\Concerns\HasCurriculumVersion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id',
    'title',
    'description',
    'instructions',
    'grading_rule',
    'passing_score',
    'status',
    'version',
])]
#[Hidden(['grading_rule'])]
class Assessment extends Model implements CurriculumVersioned
{
    use HasCurriculumVersion;
    use HasFactory;

    protected $table = 'the404_assessments';

    /**
     * Assessment status is an access gate (active|locked|draft), not content:
     * a status-only transition seals the Boss Challenge without changing it,
     * so it never bumps the version.
     *
     * @return array<int, string>
     */
    public function curriculumVersionExcludedFields(): array
    {
        return ['status'];
    }

    /**
     * Material assessment fields (§12): task content (description,
     * instructions), Boss Challenge criteria (grading_rule), and the pass
     * requirement (passing_score). The title is a display label with no
     * scoring or completion effect, so it stays version-silent.
     *
     * @return array<int, string>
     */
    public function curriculumVersionMaterialFields(): array
    {
        return ['description', 'instructions', 'grading_rule', 'passing_score'];
    }

    protected function casts(): array
    {
        return [
            'passing_score' => 'integer',
            'version' => 'integer',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class, 'assessment_id');
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'the404_assessment_skill', 'assessment_id', 'skill_id');
    }
}
