<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleNavigationTest extends TestCase
{
    use RefreshDatabase;

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
