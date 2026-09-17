<?php

namespace Database\Factories;

use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeCheckAttempt>
 */
class KnowledgeCheckAttemptFactory extends Factory
{
    protected $model = KnowledgeCheckAttempt::class;

    /**
     * Result fields are service-owned, so trusted fixtures bypass fill().
     *
     * @param  array<string, mixed>  $attributes
     */
    public function newModel(array $attributes = []): KnowledgeCheckAttempt
    {
        $instance = new KnowledgeCheckAttempt;
        $instance->forceFill($attributes);

        return $instance;
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'knowledge_check_id' => KnowledgeCheck::factory(),
            'user_id' => User::factory(),
            'attempt_number' => 1,
            'status' => KnowledgeCheckAttempt::STATUS_STARTED,
            'started_at' => now(),
        ];
    }

    public function submitted(int $score = 1, int $total = 1): static
    {
        return $this->state(fn (): array => [
            'status' => KnowledgeCheckAttempt::STATUS_SUBMITTED,
            'score' => $score,
            'total_questions' => $total,
            'percentage' => $total === 0 ? 0 : (int) round(($score / $total) * 100),
            'submitted_at' => now(),
        ]);
    }
}
