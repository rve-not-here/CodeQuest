<?php

namespace Tests\Feature;

use App\Support\GameCopy;
use Tests\TestCase;

class GameCopyTest extends TestCase
{
    public function test_lines_are_short_factual_and_deterministic(): void
    {
        $first = GameCopy::line('check.passed', 82);
        $second = GameCopy::line('check.passed', 82);

        $this->assertSame($first, $second);
        $this->assertLessThanOrEqual(12, str_word_count($first));
        $this->assertLessThanOrEqual(12, str_word_count(GameCopy::line('empty.activity')));
        $this->assertLessThanOrEqual(12, str_word_count(GameCopy::line('submit.failed', 16)));
    }

    public function test_milestones_choose_one_crossing_using_server_progress(): void
    {
        $this->assertSame('Quarter of the course logged.', GameCopy::milestone(10, 25, 18));
        $this->assertSame('Halfway through the course.', GameCopy::milestone(25, 50, 18));
        $this->assertSame('Three quarters of the course logged.', GameCopy::milestone(50, 75, 18));
        $this->assertSame('Course progress complete.', GameCopy::milestone(75, 100, 18));
        $this->assertNull(GameCopy::milestone(20, 24, 18));
        $this->assertNull(GameCopy::milestone(null, 50, 18));
    }

    public function test_knowledge_check_bands_follow_the_existing_pass_score(): void
    {
        $this->assertStringContainsString('Concept confirmed', GameCopy::checkBand(100, 28));
        $this->assertStringContainsString('Pass confirmed', GameCopy::checkBand(70, 28));
        $this->assertStringContainsString('Review the misses', GameCopy::checkBand(69, 28));
    }
}
