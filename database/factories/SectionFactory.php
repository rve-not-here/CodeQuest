<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'order_num' => fake()->unique()->numberBetween(1, 10),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'version' => 1,
        ];
    }
}
