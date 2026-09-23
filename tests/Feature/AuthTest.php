<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
            ->assertDontSee('Remember me')
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

    public function test_remember_option_does_not_break_login_with_legacy_user_schema(): void
    {
        $user = User::factory()->create([
            'username' => 'remembered',
            'password' => 'secret123',
        ]);

        $this->post(route('login'), [
            'username' => 'remembered',
            'password' => 'secret123',
            'remember' => '1',
        ])->assertRedirect(route('dashboard'))
            ->assertCookieMissing(Auth::guard()->getRecallerName());

        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_rotates_the_session_identifier(): void
    {
        User::factory()->create([
            'username' => 'session-user',
            'password' => 'secret123',
        ]);
        $this->get(route('login'));
        $previousSessionId = session()->getId();

        $this->post(route('login'), [
            'username' => 'session-user',
            'password' => 'secret123',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotSame($previousSessionId, session()->getId());
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

    public function test_operator_is_redirected_to_own_notifications_instead_of_the_student_dashboard(): void
    {
        User::factory()->create([
            'username' => 'system-operator',
            'password' => 'secret123',
            'role' => 'operator',
        ]);

        $this->post(route('login'), [
            'username' => 'system-operator',
            'password' => 'secret123',
        ])->assertRedirect(route('notifications'));

        $this->get(route('notifications'))->assertOk();
        $this->get(route('dashboard'))->assertForbidden();
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

    public function test_invalid_login_uses_the_same_safe_error_for_known_and_unknown_users(): void
    {
        $user = User::factory()->create([
            'username' => 'known',
            'password' => 'secret123',
        ]);

        foreach (['known', 'missing'] as $username) {
            $this->post(route('login'), [
                'username' => $username,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors(['username' => 'Invalid credentials.'])
                ->assertDontSee($user->password);
        }

        $this->assertGuest();
    }

    public function test_client_supplied_role_and_identity_do_not_change_the_authenticated_account(): void
    {
        $student = User::factory()->create([
            'username' => 'student-login',
            'password' => 'secret123',
            'role' => 'student',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post(route('login'), [
            'username' => 'student-login',
            'password' => 'secret123',
            'role' => 'admin',
            'user_id' => $admin->id,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($student);
    }

    public function test_repeated_login_attempts_are_rate_limited(): void
    {
        User::factory()->create([
            'username' => 'target',
            'password' => 'secret123',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'username' => 'target',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('username');
        }

        $this->post(route('login'), [
            'username' => 'target',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();

        $this->assertGuest();
    }

    public function test_login_limit_does_not_block_another_account_on_the_same_ip(): void
    {
        $user = User::factory()->create([
            'username' => 'other-account',
            'password' => 'secret123',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'username' => 'target-account',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('username');
        }

        $this->post(route('login'), [
            'username' => 'other-account',
            'password' => 'secret123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
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
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_logout_invalidates_the_session_and_rotates_the_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get(route('dashboard'));
        $previousSessionId = session()->getId();
        $previousToken = session()->token();

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertNotSame($previousSessionId, session()->getId());
        $this->assertNotSame($previousToken, session()->token());
        $this->assertGuest();
    }

    public function test_an_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('login'))->assertRedirect(route('dashboard'));
    }
}
