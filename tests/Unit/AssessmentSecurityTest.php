<?php

namespace Tests\Unit;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\User;
use App\Services\AssessmentService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private AssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);

        $this->service = app(AssessmentService::class);
    }

    public function test_evaluation_path_never_calls_a_code_execution_builtin(): void
    {
        $files = [
            app_path('Services/ValidationService.php'),
            app_path('Services/AssessmentService.php'),
        ];

        $forbidden = ['eval(', 'exec(', 'shell_exec(', 'system(', 'passthru(', 'proc_open('];

        foreach ($files as $file) {
            $source = file_get_contents($file);

            foreach ($forbidden as $grammar) {
                $this->assertStringNotContainsString(
                    $grammar,
                    $source,
                    basename($file).' must not call '.$grammar.'.'
                );
            }
        }
    }

    public function test_a_javascript_submission_is_matched_as_pattern_not_executed_server_side(): void
    {
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create([
            'grading_rule' => json_encode([
                ['type' => 'contains', 'value' => 'function', 'label' => 'Function declared'],
                ['type' => 'contains', 'value' => 'addEventListener', 'label' => 'Button wiring'],
            ]),
            'passing_score' => 100,
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'code' => "function boom() { eval(\"process.exit(1)\"); }\nbtn.addEventListener('click', boom);",
        ]);

        $result = $this->service->evaluateAttempt($user, $attempt);

        $this->assertSame(100, $result->score);
        $this->assertSame('passed', $result->status);
    }

    public function test_an_executable_looking_submission_has_no_side_effect_and_scores_by_pattern_only(): void
    {
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create([
            'grading_rule' => json_encode([
                ['type' => 'contains', 'value' => 'child_process', 'label' => 'Child process reference'],
            ]),
            'passing_score' => 70,
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'code' => "const cp = require('child_process'); cp.execSync('rm -rf /');",
        ]);

        $result = $this->service->evaluateAttempt($user, $attempt);

        $this->assertSame(100, $result->score);
        $this->assertSame('passed', $result->status);
    }

    public function test_grading_rule_is_never_exposed_in_the_evaluation_response(): void
    {
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create([
            'grading_rule' => json_encode([
                ['type' => 'contains', 'value' => 'S3CR3T-GRADING-MARKER', 'label' => 'Secret structure'],
                ['type' => 'contains', 'value' => 'public-marker', 'label' => 'Public marker'],
            ]),
            'passing_score' => 70,
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'code' => 'public-marker',
        ]);

        $this->service->evaluateAttempt($user, $attempt);

        $result = $this->service->attemptResult($user, $attempt);

        $this->assertArrayNotHasKey('grading_rule', $result);
        $this->assertStringNotContainsString('S3CR3T-GRADING-MARKER', json_encode($result));
    }

    public function test_grading_rule_is_hidden_on_assessment_serialization(): void
    {
        $assessment = Assessment::factory()->create([
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'hidden-secret']]),
        ]);

        $this->assertArrayNotHasKey('grading_rule', $assessment->toArray());
        $this->assertStringNotContainsString('hidden-secret', json_encode($assessment->toArray()));
    }
}
