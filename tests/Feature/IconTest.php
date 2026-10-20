<?php

namespace Tests\Feature;

use App\Support\IconMap;
use App\Support\StampIcon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class IconTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_generated_icon_map_matches_the_views(): void
    {
        $result = Process::timeout(60)
            ->path(base_path())
            ->run(['node', 'scripts/build-icons.mjs', '--check']);

        $this->assertTrue($result->successful(), $result->errorOutput());
        $this->assertStringContainsString('up to date', $result->output());
    }

    public function test_decorative_icons_are_hidden_from_assistive_technology(): void
    {
        $this->blade('<x-icon name="bell" />')
            ->assertSee('class="icon"', false)
            ->assertSee('viewBox="0 0 24 24"', false)
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('focusable="false"', false)
            ->assertSee('width="16" height="16"', false)
            ->assertDontSee('role="img"', false);
    }

    public function test_labelled_icons_expose_an_accessible_name(): void
    {
        $this->blade('<x-icon name="log-out" label="Sign out" />')
            ->assertSee('role="img"', false)
            ->assertSee('aria-label="Sign out"', false)
            ->assertDontSee('aria-hidden', false);
    }

    public function test_icon_sizes_are_restricted_to_the_design_scale(): void
    {
        $this->blade('<x-icon name="bell" :size="14" />')
            ->assertSee('width="14" height="14"', false);

        $this->blade('<x-icon name="bell" size="30" />')
            ->assertSee('width="16" height="16"', false);
    }

    public function test_every_runtime_resolved_icon_name_is_generated(): void
    {
        $runtimeNames = [
            'activity', 'archive', 'arrow-up-right', 'award', 'badge-check', 'bell', 'check',
            'circle', 'circle-check', 'circle-dot', 'circle-question-mark', 'eye', 'flag',
            'hourglass', 'lightbulb', 'loader-circle', 'locate', 'lock', 'lock-open', 'mail',
            'mail-open', 'megaphone', 'minus', 'pencil', 'plus', 'sparkles', 'target',
            'trending-up', 'triangle-alert', 'x',
        ];

        foreach ($runtimeNames as $name) {
            $this->assertTrue(IconMap::has($name), "Missing runtime icon: {$name}");
        }

        foreach (StampIcon::icons() as $label => $icon) {
            $this->assertTrue(IconMap::has($icon), "Stamp \"{$label}\" uses missing icon \"{$icon}\".");
        }
    }

    public function test_unknown_icon_names_fall_back_to_the_dot_marker(): void
    {
        $this->blade('<x-icon name="not-a-real-icon" />')
            ->assertSee('<circle cx="12" cy="12" r="1"', false);
    }

    public function test_icon_attributes_merge_without_losing_the_base_class(): void
    {
        $this->blade('<x-icon name="bell" class="atlas-example" data-theme-icon="light" />')
            ->assertSee('class="icon atlas-example"', false)
            ->assertSee('data-theme-icon="light"', false);
    }
}
