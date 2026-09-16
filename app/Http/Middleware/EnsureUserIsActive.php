<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Immediate lockout for deactivated accounts (US-701, §13/§14). Runs on
     * every web request, after the session starts: an authenticated user whose
     * status is no longer 'active' is logged out, the session is killed, and
     * the request is bounced to login. The account is locked out on the very
     * next request after deactivation, not just at the next sign-in attempt.
     *
     * Guests (no resolved user) pass straight through; the route-level 'auth'
     * middleware handles the guest redirect afterwards.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->status !== 'active') {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'account' => 'This account has been deactivated.',
            ]);
        }

        return $next($request);
    }
}
