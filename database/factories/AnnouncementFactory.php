<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'audience' => 'all',
            'status' => 'draft',
            'published_at' => null,
        ];
    }

    /**
     * Indicate the announcement has been published (first publish only).
     */
    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate the announcement has been archived (terminal state).
     */
    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => 'archived',
            'published_at' => now(),
        ]);
    }
}
