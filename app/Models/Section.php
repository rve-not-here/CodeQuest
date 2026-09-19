<?php

namespace App\Models;

use App\Models\Concerns\CurriculumVersioned;
use App\Models\Concerns\HasCurriculumVersion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'order_num', 'title', 'description', 'version'])]
class Section extends Model implements CurriculumVersioned
{
    use HasCurriculumVersion;
    use HasFactory;

    protected $table = 'the404_sections';

    /**
     * Material section fields (§12): taught overview content (description)
     * and the learning sequence (order_num, §3 ordering). The title is a
     * structural label with no outcome effect, so it stays version-silent.
     *
     * @return array<int, string>
     */
    public function curriculumVersionMaterialFields(): array
    {
        return ['description', 'order_num'];
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class, 'section_id');
    }
}
