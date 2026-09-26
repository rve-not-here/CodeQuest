<?php

namespace Database\Factories;

use App\Models\Mission;
use App\Models\MissionBehaviorTest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionBehaviorTest>
 */
class MissionBehaviorTestFactory extends Factory
{
    protected $model = MissionBehaviorTest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mission_id' => Mission::factory(),
            'name' => fake()->words(3, true),
            'test_type' => MissionBehaviorTest::TYPE_FUNCTION,
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
            'test_type' => MissionBehaviorTest::TYPE_CONSOLE,
            'configuration' => json_encode(['expected' => [[8]]]),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
