<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Services\AchievementService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * System-managed achievement catalog (§27, US-508). Teachers and students are
 * not catalog authors — this seeder, running like AssessmentSeeder, is the
 * controlled place where the starter catalog is authored. Slugs are owned by
 * AchievementService constants; the seeder only attaches display copy.
 *
 * Idempotent: updateOrCreate by slug, so re-running never duplicates rows.
 *
 * Run with: php artisan db:seed --class=AchievementSeeder
 */
class AchievementSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $catalog = [
            AchievementService::SLUG_FIRST_CHALLENGE => [
                'name' => 'First Challenge',
                'description' => 'Complete your first challenge.',
            ],
            AchievementService::SLUG_FIRST_COURSE => [
                'name' => 'First Course Complete',
                'description' => 'Pass your first Boss Challenge.',
            ],
            AchievementService::SLUG_STREAK_3 => [
                'name' => 'Operator Streak',
                'description' => 'Complete at least one challenge on 3 consecutive days.',
            ],
            AchievementService::SLUG_FULL_CLEAR => [
                'name' => 'System Cleared',
                'description' => "Pass every active course's Boss Challenge.",
            ],
        ];

        foreach ($catalog as $slug => $content) {
            Achievement::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $content['name'], 'description' => $content['description']],
            );
        }
    }
}
