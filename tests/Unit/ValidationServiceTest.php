<?php

namespace Tests\Unit;

use App\Models\Mission;
use App\Services\ValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ValidationService;
    }

    public function test_blank_validate_rule_passes_trivially(): void
    {
        $mission = Mission::factory()->create(['validate_rule' => null]);

        $result = $this->service->validate($mission, '<h1>Hello</h1>');

        $this->assertTrue($result['passed']);
        $this->assertSame([], $result['failures']);
    }

    public function test_empty_string_validate_rule_passes_trivially(): void
    {
        $mission = Mission::factory()->create(['validate_rule' => '']);

        $result = $this->service->validate($mission, '<h1>Hello</h1>');

        $this->assertTrue($result['passed']);
        $this->assertSame([], $result['failures']);
    }

    public function test_non_array_validate_rule_passes_trivially(): void
    {
        $mission = Mission::factory()->create(['validate_rule' => '"just a string"']);

        $result = $this->service->validate($mission, '<h1>Hello</h1>');

        $this->assertTrue($result['passed']);
    }

    public function test_contains_rule_passes_when_found(): void
    {
        $rule = json_encode([['type' => 'contains', 'value' => '<h1>']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<h1>Title</h1>');

        $this->assertTrue($result['passed']);
        $this->assertSame([], $result['failures']);
    }

    public function test_contains_rule_fails_when_not_found(): void
    {
        $rule = json_encode([['type' => 'contains', 'value' => '<h1>']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<h2>Title</h2>');

        $this->assertFalse($result['passed']);
        $this->assertCount(1, $result['failures']);
    }

    public function test_default_failure_message_does_not_expose_rule_type_or_pattern(): void
    {
        $rule = json_encode([['type' => 'contains', 'value' => 'PRIVATE_VALIDATION_PATTERN']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, 'student work');

        $this->assertSame('Requirement 1 check failed.', $result['failures'][0]);
    }

    public function test_contains_all_rule_passes_when_all_found(): void
    {
        $rule = json_encode([['type' => 'contains_all', 'values' => ['<h1>', '<p>']]]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<h1>Title</h1><p>Body</p>');

        $this->assertTrue($result['passed']);
    }

    public function test_contains_all_rule_fails_when_one_missing(): void
    {
        $rule = json_encode([['type' => 'contains_all', 'values' => ['<h1>', '<p>']]]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<h1>Title</h1>');

        $this->assertFalse($result['passed']);
    }

    public function test_contains_any_rule_passes_when_any_found(): void
    {
        $rule = json_encode([['type' => 'contains_any', 'values' => ['<h1>', '<h2>']]]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<h2>Title</h2>');

        $this->assertTrue($result['passed']);
    }

    public function test_contains_any_rule_fails_when_none_found(): void
    {
        $rule = json_encode([['type' => 'contains_any', 'values' => ['<h1>', '<h2>']]]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<h3>Title</h3>');

        $this->assertFalse($result['passed']);
    }

    public function test_count_tag_rule_passes_on_exact_match(): void
    {
        $rule = json_encode([['type' => 'count_tag', 'tag' => 'div', 'count' => 2]]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<div>A</div><div>B</div>');

        $this->assertTrue($result['passed']);
    }

    public function test_count_tag_rule_fails_when_wrong_count(): void
    {
        $rule = json_encode([['type' => 'count_tag', 'tag' => 'div', 'count' => 2]]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<div>A</div>');

        $this->assertFalse($result['passed']);
    }

    public function test_malformed_count_tag_rule_throws_without_grading(): void
    {
        $rule = json_encode([['type' => 'count_tag', 'tag' => 'di/v', 'count' => 1]]);

        $this->expectException(RuntimeException::class);

        $this->service->validateRules($rule, '<div>A</div>');
    }

    public function test_count_rule_passes_on_exact_match(): void
    {
        $rule = json_encode([['type' => 'count', 'value' => 'class=', 'count' => 3]]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, 'class="a" class="b" class="c"');

        $this->assertTrue($result['passed']);
    }

    public function test_regex_rule_passes_on_match(): void
    {
        $rule = json_encode([['type' => 'regex', 'pattern' => '<div[^>]*class="container"']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<div class="container">Content</div>');

        $this->assertTrue($result['passed']);
    }

    public function test_regex_rule_fails_on_no_match(): void
    {
        $rule = json_encode([['type' => 'regex', 'pattern' => '<div[^>]*class="container"']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<div class="wrapper">Content</div>');

        $this->assertFalse($result['passed']);
    }

    public function test_exact_normalized_rule_passes_on_normalized_match(): void
    {
        $rule = json_encode([['type' => 'exact_normalized', 'value' => '  Hello   World  ']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, 'hello world');

        $this->assertTrue($result['passed']);
    }

    public function test_exact_normalized_rule_fails_on_mismatch(): void
    {
        $rule = json_encode([['type' => 'exact_normalized', 'value' => 'Hello World']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, 'Goodbye World');

        $this->assertFalse($result['passed']);
    }

    public function test_negate_inverts_contains_result(): void
    {
        $rule = json_encode([['type' => 'contains', 'value' => '<script>', 'negate' => true]]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $passResult = $this->service->validate($mission, '<h1>Safe</h1>');
        $this->assertTrue($passResult['passed']);

        $failResult = $this->service->validate($mission, '<script>alert(1)</script>');
        $this->assertFalse($failResult['passed']);
    }

    public function test_negate_failure_uses_must_not_message(): void
    {
        $rule = json_encode([['type' => 'contains', 'value' => '<script>', 'negate' => true]]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<script>alert(1)</script>');

        $this->assertStringContainsString('must not match', $result['failures'][0]);
    }

    public function test_operator_gte_passes_when_count_meets_threshold(): void
    {
        $rule = json_encode([['type' => 'count_tag', 'tag' => 'p', 'count' => 2, 'operator' => 'gte']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<p>A</p><p>B</p><p>C</p>');

        $this->assertTrue($result['passed']);
    }

    public function test_operator_lte_fails_when_count_exceeds_threshold(): void
    {
        $rule = json_encode([['type' => 'count_tag', 'tag' => 'p', 'count' => 2, 'operator' => 'lte']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<p>A</p><p>B</p><p>C</p>');

        $this->assertFalse($result['passed']);
    }

    public function test_operator_gt_passes_when_count_exceeds(): void
    {
        $rule = json_encode([['type' => 'count', 'value' => 'x', 'count' => 2, 'operator' => 'gt']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, 'xxx');

        $this->assertTrue($result['passed']);
    }

    public function test_operator_lt_fails_when_count_meets(): void
    {
        $rule = json_encode([['type' => 'count', 'value' => 'x', 'count' => 2, 'operator' => 'lt']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, 'xx');

        $this->assertFalse($result['passed']);
    }

    public function test_multiple_rules_all_must_pass(): void
    {
        $rules = json_encode([
            ['type' => 'contains', 'value' => '<h1>'],
            ['type' => 'count_tag', 'tag' => 'p', 'count' => 2],
        ]);
        $mission = Mission::factory()->create(['validate_rule' => $rules]);

        $passResult = $this->service->validate($mission, '<h1>Title</h1><p>A</p><p>B</p>');
        $this->assertTrue($passResult['passed']);

        $failResult = $this->service->validate($mission, '<h1>Title</h1><p>A</p>');
        $this->assertFalse($failResult['passed']);
    }

    public function test_custom_label_appears_in_failure_message(): void
    {
        $rule = json_encode([['type' => 'contains', 'value' => '<h1>', 'label' => 'Main heading']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<h2>Wrong</h2>');

        $this->assertStringContainsString('Main heading', $result['failures'][0]);
    }

    public function test_custom_message_overrides_default(): void
    {
        $rule = json_encode([['type' => 'contains', 'value' => '<h1>', 'message' => 'Custom error']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, '<h2>Wrong</h2>');

        $this->assertSame('Custom error', $result['failures'][0]);
    }

    public function test_invalid_rule_type_adds_failure(): void
    {
        $rule = json_encode([['type' => 'unknown_type', 'value' => 'x']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $result = $this->service->validate($mission, 'anything');

        $this->assertFalse($result['passed']);
        $this->assertSame('Challenge validation is unavailable. Try again later.', $result['failures'][0]);
    }

    public function test_no_eval_is_respected(): void
    {
        $rule = json_encode([['type' => 'contains', 'value' => "system('rm -rf /')"]]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $passResult = $this->service->validate($mission, "echo system('rm -rf /');");
        $this->assertTrue($passResult['passed']);

        $failResult = $this->service->validate($mission, 'safe code only');
        $this->assertFalse($failResult['passed']);
    }

    public function test_count_tag_with_gt_operator(): void
    {
        $rule = json_encode([['type' => 'count_tag', 'tag' => 'div', 'count' => 1, 'operator' => 'gt']]);
        $mission = Mission::factory()->create(['validate_rule' => $rule]);

        $this->assertTrue($this->service->validate($mission, '<div>A</div><div>B</div>')['passed']);
        $this->assertFalse($this->service->validate($mission, '<div>A</div>')['passed']);
    }

    public function test_class_never_executes_code(): void
    {
        $source = file_get_contents(app_path('Services/ValidationService.php'));

        foreach (['eval(', 'exec(', 'shell_exec(', 'proc_open('] as $grammar) {
            $this->assertStringNotContainsString($grammar, $source, "ValidationService must not call {$grammar}.");
        }
    }
}
