<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cover_pages_render_the_two_option_theme_setter(): void
    {
        foreach (['/', '/login'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('data-theme-toggle', false)
                ->assertSee('data-theme-set="light"', false)
                ->assertSee('data-theme-set="dark"', false)
                ->assertSee('aria-pressed="true"', false)
                ->assertSee('aria-pressed="false"', false)
                ->assertSee('data-theme-announcer', false)
                ->assertDontSee('data-theme-endpoint', false)
                ->assertDontSee('data-theme-server', false)
                ->assertDontSee('data-theme-value', false);
        }
    }

    public function test_the_authenticated_shell_renders_the_setter_with_the_account_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-theme-set="light"', false)
            ->assertSee('data-theme-set="dark"', false)
            ->assertSee('data-theme-endpoint="'.route('theme.update').'"', false)
            ->assertDontSee('data-theme-value', false);
    }

    public function test_the_option_in_force_is_the_pressed_one(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertMatchesRegularExpression('/data-theme-set="dark"\\s+aria-pressed="true"/', $html);
        $this->assertMatchesRegularExpression('/data-theme-set="light"\\s+aria-pressed="false"/', $html);
        $this->assertStringNotContainsString('aria-label="Switch to', $html);
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
