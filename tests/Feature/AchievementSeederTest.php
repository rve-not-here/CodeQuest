<?php

namespace Tests\Feature;

use App\Models\Achievement;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_the_four_achievements(): void
    {
        (new AchievementSeeder)->run();

        $this->assertSame(4, Achievement::count());
        $this->assertSame(4, Achievement::query()->distinct()->count('slug'));
    }

    public function test_seeder_is_idempotent(): void
    {
        (new AchievementSeeder)->run();
        (new AchievementSeeder)->run();

        $this->assertSame(4, Achievement::count());
    }
}
