<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mission>
 */
class MissionFactory extends Factory
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
            'order_num' => fake()->unique()->numberBetween(1, 20),
            'title' => fake()->words(2, true),
            'difficulty' => 'EASY',
            'description' => fake()->sentence(),
            'points' => fake()->numberBetween(30, 150),
        ];
    }
}
