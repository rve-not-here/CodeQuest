<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
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
            'title' => fake()->words(3, true).' Boss Challenge',
            'description' => fake()->sentence(),
            'instructions' => fake()->paragraph(),
            'passing_score' => fake()->numberBetween(50, 100),
            'status' => 'active',
        ];
    }
}
