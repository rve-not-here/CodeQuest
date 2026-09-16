<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'order_num', 'title', 'description'])]
class Section extends Model
{
    use HasFactory;

    protected $table = 'the404_sections';

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class, 'section_id');
    }
}
