<?php

namespace Tests\Feature;

use App\Models\Course;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_database_has_curriculum_assessments_and_skill_mappings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(['html', 'css', 'js'], Course::query()->orderBy('order_num')->pluck('type')->all());
        $this->assertDatabaseCount('the404_assessments', 3);

        foreach (Course::query()->get() as $course) {
            $this->assertTrue($course->sections()->exists());
            $this->assertTrue($course->missions()->whereHas('skills')->exists());
            $this->assertTrue($course->missions()->whereHas('knowledgeChecks')->exists());
            $this->assertTrue($course->assessment()->whereHas('skills')->exists());
        }
    }
}
