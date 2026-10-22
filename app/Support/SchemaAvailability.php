<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Availability of the optional, additive schema introduced by the
 * 2026_10_22 migrations.
 *
 * The application schema is applied on its own, explicitly approved step,
 * and a deployed database can legitimately lag behind the code for a
 * while. The code must therefore never assume an optional column or
 * table exists: a missing `the404_activity.mission_id` turned every
 * Activity write into a 500 (a logout could not be recorded), and a
 * missing `the404_assessment_behavior_tests` turned every Boss Challenge
 * submission into a 500.
 *
 * Each probe is answered once per process and names the pending
 * migration in the log when the schema is behind, so a deployment that
 * skipped the DDL is visible instead of silently degrading.
 */
final class SchemaAvailability
{
    /**
     * @var array<string, bool>
     */
    private static array $cache = [];

    /**
     * Can Activity rows be attributed to a mission?
     */
    public static function activityMissionId(): bool
    {
        return self::remember(
            'activity_mission_id',
            static fn (): bool => Schema::hasColumn('the404_activity', 'mission_id'),
            '2026_10_22_000004_add_mission_id_to_the404_activity_table',
            'the404_activity.mission_id',
        );
    }

    /**
     * Can a Boss attempt carry its grading evidence?
     */
    public static function assessmentEvidenceColumns(): bool
    {
        return self::remember(
            'assessment_evidence_columns',
            static fn (): bool => Schema::hasColumn('the404_assessment_attempts', 'tests_total')
                && Schema::hasColumn('the404_assessment_attempts', 'tests_passed')
                && Schema::hasColumn('the404_assessment_attempts', 'failure_feedback'),
            '2026_10_22_000003_add_grading_evidence_to_assessment_attempts',
            'the404_assessment_attempts grading evidence columns',
        );
    }

    /**
     * Can hidden behavioural tests exist for a Boss Challenge?
     */
    public static function assessmentBehaviorTests(): bool
    {
        return self::remember(
            'assessment_behavior_tests',
            static fn (): bool => Schema::hasTable('the404_assessment_behavior_tests'),
            '2026_10_22_000002_create_the404_assessment_behavior_tests_table',
            'the404_assessment_behavior_tests',
        );
    }

    /**
     * Forget the cached probes. Used by tests that fake a lagging schema.
     */
    public static function reset(): void
    {
        self::$cache = [];
    }

    /**
     * @param  callable(): bool  $probe
     */
    private static function remember(string $key, callable $probe, string $migration, string $subject): bool
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $available = $probe();

        if (! $available) {
            Log::warning('Optional schema is missing; the feature runs without it until the migration is applied.', [
                'subject' => $subject,
                'pending_migration' => $migration,
            ]);
        }

        return self::$cache[$key] = $available;
    }
}
