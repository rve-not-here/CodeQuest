<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Gate for the admin area (US-701, §6). Stricter than the 'teacher'
     * middleware: only the 'admin' role passes, and teacher, student, and
     * operator are all rejected with 403. `auth` runs this middleware only
     * after authentication, so guests are already redirected to login.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== 'admin') {
            abort(403, 'This area is restricted to administrators.');
        }

        return $next($request);
    }
}
