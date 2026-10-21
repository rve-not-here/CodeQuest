<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ThemePreferenceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ThemePreferenceController extends Controller
{
    /**
     * Persist the authenticated user's Light/Dark choice. Role-agnostic on
     * purpose (like notifications and logout): every active account has a
     * theme. The target is always the caller, so no request field can point
     * the write at another user.
     */
    public function __invoke(Request $request, ThemePreferenceService $preferences): Response
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', Rule::in(ThemePreferenceService::THEMES)],
        ]);

        /** @var User $user */
        $user = $request->user();

        $preferences->save($user, $validated['theme']);

        return response()->noContent();
    }
}
