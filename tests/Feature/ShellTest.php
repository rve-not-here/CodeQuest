<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_dismissible_status_messages_do_not_need_live_region_semantics(): void
    {
        $this->blade('<x-status-message dismissible>Draft saved</x-status-message>')
            ->assertSee('data-status-message', false)
            ->assertSee('data-dismiss', false)
            ->assertDontSee('role=', false)
            ->assertDontSee('aria-live=', false)
            ->assertDontSee('aria-atomic=', false);
    }

    public function test_error_status_messages_retain_alert_semantics_and_dismiss_hook(): void
    {
        $this->blade('<x-status-message type="error" dismissible>Save failed</x-status-message>')
            ->assertSee('role="alert"', false)
            ->assertSee('data-status-message', false)
            ->assertSee('data-dismiss', false);
    }

    public function test_the_shell_renders_the_design_system(): void
    {
        $response = $this->get(route('shell'));

        $response->assertOk()
            ->assertSee('Design System')
            ->assertSee('Component examples only')
            ->assertSee('CONTINUE LEARNING →')
            ->assertSee('RULE FAILED');
    }

    public function test_teacher_cannot_open_the_student_achievement_page(): void
    {
        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('achievements'))
            ->assertForbidden();
    }

    public function test_the_dashboard_is_an_authenticated_route(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_shell_layout_includes_the_primary_navigation(): void
    {
        $response = $this->get(route('shell'));

        $response->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Learning Path')
            ->assertSee('Notifications')
            ->assertDontSee('Challenge Index')
            ->assertSee('CODEQUEST // SYSTEM 404 LEARNING NETWORK');
    }
}
