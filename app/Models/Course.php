<?php

namespace App\Models;

use App\Models\Concerns\CurriculumVersioned;
use App\Models\Concerns\HasCurriculumVersion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'slug',
    'name',
    'type',
    'description',
    'status',
    'order_num',
    'version',
])]
class Course extends Model implements CurriculumVersioned
{
    use HasCurriculumVersion;
    use HasFactory;

    protected $table = 'the404_courses';

    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    /**
     * Course status is an access gate (active|locked|draft), not content: a
     * status-only transition seals use without changing the curriculum, so
     * it never bumps the version.
     *
     * @return array<int, string>
     */
    public function curriculumVersionExcludedFields(): array
    {
        return ['status'];
    }

    /**
     * Material course fields (§12): taught catalog content (description) and
     * the progression sequence (order_num, §3 ordering — it decides which
     * course unlocks next). Name, slug, and type are identity labels with no
     * completion, scoring, eligibility, or unlock effect, so they stay
     * version-silent.
     *
     * @return array<int, string>
     */
    public function curriculumVersionMaterialFields(): array
    {
        return ['description', 'order_num'];
    }

    /** @return HasMany<Mission, $this> */
    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class, 'course_id');
    }

    /** @return HasMany<Section, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'course_id')->orderBy('order_num');
    }

    /**
     * @return HasOne<Assessment, $this>
     */
    public function assessment(): HasOne
    {
        return $this->hasOne(Assessment::class, 'course_id');
    }

    /** @return BelongsToMany<Classroom, $this> */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'the404_classroom_courses', 'course_id', 'classroom_id')
            ->withTimestamps();
    }
}
