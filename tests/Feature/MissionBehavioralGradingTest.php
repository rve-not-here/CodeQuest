<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionBehaviorTest;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Structural plus hidden behavioral grading through the real submit flow.
 * The grader transport is faked at the HTTP layer; runner behavior itself
 * is proven by the grader/ unit suites. Every test here asserts the
 * Laravel-side contract: authority, hybrid logic, fail-closed behavior,
 * and hidden-test confidentiality.
 */
class MissionBehavioralGradingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);

        config()->set('grader.url', 'http://grader.internal');
        config()->set('grader.token', 'test-token');
    }

    /** @return array{course: Course, section: Section, mission: Mission} */
    private function behavioralMission(?string $validateRule = null): array
    {
        $course = Course::factory()->create(['status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'validate_rule' => $validateRule,
            'points' => 50,
        ]);
        MissionBehaviorTest::factory()->create([
            'mission_id' => $mission->id,
            'order_num' => 1,
        ]);

        return compact('course', 'section', 'mission');
    }

    /** @return array<string, mixed> */
    private function passedResult(int $total = 2): array
    {
        return [
            'status' => 'passed',
            'tests_total' => $total,
            'tests_passed' => $total,
            'duration_ms' => 31,
            'error_type' => null,
        ];
    }

    public function test_legacy_mission_never_calls_the_grader(): void
    {
        Http::fake();
        $student = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 50,
        ]);

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => '<h1>Title</h1>'])
            ->assertSessionHas('mission_success');

        Http::assertNothingSent();
        $this->assertSame(50, (int) XpTransaction::query()->where('user_id', $student->id)->sum('amount'));
    }

    public function test_inactive_behavior_tests_keep_the_legacy_path(): void
    {
        Http::fake();
        $student = User::factory()->create();
        ['mission' => $mission] = $this->behavioralMission(json_encode([['type' => 'contains', 'value' => '<h1>']]));
        MissionBehaviorTest::query()->update(['active' => false]);

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => '<h1>Title</h1>'])
            ->assertSessionHas('mission_success');

        Http::assertNothingSent();
    }

    #[DataProvider('validImplementations')]
    public function test_valid_implementations_pass_behavioral_grading(string $code): void
    {
        Http::fake(['*' => Http::response($this->passedResult(), 200)]);
        $student = User::factory()->create();
        ['mission' => $mission] = $this->behavioralMission();

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => $code])
            ->assertSessionHas('mission_success');

        $this->assertDatabaseHas('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertSame(50, (int) XpTransaction::query()->where('user_id', $student->id)->sum('amount'));
    }

    /** @return list<array{0: string}> */
    public static function validImplementations(): array
    {
        return [
            'declaration' => ['function add(a, b) { return a + b; }'],
            'arrow' => ['const add = (a, b) => a + b;'],
            'expression' => ['const add = function (a, b) { return a + b; };'],
        ];
    }

    public function test_failed_behavioral_grading_deducts_and_reports_counts(): void
    {
        Http::fake(['*' => Http::response([
            'status' => 'failed',
            'tests_total' => 4,
            'tests_passed' => 3,
            'duration_ms' => 28,
            'error_type' => 'assertion',
        ], 200)]);
        $student = User::factory()->create();
        ['mission' => $mission] = $this->behavioralMission();

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => 'function add() { return 8; }'])
            ->assertSessionHas('mission_error', function (array $error): bool {
                return str_contains($error['message'], '3 of 4 hidden tests passed');
            });

        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'type' => 'wrong_submission',
        ]);
        $this->assertSame(0, (int) XpTransaction::query()->where('user_id', $student->id)->sum('amount'));
    }

    public function test_structural_failure_never_reaches_the_grader(): void
    {
        Http::fake(['*' => Http::response($this->passedResult(), 200)]);
        $student = User::factory()->create();
        ['mission' => $mission] = $this->behavioralMission(json_encode([['type' => 'contains', 'value' => '<h1>']]));

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => 'no headings here'])
            ->assertSessionHas('mission_error');

        Http::assertNothingSent();
        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
    }

    public function test_injected_grading_fields_cannot_influence_the_outcome(): void
    {
        Http::fake(['*' => Http::response($this->passedResult(), 200)]);
        $student = User::factory()->create();
        $other = User::factory()->create();
        ['mission' => $mission] = $this->behavioralMission();

        $this->actingAs($student)->post(route('mission.submit', $mission), [
            'code' => 'function add(a, b) { return a + b; }',
            'passed' => true,
            'score' => 100,
            'xp' => 999999,
            'testsPassed' => 999,
            'gradingResult' => 'passed',
            'user_id' => $other->id,
        ])->assertSessionHas('mission_success');

        $this->assertSame(50, (int) XpTransaction::query()->where('user_id', $student->id)->sum('amount'));
        $this->assertSame(0, XpTransaction::query()->where('user_id', $other->id)->count());
        $this->assertDatabaseMissing('the404_progress', ['user_id' => $other->id]);
    }

    public function test_grader_timeout_fails_closed_without_deduction(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('cURL error 28: timeout');
        });
        $student = User::factory()->create();
        ['mission' => $mission] = $this->behavioralMission();

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => 'function add(a, b) { return a + b; }'])
            ->assertSessionHas('mission_error', function (array $error): bool {
                return str_contains($error['message'], 'unavailable');
            });

        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertSame(0, XpTransaction::query()->where('user_id', $student->id)->count());
    }

    public function test_grader_http_error_fails_closed(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);
        $student = User::factory()->create();
        ['mission' => $mission] = $this->behavioralMission();

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => 'function add(a, b) { return a + b; }'])
            ->assertSessionHas('mission_error');

        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertSame(0, XpTransaction::query()->where('user_id', $student->id)->count());
    }

    public function test_syntax_error_is_an_academic_failure(): void
    {
        Http::fake(['*' => Http::response([
            'status' => 'failed',
            'tests_total' => 2,
            'tests_passed' => 0,
            'duration_ms' => 12,
            'error_type' => 'syntax',
        ], 200)]);
        $student = User::factory()->create();
        ['mission' => $mission] = $this->behavioralMission();

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => 'function add(a, b) { return '])
            ->assertSessionHas('mission_error');

        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertDatabaseHas('the404_xp_transactions', [
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'type' => 'wrong_submission',
        ]);
    }

    public function test_unconfigured_grader_fails_closed(): void
    {
        config()->set('grader.url', '');
        Http::fake();
        $student = User::factory()->create();
        ['mission' => $mission] = $this->behavioralMission();

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => 'function add(a, b) { return a + b; }'])
            ->assertSessionHas('mission_error', function (array $error): bool {
                return str_contains($error['message'], 'unavailable');
            });

        Http::assertNothingSent();
        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertSame(0, XpTransaction::query()->where('user_id', $student->id)->count());
    }

    /**
     * Semantically impossible grader responses fail closed: no completion,
     * no XP, and no wrong-submission deduction, since malformed
     * infrastructure data must never become an academic failure.
     *
     * @param  array<string, mixed>  $response
     */
    #[DataProvider('malformedGraderResponses')]
    public function test_malformed_grader_response_fails_closed(array $response): void
    {
        Http::fake(['*' => Http::response($response, 200)]);
        $student = User::factory()->create();
        ['mission' => $mission] = $this->behavioralMission();

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => 'function add(a, b) { return a + b; }'])
            ->assertSessionHas('mission_error', function (array $error): bool {
                return str_contains($error['message'], 'unavailable');
            });

        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertSame(0, XpTransaction::query()->where('user_id', $student->id)->count());
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function malformedGraderResponses(): array
    {
        $base = ['duration_ms' => 10, 'error_type' => null];

        return [
            'missing keys' => [['nonsense' => true]],
            'failed with zero tests' => [['status' => 'failed', 'tests_total' => 0, 'tests_passed' => 0] + $base],
            'failed with all passing' => [['status' => 'failed', 'tests_total' => 4, 'tests_passed' => 4] + $base],
            'passed with zero tests' => [['status' => 'passed', 'tests_total' => 0, 'tests_passed' => 0] + $base],
            'passed with a failure' => [['status' => 'passed', 'tests_total' => 4, 'tests_passed' => 3] + $base],
            'passed carrying an error type' => [['status' => 'passed', 'tests_total' => 2, 'tests_passed' => 2, 'duration_ms' => 10, 'error_type' => 'assertion']],
            'negative duration' => [['status' => 'failed', 'tests_total' => 4, 'tests_passed' => 3, 'duration_ms' => -5, 'error_type' => 'assertion']],
            'passed above total' => [['status' => 'passed', 'tests_total' => 2, 'tests_passed' => 3] + $base],
            'unknown status' => [['status' => 'confused', 'tests_total' => 2, 'tests_passed' => 2] + $base],
            'wrong field types' => [['status' => 'passed', 'tests_total' => '2', 'tests_passed' => 2] + $base],
            'unknown error type' => [['status' => 'failed', 'tests_total' => 4, 'tests_passed' => 3, 'duration_ms' => 10, 'error_type' => 'mind-control']],
        ];
    }

    public function test_hidden_test_configuration_never_reaches_the_student(): void
    {
        $secret = 'SECRET_HIDDEN_INPUT_9911';
        Http::fake(['*' => Http::response($this->passedResult(), 200)]);
        $student = User::factory()->create();
        ['mission' => $mission] = $this->behavioralMission();
        MissionBehaviorTest::query()->update(['configuration' => json_encode([
            'function' => 'add',
            'cases' => [['args' => [$secret], 'expected' => 0]],
        ])]);

        $this->actingAs($student)
            ->get(route('mission.show', $mission))
            ->assertOk()
            ->assertDontSee($secret);

        $this->actingAs($student)
            ->get(route('mission.challenge', $mission))
            ->assertOk()
            ->assertDontSee($secret);

        $this->actingAs($student)
            ->post(route('mission.submit', $mission), ['code' => 'function add(a, b) { return a + b; }'])
            ->assertSessionHas('mission_success');

        $this->assertStringNotContainsString($secret, (string) json_encode(session('mission_success')));
    }
}
