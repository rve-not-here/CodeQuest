<?php

namespace App\Models;

use App\Support\BehaviorTestDefinition;
use Database\Factories\AssessmentBehaviorTestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One hidden behavioural check for a Boss Challenge. System-owned grading
 * configuration: the configuration payload never leaves the server. It is
 * hidden from every array and JSON dump, and no student route reads it.
 *
 * Without any active rows the assessment keeps its historical pattern-only
 * evaluation. With rows, every test must pass for the attempt to pass.
 */
class AssessmentBehaviorTest extends Model
{
    /** @use HasFactory<AssessmentBehaviorTestFactory> */
    use HasFactory;

    protected $table = 'the404_assessment_behavior_tests';

    protected $fillable = [
        'assessment_id',
        'name',
        'test_type',
        'configuration',
        'order_num',
        'active',
    ];

    /**
     * The test definition must never serialize. Students see only counts.
     *
     * @var list<string>
     */
    protected $hidden = ['configuration'];

    public const TYPE_FUNCTION = BehaviorTestDefinition::TYPE_FUNCTION;

    public const TYPE_CONSOLE = BehaviorTestDefinition::TYPE_CONSOLE;

    /** @return BelongsTo<Assessment, $this> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    /**
     * Decoded test definition. Shape varies by test type and is validated
     * field by field by the grading service, so this stays a plain array.
     *
     * @return array<string|int, mixed>
     */
    public function decodedConfiguration(): array
    {
        $decoded = json_decode($this->configuration ?? '', true);

        return is_array($decoded) ? $decoded : [];
    }
}
