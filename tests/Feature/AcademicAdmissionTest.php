<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionBehaviorTest;
use App\Models\Progress;
use App\Models\User;
use App\Services\ReportPdfExporter;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AcademicAdmissionTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('sourceRoutes')]
    public function test_oversized_source_is_rejected_without_persisting_or_flashing_it(string $route): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        $target = $mission;
        if ($route === 'assessment.submit') {
            Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
            $target = Assessment::factory()->create(['course_id' => $course->id]);
            AssessmentAttempt::factory()->started()->create(['user_id' => $student->id, 'assessment_id' => $target->id]);
        }
        $this->actingAs($student)->post(route($route, $target), ['code' => str_repeat('😀', 16385)])
            ->assertSessionHasErrors('code');
        $this->assertNull(session()->getOldInput('code'));
        $this->assertDatabaseCount('the404_xp_transactions', 0);
        $this->assertDatabaseCount('the404_mission_drafts', 0);
        $this->assertDatabaseMissing('the404_assessment_attempts', ['status' => 'submitted']);
    }

    /** @return array<string, array{string}> */
    public static function sourceRoutes(): array
    {
        return ['mission submission' => ['mission.submit'], 'draft' => ['mission.draft'], 'boss' => ['assessment.submit']];
    }

    public function test_assistance_rejects_oversized_source_without_charging_or_flashing(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create();

        foreach (['mission.hint', 'mission.reveal'] as $route) {
            $this->actingAs($student)->post(route($route, $mission), ['code' => str_repeat('x', 65537)])
                ->assertSessionHasErrors('code');
            $this->assertNull(session()->getOldInput('code'));
        }

        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    public function test_grading_limits_are_shared_across_missions_and_boss_and_recover_without_penalty(): void
    {
        $this->seed(AchievementSeeder::class);
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $mission = Mission::factory()->create(['course_id' => $course->id]);
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
        $boss = Assessment::factory()->create(['course_id' => $course->id, 'grading_rule' => '[{"type":"contains","value":"valid"}]']);
        $attempt = AssessmentAttempt::factory()->started()->create(['user_id' => $student->id, 'assessment_id' => $boss->id]);
        $this->freezeTime();

        for ($request = 0; $request < 12; $request++) {
            $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => 'valid'])->assertRedirect();
        }
        $this->postJson(route('assessment.submit', $boss), ['code' => 'valid', 'score' => 100, 'passed' => true])
            ->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertSame('started', $attempt->fresh()->status);
        $this->assertDatabaseCount('the404_xp_transactions', 0);

        $this->travel(61)->seconds();
        $this->post(route('assessment.submit', $boss), ['code' => 'valid'])->assertRedirect();
        $this->assertSame('passed', $attempt->fresh()->status);
        $this->assertDatabaseCount('the404_xp_transactions', 1);
    }

    public function test_export_limit_blocks_rendering_and_recovers_without_affecting_other_users(): void
    {
        $student = User::factory()->create();
        $this->freezeTime();

        for ($request = 0; $request < 30; $request++) {
            $this->actingAs($student)->get(route('export.progress'))->assertOk();
        }
        $this->getJson(route('export.progress', ['format' => 'pdf']))->assertTooManyRequests()->assertHeader('Retry-After');
        $this->actingAs(User::factory()->create())->get(route('export.progress'))->assertOk();
        $this->travel(61)->seconds();
        $this->actingAs($student)->get(route('export.progress'))->assertOk();
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    public function test_pdf_rejects_excess_rows_before_rendering_and_excess_bytes_before_execution(): void
    {
        $exporter = app(ReportPdfExporter::class);
        $document = $exporter->document('Large report', [], [
            $exporter->tableSection('Rows', null, ['Name'], array_fill(0, 1001, ['Student'])),
        ]);

        try {
            $exporter->render($document);
            $this->fail('Oversized PDF must be refused.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        try {
            $exporter->pdf(str_repeat('x', 1048577));
            $this->fail('Oversized PDF HTML must be refused.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        $this->assertStringContainsString('Student', $exporter->render($exporter->document('Small report', [], [
            $exporter->tableSection('Rows', null, ['Name'], [['Student']]),
        ])));
    }

    public function test_grader_capacity_refusal_cannot_penalize_or_complete_a_mission(): void
    {
        $this->seed(AchievementSeeder::class);
        $student = User::factory()->create();
        $mission = Mission::factory()->create([
            'validate_rule' => '[{"type":"contains","value":"function"}]',
        ]);
        MissionBehaviorTest::factory()->create([
            'mission_id' => $mission->id,
            'configuration' => json_encode(['function' => 'add', 'cases' => [['args' => [2, 3], 'expected' => 5]]]),
        ]);
        config(['grader.url' => 'http://grader.test', 'grader.token' => 'test']);
        Http::preventStrayRequests();
        Http::fake(['http://grader.test/grade' => Http::response(['error' => 'grader_unavailable'], 503, ['Retry-After' => '1'])]);

        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => 'function add(a,b){return a+b}'])
            ->assertRedirect()->assertSessionHas('mission_error');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('the404_progress', 0);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
        $this->assertDatabaseCount('the404_user_achievements', 0);
        $this->assertDatabaseCount('the404_assessment_attempts', 0);
    }

    public function test_byte_boundary_draft_succeeds_and_per_user_limiter_recovers(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create();
        $source = str_repeat('😀', 16384);
        $this->actingAs($student)->post(route('mission.draft', $mission), ['code' => $source])->assertRedirect();
        $this->assertDatabaseHas('the404_mission_drafts', ['user_id' => $student->id, 'code' => $source]);
        for ($request = 1; $request < 60; $request++) {
            $this->post(route('mission.draft', $mission), ['code' => 'valid'])->assertRedirect();
        }
        $this->postJson(route('mission.draft', $mission), ['code' => 'blocked'])->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertDatabaseHas('the404_mission_drafts', ['user_id' => $student->id, 'code' => 'valid']);
        $this->actingAs(User::factory()->create())->post(route('mission.draft', $mission), ['code' => 'other student'])->assertRedirect();
        $this->travel(61)->seconds();
        $this->actingAs($student)->post(route('mission.draft', $mission), ['code' => 'recovered'])->assertRedirect();
        $this->assertDatabaseHas('the404_mission_drafts', ['user_id' => $student->id, 'code' => 'recovered']);

        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }
}
