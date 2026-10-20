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
                ->assertSee('data-theme-icon="system"', false)
                ->assertSee('data-theme-icon="light"', false)
                ->assertSee('data-theme-icon="dark"', false)
                ->assertSee('aria-label="Theme: System. Switch to light."', false)
                ->assertSee('data-theme-announcer', false)
                ->assertDontSee('data-theme-value', false)
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
            ->assertSee('aria-label="Theme: System. Switch to light."', false)
            ->assertDontSee('data-theme-value', false)
            ->assertDontSee('Light theme', false)
            ->assertDontSee('System theme', false)
            ->assertDontSee('Dark theme', false);
    }

    public function test_the_top_navigation_items_carry_icons_next_to_their_labels(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="icon"', false)
            ->assertSee('Dashboard', false)
            ->assertSee('Learning Path', false)
            ->assertSee('Boss Challenges', false)
            ->assertSee('M3.262 15.326', false);
    }
}
