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
        return $this->stateFor($user)['theme'] ?? null;
    }

    /**
     * The account choice together with when it was made, in epoch
     * milliseconds.
     *
     * The timestamp is what lets the page decide between the device's own
     * choice and the account's: a student who picks a theme and navigates
     * straight away must not have that choice overwritten by an account
     * write that has not landed yet. The newer of the two wins.
     *
     * @return array{theme: string, saved_at: int|null}|null
     */
    public function stateFor(User $user): ?array
    {
        $preference = $user->preference()->first();

        if ($preference === null) {
            return null;
        }

        return [
            'theme' => (string) $preference->theme,
            'saved_at' => $preference->updated_at?->getTimestampMs(),
        ];
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
