<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentAttempt>
 */
class AssessmentAttemptFactory extends Factory
{
    protected $model = AssessmentAttempt::class;

    /**
     * The model guards status, score, and passed_at (#[Fillable] excludes
     * them), so factory attributes for those fields must bypass fill(). The
     * factory is trusted test/seed tooling, not client input, so forceFill is
     * correct here.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function newModel(array $attributes = []): AssessmentAttempt
    {
        $instance = new AssessmentAttempt;

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
            'assessment_id' => Assessment::factory(),
            'user_id' => User::factory(),
            'status' => 'available',
        ];
    }

    public function started(): static
    {
        return $this->state(fn (): array => ['status' => 'started']);
    }
}
