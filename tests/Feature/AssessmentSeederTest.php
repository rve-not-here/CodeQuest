<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Services\AssessmentService;
use Database\Seeders\AssessmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentSeederTest extends TestCase
{
    use RefreshDatabase;

    private function runSeeder(): void
    {
        app(AssessmentSeeder::class)->run();
    }

    public function test_creates_one_assessment_for_each_active_course(): void
    {
        $html = Course::factory()->create(['type' => 'html', 'order_num' => 1]);
        $css = Course::factory()->create(['type' => 'css', 'order_num' => 2]);
        $js = Course::factory()->create(['type' => 'js', 'order_num' => 3]);

        $this->runSeeder();

        $this->assertSame(3, Assessment::query()->count());
        $this->assertTrue($html->assessment()->exists());
        $this->assertTrue($css->assessment()->exists());
        $this->assertTrue($js->assessment()->exists());
    }

    public function test_authored_content_varied_by_course_type(): void
    {
        $html = Course::factory()->create(['type' => 'html']);
        $css = Course::factory()->create(['type' => 'css']);

        $this->runSeeder();

        $htmlAssessment = $html->assessment()->first();
        $cssAssessment = $css->assessment()->first();

        $this->assertNotSame($htmlAssessment->title, $cssAssessment->title);
        $this->assertStringContainsString('HTML Boss Challenge', $htmlAssessment->title);
        $this->assertStringContainsString('CSS Boss Challenge', $cssAssessment->title);
    }

    public function test_is_idempotent_across_repeated_runs(): void
    {
        $course = Course::factory()->create(['type' => 'html']);

        $this->runSeeder();
        $this->runSeeder();

        $this->assertSame(1, Assessment::query()->where('course_id', $course->id)->count());
    }

    public function test_skips_non_active_courses(): void
    {
        $active = Course::factory()->create(['type' => 'html', 'status' => 'active']);
        $locked = Course::factory()->locked()->create(['type' => 'html']);
        $draft = Course::factory()->create(['type' => 'html', 'status' => 'draft']);

        $this->runSeeder();

        $this->assertTrue($active->assessment()->exists());
        $this->assertFalse($locked->assessment()->exists());
        $this->assertFalse($draft->assessment()->exists());
    }

    public function test_seeded_content_is_persisted_and_grading_rule_valid_json(): void
    {
        $course = Course::factory()->create(['type' => 'html']);

        $this->runSeeder();

        $assessment = $course->assessment()->first();
        $rules = json_decode($assessment->getRawOriginal('grading_rule'), true);

        $this->assertIsArray($rules);
        $this->assertGreaterThanOrEqual(1, count($rules));
        $this->assertNotEmpty($assessment->instructions);
        $this->assertGreaterThan(0, $assessment->passing_score);
    }

    public function test_respects_one_per_course_invariant_when_a_course_already_has_an_assessment(): void
    {
        $course = Course::factory()->create(['type' => 'html']);
        app(AssessmentService::class)->createForCourse($course, [
            'title' => 'Manually seeded',
            'passing_score' => 60,
        ]);

        $this->runSeeder();

        $this->assertSame(1, Assessment::query()->where('course_id', $course->id)->count());
        $this->assertSame('Manually seeded', $course->assessment()->first()->title);
    }
}
