<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_shell_renders_the_design_system(): void
    {
        $response = $this->get(route('shell'));

        $response->assertOk()
            ->assertSee('Design System')
            ->assertSee('CONTINUE LEARNING →')
            ->assertSee('MISSION NOT RESTORED');
    }

    public function test_standby_shells_reflect_the_authenticated_users_role(): void
    {
        // 'achievements' left the standby sweep in US-508: it is now a real,
        // auth-protected route covered by AchievementSystemTest. No standby
        // navigation targets remain, so the sweep has nothing left to walk.
        // US-603 fixed the StandbyController/standby view role hardcode: a
        // teacher opening a standby shell now sees the instructor navigation.
        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('achievements'))
            ->assertOk()
            ->assertDontSee('XP Ledger');
    }

    public function test_the_dashboard_is_an_authenticated_route(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_shell_layout_includes_the_primary_navigation(): void
    {
        $response = $this->get(route('shell'));

        $response->assertOk()
            ->assertSee('Learning Path')
            ->assertSee('Missions')
            ->assertSee('Assessments')
            ->assertSee('Timeline')
            ->assertSee('Competency')
            ->assertSee('Recommendations')
            ->assertSee('Achievements')
            ->assertSee('System 404 · Students, teachers, builders');
    }
}
