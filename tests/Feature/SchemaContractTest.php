<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AdminAudit;
use App\Models\Notification;
use App\Models\Progress;
use App\Models\UserAchievement;
use App\Models\XpTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_uniqueness_and_created_at_only_models_match_the_fixture_schema(): void
    {
        foreach ([
            'the404_progress' => ['user_id', 'mission_id'],
            'the404_notifications' => ['user_id', 'dedupe_key'],
            'the404_user_achievements' => ['user_id', 'achievement_id'],
            'the404_assessments' => ['course_id'],
        ] as $table => $columns) {
            $indexes = Schema::getIndexes($table);
            $this->assertTrue(collect($indexes)->contains(fn (array $index): bool => $index['unique'] && $index['columns'] === $columns), $table.' lacks its academic uniqueness contract.');
        }
        foreach ([new Activity, new AdminAudit, new Notification, new Progress, new UserAchievement, new XpTransaction] as $model) {
            $this->assertFalse($model->usesTimestamps());
            $this->assertFalse(Schema::hasColumn($model->getTable(), 'updated_at'));
        }
        foreach (['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $snapshot = getenv('CODEQUEST_SCHEMA_SNAPSHOT');
        if (is_string($snapshot) && $snapshot !== '') {
            $this->assertStringStartsWith('/tmp/', $snapshot);
            $tables = [];
            foreach (Schema::getTables() as $table) {
                $name = $table['name'];
                $tables[$name] = ['table' => $table, 'columns' => Schema::getColumns($name), 'indexes' => Schema::getIndexes($name), 'foreign_keys' => Schema::getForeignKeys($name)];
            }
            $this->assertNotFalse(file_put_contents($snapshot, json_encode($tables, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)));
        }
    }
}
