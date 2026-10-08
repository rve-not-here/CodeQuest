<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use Tests\TestCase;

class FactoryCapacityTest extends TestCase
{
    public function test_course_fixtures_support_more_than_ten_records(): void
    {
        $this->assertCount(30, Course::factory()->count(30)->make());
    }

    public function test_section_fixtures_support_more_than_ten_records(): void
    {
        $this->assertCount(30, Section::factory()->count(30)->make(['course_id' => 1]));
    }

    public function test_mission_fixtures_support_more_than_twenty_records(): void
    {
        $this->assertCount(30, Mission::factory()->count(30)->make(['course_id' => 1]));
    }
}
