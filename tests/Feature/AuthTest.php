<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_see_the_login_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Restore the system.')
            ->assertSee('Sign in')
            ->assertSee('Operators sign in to continue their mission.');
    }

    public function test_guest_is_redirected_to_login_when_protecting_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_log_in_with_username_and_password(): void
    {
        $user = User::factory()->create([
            'username' => 'operator',
            'password' => 'secret123',
            'role' => 'student',
        ]);

        $response = $this->post(route('login'), [
            'username' => 'operator',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_is_redirected_to_the_instructor_dashboard_when_teacher(): void
    {
        User::factory()->create([
            'username' => 'teacher',
            'password' => 'secret123',
            'role' => 'teacher',
        ]);

        $response = $this->post(route('login'), [
            'username' => 'teacher',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('students'));
    }

    public function test_admin_is_redirected_to_the_admin_dashboard_when_logging_in(): void
    {
        User::factory()->create([
            'username' => 'root',
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $response = $this->post(route('login'), [
            'username' => 'root',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_failure_returns_errors_and_does_not_authenticate(): void
    {
        User::factory()->create([
            'username' => 'operator',
            'password' => 'secret123',
        ]);

        $response = $this->post(route('login'), [
            'username' => 'operator',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_valid_login_records_a_login_activity(): void
    {
        $user = User::factory()->create([
            'username' => 'operator',
            'password' => 'secret123',
        ]);

        $this->post(route('login'), [
            'username' => 'operator',
            'password' => 'secret123',
        ]);

        $this->assertDatabaseHas('the404_activity', [
            'user_id' => $user->id,
            'type' => 'login',
        ]);
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_an_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('login'))->assertRedirect(route('dashboard'));
    }
}
