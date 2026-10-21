<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cover_pages_render_one_theme_toggle_button(): void
    {
        foreach (['/', '/login'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('data-theme-toggle', false)
                ->assertSee('data-theme-icon="light"', false)
                ->assertSee('data-theme-icon="dark"', false)
                ->assertDontSee('data-theme-icon="system"', false)
                ->assertSee('aria-label="Theme: Light. Switch to dark."', false)
                ->assertSee('data-theme-announcer', false)
                ->assertDontSee('data-theme-value', false)
                ->assertDontSee('data-theme-endpoint', false)
                ->assertDontSee('atlas-theme"', false);
        }
    }

    public function test_authenticated_shell_renders_the_theme_toggle_instead_of_a_segmented_control(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('aria-label="Theme: Light. Switch to dark."', false)
            ->assertSee('data-theme-endpoint="'.route('theme.update').'"', false)
            ->assertDontSee('data-theme-icon="system"', false)
            ->assertDontSee('data-theme-value', false)
            ->assertDontSee('Light theme', false)
            ->assertDontSee('System theme', false)
            ->assertDontSee('Dark theme', false);
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
