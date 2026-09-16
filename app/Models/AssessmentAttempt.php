<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $status
 * @property int|null $score
 * @property Carbon|null $passed_at
 * @property Carbon|null $submitted_at
 */
#[Fillable([
    'assessment_id',
    'user_id',
    'code',
    'submitted_at',
])]
class AssessmentAttempt extends Model
{
    use HasFactory;

    protected $table = 'the404_assessment_attempts';

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'passed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Assessment, $this> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
