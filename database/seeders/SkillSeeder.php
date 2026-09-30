<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckQuestion;
use App\Models\Mission;
use App\Models\Skill;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Canonical skill taxonomy and curriculum mappings (US-905/US-906).
 *
 * Skills derive strictly from the seeded CodeQuest curriculum
 * (~/system404/database/seed.sql): three courses typed html, css, js with
 * ten missions each, plus the per-type Boss Challenge content authored in
 * AssessmentSeeder. Every skill below names a concept taught by at least
 * one real mission; nothing is invented.
 *
 * Idempotent: skills upsert by stable key, mappings sync to the declared
 * sets. Whenever a sync actually changes a mission's, question's, or
 * assessment's mapped set, the parent curriculum version advances
 * explicitly — BelongsToMany syncs never dirty the parent row, so the
 * versioning hook cannot see them. Seeder writes carry no AdminAudit rows
 * by the accepted release/git convention; historical interpretation comes
 * from evidence snapshots, never from git.
 *
 * Mission lookup is by (course slug, mission title): the stable identifiers
 * in the seed data. Knowledge Check questions map to their parent mission's
 * primary skill (first listed skill), exactly one per question.
 */
class SkillSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function taxonomy(): array
    {
        return [
            'html.headings' => ['label' => 'HTML Headings', 'description' => 'Heading elements and document structure (h1).'],
            'html.links' => ['label' => 'HTML Links', 'description' => 'Anchor elements and destinations (a, href).'],
            'html.images' => ['label' => 'HTML Images', 'description' => 'Image elements with source and alt text (img, src, alt).'],
            'html.text' => ['label' => 'HTML Text', 'description' => 'Paragraphs and text content (p).'],
            'html.lists' => ['label' => 'HTML Lists', 'description' => 'Unordered lists and list items (ul, li).'],
            'html.forms' => ['label' => 'HTML Forms', 'description' => 'Form controls: inputs and buttons (input, button).'],
            'html.tables' => ['label' => 'HTML Tables', 'description' => 'Tabular structure: tables, rows, cells (table, tr, td).'],
            'html.structure' => ['label' => 'HTML Structure', 'description' => 'Document skeleton and containers (doctype, html, head, body, div, class).'],
            'css.color' => ['label' => 'CSS Color', 'description' => 'Text color with the color property.'],
            'css.background' => ['label' => 'CSS Background', 'description' => 'Element backgrounds with background-color.'],
            'css.typography' => ['label' => 'CSS Typography', 'description' => 'Text sizing with font-size.'],
            'css.spacing' => ['label' => 'CSS Spacing', 'description' => 'Inner spacing with padding.'],
            'css.borders' => ['label' => 'CSS Borders', 'description' => 'Border shorthand: width, style, color.'],
            'css.flexbox' => ['label' => 'CSS Flexbox', 'description' => 'Horizontal alignment with display flex and align-items.'],
            'css.effects' => ['label' => 'CSS Effects', 'description' => 'Transparency and animation (opacity, transition).'],
            'css.positioning' => ['label' => 'CSS Positioning', 'description' => 'Absolute positioning with top and right offsets.'],
            'css.responsive' => ['label' => 'CSS Responsive', 'description' => 'Media queries adapting layout to screen width.'],
            'js.variables' => ['label' => 'JS Variables', 'description' => 'Declaring variables with let and assigning values.'],
            'js.operators' => ['label' => 'JS Operators', 'description' => 'Arithmetic expressions with the addition operator.'],
            'js.conditionals' => ['label' => 'JS Conditionals', 'description' => 'Branching with if statements and comparisons.'],
            'js.loops' => ['label' => 'JS Loops', 'description' => 'Counted repetition with for loops.'],
            'js.functions' => ['label' => 'JS Functions', 'description' => 'Declaring functions with parameters and return values.'],
            'js.arrays' => ['label' => 'JS Arrays', 'description' => 'Ordered string collections in square brackets.'],
            'js.objects' => ['label' => 'JS Objects', 'description' => 'Key-value records in curly braces.'],
            'js.dom' => ['label' => 'JS DOM', 'description' => 'Selecting elements and updating textContent.'],
            'js.events' => ['label' => 'JS Events', 'description' => 'Wiring click handlers with addEventListener.'],
            'js.async' => ['label' => 'JS Async', 'description' => 'Async functions, await fetch, and JSON parsing.'],
        ];
    }

    /**
     * Mission mappings by course slug and mission title. Skills are listed
     * in priority order; the first entry is the primary skill used for
     * Knowledge Check question mapping.
     *
     * @return array<string, array<string, list<string>>>
     */
    public static function missionMappings(): array
    {
        return [
            'html-fundamentals' => [
                'Header_Reconstruction' => ['html.headings'],
                'Hyperlink_Restoration' => ['html.links'],
                'Image_Feed_Restoration' => ['html.images'],
                'Paragraph_Broadcast' => ['html.text'],
                'List_Structure_Repair' => ['html.lists'],
                'Input_Field_Calibration' => ['html.forms'],
                'Control_Button_Repair' => ['html.forms'],
                'Table_Grid_Reconstruction' => ['html.tables'],
                'Container_Structure' => ['html.structure'],
                'Full_Page_Structure' => ['html.structure', 'html.headings'],
            ],
            'css-styling' => [
                'Color_Signal_Restore' => ['css.color'],
                'Background_Blackout_Fix' => ['css.background'],
                'Font_Frequency_Calibration' => ['css.typography'],
                'Spacing_Shield_Repair' => ['css.spacing'],
                'Border_Perimeter_Restore' => ['css.borders'],
                'Flexbox_Grid_Alignment' => ['css.flexbox'],
                'Opacity_Cloak_Removal' => ['css.effects'],
                'Position_Anchor_Lock' => ['css.positioning'],
                'Transition_Signal_Smooth' => ['css.effects'],
                'Responsive_Frequency_Lock' => ['css.responsive'],
            ],
            'js-scripting' => [
                'Variable_Signal_Declare' => ['js.variables'],
                'Arithmetic_Core_Repair' => ['js.operators'],
                'Conditional_Gate_Restore' => ['js.conditionals'],
                'Loop_Transmitter_Repair' => ['js.loops'],
                'Function_Protocol_Rebuild' => ['js.functions'],
                'Array_Database_Reconstruct' => ['js.arrays'],
                'Object_Record_Restoration' => ['js.objects'],
                'DOM_Node_Activation' => ['js.dom'],
                'Event_Listener_Rewire' => ['js.events'],
                'Fetch_Transmission_Protocol' => ['js.async'],
            ],
        ];
    }

    /**
     * Boss Challenge mappings by course slug. Stored for future use; v1
     * skill scoring ignores Boss evidence entirely.
     *
     * @return array<string, list<string>>
     */
    public static function assessmentMappings(): array
    {
        return [
            'html-fundamentals' => ['html.headings', 'html.links', 'html.forms'],
            'css-styling' => ['css.background', 'css.color', 'css.flexbox'],
            'js-scripting' => ['js.functions', 'js.events'],
        ];
    }

    public function run(): void
    {
        $skillIds = $this->syncTaxonomy();
        $missionSkillKeys = $this->syncMissionMappings($skillIds);
        $this->syncQuestionMappings($skillIds, $missionSkillKeys);
        $this->syncAssessmentMappings($skillIds);
    }

    /**
     * @return Collection<string, int> skill key => id
     */
    private function syncTaxonomy(): Collection
    {
        $ids = collect();

        foreach (self::taxonomy() as $key => $meta) {
            $skill = Skill::updateOrCreate(
                ['key' => $key],
                ['label' => $meta['label'], 'description' => $meta['description']]
            );
            $ids->put($key, $skill->id);
        }

        return $ids;
    }

    /**
     * @param  Collection<string, int>  $skillIds
     * @return Collection<int, list<string>> mission id => ordered skill keys
     */
    private function syncMissionMappings(Collection $skillIds): Collection
    {
        $mapped = collect();

        foreach (self::missionMappings() as $slug => $missions) {
            $course = Course::query()->where('slug', $slug)->first();

            if ($course === null) {
                continue;
            }

            foreach ($missions as $title => $keys) {
                $mission = Mission::query()
                    ->where('course_id', $course->id)
                    ->where('title', $title)
                    ->first();

                if ($mission === null) {
                    continue;
                }

                $ids = collect($keys)->map(fn (string $key): ?int => $skillIds->get($key))->filter()->values();

                if ($ids->count() !== count($keys)) {
                    continue;
                }

                $mapped->put($mission->id, $keys);

                $current = $mission->skills()->pluck('key')->sort()->values()->all();
                $desired = collect($keys)->sort()->values()->all();

                if ($current !== $desired) {
                    $mission->skills()->sync($ids->all());
                    $mission->increment('version');
                }
            }
        }

        return $mapped;
    }

    /**
     * @param  Collection<string, int>  $skillIds
     * @param  Collection<int, list<string>>  $missionSkillKeys  mission id => ordered skill keys
     */
    private function syncQuestionMappings(Collection $skillIds, Collection $missionSkillKeys): void
    {
        $bumpedChecks = [];

        KnowledgeCheckQuestion::query()->with('knowledgeCheck')->chunkById(200, function ($questions) use ($skillIds, $missionSkillKeys, &$bumpedChecks): void {
            foreach ($questions as $question) {
                $missionId = $question->knowledgeCheck?->mission_id;

                if ($missionId === null) {
                    continue;
                }

                $keys = $missionSkillKeys->get($missionId);

                if (empty($keys)) {
                    continue;
                }

                $primaryId = $skillIds->get($keys[0]);

                if ($primaryId === null) {
                    continue;
                }

                $current = $question->skills()->pluck('id')->all();

                if ($current !== [$primaryId]) {
                    $question->skills()->sync([$primaryId]);
                    $bumpedChecks[$question->knowledge_check_id] = true;
                }
            }
        });

        foreach (array_keys($bumpedChecks) as $checkId) {
            KnowledgeCheck::query()->where('id', $checkId)->increment('version');
        }
    }

    /**
     * @param  Collection<string, int>  $skillIds
     */
    private function syncAssessmentMappings(Collection $skillIds): void
    {
        foreach (self::assessmentMappings() as $slug => $keys) {
            $course = Course::query()->where('slug', $slug)->first();

            if ($course === null) {
                continue;
            }

            $ids = collect($keys)->map(fn (string $key): ?int => $skillIds->get($key))->filter()->values();

            if ($ids->count() !== count($keys)) {
                continue;
            }

            Assessment::query()->where('course_id', $course->id)->each(function (Assessment $assessment) use ($ids): void {
                $current = $assessment->skills()->pluck('id')->sort()->values()->all();
                $desired = $ids->sort()->values()->all();

                if ($current !== $desired) {
                    $assessment->skills()->sync($ids->all());
                    $assessment->increment('version');
                }
            });
        }
    }
}
