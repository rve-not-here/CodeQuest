<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionBehaviorTest;
use App\Models\User;
use App\Services\ValidationService;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\CurriculumContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MissionDefinitionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('malformedDefinitions')]
    public function test_malformed_rules_are_unavailable_without_academic_effects(?string $definition): void
    {
        $this->seed(AchievementSeeder::class);
        $student = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'validate_rule' => $definition]);
        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => 'arbitrary code'])
            ->assertSessionHas('mission_error', fn ($message) => str_contains($message['message'], 'unavailable'));
        $this->assertDatabaseMissing('the404_progress', ['user_id' => $student->id]);
        $this->assertDatabaseMissing('the404_xp_transactions', ['user_id' => $student->id]);
        $this->assertDatabaseMissing('the404_user_achievements', ['user_id' => $student->id]);
        $this->assertDatabaseMissing('the404_activity', ['user_id' => $student->id, 'type' => 'wrong_submission']);
    }

    /** @return array<string, array{?string}> */
    public static function malformedDefinitions(): array
    {
        return [
            'null' => [null], 'blank' => [' '], 'invalid JSON' => ['{'], 'empty list' => ['[]'], 'object' => ['{"type":"contains","value":"x"}'],
            'scalar member' => ['[1]'], 'unknown member' => ['[{"type":"future_rule"}]'], 'missing value' => ['[{"type":"contains"}]'],
            'empty needle' => ['[{"type":"contains","value":""}]'], 'non-string needles' => ['[{"type":"contains_all","values":[1]}]'],
            'empty needles' => ['[{"type":"contains_all","values":[]}]'], 'invalid regex' => ['[{"type":"regex","pattern":"["}]'],
            'invalid tag' => ['[{"type":"count_tag","tag":"[","count":1}]'], 'bad operator' => ['[{"type":"count","value":"x","count":1,"operator":"unknown"}]'],
            'string negation' => ['[{"type":"contains","value":"x","negate":"false"}]'],
        ];
    }

    public function test_valid_definition_still_awards_completion(): void
    {
        $this->seed(AchievementSeeder::class);
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['validate_rule' => '[{"type":"contains","value":"correct"}]']);
        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => 'correct'])->assertSessionHas('mission_success');
        $this->assertDatabaseHas('the404_progress', ['user_id' => $student->id, 'mission_id' => $mission->id]);
        $this->assertDatabaseHas('the404_xp_transactions', ['user_id' => $student->id, 'type' => 'mission_completed', 'amount' => $mission->points]);
    }

    public function test_curriculum_release_contains_only_valid_mission_definitions(): void
    {
        $this->seed(CurriculumContentSeeder::class);
        $validator = app(ValidationService::class);
        $missions = Mission::query()->with('behaviorTests')->get();
        $this->assertNotEmpty($missions);

        foreach ($missions as $mission) {
            $this->assertTrue($validator->isValidDefinition($mission->validate_rule ?? '', allowEmpty: $mission->behaviorTests->isNotEmpty()), $mission->title);
        }
    }

    public function test_invalid_behavior_configuration_is_not_charged_even_when_source_fails_structural_checks(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['validate_rule' => '[{"type":"contains","value":"required"}]']);
        MissionBehaviorTest::factory()->create(['mission_id' => $mission->id, 'configuration' => '{}']);
        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => 'wrong source'])
            ->assertSessionHas('mission_error', fn ($message) => str_contains($message['message'], 'unavailable'));
        $this->assertDatabaseCount('the404_progress', 0);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    public function test_release_definition_refusal_happens_before_any_curriculum_write(): void
    {
        $this->mock(ValidationService::class)->shouldReceive('isValidDefinition')->once()->andReturn(false);

        try {
            $this->seed(CurriculumContentSeeder::class);
            $this->fail('Invalid definitions must stop publication.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('the404_courses', 0);
            $this->assertDatabaseCount('the404_sections', 0);
            $this->assertDatabaseCount('the404_missions', 0);
        }
    }
}
