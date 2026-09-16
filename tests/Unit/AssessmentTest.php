<?php

namespace Tests\Unit;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_persists_over_the404_assessments_table(): void
    {
        $assessment = Assessment::factory()->create([
            'description' => 'A practical boss challenge.',
            'passing_score' => 80,
        ]);

        $this->assertDatabaseHas('the404_assessments', [
            'id' => $assessment->id,
            'description' => 'A practical boss challenge.',
            'passing_score' => 80,
        ]);
        $this->assertSame('the404_assessments', $assessment->getTable());
    }

    public function test_assessment_belongs_to_a_course_and_course_has_one_assessment(): void
    {
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->for($course)->create();

        $this->assertTrue($course->assessment->is($assessment));
        $this->assertTrue($assessment->course->is($course));
    }

    public function test_assessment_is_a_separate_domain_from_mission(): void
    {
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->for($course)->create();
        $mission = Mission::factory()->for($course)->create();

        $this->assertNotSame($assessment->getTable(), $mission->getTable());
        $this->assertNotSame($assessment->getTable(), 'the404_missions');
        $this->assertNotInstanceOf(Mission::class, $assessment);
        $this->assertNotInstanceOf(Assessment::class, $mission);
        $this->assertFalse(method_exists($mission, 'assessment'));
    }

    public function test_assessment_never_serializes_grading_rule(): void
    {
        $assessment = Assessment::factory()->create([
            'grading_rule' => json_encode([['type' => 'hidden']]),
        ]);

        $this->assertArrayNotHasKey('grading_rule', $assessment->toArray());
        $this->assertArrayNotHasKey('grading_rule', $assessment->attributesToArray());
    }

    public function test_assessment_persists_grading_rule_in_database(): void
    {
        $rule = json_encode([['type' => 'contains', 'value' => '<form>']]);

        $assessment = Assessment::factory()->create(['grading_rule' => $rule]);

        $this->assertDatabaseHas('the404_assessments', [
            'id' => $assessment->id,
            'grading_rule' => $rule,
        ]);
        $this->assertSame($rule, $assessment->fresh()->getRawOriginal('grading_rule'));
    }
}
