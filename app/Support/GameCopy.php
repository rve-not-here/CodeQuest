<?php

namespace App\Support;

use App\Services\KnowledgeCheckService;

/**
 * One source for new game-voice lines. Variants are chosen by a stable server
 * id (mission, check or assessment), never by time or randomness, so the same
 * page always renders the same line. Lines are short factual additions only.
 */
final class GameCopy
{
    /** @var array<string, list<string>|string> */
    private const LINES = [
        'completion.restored' => ['Progress restored.', 'Progress recorded.'],
        'completion.next' => 'Next step:',
        'completion.milestone.25' => 'Quarter of the course logged.',
        'completion.milestone.50' => 'Halfway through the course.',
        'completion.milestone.75' => 'Three quarters of the course logged.',
        'completion.milestone.100' => 'Course progress complete.',
        'achievement.recorded' => 'Recorded in your ledger.',
        'submit.failed' => ['Attempt logged. Review and retry.', 'Attempt recorded. Review and retry.'],
        'boss.briefing' => 'Review the brief. Begin when ready.',
        'boss.passed' => 'Milestone logged. Course cleared.',
        'boss.failed' => 'Debrief logged. Review and retry.',
        'check.perfect' => ['All answers correct. Concept confirmed.', 'Perfect check. Concept confirmed.'],
        'check.passed' => ['Pass confirmed. Review the misses.', 'Check passed. Review the misses.'],
        'check.retry' => ['Review the misses, then retry.', 'Review the misses and retry.'],
        'empty.activity' => 'No records yet. Your first result starts here.',
        'empty.achievements' => 'No milestones yet. Complete a challenge.',
        'empty.notifications' => 'Inbox clear. New notices appear here.',
        'empty.courses' => 'No courses yet. Check back soon.',
    ];

    public static function line(string $key, ?int $seed = null): string
    {
        $value = self::LINES[$key] ?? '';

        if (! is_array($value)) {
            return (string) $value;
        }

        $index = $seed === null ? 0 : abs($seed) % count($value);

        return (string) $value[$index];
    }

    public static function milestone(?int $before, ?int $after, ?int $seed = null): ?string
    {
        if ($before === null || $after === null) {
            return null;
        }

        $hit = null;
        foreach ([25, 50, 75, 100] as $threshold) {
            if ($before < $threshold && $after >= $threshold) {
                $hit = $threshold;
            }
        }

        return $hit === null ? null : self::line('completion.milestone.'.$hit, $seed);
    }

    public static function nextStep(string $label): string
    {
        return self::line('completion.next').' '.$label;
    }

    public static function completionAnnouncement(int $awarded, int $balance, ?int $progress): string
    {
        $line = 'Challenge complete. '.$awarded.' XP earned. Total '.$balance.' XP.';
        if ($progress !== null) {
            $line .= ' Progress '.$progress.' percent.';
        }

        return $line;
    }

    public static function checkBand(int $percentage, ?int $seed = null): string
    {
        if ($percentage >= 100) {
            return self::line('check.perfect', $seed);
        }

        if ($percentage >= KnowledgeCheckService::PASS_SCORE) {
            return self::line('check.passed', $seed);
        }

        return self::line('check.retry', $seed);
    }
}
