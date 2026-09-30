<?php

namespace App\Models;

use Database\Factories\MissionBehaviorTestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One hidden behavioral check for a coding mission. System-owned grading
 * configuration: the configuration payload never leaves the server. It is
 * hidden from every array and JSON dump, and no student route reads it.
 *
 * function configuration shape:
 * {function: string, cases: list<array{args: list<mixed>, expected: mixed}>}
 *
 * console configuration shape:
 * {expected: list<list<mixed>>} (ordered console.log/info argument lists)
 */
class MissionBehaviorTest extends Model
{
    /** @use HasFactory<MissionBehaviorTestFactory> */
    use HasFactory;

    protected $table = 'the404_mission_behavior_tests';

    protected $fillable = [
        'mission_id',
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

    public const TYPE_FUNCTION = 'function';

    public const TYPE_CONSOLE = 'console';

    /**
     * @return BelongsTo<Mission, $this>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
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
