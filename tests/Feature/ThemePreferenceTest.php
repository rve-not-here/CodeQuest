<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_persist_a_theme_preference(): void
    {
        $this->postJson(route('theme.update'), ['theme' => 'dark'])
            ->assertUnauthorized();

        $this->assertDatabaseCount('the404_user_preferences', 0);
    }

    public function test_authenticated_users_persist_one_preference_row(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('theme.update'), ['theme' => 'dark'])
            ->assertNoContent();

        $this->assertDatabaseHas('the404_user_preferences', [
            'user_id' => $user->id,
            'theme' => 'dark',
        ]);

        $this->actingAs($user)
            ->postJson(route('theme.update'), ['theme' => 'light'])
            ->assertNoContent();

        $this->assertDatabaseCount('the404_user_preferences', 1);
        $this->assertDatabaseHas('the404_user_preferences', [
            'user_id' => $user->id,
            'theme' => 'light',
        ]);
    }

    public function test_only_light_and_dark_are_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('theme.update'), ['theme' => 'system'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('theme');

        $this->assertDatabaseCount('the404_user_preferences', 0);
    }

    public function test_saved_preference_is_applied_before_paint_and_reflected_in_the_toggle(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->dark()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('var stored = "dark";', false)
            ->assertSee('data-theme-server="dark"', false)
            ->assertSee('aria-label="Theme: Dark. Switch to light."', false)
            ->assertSee('data-theme-endpoint="'.route('theme.update').'"', false);
    }

    public function test_accounts_without_a_preference_keep_the_device_choice(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('var stored = null;', false)
            ->assertDontSee('data-theme-server', false)
            ->assertSee('data-theme-endpoint="'.route('theme.update').'"', false)
            ->assertSee('aria-label="Theme: Light. Switch to dark."', false);
    }
}
