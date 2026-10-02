<?php

namespace Tests\Feature;

use Tests\TestCase;

class SetupSafetyTest extends TestCase
{
    public function test_installation_scripts_cannot_run_schema_or_seed_operations(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach (['setup', 'post-create-project-cmd', 'post-root-package-install', 'post-autoload-dump', 'post-update-cmd'] as $script) {
            foreach ($composer['scripts'][$script] ?? [] as $command) {
                $this->assertDoesNotMatchRegularExpression('/artisan\s+(?:migrate\b|db:(?:wipe|seed)\b)/', $command, $script);
            }
        }
    }
}
