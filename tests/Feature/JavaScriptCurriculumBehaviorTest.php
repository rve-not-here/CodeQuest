<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionBehaviorTest;
use App\Models\Progress;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\LearningPathService;
use App\Services\MissionService;
use App\Services\ValidationService;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\CurriculumContentSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class JavaScriptCurriculumBehaviorTest extends TestCase
{
    use RefreshDatabase;

    private const BEHAVIOR_ORDERS = [1, 2, 3, 4, 5, 6, 7, 11, 12, 13, 14, 15, 16, 21, 22, 23, 24, 25, 31, 32, 33];

    private const TABLES = ['the404_courses', 'the404_sections', 'the404_missions', 'the404_mission_behavior_tests', 'the404_knowledge_checks', 'the404_knowledge_check_questions', 'the404_knowledge_check_options'];

    public function test_seeded_curriculum_has_real_behavior_contracts_and_stable_ordering(): void
    {
        $this->seed(CurriculumContentSeeder::class);
        $missions = $this->javaScriptMissions();

        $this->assertCount(35, $missions);
        $this->assertSame(self::BEHAVIOR_ORDERS, $missions->filter(fn (Mission $mission): bool => $mission->behaviorTests()->exists())->pluck('order_num')->all());
        $this->assertSame(range(1, 35), $missions->pluck('order_num')->all());
        $this->assertSame([3, 4, 3, 3, 3, 3, 3, 3, 3, 2, 2, 1, 2], $missions->groupBy('section_id')->map->count()->values()->all());

        foreach ($missions as $mission) {
            $this->assertSame($mission->course_id, $mission->section->course_id);
            $this->assertTrue(app(ValidationService::class)->validate($mission, $mission->solution_code)['passed'], $mission->title);
            foreach ($mission->behaviorTests as $test) {
                $configuration = $test->decodedConfiguration();
                $this->assertArrayNotHasKey('configuration', $test->toArray());
                $this->assertSame(1, (int) $test->active);
                if ($test->test_type === 'function') {
                    $this->assertIsString($configuration['function']);
                    $this->assertLessThanOrEqual(120, strlen($configuration['function']));
                    $this->assertNotEmpty($configuration['cases']);
                    foreach ($configuration['cases'] as $case) {
                        $this->assertIsArray($case['args']);
                        $this->assertArrayHasKey('expected', $case);
                    }
                    if (in_array($mission->order_num, [5, 21, 25], true)) {
                        $this->assertGreaterThanOrEqual(2, count($configuration['cases']));
                    }
                } else {
                    $this->assertSame('console', $test->test_type);
                    $this->assertNotEmpty($configuration['expected']);
                    foreach ($configuration['expected'] as $entry) {
                        $this->assertIsArray($entry);
                    }
                }
            }
        }

        $this->assertSame(18, MissionBehaviorTest::query()->where('test_type', 'console')->count());
        $this->assertSame(10, MissionBehaviorTest::query()->where('test_type', 'function')->count());
        $counts = [];
        foreach (self::TABLES as $table) {
            $counts[$table] = DB::table($table)->count();
        }
        $this->assertSame([3, 36, 95, 28, 12, 36, 108], array_values($counts));
    }

    public function test_reseeding_preserves_ids_definitions_and_student_history(): void
    {
        $this->seed(CurriculumContentSeeder::class);
        $before = $this->snapshot();
        $student = User::factory()->create();
        $mission = $this->javaScriptMissions()->first();
        $progress = Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);

        $this->seed(CurriculumContentSeeder::class);

        $this->assertSame($before, $this->snapshot());
        $this->assertModelExists($progress);
    }

    public function test_every_behavioral_reference_and_alternate_passes_and_starter_fails(): void
    {
        $this->seed(CurriculumContentSeeder::class);
        foreach ($this->javaScriptMissions() as $mission) {
            if (! $mission->behaviorTests()->exists()) {
                continue;
            }
            $this->assertFixture($mission, $mission->solution_code, true);
            $this->assertFixture($mission, $this->alternate($mission), true);
            $this->assertFixture($mission, $mission->broken_code, false);
            $comment = '// '.str_replace("\n", "\n// ", $mission->solution_code);
            $this->assertFixture($mission, $comment, false);
            $tests = $mission->behaviorTests->map(fn (MissionBehaviorTest $test): array => [
                'type' => $test->test_type, 'payload' => $test->decodedConfiguration(),
            ])->all();
            $this->assertSame('failed', $this->runTrustedFixture($comment, $tests)['status'], $mission->title);
        }
    }

    public function test_reusable_contracts_reject_hardcoded_results_wrong_logic_and_shared_state(): void
    {
        $this->seed(CurriculumContentSeeder::class);
        $greeting = $this->mission('Function_Protocol_Rebuild');
        $this->assertFixture($greeting, 'function greetOperator() { return "WELCOME, CHEN"; }', false);
        $this->assertFixture($greeting, 'const greetOperator = name => "WELCOME, " + name.toLowerCase();', false);
        $counter = $this->mission('Closure_Counter');
        $this->assertFixture($counter, 'const makeCounter = () => () => 1;', false);
        $this->assertFixture($counter, 'let n = 0; function makeCounter() { return () => ++n; }', false);
        $operator = $this->mission('Class_Blueprint');
        $this->assertFixture($operator, 'class Operator { constructor(name) {} report() { return "CHEN READY"; } }', false);
        $this->assertFixture($this->mission('Variable_Signal_Declare'), 'let signalStatus = "ONLINE"; const answer = "ONLINE";', false);
        $this->assertFixture($this->mission('Array_Database_Reconstruct'), 'let operators = []; console.log(3);', false);
        $this->assertFixture($this->mission('Object_Record_Restoration'), 'let operatorRecord = {name:"CHEN"}; console.log(operatorRecord.name);', false);
    }

    public function test_curriculum_grading_keeps_xp_progression_and_hidden_configuration_authoritative(): void
    {
        $this->seed([CurriculumContentSeeder::class, AchievementSeeder::class]);
        $student = User::factory()->create();
        $mission = $this->mission('Variable_Signal_Declare');
        $course = $mission->course;
        $course->update(['status' => 'active']);
        foreach (Course::query()->where('order_num', '<', $course->order_num)->get() as $earlierCourse) {
            $assessment = Assessment::factory()->create(['course_id' => $earlierCourse->id]);
            AssessmentAttempt::factory()->create(['assessment_id' => $assessment->id, 'user_id' => $student->id, 'status' => 'passed', 'score' => 100, 'created_at' => now(), 'passed_at' => now()]);
        }
        config()->set('grader.url', 'http://curriculum-grader.internal');
        Http::preventStrayRequests();
        Http::fake(['http://curriculum-grader.internal/grade' => function (Request $request) {
            return Http::response($this->runTrustedFixture($request['source'], $request['tests']));
        }]);

        $this->assertSame($mission->id, app(LearningPathService::class)->nextMission($student, $course)->id);
        $this->actingAs($student)->get(route('mission.challenge', $mission))
            ->assertOk()->assertDontSee('Required data')->assertDontSee('() =&gt; signalStatus', false);
        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => '// '.$mission->solution_code])
            ->assertSessionHas('mission_error');
        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertSame(0, (int) XpTransaction::query()->where('user_id', $student->id)->sum('amount'));

        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => $this->alternate($mission)])
            ->assertSessionHas('mission_success');
        $this->assertDatabaseHas('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertSame(50, (int) XpTransaction::query()->where('user_id', $student->id)->sum('amount'));
        $this->assertSame(2, app(LearningPathService::class)->nextMission($student, $course)->order_num);
        $this->assertSame(0, app(MissionService::class)->submit($student, $mission, $mission->solution_code)['xpAwarded']);
        Http::assertSent(fn (Request $request): bool => $request['tests'][1]['payload']['function'] === '() => signalStatus');

        config()->set('grader.url', '');
        $second = $this->mission('Arithmetic_Core_Repair');
        $result = app(MissionService::class)->submit($student, $second, $second->solution_code);
        $this->assertFalse($result['passed']);
        $this->assertSame(50, $result['xpBalance']);
        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id, 'mission_id' => $second->id]);
        $this->assertSame(2, XpTransaction::query()->where('user_id', $student->id)->count());
    }

    /** @return Collection<int, Mission> */
    private function javaScriptMissions(): Collection
    {
        return Mission::query()->whereHas('course', fn ($query) => $query->where('type', 'js'))->orderBy('order_num')->get();
    }

    private function mission(string $title): Mission
    {
        return Mission::query()->where('title', $title)->firstOrFail();
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function snapshot(): array
    {
        $snapshot = [];
        foreach (self::TABLES as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->map(function (object $row): array {
                $attributes = (array) $row;
                unset($attributes['created_at'], $attributes['updated_at']);

                return $attributes;
            })->all();
        }

        return $snapshot;
    }

    private function assertFixture(Mission $mission, string $source, bool $passes): void
    {
        $structural = app(ValidationService::class)->validate($mission, $source);
        $tests = $mission->behaviorTests->map(fn (MissionBehaviorTest $test): array => [
            'type' => $test->test_type, 'payload' => $test->decodedConfiguration(),
        ])->all();
        $result = $this->runTrustedFixture($source, $tests);
        $this->assertSame($passes, $structural['passed'] && $result['status'] === 'passed', $mission->title.' '.$source);
    }

    /**
     * Only repository-owned fixtures run here, following grader/test's runner
     * unit tests. Production submissions still require the container service.
     *
     * @param  list<array<string, mixed>>  $tests
     * @return array<string, mixed>
     */
    private function runTrustedFixture(string $source, array $tests): array
    {
        $process = new Process(['node', base_path('grader/src/runner.js')]);
        $process->setInput(json_encode(['source' => $source, 'tests' => $tests], JSON_THROW_ON_ERROR));
        $process->mustRun();

        return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }

    private function alternate(Mission $mission): string
    {
        return match ($mission->title) {
            'Variable_Signal_Declare' => "let signalStatus = 'ONLINE'; console.info(signalStatus);",
            'Arithmetic_Core_Repair' => 'let baseScore=100, bonusScore=50; let totalScore=bonusScore + baseScore; console.info(totalScore);',
            'Conditional_Gate_Restore' => 'let clearanceLevel=4; if(clearanceLevel >=3) console.info("ACCESS GRANTED"); else console.info("ACCESS DENIED");',
            'Loop_Transmitter_Repair' => 'for (let count=1; count<6; count+=1) console.info(count);',
            'Function_Protocol_Rebuild' => 'const greetOperator = operator => `WELCOME, ${operator.toUpperCase()}`;',
            'Array_Database_Reconstruct' => "const operators=['CHEN','REYES','OKAFOR']; console.info(operators.length);",
            'Object_Record_Restoration' => "const operatorRecord={active:true,clearance:4,name:'CHEN'}; console.info(operatorRecord.name);",
            'Closure_Counter' => 'const makeCounter = () => { let count=0; return () => count+=1; };',
            'Class_Blueprint' => 'class Operator { constructor(callsign) { this.name=callsign; } report() { return `${this.name} READY`; } }',
            'Map_Filter' => 'const readings=[-5,10,-2,20]; const clean=readings.filter(function(n){return n>=0;}).map(function(n){return n+n;}); console.info(clean);',
            'Find_Reduce' => 'const readings=[10,20,30]; const firstOver=readings.find(function(n){return n>15;}); const total=readings.reduce(function(a,b){return a+b;},0); console.info(firstOver,total);',
            default => str_replace(['console.log', '  ', "\n"], ['console.info', "\t", "\n\n"], $mission->solution_code),
        };
    }
}
