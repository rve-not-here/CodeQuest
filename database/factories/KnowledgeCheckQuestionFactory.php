<?php

namespace Database\Factories;

use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeCheckQuestion>
 */
class KnowledgeCheckQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'knowledge_check_id' => KnowledgeCheck::factory(),
            'order_num' => 1,
            'type' => KnowledgeCheckQuestion::TYPE_MULTIPLE_CHOICE,
            'prompt' => fake()->sentence(),
            'explanation' => fake()->sentence(),
        ];
    }

    public function codeReading(string $code = '<p>System online.</p>'): static
    {
        return $this->state(fn (): array => [
            'type' => KnowledgeCheckQuestion::TYPE_CODE_READING,
            'code_snippet' => $code,
        ]);
    }
}
