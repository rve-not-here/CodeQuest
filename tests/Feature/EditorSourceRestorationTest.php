<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EditorSourceRestorationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('sources')]
    public function test_workspace_restores_exact_source_without_javascript_parse_errors(string $workspace, bool $oldInput, string $source, bool $malformed = false): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        if ($workspace === 'mission') {
            MissionDraft::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id, 'code' => $oldInput ? 'stored draft' : $source]);
            $url = route('mission.challenge', $mission);
        } else {
            Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
            $boss = Assessment::factory()->create(['course_id' => $course->id]);
            AssessmentAttempt::factory()->started()->create(['user_id' => $student->id, 'assessment_id' => $boss->id, 'code' => $oldInput ? 'stored attempt' : $source]);
            $url = route('assessment.show', $boss);
        }
        if ($oldInput) {
            $this->withSession(['_old_input' => ['code' => $malformed ? ['forged'] : $source]]);
        }
        $response = $this->actingAs($student)->get($url)->assertOk();
        $this->assertSame(1, preg_match('/^\s*var initial = (.*);$/m', $response->getContent(), $matches));
        $result = Process::timeout(5)->run(['node', '-e', 'process.stdout.write(JSON.stringify(require("node:vm").runInNewContext(process.argv[1], {})))', $matches[1]]);
        $this->assertTrue($result->successful(), $result->errorOutput());
        $this->assertSame($source, json_decode($result->output(), true, flags: JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('</script><script>globalThis', $response->getContent());
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    /** @return array<string, array{0: string, 1: bool, 2: string, 3?: bool}> */
    public static function sources(): array
    {
        $cases = [];
        $sources = [
            'empty source' => '',
            'multiline and quotes' => "const text = 'hello';\nconst quoted = \"world\";\nconsole.log(text);",
            'backslashes and unicode' => 'const pattern = /\\w+\\s/; const path = "C:\\tmp\\file"; /* 日本語 😀 */'."\u{2028}\u{2029}",
            'script boundary and template' => '</script><script>globalThis.injected = true</script>` ${value} & " \'',
        ];
        foreach (['mission', 'boss'] as $workspace) {
            foreach ([false, true] as $oldInput) {
                foreach ($sources as $name => $source) {
                    $cases[$workspace.' '.($oldInput ? 'old input ' : 'stored ').$name] = [$workspace, $oldInput, $source];
                }
            }
        }

        $cases['mission malformed old input'] = ['mission', true, 'stored draft', true];
        $cases['boss malformed old input'] = ['boss', true, 'stored attempt', true];

        return $cases;
    }
}
