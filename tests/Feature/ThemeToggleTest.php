<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cover_pages_render_the_single_theme_toggle(): void
    {
        foreach (['/', '/login'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('data-theme-toggle', false)
                ->assertSee('aria-label="Switch to dark mode"', false)
                ->assertSee('data-theme-announcer', false)
                ->assertSee('data-theme-icon="light"', false)
                ->assertSee('data-theme-icon="dark"', false)
                // A guest has no account to write the choice to.
                ->assertDontSee('data-theme-endpoint', false)
                ->assertDontSee('data-theme-server', false)
                // There is no third mode to fall back on: the choice stays
                // in force until the student changes it.
                ->assertDontSee('data-theme-set', false);
        }
    }

    public function test_the_authenticated_shell_renders_the_toggle_with_the_account_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('aria-label="Switch to dark mode"', false)
            ->assertSee('data-theme-endpoint="'.route('theme.update').'"', false)
            ->assertDontSee('data-theme-set', false);
    }

    public function test_the_toggle_names_the_mode_it_applies(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        // A single button whose label says what the next press does.
        $this->assertStringContainsString('aria-label="Switch to dark mode"', $html);
        $this->assertStringContainsString('data-theme-icon="light"', $html);
        $this->assertStringContainsString('data-theme-icon="dark"', $html);
        $this->assertStringNotContainsString('aria-pressed', $html);
    }

    public function test_the_shell_keeps_icons_in_its_controls_and_menus(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="icon"', false)
            ->assertSee('Boss Challenges', false)
            ->assertSee('M3.262 15.326', false);
    }
}
