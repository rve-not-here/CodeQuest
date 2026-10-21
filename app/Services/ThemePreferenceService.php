<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPreference;

/**
 * The account-level Light/Dark preference. Stored server-side so the choice
 * follows the user across browsers and devices; localStorage remains the
 * pre-paint cache and the only store for guests.
 */
class ThemePreferenceService
{
    /** @var list<string> */
    public const THEMES = ['light', 'dark'];

    /**
     * The saved theme for one user, or null when the account has never
     * persisted a choice (the device's localStorage then stays in charge).
     */
    public function for(User $user): ?string
    {
        return $user->preference()->first()?->theme;
    }

    /**
     * Upsert the account preference. One row per user; repeated toggles update
     * it in place.
     */
    public function save(User $user, string $theme): void
    {
        UserPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['theme' => $theme],
        );
    }
}
