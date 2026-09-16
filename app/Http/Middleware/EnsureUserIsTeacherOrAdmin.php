<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTeacherOrAdmin
{
    /**
     * Gate for the teacher area (US-601). Decided model: any authenticated
     * teacher or admin reaches the whole instructor surface, system-wide, and
     * every other role is rejected with 403. `auth` runs this middleware only
     * after authentication, so guests are already redirected to login.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->user()?->role, ['teacher', 'admin'], true)) {
            abort(403, 'This area is restricted to teachers.');
        }

        return $next($request);
    }
}
