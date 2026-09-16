<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_inactive_account_cannot_log_in(): void
    {
        User::factory()->deactivated()->create([
            'username' => 'retired',
            'password' => 'secret123',
        ]);

        $response = $this->post(route('login'), [
            'username' => 'retired',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('account');
        $this->assertGuest();
    }

    public function test_a_deactivated_account_is_locked_out_on_the_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertOk();

        $user->forceFill(['status' => 'inactive'])->save();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_an_active_account_passes_the_per_request_status_check(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertOk();
        $this->assertAuthenticatedAs($user);
    }
}
