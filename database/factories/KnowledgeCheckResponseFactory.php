<?php

namespace Database\Factories;

use App\Models\KnowledgeCheckAttempt;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\KnowledgeCheckResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeCheckResponse>
 */
class KnowledgeCheckResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'knowledge_check_attempt_id' => KnowledgeCheckAttempt::factory(),
            'knowledge_check_question_id' => KnowledgeCheckQuestion::factory(),
            'selected_option_id' => KnowledgeCheckOption::factory(),
            'correct_option_id' => KnowledgeCheckOption::factory()->correct(),
            'is_correct' => false,
            'prompt_snapshot' => fake()->sentence(),
            'selected_option_snapshot' => fake()->words(3, true),
            'correct_option_snapshot' => fake()->words(3, true),
            'explanation_snapshot' => fake()->sentence(),
        ];
    }
}
