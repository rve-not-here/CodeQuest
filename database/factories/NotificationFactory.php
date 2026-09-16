<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * created_at is not fillable (table default), so trusted fixtures set it
     * via forceFill — the same pattern AssessmentAttemptFactory uses for its
     * guarded result fields.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function newModel(array $attributes = []): Notification
    {
        $instance = new Notification;

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
            'user_id' => User::factory(),
            'type' => 'system_announcement',
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'dedupe_key' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }

    public function unread(): static
    {
        return $this->state(fn (): array => ['read_at' => null]);
    }
}
