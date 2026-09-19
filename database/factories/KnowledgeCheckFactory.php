<?php

namespace Database\Factories;

use App\Models\KnowledgeCheck;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeCheck>
 */
class KnowledgeCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mission_id' => Mission::factory(),
            'order_num' => 1,
            'title' => fake()->words(3, true),
            'instructions' => fake()->sentence(),
            'is_required' => false,
            'status' => KnowledgeCheck::STATUS_PUBLISHED,
            'version' => 1,
        ];
    }

    public function required(): static
    {
        return $this->state(fn (): array => ['is_required' => true]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => KnowledgeCheck::STATUS_DRAFT]);
    }
}
