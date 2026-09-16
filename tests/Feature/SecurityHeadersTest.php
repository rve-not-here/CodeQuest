<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Baseline transport hardening (§25) ships on every rendered page. These
     * four are static values the platform commits to unconditionally, so they
     * must be present regardless of which view answers the request.
     */
    public function test_transport_security_headers_are_present_on_pages(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/achievements')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    /**
     * The CSP is the meaningful line (§25): the platform renders zero
     * third-party origins (no CDN, no external font, no analytics beacon — see
     * resources/views/layouts/app.blade.php), so default-src 'self' is accurate
     * and free.
     *
     * The two CodeMirror editor pages (mission + assessment summary) boot the
     * editor from inline @push('scripts') blocks — the editor bundle is swept
     * by inline boot scripts, so script-src 'self' 'unsafe-inline' is required
     * and we grant it rather than break the interactive core. What we deny:
     * 'unsafe-eval' (no eval/new Function/Function() compilation from injected
     * strings), third-party origins, frame-ancestors (clickjacking), and
     * object-src/plugins entirely.
     */
    public function test_csp_is_strict_but_allows_inline_boot_scripts_and_forbids_eval(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/achievements');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp, 'Every page must carry a Content-Security-Policy.');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", $csp, 'The CodeMirror inline boot scripts are app-owned and static.');
        $this->assertStringContainsString("object-src 'none'", $csp, "object-src must be 'none'.");
        $this->assertStringContainsString("frame-ancestors 'none'", $csp, 'No page may be embedded by any frame.');
        $this->assertStringNotContainsString('unsafe-eval', $csp, 'eval/new Function must never be permitted.');
    }

    /**
     * The container health probe (/up) also passes through the global
     * middleware stack (§25 — registered via withMiddleware(global:) in
     * bootstrap/app.php), so the probe is not a header-free side door.
     */
    public function test_health_probe_is_also_hardened(): void
    {
        $this->get('/up')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    }
}
