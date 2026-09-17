<?php

namespace Database\Factories;

use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeCheckOption>
 */
class KnowledgeCheckOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'knowledge_check_question_id' => KnowledgeCheckQuestion::factory(),
            'order_num' => 1,
            'option_text' => fake()->sentence(4),
            'is_correct' => false,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn (): array => ['is_correct' => true]);
    }
}
