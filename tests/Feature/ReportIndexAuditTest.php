<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\MissionService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * US-1011 MariaDB index audit. Runs EXPLAIN over the canonical reporting
 * query shapes on a seeded fixture and proves the optimizer never falls
 * back to a full table scan. SQLite skips this file: query plans are a
 * production-engine concern.
 */
class ReportIndexAuditTest extends TestCase
{
    use RefreshDatabase;

    private static int $orderSequence = 21000;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('MariaDB index audit only.');
        }

        $this->seed(AchievementSeeder::class);
    }

    /**
     * @return list<object{key: string|null, type: string}>
     */
    private function explain(string $sql, array $bindings = []): array
    {
        return DB::select('EXPLAIN '.$sql, $bindings);
    }

    private function assertNoFullScan(array $plan, string $query): void
    {
        foreach ($plan as $row) {
            $this->assertNotSame(
                'ALL',
                strtoupper((string) ($row->type ?? '')),
                "Full table scan in {$query} (key=".var_export($row->key ?? null, true).')'
            );
        }

        $this->assertNotEmpty($plan);
    }

    public function test_reporting_query_shapes_use_indexes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $students = User::factory()->count(5)->create(['role' => 'student'])->all();

        $course = new Course([
            'slug' => 'alpha',
            'name' => 'Alpha',
            'type' => 'html',
            'status' => 'active',
            'order_num' => ++self::$orderSequence,
        ]);
        $course->save();

        $section = new Section([
            'course_id' => $course->id,
            'order_num' => self::$orderSequence,
            'title' => 'Section',
        ]);
        $section->save();

        $mission = new Mission([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => self::$orderSequence,
            'title' => 'Alpha One',
            'difficulty' => 'EASY',
            'points' => 50,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'version' => 1,
        ]);
        $mission->save();

        foreach ($students as $student) {
            $result = app(MissionService::class)->submit($student, $mission, '<h1>Title</h1>');
            $this->assertTrue($result['passed']);
        }

        $userIds = collect($students)->pluck('id')->all();

        $this->assertNoFullScan($this->explain(
            'SELECT p.user_id, m.course_id, COUNT(DISTINCT p.mission_id) AS done '
            .'FROM the404_progress AS p INNER JOIN the404_missions AS m ON m.id = p.mission_id '
            .'WHERE p.user_id IN ('.implode(',', array_fill(0, count($userIds), '?')).') AND p.mission_id = ? '
            .'GROUP BY p.user_id, m.course_id',
            [...$userIds, $mission->id],
        ), 'batch progress read');

        $this->assertNoFullScan($this->explain(
            'SELECT a.user_id, a.assessment_id, MIN(a.id) AS first_id '
            .'FROM the404_assessment_attempts AS a INNER JOIN the404_assessments AS s ON s.id = a.assessment_id '
            .'WHERE a.user_id IN ('.implode(',', array_fill(0, count($userIds), '?')).') AND s.course_id = ? '
            .'GROUP BY a.user_id, a.assessment_id',
            [...$userIds, $course->id],
        ), 'batch attempt read');

        $this->assertNoFullScan($this->explain(
            'SELECT user_id, mission_id, count(*) AS wrong_count FROM the404_xp_transactions '
            .'WHERE user_id IN ('.implode(',', array_fill(0, count($userIds), '?')).') '
            ."AND type = 'wrong_submission' AND mission_id = ? GROUP BY user_id, mission_id",
            [...$userIds, $mission->id],
        ), 'wrong-submission read');

        $this->assertNoFullScan($this->explain(
            'SELECT s.student_id, c.course_id FROM the404_classroom_students AS s '
            .'INNER JOIN the404_classroom_courses AS c ON c.classroom_id = s.classroom_id '
            .'WHERE s.classroom_id IN (SELECT classroom_id FROM the404_classroom_teachers WHERE teacher_id = ?)',
            [$teacher->id],
        ), 'classroom pair read');
    }
}
