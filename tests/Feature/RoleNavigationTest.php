<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_primary_navigation_uses_learning_terms_and_report_shares_the_shell(): void
    {
        $student = User::factory()->create();
        foreach (['dashboard', 'reports.progress'] as $route) {
            $response = $this->actingAs($student)->get(route($route))->assertOk();
            preg_match('/<nav aria-label="Primary"[^>]*>(.*?)<\/nav>/s', $response->getContent(), $primary);
            $this->assertStringContainsString('Dashboard', $primary[1]);
            $this->assertStringContainsString('Learn', $primary[1]);
            $this->assertStringContainsString('Boss Challenges', $primary[1]);
            $this->assertStringNotContainsString('Missions', $primary[1]);
            $this->assertStringNotContainsString('Assessments', $primary[1]);
            $response->assertSee('aria-label="Learning record"', false);
        }
    }

    /** @return array<string, array{string, string}> */
    public static function roleHomes(): array
    {
        return [
            'student' => ['student', 'dashboard'],
            'teacher' => ['teacher', 'students'],
            'admin' => ['admin', 'admin.dashboard'],
            'operator' => ['operator', 'notifications'],
        ];
    }

    #[DataProvider('roleHomes')]
    public function test_role_home_brand_and_offered_navigation_are_authorized(string $role, string $home): void
    {
        $user = User::factory()->create(['role' => $role]);
        $response = $this->actingAs($user)->get(route($home))->assertOk();
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/href="'.preg_quote(route($home), '/').'"[^>]*aria-label="CodeQuest home"/', $html);

        preg_match_all('/<(?:nav|aside)\b[^>]*>.*?<\/(?:nav|aside)>/s', $html, $regions);
        foreach ($regions[0] as $region) {
            preg_match_all('/href="([^"]+)"/', $region, $links);
            foreach (array_unique($links[1]) as $link) {
                if (str_starts_with($link, 'http')) {
                    $this->get(html_entity_decode($link))->assertSuccessful();
                }
            }
        }

        $this->get(route('login'))->assertRedirect(route($home));
        if ($role !== 'student') {
            $this->get(route('dashboard'))->assertForbidden();
            $this->get(route('assessments'))->assertForbidden();
            $this->get(route('competency'))->assertForbidden();
        }
    }
}
