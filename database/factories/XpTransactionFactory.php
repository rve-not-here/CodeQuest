<?php

namespace Database\Factories;

use App\Models\Mission;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<XpTransaction>
 */
class XpTransactionFactory extends Factory
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
            'amount' => fake()->numberBetween(-30, 50),
            'type' => fake()->randomElement(['mission_completed', 'wrong_submission', 'hint_used', 'solution_revealed']),
            'description' => fake()->sentence(),
        ];
    }
}
