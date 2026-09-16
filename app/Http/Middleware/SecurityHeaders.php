<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline response hardening (Phase 11, §25 security posture). Sets the
 * transport headers the platform can commit to unconditionally — every one is
 * a static, framework-agnostic value, so there is no deploy-time knob to get
 * wrong and no user-suppliable input in the header values.
 *
 * Content-Security-Policy is the meaningful line here (§25.Z): the app renders
 * zero third-party origins (no CDN, no external font, no analytics beacon —
 * see resources/views/layouts/app.blade.php), so default-src 'self' is both
 * accurate and free. eval()/new Function/Function() are forbidden by omitting
 * 'unsafe-eval', and no remote script origin is permitted.
 *
 * Two relaxations kept deliberately small:
 *  - script-src 'unsafe-inline': the CodeMirror editor boot scripts live in
 *    @push('scripts')/@stack('scripts') blocks (mission + assessments/show),
 *    i.e. are app-owned static inline blobs, not generated markup. Bundling
 *    them is tracked as follow-up work; until then they must be allowed.
 *    'unsafe-eval' is NOT granted, so injected code cannot compile strings.
 *  - style-src 'unsafe-inline': element-level styling in the visible UI.
 *
 * Frames/plugins are fully closed: frame-ancestors 'none' (clickjacking),
 * object-src 'none' (plugin/embed drop targets), X-Frame-Options DENY.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; "
            ."img-src 'self' data:; font-src 'self'; connect-src 'self'; "
            ."object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
        );

        return $response;
    }
}
