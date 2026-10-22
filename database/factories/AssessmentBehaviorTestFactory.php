<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\AssessmentBehaviorTest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentBehaviorTest>
 */
class AssessmentBehaviorTestFactory extends Factory
{
    protected $model = AssessmentBehaviorTest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'name' => fake()->words(3, true),
            'test_type' => AssessmentBehaviorTest::TYPE_FUNCTION,
            'configuration' => json_encode([
                'function' => 'add',
                'cases' => [
                    ['args' => [2, 3], 'expected' => 5],
                    ['args' => [10, 20], 'expected' => 30],
                ],
            ]),
            'order_num' => fake()->unique()->numberBetween(1, 50),
            'active' => true,
        ];
    }

    public function console(): static
    {
        return $this->state(fn (): array => [
            'test_type' => AssessmentBehaviorTest::TYPE_CONSOLE,
            'configuration' => json_encode(['expected' => [[8]]]),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
