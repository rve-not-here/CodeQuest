<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CurriculumVersioningMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, list<string>>
     */
    private const ADDED_COLUMNS = [
        'the404_courses' => ['version'],
        'the404_sections' => ['version'],
        'the404_missions' => ['version'],
        'the404_assessments' => ['version'],
        'the404_knowledge_checks' => ['version'],
        'the404_progress' => ['mission_version'],
        'the404_assessment_attempts' => ['assessment_version', 'grading_rule_snapshot', 'passing_score_snapshot'],
        'the404_knowledge_check_attempts' => ['knowledge_check_version'],
        'the404_xp_transactions' => ['mission_version', 'assessment_version'],
    ];

    public function test_versioning_migration_backfills_legacy_rows_to_version_one(): void
    {
        foreach (self::ADDED_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn($column));
            }
        }

        $ids = [];

        try {
            $user = User::factory()->create();
            $ids['user'] = $user->id;

            $ids['course'] = DB::table('the404_courses')->insertGetId([
                'slug' => 'legacy-course',
                'name' => 'Legacy Course',
            ]);
            $ids['section'] = DB::table('the404_sections')->insertGetId([
                'course_id' => $ids['course'],
                'title' => 'Legacy Section',
            ]);
            $ids['mission'] = DB::table('the404_missions')->insertGetId([
                'course_id' => $ids['course'],
                'title' => 'Legacy Mission',
            ]);
            $ids['assessment'] = DB::table('the404_assessments')->insertGetId([
                'course_id' => $ids['course'],
                'title' => 'Legacy Challenge',
            ]);
            $ids['check'] = DB::table('the404_knowledge_checks')->insertGetId([
                'mission_id' => $ids['mission'],
                'title' => 'Legacy Check',
            ]);
            $ids['progress'] = DB::table('the404_progress')->insertGetId([
                'user_id' => $user->id,
                'mission_id' => $ids['mission'],
                'pts_earned' => 50,
            ]);
            $ids['assessment_attempt'] = DB::table('the404_assessment_attempts')->insertGetId([
                'assessment_id' => $ids['assessment'],
                'user_id' => $user->id,
            ]);
            $ids['check_attempt'] = DB::table('the404_knowledge_check_attempts')->insertGetId([
                'knowledge_check_id' => $ids['check'],
                'user_id' => $user->id,
                'attempt_number' => 1,
            ]);
            $ids['xp_mission'] = DB::table('the404_xp_transactions')->insertGetId([
                'user_id' => $user->id,
                'mission_id' => $ids['mission'],
                'amount' => 50,
                'type' => 'mission_completed',
            ]);
            $ids['xp_assessment'] = DB::table('the404_xp_transactions')->insertGetId([
                'user_id' => $user->id,
                'assessment_id' => $ids['assessment'],
                'amount' => 100,
                'type' => 'assessment_completed',
            ]);

            $migration = require base_path('database/migrations/2026_09_19_000001_add_curriculum_versioning.php');
            $migration->up();

            $this->assertSame(1, (int) DB::table('the404_courses')->where('id', $ids['course'])->value('version'));
            $this->assertSame(1, (int) DB::table('the404_sections')->where('id', $ids['section'])->value('version'));
            $this->assertSame(1, (int) DB::table('the404_missions')->where('id', $ids['mission'])->value('version'));
            $this->assertSame(1, (int) DB::table('the404_assessments')->where('id', $ids['assessment'])->value('version'));
            $this->assertSame(1, (int) DB::table('the404_knowledge_checks')->where('id', $ids['check'])->value('version'));
            $this->assertSame(1, (int) DB::table('the404_progress')->where('id', $ids['progress'])->value('mission_version'));
            $this->assertSame(1, (int) DB::table('the404_assessment_attempts')->where('id', $ids['assessment_attempt'])->value('assessment_version'));
            $this->assertSame(1, (int) DB::table('the404_knowledge_check_attempts')->where('id', $ids['check_attempt'])->value('knowledge_check_version'));

            $xpMission = DB::table('the404_xp_transactions')->where('id', $ids['xp_mission'])->first();
            $this->assertSame(1, (int) $xpMission->mission_version);
            $this->assertNull($xpMission->assessment_version);

            $xpAssessment = DB::table('the404_xp_transactions')->where('id', $ids['xp_assessment'])->first();
            $this->assertSame(1, (int) $xpAssessment->assessment_version);
            $this->assertNull($xpAssessment->mission_version);

            $attempt = DB::table('the404_assessment_attempts')->where('id', $ids['assessment_attempt'])->first();
            $this->assertNull($attempt->grading_rule_snapshot);
            $this->assertNull($attempt->passing_score_snapshot);
        } finally {
            // DDL commits implicitly on MariaDB, so test rows are removed
            // explicitly in dependency order rather than relying on rollback.
            foreach (['xp_mission', 'xp_assessment'] as $key) {
                if (isset($ids[$key])) {
                    DB::table('the404_xp_transactions')->where('id', $ids[$key])->delete();
                }
            }

            foreach ([
                'the404_progress' => 'progress',
                'the404_assessment_attempts' => 'assessment_attempt',
                'the404_knowledge_check_attempts' => 'check_attempt',
                'the404_knowledge_checks' => 'check',
                'the404_assessments' => 'assessment',
                'the404_missions' => 'mission',
                'the404_sections' => 'section',
                'the404_courses' => 'course',
            ] as $table => $key) {
                if (isset($ids[$key])) {
                    DB::table($table)->where('id', $ids[$key])->delete();
                }
            }

            if (isset($ids['user'])) {
                User::query()->where('id', $ids['user'])->delete();
            }
        }
    }
}
