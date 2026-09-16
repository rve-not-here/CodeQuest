<?php

namespace Database\Factories;

use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Progress>
 */
class ProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mission_id' => Mission::factory(),
            'pts_earned' => fake()->numberBetween(0, 150),
            'completed_at' => now(),
        ];
    }
}
