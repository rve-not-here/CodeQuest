<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Services\AssessmentService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * System-managed Boss Challenge content (§8). Teachers are not content
 * authors — this seeder, running against the isolated/test database, is the
 * controlled place where each course's single assessment is authored.
 *
 * The seeder is idempotent: it skips any course that already has an
 * assessment, and it always routes creation through AssessmentService so the
 * one-per-course application-layer guard from US-401 is respected.
 *
 * Run with: php artisan db:seed --class=AssessmentSeeder
 */
class AssessmentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(private readonly AssessmentService $assessments) {}

    public function run(): void
    {
        Course::query()
            ->where('status', 'active')
            ->orderBy('order_num')
            ->get()
            ->each(function (Course $course): void {
                if ($this->assessments->existsForCourse($course)) {
                    return;
                }

                $this->assessments->createForCourse($course, $this->contentFor($course));
            });
    }

    /**
     * Authored Boss Challenge content for a course, keyed by subject type.
     *
     * @return array{title: string, description: string, instructions: string, grading_rule: string, passing_score: int}
     */
    private function contentFor(Course $course): array
    {
        $type = strtolower($course->type ?? 'html');

        return match ($type) {
            'css' => [
                'title' => 'Style Restoration — CSS Boss Challenge',
                'description' => $course->name.' final assessment: rebuild a styled broadcast page.',
                'instructions' => 'Restore the damaged stylesheet. Give the body a dark background, make the primary heading phosphor-green and monospace, and lay the menu items out horizontally. The page must render as a working emergency terminal.',
                'grading_rule' => json_encode([
                    ['type' => 'contains', 'value' => 'background', 'label' => 'Body background'],
                    ['type' => 'contains', 'value' => 'color', 'label' => 'Text color'],
                    ['type' => 'contains_all', 'values' => ['display', 'flex'], 'label' => 'Horizontal menu'],
                    ['type' => 'count_tag', 'tag' => 'style', 'count' => 1, 'operator' => 'gte', 'label' => 'Style rule present'],
                ]),
                'passing_score' => 70,
            ],
            'js' => [
                'title' => 'Uplink Repair — JS Boss Challenge',
                'description' => $course->name.' final assessment: rewire a broken control loop.',
                'instructions' => 'Repair the damaged script. Declare a function that toggles the signal state, attach it so the control button responds, and restart the tick counter when the operation completes. The console must stay clean.',
                'grading_rule' => json_encode([
                    ['type' => 'contains', 'value' => 'function', 'label' => 'Function declared'],
                    ['type' => 'contains_all', 'values' => ['addEventListener', 'click'], 'label' => 'Button wiring'],
                    ['type' => 'count_tag', 'tag' => 'script', 'count' => 1, 'operator' => 'gte', 'label' => 'Script rule present'],
                ]),
                'passing_score' => 70,
            ],
            default => [
                'title' => 'Signal Restoration — HTML Boss Challenge',
                'description' => $course->name.' final assessment: rebuild a damaged broadcast page.',
                'instructions' => 'Rebuild the damaged broadcast page from the supplied fragments. Restore the primary heading, rebuild a working navigation link to /safe, and include a signup form that submits to /register. Every missing element must be reconstructed for the signal to hold.',
                'grading_rule' => json_encode([
                    ['type' => 'count_tag', 'tag' => 'h1', 'count' => 1, 'operator' => 'gte', 'label' => 'Primary heading'],
                    ['type' => 'contains_all', 'values' => ['<a', 'href="/safe"'], 'label' => 'Safe navigation link'],
                    ['type' => 'contains_all', 'values' => ['<form', 'action="/register"'], 'label' => 'Registration form'],
                ]),
                'passing_score' => 70,
            ],
        };
    }
}
