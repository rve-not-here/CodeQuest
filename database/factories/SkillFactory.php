<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = 'test.'.fake()->unique()->slug(2);

        return [
            'key' => $key,
            'label' => fake()->words(2, true),
            'description' => fake()->sentence(),
        ];
    }
}
