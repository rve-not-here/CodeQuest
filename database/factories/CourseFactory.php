<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'type' => 'html',
            'description' => fake()->sentence(),
            'status' => 'active',
            'order_num' => fake()->unique()->numberBetween(1, 10),
        ];
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'locked',
        ]);
    }
}
