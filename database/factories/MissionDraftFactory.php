<?php

namespace Database\Factories;

use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionDraft>
 */
class MissionDraftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mission_id' => Mission::factory(),
            'code' => '<h1>draft</h1>',
        ];
    }
}
