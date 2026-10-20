<?php

namespace App\Support;

/**
 * Maps status-stamp labels to Lucide icons.
 *
 * Stamps are labels first: neutral metadata (roles, course codes, counts) has
 * no entry here and stays text-only. Add labels only when the glyph carries
 * meaning the word does not already carry on its own.
 */
final class StampIcon
{
    /**
     * @var array<string, string>
     */
    private const ICONS = [
        'Active' => 'circle-check',
        'All cleared' => 'badge-check',
        'Archived' => 'archive',
        'Available' => 'circle-dot',
        'Awaiting verdict' => 'hourglass',
        'Boss ready' => 'target',
        'Completed' => 'check',
        'Correct' => 'check',
        'Current' => 'locate',
        'Demonstrated' => 'badge-check',
        'Developing' => 'trending-up',
        'Done' => 'check',
        'Draft' => 'pencil',
        'Empty' => 'circle',
        'Evaluated' => 'badge-check',
        'Failed' => 'x',
        'In progress' => 'loader-circle',
        'Locked' => 'lock',
        'Needs work' => 'triangle-alert',
        'New' => 'sparkles',
        'Not assessed' => 'minus',
        'Not attempted' => 'minus',
        'Not recorded' => 'minus',
        'Not started' => 'circle',
        'On track' => 'check',
        'Passed' => 'check',
        'Practicing' => 'activity',
        'Published' => 'check',
        'Read' => 'mail-open',
        'Ready' => 'flag',
        'Review needed' => 'triangle-alert',
        'Unlocked' => 'lock-open',
        'Unread' => 'mail',
    ];

    public static function for(string $label): ?string
    {
        return self::ICONS[$label] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function icons(): array
    {
        return self::ICONS;
    }
}
