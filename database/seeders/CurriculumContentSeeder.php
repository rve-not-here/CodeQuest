<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\Mission;
use App\Models\MissionBehaviorTest;
use App\Models\Section;
use App\Services\ValidationService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

/**
 * System-managed curriculum content (§3 ordering, §12 taught overview).
 * Teachers are not content authors, so lessons live here under code review
 * instead of an admin write path.
 *
 * Idempotent: courses, sections, and missions are matched by order, never
 * duplicated. Solutions, targets, hints, points and difficulty are preserved.
 * JavaScript runtime lessons receive server-owned behavior checks and syntax
 * rules limited to the taught construct. Reruns preserve student history.
 *
 * Lesson bodies are original CodeQuest explanations written in a
 * reference-then-task style: concept, syntax, example, then the task. They
 * render as paragraphs through .lesson-copy (white-space: pre-line).
 *
 * Run with: php artisan db:seed --class=CurriculumContentSeeder
 */
class CurriculumContentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $curriculum = $this->curriculum();
        $validator = app(ValidationService::class);

        foreach ($curriculum as $courseData) {
            foreach ($this->sectionsFor($courseData) as $sectionData) {
                foreach ($sectionData['missions'] as $mission) {
                    $definition = $courseData['type'] === 'js' ? $this->javaScriptBehavior($mission['title']) : null;
                    $rules = $definition !== null
                        ? json_encode($definition['rules'], JSON_THROW_ON_ERROR)
                        : (string) ($mission['validate_rule'] ?? '');

                    if (! $validator->isValidDefinition($rules, allowEmpty: ! empty($definition['tests']))) {
                        throw new InvalidArgumentException('Invalid mission rule definition: '.$mission['title']);
                    }
                }
            }
        }

        foreach ($curriculum as $courseData) {
            $course = Course::query()->updateOrCreate(
                ['order_num' => $courseData['order_num']],
                [
                    'slug' => $courseData['slug'],
                    'name' => $courseData['name'],
                    'type' => $courseData['type'],
                    'description' => $courseData['description'],
                ]
            );

            $missionOrder = 0;

            foreach ($this->sectionsFor($courseData) as $sectionOrder => $sectionData) {
                $section = Section::query()->updateOrCreate(
                    ['course_id' => $course->id, 'order_num' => $sectionOrder + 1],
                    ['title' => $sectionData['title'], 'description' => $sectionData['description']],
                );

                foreach ($sectionData['missions'] as $mission) {
                    $missionOrder++;
                    $definition = $courseData['type'] === 'js'
                        ? $this->javaScriptBehavior($mission['title'])
                        : null;

                    if ($definition !== null) {
                        $mission['validate_rule'] = json_encode($definition['rules'], JSON_THROW_ON_ERROR);
                    }

                    $seededMission = Mission::query()->firstOrNew([
                        'course_id' => $course->id,
                        'order_num' => $missionOrder,
                    ]);
                    $seededMission->fill(array_merge($mission, ['section_id' => $section->id]));

                    $materialChanged = collect($seededMission->curriculumVersionMaterialFields())->contains(
                        fn (string $field): bool => $seededMission->isDirty($field)
                            && Mission::versionValueChanged($seededMission->getAttribute($field), $seededMission->getOriginal($field)),
                    );

                    if ($seededMission->exists && $materialChanged) {
                        $seededMission->version = max(1, (int) $seededMission->version) + 1;
                    }

                    $seededMission->save();

                    foreach ($definition['tests'] ?? [] as $testOrder => $test) {
                        MissionBehaviorTest::query()->updateOrCreate(
                            ['mission_id' => $seededMission->id, 'order_num' => $testOrder + 1],
                            [
                                'name' => $test['name'],
                                'test_type' => $test['type'],
                                'configuration' => json_encode($test['configuration'], JSON_THROW_ON_ERROR),
                                'active' => true,
                            ],
                        );
                    }
                }
            }
        }

        $this->seedChecks();
    }

    /**
     * Fixed scripts use captured output plus state probes where output alone
     * omits required data. Reusable functions receive varied inputs. Probe
     * expressions use the existing runner lookup and only observe student
     * bindings; they never implement the student's missing computation.
     * Browser, network and module lessons retain their existing validators.
     *
     * @return array{rules: list<array<string, mixed>>, tests: list<array{name: string, type: string, configuration: array<string, mixed>}>}|null
     */
    private function javaScriptBehavior(string $title): ?array
    {
        $console = match ($title) {
            'Variable_Signal_Declare' => [['ONLINE']],
            'Arithmetic_Core_Repair' => [[150]],
            'Conditional_Gate_Restore' => [['ACCESS GRANTED']],
            'Loop_Transmitter_Repair' => [[1], [2], [3], [4], [5]],
            'Array_Database_Reconstruct' => [[3]],
            'Object_Record_Restoration' => [['CHEN']],
            'Template_Lines' => [['STATUS: ONLINE - SECTOR 7']],
            'Switch_Board' => [['VHF LINK']],
            'While_Watch' => [[3], [2], [1]],
            'Map_Filter' => [[[20, 40]]],
            'Destructure_Spread' => [[3]],
            'Try_Catch_JSON' => [['BAD PAYLOAD']],
            'Arrow_Callbacks' => [[10], [20], [30]],
            'Find_Reduce' => [[20, 60]],
            'Object_Tools' => [[['name', 'radio']], ['UHF']],
            'Strict_Logic' => [['string:LUCKY']],
            'For_Of_Control' => [[10], [20]],
            'Object_Entries' => [['Relay 7, UHF'], ['name=Relay 7'], ['band=UHF']],
            default => null,
        };

        $patterns = match ($title) {
            'Variable_Signal_Declare' => ['\\blet\\s+signalStatus\\s*='],
            'Arithmetic_Core_Repair' => ['\\+'],
            'Conditional_Gate_Restore' => ['\\bif\\s*\\(', '>=\\s*3'],
            'Loop_Transmitter_Repair' => ['\\bfor\\s*\\('],
            'Array_Database_Reconstruct' => ['\\boperators\\s*=\\s*\\['],
            'Object_Record_Restoration' => ['\\boperatorRecord\\s*=\\s*\\{'],
            'Template_Lines' => ['`', '\\$\\{\\s*status\\s*\\}', '\\$\\{\\s*sector\\s*\\}'],
            'Switch_Board' => ['\\bswitch\\s*\\(', '\\bcase\\b', '\\bbreak\\b', '\\bdefault\\s*:'],
            'While_Watch' => ['\\bwhile\\s*\\('],
            'Map_Filter' => ['\\.filter\\s*\\(', '\\.map\\s*\\('],
            'Destructure_Spread' => ['\\b(?:const|let)\\s*\\{[^}]*\\}\\s*=\\s*operator\\b', '\\.\\.\\.\\s*backup\\b'],
            'Try_Catch_JSON' => ['\\btry\\s*\\{', '\\bcatch\\b', '\\bJSON\\.parse\\s*\\('],
            'Arrow_Callbacks' => ['\\.forEach\\s*\\(', '=>'],
            'Find_Reduce' => ['\\.find\\s*\\(', '\\.reduce\\s*\\('],
            'Object_Tools' => ['\\bObject\\.keys\\s*\\('],
            'Class_Blueprint' => ['\\bclass\\s+Operator\\b', '\\bconstructor\\s*\\('],
            'Strict_Logic' => ['===', '\\?', '\\btypeof\\b'],
            'For_Of_Control' => ['\\bfor\\s*\\([^)]*\\bof\\b', '\\bcontinue\\b', '\\bbreak\\b'],
            'Object_Entries' => ['\\bObject\\.values\\s*\\(', '\\bObject\\.entries\\s*\\('],
            default => [],
        };

        $tests = [];
        if ($console !== null) {
            $tests[] = ['name' => 'Captured output', 'type' => 'console', 'configuration' => ['expected' => $console]];
        }

        $probe = match ($title) {
            'Variable_Signal_Declare' => ['() => signalStatus', 'ONLINE'],
            'Arithmetic_Core_Repair' => ['() => [baseScore, bonusScore, totalScore]', [100, 50, 150]],
            'Array_Database_Reconstruct' => ['() => operators', ['CHEN', 'REYES', 'OKAFOR']],
            'Object_Record_Restoration' => ['() => operatorRecord', ['name' => 'CHEN', 'clearance' => 4, 'active' => true]],
            'Map_Filter' => ['() => [readings, clean]', [[-5, 10, -2, 20], [20, 40]]],
            'Destructure_Spread' => ['() => [name, band, crew]', ['CHEN', 'UHF', ['CHEN', 'REYES', 'OKAFOR']]],
            'Find_Reduce' => ['() => [firstOver, total]', [20, 60]],
            default => null,
        };
        if ($probe !== null) {
            $tests[] = ['name' => 'Required data', 'type' => 'function', 'configuration' => [
                'function' => $probe[0], 'cases' => [['args' => [], 'expected' => $probe[1]]],
            ]];
        }

        if ($title === 'Function_Protocol_Rebuild') {
            $tests[] = ['name' => 'Operator greetings', 'type' => 'function', 'configuration' => [
                'function' => 'greetOperator',
                'cases' => [
                    ['args' => ['Chen'], 'expected' => 'WELCOME, CHEN'],
                    ['args' => ['reyes'], 'expected' => 'WELCOME, REYES'],
                    ['args' => ['OkAfOr'], 'expected' => 'WELCOME, OKAFOR'],
                    ['args' => [''], 'expected' => 'WELCOME, '],
                ],
            ]];
        }
        if ($title === 'Closure_Counter') {
            $tests[] = ['name' => 'Independent counters', 'type' => 'function', 'configuration' => [
                'function' => '() => { const a = makeCounter(), b = makeCounter(); return [a(), a(), b(), a(), b()]; }',
                'cases' => [
                    ['args' => [], 'expected' => [1, 2, 1, 3, 2]],
                    ['args' => [], 'expected' => [1, 2, 1, 3, 2]],
                ],
            ]];
        }
        if ($title === 'Class_Blueprint') {
            $tests[] = ['name' => 'Instance reports', 'type' => 'function', 'configuration' => [
                'function' => '(name) => new Operator(name).report()',
                'cases' => [
                    ['args' => ['CHEN'], 'expected' => 'CHEN READY'],
                    ['args' => ['REYES'], 'expected' => 'REYES READY'],
                    ['args' => ['OKAFOR'], 'expected' => 'OKAFOR READY'],
                ],
            ]];
        }

        return $tests === [] ? null : [
            'rules' => array_map(fn (string $pattern): array => ['type' => 'regex', 'pattern' => $pattern], $patterns),
            'tests' => $tests,
        ];
    }

    /**
     * Base sections plus the expansion sections appended after them, so new
     * missions continue the order sequence instead of overwriting early ones.
     *
     * @param  array{order_num: int, sections: list<array{title: string, description: string, missions: list<array<string, mixed>>}>}  $courseData
     * @return list<array{title: string, description: string, missions: list<array<string, mixed>>}>
     */
    private function sectionsFor(array $courseData): array
    {
        return array_merge(
            $courseData['sections'],
            $this->extraSections($courseData['order_num']),
            $this->gapSections($courseData['order_num'])
        );
    }

    /**
     * Final coverage-gap pass. Same shape and rules as extraSections().
     *
     * @return list<array{title: string, description: string, missions: list<array<string, mixed>>}>
     */
    private function gapSections(int $courseOrder): array
    {
        return match ($courseOrder) {
            1 => array_merge($this->htmlGapSections(), $this->htmlFinishSections()),
            2 => array_merge($this->cssGapSections(), $this->cssFinishSections()),
            3 => array_merge($this->jsGapSections(), $this->jsFinishSections()),
            default => [],
        };
    }

    /**
     * Formative checks for key sections. Seeded as optional by default so
     * baseline content does not silently gate existing missions; a required
     * check is turned on per mission through the admin Knowledge Check
     * metadata screen (P1-1), which also owns the draft/published lifecycle.
     * Matched by course and mission order, questions by order, options by
     * order, so reruns update instead of duplicating.
     */
    private function seedChecks(): void
    {
        foreach ($this->knowledgeChecks() as $checkData) {
            $course = Course::query()->where('order_num', $checkData['course_order'])->first();

            if ($course === null) {
                continue;
            }

            $mission = Mission::query()
                ->where('course_id', $course->id)
                ->where('order_num', $checkData['mission_order'])
                ->first();

            if ($mission === null) {
                continue;
            }

            $seededCheck = KnowledgeCheck::query()->firstOrNew([
                'mission_id' => $mission->id,
                'order_num' => $checkData['order_num'],
            ]);
            $seededCheck->fill([
                'title' => $checkData['title'],
                'instructions' => $checkData['instructions'],
                // A Knowledge Check is the mission's concept check: it gates
                // the coding challenge, so it is required by default. The
                // admin screen can still make an individual check optional.
                'is_required' => true,
                'status' => KnowledgeCheck::STATUS_PUBLISHED,
            ]);

            // A material change to a check is a curriculum revision: the
            // version is what an attempt records, so it must move with the
            // definition (the mission path above works the same way).
            $checkMaterialChanged = collect($seededCheck->curriculumVersionMaterialFields())->contains(
                fn (string $field): bool => $seededCheck->isDirty($field)
                    && KnowledgeCheck::versionValueChanged($seededCheck->getAttribute($field), $seededCheck->getOriginal($field)),
            );

            if ($seededCheck->exists && $checkMaterialChanged) {
                $seededCheck->version = max(1, (int) $seededCheck->version) + 1;
            }

            $seededCheck->save();
            $check = $seededCheck;

            foreach ($checkData['questions'] as $questionOrder => $questionData) {
                $question = KnowledgeCheckQuestion::query()->updateOrCreate(
                    ['knowledge_check_id' => $check->id, 'order_num' => $questionOrder + 1],
                    [
                        'type' => KnowledgeCheckQuestion::TYPE_MULTIPLE_CHOICE,
                        'prompt' => $questionData['prompt'],
                        'explanation' => $questionData['explanation'],
                    ],
                );

                foreach ($questionData['options'] as $optionOrder => $optionText) {
                    KnowledgeCheckOption::query()->updateOrCreate(
                        ['knowledge_check_question_id' => $question->id, 'order_num' => $optionOrder + 1],
                        ['option_text' => $optionText, 'is_correct' => $optionOrder === $questionData['correct']],
                    );
                }
            }
        }
    }

    /**
     * @return list<array{order_num: int, slug: string, name: string, type: string, description: string, sections: list<array{title: string, description: string, missions: list<array<string, mixed>>}>}>
     */
    private function curriculum(): array
    {
        return [
            $this->htmlCourse(),
            $this->cssCourse(),
            $this->jsCourse(),
        ];
    }

    /** @return array{order_num: int, slug: string, name: string, type: string, description: string, sections: list<array{title: string, description: string, missions: list<array<string, mixed>>}>} */
    private function htmlCourse(): array
    {
        return [
            'order_num' => 1,
            'slug' => 'html-fundamentals',
            'name' => 'HTML Fundamentals',
            'type' => 'html',
            'description' => 'HTML gives every page its structure. This course walks from single elements (headings, links, images, paragraphs) through lists, inputs, and tables to a complete page skeleton.',
            'sections' => [
                [
                    'title' => 'Text and Structure',
                    'description' => 'The elements that carry readable content: headings, links, images, and paragraphs.',
                    'missions' => [
                        [
                            'title' => 'Header_Reconstruction',
                            'difficulty' => 'EASY',
                            'points' => 50,
                            'description' => <<<'TEXT'
                                A heading tells the browser how important a line of text is. HTML offers six levels, h1 down to h6, and h1 marks the single most important headline on the page. Search engines and screen readers both use it to understand page structure, so every page should have exactly one clear h1.

                                Syntax: an opening tag, the headline text, then a matching closing tag with a forward slash.

                                Example: <h1>Emergency Broadcast</h1> renders as the largest headline on the page.

                                Your task: the broadcast header below is corrupted. Restore the h1 element around EMERGENCY BROADCAST so the signal headline is valid again.
                                TEXT,
                            'broken_code' => '<H1>EMERGENCY BROADCAST</H1>',
                            'solution_code' => '<h1>EMERGENCY BROADCAST</h1>',
                            'target_html' => '<h1>EMERGENCY BROADCAST</h1>',
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<h1>', '</h1>', 'EMERGENCY BROADCAST']]]),
                            'hints' => json_encode(['The h1 element defines the primary heading.', 'Syntax: <h1>content</h1>', 'Ensure proper opening and closing tags.']),
                        ],
                        [
                            'title' => 'Hyperlink_Restoration',
                            'difficulty' => 'EASY',
                            'points' => 60,
                            'description' => <<<'TEXT'
                                Links are what make the web a web. The anchor element wraps clickable text, and its href attribute holds the destination: a path like /safe, a full URL, or a page fragment. Without href, the text looks like a link but goes nowhere.

                                Syntax: <a href="destination">clickable text</a>. The attribute sits inside the opening tag, with the value in quotes.

                                Example: <a href="/safe">Return to Safety</a> sends the reader to the /safe path when clicked.

                                Your task: the evacuation link lost its destination. Give the anchor an href of /safe so operators can click through.
                                TEXT,
                            'broken_code' => '<a>Return to Safety</a>',
                            'solution_code' => '<a href="/safe">Return to Safety</a>',
                            'target_html' => '<a href="/safe">Return to Safety</a>',
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<a', 'href=', '/safe', '</a>']]]),
                            'hints' => json_encode(['Anchor tags require href attribute.', 'Format: <a href="path">text</a>', 'The href value is the destination path.']),
                        ],
                        [
                            'title' => 'Image_Feed_Restoration',
                            'difficulty' => 'EASY',
                            'points' => 60,
                            'description' => <<<'TEXT'
                                Images are void elements: they have no closing tag and cannot wrap text. Everything the browser needs goes into attributes. The src attribute points at the image file, and the alt attribute carries a text description used when the image cannot load and read aloud by screen readers.

                                Syntax: <img src="file.png" alt="description of the image">. Both attributes are required for correct, accessible markup.

                                Example: <img src="signal.png" alt="Visual Feed"> loads signal.png and falls back to the words Visual Feed.

                                Your task: the visual feed tag is broken. Rebuild it as a self-closing img with src signal.png and a Visual Feed alt description.
                                TEXT,
                            'broken_code' => '<img>Visual Feed</img>',
                            'solution_code' => '<img src="signal.png" alt="Visual Feed">',
                            'target_html' => '<img src="signal.png" alt="Visual Feed">',
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<img', 'src=', 'signal.png', 'alt=']]]),
                            'hints' => json_encode(['Image tags are self-closing void elements.', 'Use src attribute for the file path.', 'Alt text provides fallback description.']),
                        ],
                        [
                            'title' => 'Paragraph_Broadcast',
                            'difficulty' => 'EASY',
                            'points' => 50,
                            'description' => <<<'TEXT'
                                Paragraphs group running text into readable blocks. Browsers add vertical space between p elements automatically, which is what turns a wall of words into scannable copy. A paragraph is a block-level element, so it always starts on a new line and stretches the full width available.

                                Syntax: <p>Your sentence or sentences go here.</p>. Keep one idea per paragraph, the way you would in any document.

                                Example: <p>This is an emergency broadcast signal.</p> renders as a spaced block of text.

                                Your task: the broadcast sentence is floating without markup. Wrap it in a p element so it displays as a proper paragraph.
                                TEXT,
                            'broken_code' => 'This is an emergency broadcast signal.',
                            'solution_code' => '<p>This is an emergency broadcast signal.</p>',
                            'target_html' => '<p>This is an emergency broadcast signal.</p>',
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<p>', '</p>', 'emergency broadcast signal']]]),
                            'hints' => json_encode(['Paragraph tags wrap text content.', 'Syntax: <p>text content</p>', 'P elements are block-level elements.']),
                        ],
                    ],
                ],
                [
                    'title' => 'Lists and Input',
                    'description' => 'Grouped content and user controls: lists, text inputs, and buttons.',
                    'missions' => [
                        [
                            'title' => 'List_Structure_Repair',
                            'difficulty' => 'MEDIUM',
                            'points' => 80,
                            'description' => <<<'TEXT'
                                An unordered list groups related items with bullets. It takes two tags working together: ul marks the list itself, and each li marks one item inside it. Use ul when the order does not matter, and ol with the same li items when sequence matters, like ranked steps.

                                Syntax: <ul> on its own line, one <li>item</li> per line, then </ul>. Indent the items so the nesting is visible.

                                Example: a three-item supply list is <ul> with three li children holding Item1, Item2, and Item3.

                                Your task: the comma-separated supply line is unreadable. Rebuild it as a ul containing exactly three li items.
                                TEXT,
                            'broken_code' => '<data>Item1, Item2, Item3</data>',
                            'solution_code' => "<ul>\n  <li>Item1</li>\n  <li>Item2</li>\n  <li>Item3</li>\n</ul>",
                            'target_html' => '<ul><li>Item1</li><li>Item2</li><li>Item3</li></ul>',
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<ul>', '</ul>']], ['type' => 'count_tag', 'tag' => 'li', 'count' => 3, 'operator' => 'gte']]),
                            'hints' => json_encode(['Use <ul> for unordered lists.', 'Each item is wrapped in <li> tags.', 'Three list items are required.']),
                        ],
                        [
                            'title' => 'Input_Field_Calibration',
                            'difficulty' => 'MEDIUM',
                            'points' => 90,
                            'description' => <<<'TEXT'
                                The input element collects a single line of user data. Like img, it is void: no closing tag, everything in attributes. The type attribute decides what the field accepts (text, password, number, and more), name labels the value for the server, and placeholder shows grey hint text that vanishes when the user types.

                                Syntax: <input type="text" name="username" placeholder="Enter ID">. All three attributes together make a complete, usable field.

                                Example: a login form field uses type text, name username, and a short placeholder prompt.

                                Your task: the identification field is malformed. Rebuild it as a void input with type text, name username, and an Enter ID placeholder.
                                TEXT,
                            'broken_code' => '<input>Username</input>',
                            'solution_code' => '<input type="text" name="username" placeholder="Enter ID">',
                            'target_html' => '<input type="text" name="username" placeholder="Enter ID">',
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<input', 'type=', 'text', 'name=']]]),
                            'hints' => json_encode(['Input is a self-closing void element.', 'The type attribute defines input behavior.', 'Placeholder shows hint text to users.']),
                        ],
                        [
                            'title' => 'Control_Button_Repair',
                            'difficulty' => 'MEDIUM',
                            'points' => 80,
                            'description' => <<<'TEXT'
                                Buttons trigger actions: submitting forms, starting processes, confirming choices. The tag is button, spelled in full, and unlike input it is a normal paired element, so the visible label sits between the opening and closing tags.

                                Syntax: <button>Label text</button>. The label should say what happens, using a verb like Initialize, Save, or Send.

                                Example: <button>Initialize</button> renders a clickable control labeled Initialize.

                                Your task: someone abbreviated the tag to btn, which browsers do not recognize. Replace it with a proper button element labeled Initialize.
                                TEXT,
                            'broken_code' => '<btn>Initialize</btn>',
                            'solution_code' => '<button>Initialize</button>',
                            'target_html' => '<button>Initialize</button>',
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<button>', '</button>', 'Initialize']]]),
                            'hints' => json_encode(['The correct tag is <button>, not <btn>.', 'Button requires both open and close tags.', 'Content between tags is the button label.']),
                        ],
                    ],
                ],
                [
                    'title' => 'Layout and Pages',
                    'description' => 'Bigger structures: data tables, generic containers, and the full document skeleton.',
                    'missions' => [
                        [
                            'title' => 'Table_Grid_Reconstruction',
                            'difficulty' => 'HARD',
                            'points' => 120,
                            'description' => <<<'TEXT'
                                Tables display grid data: rows and columns with a predictable shape. Three nested tags build one: table wraps everything, each tr marks one row, and each td holds one cell inside its row. Read it inside out: cells live in rows, rows live in the table.

                                Syntax: <table>, then one <tr> per row, with <td>cells</td> inside each row. A two-by-two grid needs two rows of two cells.

                                Example: readings A1 A2 on row one and B1 B2 on row two form a 2x2 table of four td cells.

                                Your task: the sensor grid collapsed into plain text. Rebuild it as a table with 2 rows and 4 cells holding A1, A2, B1, B2.
                                TEXT,
                            'broken_code' => '<grid>A1 A2 B1 B2</grid>',
                            'solution_code' => "<table>\n  <tr><td>A1</td><td>A2</td></tr>\n  <tr><td>B1</td><td>B2</td></tr>\n</table>",
                            'target_html' => '<table><tr><td>A1</td><td>A2</td></tr><tr><td>B1</td><td>B2</td></tr></table>',
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<table>', '</table>']], ['type' => 'count_tag', 'tag' => 'tr', 'count' => 2, 'operator' => 'gte'], ['type' => 'count_tag', 'tag' => 'td', 'count' => 4, 'operator' => 'gte']]),
                            'hints' => json_encode(['Table contains rows (tr) and cells (td).', 'Structure: table > tr > td.', 'Need 2 rows and 2 columns (4 cells total).']),
                        ],
                        [
                            'title' => 'Container_Structure',
                            'difficulty' => 'HARD',
                            'points' => 100,
                            'description' => <<<'TEXT'
                                The div is a generic block container with no meaning of its own. Its power comes from the class attribute, which attaches a reusable label that CSS and JavaScript can target. Think of div as a plain box and class as the sticker that says what the box is for.

                                Syntax: <div class="container">Content</div>. The class value is a plain word you choose; container is the conventional name for a page-width wrapper.

                                Example: wrapping Content in <div class='container'> groups it for centered, width-limited styling later.

                                Your task: the content sits in a meaningless box tag. Replace it with a div carrying the container class.
                                TEXT,
                            'broken_code' => '<box>Content</box>',
                            'solution_code' => "<div class='container'>Content</div>",
                            'target_html' => "<div class='container'>Content</div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<div', "class='container'", 'Content', '</div>']]]),
                            'hints' => json_encode(['Use <div> for generic containers.', 'Class attribute assigns an identifier.', 'div is a block-level structural element.']),
                        ],
                        [
                            'title' => 'Full_Page_Structure',
                            'difficulty' => 'HARD',
                            'points' => 150,
                            'description' => <<<'TEXT'
                                A complete page follows one skeleton. The <!DOCTYPE html> declaration tells the browser to use standards mode. The html element wraps everything. Inside it, head holds invisible metadata like the title, and body holds everything the reader sees. A page missing any layer may still render, but it renders unpredictably.

                                Syntax, top to bottom: <!DOCTYPE html>, <html>, <head> with <title>, then <body> with visible content, closing each layer in reverse.

                                Example: a page titled My Page showing a Hello World h1 needs all six landmarks: doctype, html, head, title, body, and h1.

                                Your task: the page fragment has content but no skeleton. Wrap it in the full document structure so every landmark is present.
                                TEXT,
                            'broken_code' => "<title>My Page</title>\n<h1>Hello World</h1>",
                            'solution_code' => "<!DOCTYPE html>\n<html>\n  <head>\n    <title>My Page</title>\n  </head>\n  <body>\n    <h1>Hello World</h1>\n  </body>\n</html>",
                            'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a;color:#6fc49a;font-family:monospace;line-height:2'><div style='color:#d9a94f'>✓ DOCTYPE declared</div><div>✓ html wraps everything</div><div>✓ head contains title</div><div>✓ body contains h1</div></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<!DOCTYPE html>', '<html>', '<head>', '<body>', '</body>', '</html>']]]),
                            'hints' => json_encode(['Every HTML file starts with <!DOCTYPE html>.', 'html element wraps the entire document.', 'head = meta info; body = visible content.']),
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return array{order_num: int, slug: string, name: string, type: string, description: string, sections: list<array{title: string, description: string, missions: list<array<string, mixed>>}>} */
    private function cssCourse(): array
    {
        return [
            'order_num' => 2,
            'slug' => 'css-styling',
            'name' => 'CSS Styling',
            'type' => 'css',
            'description' => 'CSS controls how the structured page looks. This course moves from single properties (color, background, size) through the box model and flexbox to overlays, motion, and responsive layouts.',
            'sections' => [
                [
                    'title' => 'Text and Color',
                    'description' => 'Styling what the reader sees first: text color, page background, and type size.',
                    'missions' => [
                        [
                            'title' => 'Color_Signal_Restore',
                            'difficulty' => 'EASY',
                            'points' => 50,
                            'description' => <<<'TEXT'
                                The color property paints text. Values come in three interchangeable forms: a named color like green, a hex code like #008000, or an rgb() function like rgb(0, 128, 0). Browsers treat all three identically, so pick whichever reads clearest in context.

                                Syntax: selector { color: value; }. The declaration sits inside the rule block and always ends with a semicolon.

                                Example: p { color: green; } turns every paragraph green.

                                Your task: the signal paragraph lost its color declaration. Add one so the text renders green.
                                TEXT,
                            'broken_code' => "p {\n  font-size: 16px;\n}",
                            'solution_code' => "p {\n  font-size: 16px;\n  color: green;\n}",
                            'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a'><p style='color:green;font-family:monospace;margin:0'>SIGNAL ONLINE — color restored.</p></div>",
                            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'color'], ['type' => 'contains_any', 'values' => ['green', '#008000', '#0f0', 'rgb(0,128,0)', 'rgb(0, 128, 0)', '#6fc49a', '#6FC49A', '#2e6b4f', '#2E6B4F']]]),
                            'hints' => json_encode(['The color property sets text color.', 'Syntax: color: value;', 'Use a named color like green, or a hex like #008000.']),
                        ],
                        [
                            'title' => 'Background_Blackout_Fix',
                            'difficulty' => 'EASY',
                            'points' => 50,
                            'description' => <<<'TEXT'
                                While color paints the text, background-color paints the surface behind it. The two properties are independent: setting one never affects the other. Terminal-style designs lean on this pairing constantly, light text on a near-black field.

                                Syntax: selector { background-color: value; }. It accepts the same named, hex, and rgb values as color.

                                Example: body { background-color: black; } drops the whole page onto a black field.

                                Your task: the display surface lost its background. Add a black background-color to the body rule.
                                TEXT,
                            'broken_code' => "body {\n  font-family: monospace;\n}",
                            'solution_code' => "body {\n  font-family: monospace;\n  background-color: black;\n}",
                            'target_html' => "<div style='background:black;padding:1.5rem;border:1px solid #6fc49a'><span style='color:#6fc49a;font-family:monospace'>DISPLAY BLACKOUT RESOLVED</span></div>",
                            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'background-color'], ['type' => 'contains_any', 'values' => ['black', '#000', '#000000', '#14161b', '#14161B', '#171b21', '#171B21', 'rgb(0,0,0)', 'rgb(0, 0, 0)', 'rgb(20,22,27)', 'rgb(20, 22, 27)']]]),
                            'hints' => json_encode(['background-color sets the element background.', 'Syntax: background-color: value;', 'black or #14161b are both valid values.']),
                        ],
                        [
                            'title' => 'Font_Frequency_Calibration',
                            'difficulty' => 'EASY',
                            'points' => 60,
                            'description' => <<<'TEXT'
                                The font-size property sets how large type renders. Pixels (px) are the fixed unit you will meet most often: 16px is roughly body copy, 32px is a strong headline. Larger projects later switch to relative units, but px gives exact control while learning.

                                Syntax: selector { font-size: 32px; }. Number first, unit attached with no space, semicolon to close.

                                Example: h1 { font-size: 32px; } makes top headlines twice body size.

                                Your task: the broadcast header kept its color but lost its size. Set its font-size to 32px.
                                TEXT,
                            'broken_code' => "h1 {\n  color: #6fc49a;\n}",
                            'solution_code' => "h1 {\n  color: #6fc49a;\n  font-size: 32px;\n}",
                            'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a'><h1 style='color:#6fc49a;font-size:32px;margin:0;font-family:monospace'>BROADCAST HEADER</h1></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['font-size', '32px']]]),
                            'hints' => json_encode(['font-size controls text size.', 'Syntax: font-size: 32px;', 'px stands for pixels — a fixed unit.']),
                        ],
                    ],
                ],
                [
                    'title' => 'Box Model and Layout',
                    'description' => 'How elements take up space and line up: padding, borders, and flexbox.',
                    'missions' => [
                        [
                            'title' => 'Spacing_Shield_Repair',
                            'difficulty' => 'EASY',
                            'points' => 60,
                            'description' => <<<'TEXT'
                                Padding is the space between an element's content and its border. It belongs to the element, so the background stretches across it. One value like 20px applies to all four sides at once; later you will meet per-side forms for finer control.

                                Syntax: selector { padding: 20px; }. Think of it as inner clearance that keeps text off the edges.

                                Example: .box { padding: 20px; } gives the box breathing room on every side.

                                Your task: the shield box has border and background but its content touches the edges. Add 20px of padding.
                                TEXT,
                            'broken_code' => ".box {\n  border: 2px solid #6fc49a;\n  background: #171b21;\n}",
                            'solution_code' => ".box {\n  border: 2px solid #6fc49a;\n  background: #171b21;\n  padding: 20px;\n}",
                            'target_html' => "<div style='border:2px solid #6fc49a;background:#171b21;padding:20px'><span style='color:#6fc49a;font-family:monospace'>CONTENT WITH PADDING</span></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['padding', '20px']]]),
                            'hints' => json_encode(['padding adds space inside an element.', 'Syntax: padding: 20px;', 'This applies 20px on all four sides.']),
                        ],
                        [
                            'title' => 'Border_Perimeter_Restore',
                            'difficulty' => 'MEDIUM',
                            'points' => 80,
                            'description' => <<<'TEXT'
                                The border shorthand draws the outline in one declaration: width, style, then color, separated by spaces. Common styles are solid for a clean line and dashed for a technical or warning feel. The three values can technically come in any order, but width-style-color is the convention every codebase expects.

                                Syntax: selector { border: 2px solid cyan; }. Two pixels thick, solid line, cyan color.

                                Example: .panel { border: 2px solid cyan; } frames the panel in a cyan perimeter.

                                Your task: the panel lost its perimeter. Add a 2px solid cyan border to the rule.
                                TEXT,
                            'broken_code' => ".panel {\n  background: #1c1f26;\n  padding: 1rem;\n}",
                            'solution_code' => ".panel {\n  background: #1c1f26;\n  padding: 1rem;\n  border: 2px solid cyan;\n}",
                            'target_html' => "<div style='background:#1c1f26;padding:1rem;border:2px solid cyan'><span style='color:cyan;font-family:monospace'>PERIMETER SECURED</span></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['border', '2px', 'solid', 'cyan']]]),
                            'hints' => json_encode(['border shorthand sets width, style, and color.', 'Syntax: border: 2px solid cyan;', 'Three values: thickness, style (solid/dashed), color.']),
                        ],
                        [
                            'title' => 'Flexbox_Grid_Alignment',
                            'difficulty' => 'MEDIUM',
                            'points' => 90,
                            'description' => <<<'TEXT'
                                Flexbox lines children up along one axis. Setting display: flex on a parent turns it into a flex container, and its children become flex items laid out in a row by default. The align-items property then controls the cross axis: center pulls every item to the vertical middle.

                                Syntax, both on the parent: display: flex; align-items: center;. One without the other does nothing useful.

                                Example: a nav bar with .nav { display: flex; align-items: center; } lines its logo and links in one centered row.

                                Your task: the nav has spacing but its items stack and sit high. Add both flex declarations so they form a centered row.
                                TEXT,
                            'broken_code' => ".nav {\n  background: #171b21;\n  padding: 1rem;\n  gap: 1rem;\n}",
                            'solution_code' => ".nav {\n  background: #171b21;\n  padding: 1rem;\n  gap: 1rem;\n  display: flex;\n  align-items: center;\n}",
                            'target_html' => "<div style='background:#171b21;padding:1rem;display:flex;align-items:center;gap:1rem;border:1px solid #6fc49a'><span style='color:#6fc49a;font-family:monospace'>NAV</span><span style='color:#d9a94f;font-family:monospace'>ITEM 1</span><span style='color:#d9a94f;font-family:monospace'>ITEM 2</span><span style='color:#d9a94f;font-family:monospace'>ITEM 3</span></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['display', 'flex', 'align-items', 'center']]]),
                            'hints' => json_encode(['display: flex turns an element into a flex container.', 'align-items: center vertically centers children.', 'Both properties are needed on the parent element.']),
                        ],
                    ],
                ],
                [
                    'title' => 'Effects and Responsive',
                    'description' => 'Motion, layering, and small screens: opacity, positioning, transitions, and media queries.',
                    'missions' => [
                        [
                            'title' => 'Opacity_Cloak_Removal',
                            'difficulty' => 'MEDIUM',
                            'points' => 80,
                            'description' => <<<'TEXT'
                                The opacity property sets transparency on a 0 to 1 scale. At 0 the element is fully invisible but still occupies layout space; at 1 it is fully solid. Values between fade it proportionally, which is handy for disabled states and overlays.

                                Syntax: selector { opacity: 1; }. No unit, just the number.

                                Example: .alert { opacity: 1; } makes the alert panel fully visible.

                                Your task: the alert panel is cloaked at opacity 0. Raise it to 1 so operators can read the warning.
                                TEXT,
                            'broken_code' => ".alert {\n  background: rgba(255,0,0,0.15);\n  border: 1px solid red;\n  color: red;\n  padding: 1rem;\n  opacity: 0;\n}",
                            'solution_code' => ".alert {\n  background: rgba(255,0,0,0.15);\n  border: 1px solid red;\n  color: red;\n  padding: 1rem;\n  opacity: 1;\n}",
                            'target_html' => "<div style='background:rgba(255,0,0,0.15);border:1px solid red;color:red;padding:1rem;font-family:monospace;opacity:1'>⚠ ALERT PANEL VISIBLE</div>",
                            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'opacity'], ['type' => 'contains_any', 'values' => ['opacity: 1', 'opacity:1']]]),
                            'hints' => json_encode(['opacity controls element transparency.', '0 = fully invisible, 1 = fully visible.', 'Syntax: opacity: 1;']),
                        ],
                        [
                            'title' => 'Position_Anchor_Lock',
                            'difficulty' => 'HARD',
                            'points' => 120,
                            'description' => <<<'TEXT'
                                Absolute positioning pins an element to exact coordinates, but the coordinates are measured from the nearest positioned ancestor. That is why the pattern uses two rules: the parent gets position: relative to become the anchor, and the child gets position: absolute with top and right offsets to sit in its corner.

                                Syntax: parent { position: relative; }, child { position: absolute; top: 0; right: 0; }. Skip the parent rule and the badge anchors to the whole page instead.

                                Example: a LIVE badge pinned to the top-right of its container needs exactly this pair.

                                Your task: the badge rule has colors but no placement. Add absolute positioning with top 0 and right 0 so it locks to the container corner.
                                TEXT,
                            'broken_code' => ".container {\n  position: relative;\n  background: #171b21;\n  padding: 2rem;\n  border: 1px solid #6fc49a;\n}\n.badge {\n  background: #6fc49a;\n  color: #14161b;\n  padding: 0.3rem 0.6rem;\n  font-size: 0.75rem;\n}",
                            'solution_code' => ".container {\n  position: relative;\n  background: #171b21;\n  padding: 2rem;\n  border: 1px solid #6fc49a;\n}\n.badge {\n  background: #6fc49a;\n  color: #14161b;\n  padding: 0.3rem 0.6rem;\n  font-size: 0.75rem;\n  position: absolute;\n  top: 0;\n  right: 0;\n}",
                            'target_html' => "<div style='position:relative;background:#171b21;padding:2rem;border:1px solid #6fc49a'><span style='background:#6fc49a;color:#14161b;padding:0.3rem 0.6rem;font-size:0.75rem;position:absolute;top:0;right:0;font-family:monospace'>LIVE</span><span style='color:#6fc49a;font-family:monospace'>Container content</span></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['position', 'absolute', 'top', '0', 'right']]]),
                            'hints' => json_encode(['position: absolute takes an element out of normal flow.', 'top and right set distance from the container edges.', 'The parent needs position: relative to contain it.']),
                        ],
                        [
                            'title' => 'Transition_Signal_Smooth',
                            'difficulty' => 'HARD',
                            'points' => 120,
                            'description' => <<<'TEXT'
                                A transition animates a property change instead of snapping instantly. You name the property, the duration, and the easing curve: transition: background 0.3s ease means background shifts spread over three tenths of a second, slowing gently at the end. Without it, hover states flip harshly.

                                Syntax: selector { transition: background 0.3s ease; }. Place it on the base rule, not the :hover rule, so both directions animate.

                                Example: a button whose hover brightens its background feels smooth once the base rule carries the transition.

                                Your task: the button hover snaps. Add the background transition to the base .btn rule.
                                TEXT,
                            'broken_code' => ".btn {\n  background: #2e6b4f;\n  color: #f4f6ed;\n  padding: 0.8rem 1.5rem;\n  border: none;\n  cursor: pointer;\n}\n.btn:hover {\n  background: #6fc49a;\n  color: #14161b;\n}",
                            'solution_code' => ".btn {\n  background: #2e6b4f;\n  color: #f4f6ed;\n  padding: 0.8rem 1.5rem;\n  border: none;\n  cursor: pointer;\n  transition: background 0.3s ease;\n}",
                            'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a'><button style='background:#2e6b4f;color:#f4f6ed;padding:0.8rem 1.5rem;border:none;cursor:pointer;transition:background 0.3s ease;font-family:monospace' onmouseover=\"this.style.background='#6fc49a';this.style.color='#14161b'\" onmouseout=\"this.style.background='#2e6b4f';this.style.color='#f4f6ed'\">HOVER ME</button></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['transition', 'background', '0.3s', 'ease']]]),
                            'hints' => json_encode(['transition animates property changes over time.', 'Syntax: transition: property duration easing;', 'Example: transition: background 0.3s ease;']),
                        ],
                        [
                            'title' => 'Responsive_Frequency_Lock',
                            'difficulty' => 'HARD',
                            'points' => 150,
                            'description' => <<<'TEXT'
                                A media query applies styles only when the viewport meets a condition. The max-width form targets small screens: everything inside @media (max-width: 600px) activates on phones and narrow windows, leaving the desktop layout untouched. Responsive design is a series of these overrides stacked after the base rules.

                                Syntax: @media (max-width: 600px) { .grid { display: block; } }. The query wraps complete rules, not loose declarations.

                                Example: a two-column grid that collapses to stacked blocks on phones keeps one base rule plus one media override.

                                Your task: the grid never adapts. Add the 600px media query that switches .grid to display block on small screens.
                                TEXT,
                            'broken_code' => ".grid {\n  display: grid;\n  grid-template-columns: 1fr 1fr;\n  gap: 1rem;\n}",
                            'solution_code' => ".grid {\n  display: grid;\n  grid-template-columns: 1fr 1fr;\n  gap: 1rem;\n}\n@media (max-width: 600px) {\n  .grid {\n    display: block;\n  }\n}",
                            'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a;margin-bottom:.5rem'>✓ Grid: 2-col on desktop</div><div style='color:#d9a94f'>✓ @media (max-width:600px) → display:block</div><div style='color:#6fc49a;margin-top:.5rem;font-size:.85rem'>Responsive layout secured.</div></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['@media', 'max-width', '600px', 'display', 'block']]]),
                            'hints' => json_encode(['@media queries apply styles at specific screen sizes.', 'Syntax: @media (max-width: 600px) { ... }', 'Inside the query, override the .grid display property.']),
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return array{order_num: int, slug: string, name: string, type: string, description: string, sections: list<array{title: string, description: string, missions: list<array<string, mixed>>}>} */
    private function jsCourse(): array
    {
        return [
            'order_num' => 3,
            'slug' => 'js-scripting',
            'name' => 'JavaScript Scripting',
            'type' => 'js',
            'description' => 'JavaScript makes pages respond. This course builds from variables and operators through conditionals, loops, and functions to data structures, DOM control, events, and network requests.',
            'sections' => [
                [
                    'title' => 'Variables and Logic',
                    'description' => 'Storing values and making decisions: declarations, arithmetic, and conditionals.',
                    'missions' => [
                        [
                            'title' => 'Variable_Signal_Declare',
                            'difficulty' => 'EASY',
                            'points' => 50,
                            'description' => <<<'TEXT'
                                A variable names a stored value so code can reuse it. The let keyword declares one, the equals sign assigns it, and the name you choose should say what the value means. Strings, text values, always sit inside quotes, single or double.

                                Syntax: let signalStatus = "ONLINE";. Keyword, name, equals, quoted value, semicolon.

                                Example: declaring signalStatus as "ONLINE" and logging it prints ONLINE to the console.

                                Your task: the status register is empty and the log prints undefined. Declare signalStatus with the value "ONLINE".
                                TEXT,
                            'broken_code' => "// Signal status register is empty\nconsole.log(signalStatus);",
                            'solution_code' => "let signalStatus = \"ONLINE\";\nconsole.log(signalStatus);",
                            'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>ONLINE</div><div style='color:#6fc49a;margin-top:1rem;font-size:.85rem'>✓ Variable declared and assigned.</div></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['let', 'signalStatus']], ['type' => 'contains_any', 'values' => ['"ONLINE"', "'ONLINE'"]]]),
                            'hints' => json_encode(['Use let to declare a variable.', 'Syntax: let name = value;', 'String values go inside quotes.']),
                        ],
                        [
                            'title' => 'Arithmetic_Core_Repair',
                            'difficulty' => 'EASY',
                            'points' => 50,
                            'description' => <<<'TEXT'
                                Arithmetic operators compute with numbers: + adds, - subtracts, * multiplies, / divides. The single most common bug is reaching for the wrong one, especially minus where plus belongs, because the code still runs and only the result is wrong.

                                Syntax: let totalScore = baseScore + bonusScore;. The expression on the right evaluates first, then assigns.

                                Example: 100 + 50 evaluates to 150, so totalScore holds 150.

                                Your task: the core subtracts the bonus instead of adding it. Swap the operator so the total reads 150.
                                TEXT,
                            'broken_code' => "let baseScore = 100;\nlet bonusScore = 50;\nlet totalScore = baseScore - bonusScore;\nconsole.log(totalScore);",
                            'solution_code' => "let baseScore = 100;\nlet bonusScore = 50;\nlet totalScore = baseScore + bonusScore;\nconsole.log(totalScore);",
                            'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>150</div><div style='color:#6fc49a;margin-top:1rem;font-size:.85rem'>✓ Arithmetic core restored.</div></div>",
                            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'totalScore'], ['type' => 'contains_any', 'values' => ['baseScore + bonusScore', 'baseScore+bonusScore']]]),
                            'hints' => json_encode(['The - operator subtracts. You need addition.', 'Use the + operator to add two values.', 'baseScore + bonusScore should equal 150.']),
                        ],
                        [
                            'title' => 'Conditional_Gate_Restore',
                            'difficulty' => 'EASY',
                            'points' => 60,
                            'description' => <<<'TEXT'
                                An if statement runs a block only when its condition is true, with an optional else for the false path. Comparison operators do the asking: > means strictly greater, while >= means greater than or equal. That one equals sign is the entire difference between a gate that opens and one that never does.

                                Syntax: if (clearanceLevel >= 3) { grant } else { deny }. The condition sits in parentheses, each branch in braces.

                                Example: with clearance 4, the >= 3 check passes and prints ACCESS GRANTED.

                                Your task: the gate demands clearance above 10, which 4 can never satisfy. Loosen it to >= 3 so access is granted.
                                TEXT,
                            'broken_code' => "let clearanceLevel = 4;\nif (clearanceLevel > 10) {\n  console.log(\"ACCESS GRANTED\");\n} else {\n  console.log(\"ACCESS DENIED\");\n}",
                            'solution_code' => "let clearanceLevel = 4;\nif (clearanceLevel >= 3) {\n  console.log(\"ACCESS GRANTED\");\n} else {\n  console.log(\"ACCESS DENIED\");\n}",
                            'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>ACCESS GRANTED</div><div style='color:#6fc49a;margin-top:1rem;font-size:.85rem'>✓ Gate condition restored.</div></div>",
                            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'if'], ['type' => 'contains_any', 'values' => ['>= 3', '>=3']], ['type' => 'contains', 'value' => 'ACCESS GRANTED']]),
                            'hints' => json_encode(['The condition > 10 is never true for 4.', 'Use >= to check greater than OR equal to.', 'Syntax: if (clearanceLevel >= 3)']),
                        ],
                    ],
                ],
                [
                    'title' => 'Loops and Functions',
                    'description' => 'Repeating work and packaging it: for loops, functions, arrays, and objects.',
                    'missions' => [
                        [
                            'title' => 'Loop_Transmitter_Repair',
                            'difficulty' => 'MEDIUM',
                            'points' => 60,
                            'description' => <<<'TEXT'
                                A for loop repeats a block a counted number of times. Its header holds three parts separated by semicolons: an initializer that runs once (let i = 1), a condition checked before each pass (i <= 5), and an increment after each pass (i++). Get the condition wrong and the loop runs zero times or forever.

                                Syntax: for (let i = 1; i <= 5; i++) { console.log(i); }. Five passes print 1 through 5.

                                Example: counting the five signal repeats needs exactly this shape.

                                Your task: the transmitter has no loop at all. Write the for loop that logs the numbers 1 to 5.
                                TEXT,
                            'broken_code' => "// Signal needs to repeat 5 times\n// Write your for loop here",
                            'solution_code' => "for (let i = 1; i <= 5; i++) {\n  console.log(i);\n}",
                            'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem;line-height:1.8'>1<br>2<br>3<br>4<br>5</div><div style='color:#6fc49a;margin-top:1rem;font-size:.85rem'>✓ Loop transmitter restored.</div></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['for', 'let i']], ['type' => 'contains_any', 'values' => ['i <= 5', 'i<=5', 'i < 6', 'i<6']], ['type' => 'contains', 'value' => 'i++']]),
                            'hints' => json_encode(['A for loop has three parts: init; condition; increment.', 'Syntax: for (let i = 1; i <= 5; i++)', 'console.log(i) prints the current value.']),
                        ],
                        [
                            'title' => 'Function_Protocol_Rebuild',
                            'difficulty' => 'MEDIUM',
                            'points' => 80,
                            'description' => <<<'TEXT'
                                A function packages reusable work under a name. Parameters listed in parentheses receive caller values, and return sends a result back out. The toUpperCase string method returns the uppercased copy, and the + operator joins strings together.

                                Syntax: function greetOperator(name) { return "WELCOME, " + name.toUpperCase(); }. Calling greetOperator("Chen") then evaluates to WELCOME, CHEN.

                                Example: the greeting protocol takes any operator name and returns the standardized uppercase welcome line.

                                Your task: the protocol body is missing. Define greetOperator so it returns the welcome string for the given name.
                                TEXT,
                            'broken_code' => "// Greeting protocol offline\n// Define greetOperator here\n\nconsole.log(greetOperator(\"Chen\"));",
                            'solution_code' => "function greetOperator(name) {\n  return \"WELCOME, \" + name.toUpperCase();\n}\n\nconsole.log(greetOperator(\"Chen\"));",
                            'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>WELCOME, CHEN</div><div style='color:#6fc49a;margin-top:1rem;font-size:.85rem'>✓ Greeting protocol online.</div></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['function greetOperator', 'name', 'return', 'WELCOME,', 'toUpperCase()']]]),
                            'hints' => json_encode(['Declare with: function greetOperator(name) { ... }', 'Use return to send back a value.', 'String concatenation: "WELCOME, " + name.toUpperCase()']),
                        ],
                        [
                            'title' => 'Array_Database_Reconstruct',
                            'difficulty' => 'MEDIUM',
                            'points' => 80,
                            'description' => <<<'TEXT'
                                An array holds an ordered list of values in one variable. Square brackets wrap the elements, commas separate them, and the length property reports how many there are. Indexing starts at zero, but counting the registry only needs length.

                                Syntax: let operators = ["CHEN", "REYES", "OKAFOR"];. Three quoted strings, commas between, brackets around.

                                Example: logging operators.length for that registry prints 3.

                                Your task: the registry array is empty. Fill it with the three operator callsigns so the count reads 3.
                                TEXT,
                            'broken_code' => "let operators = [];\nconsole.log(operators.length);",
                            'solution_code' => "let operators = [\"CHEN\", \"REYES\", \"OKAFOR\"];\nconsole.log(operators.length);",
                            'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>3</div><div style='color:#6fc49a;margin-top:1rem;font-size:.85rem'>✓ Registry loaded: 3 operators.</div></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['operators', 'CHEN', 'REYES', 'OKAFOR', '[', ']']]]),
                            'hints' => json_encode(['Arrays use square brackets: [ ]', 'Separate elements with commas.', 'String elements need quotes: ["A", "B", "C"]']),
                        ],
                        [
                            'title' => 'Object_Record_Restoration',
                            'difficulty' => 'MEDIUM',
                            'points' => 90,
                            'description' => <<<'TEXT'
                                An object groups named properties into one record. Curly braces wrap key-value pairs separated by commas: string values in quotes, numbers and booleans bare. Dot notation like operatorRecord.name reads a single property back out.

                                Syntax: let operatorRecord = { name: "CHEN", clearance: 4, active: true };. Three pairs, three types: string, number, boolean.

                                Example: logging operatorRecord.name for that record prints CHEN.

                                Your task: the record is an empty object. Restore the three properties so the name lookup returns CHEN.
                                TEXT,
                            'broken_code' => "let operatorRecord = {};\nconsole.log(operatorRecord.name);",
                            'solution_code' => "let operatorRecord = {\n  name: \"CHEN\",\n  clearance: 4,\n  active: true\n};\nconsole.log(operatorRecord.name);",
                            'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>CHEN</div><div style='color:#6fc49a;margin-top:1rem;font-size:.85rem'>✓ Operator record restored.</div></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['operatorRecord', 'clearance', '4', 'active', 'true']], ['type' => 'contains_any', 'values' => ['"CHEN"', "'CHEN'"]]]),
                            'hints' => json_encode(['Objects use curly braces: { }', 'Each property is a key: value pair.', 'Syntax: { name: "CHEN", clearance: 4, active: true }']),
                        ],
                    ],
                ],
                [
                    'title' => 'Browser and Network',
                    'description' => 'Talking to the page and the server: DOM updates, events, and fetch.',
                    'missions' => [
                        [
                            'title' => 'DOM_Node_Activation',
                            'difficulty' => 'HARD',
                            'points' => 100,
                            'description' => <<<'TEXT'
                                The DOM is the browser's live model of the page, and document.getElementById reaches into it by id. It returns the element object, whose textContent property holds the text inside. Assigning to textContent rewrites what the reader sees without touching the markup file.

                                Syntax: document.getElementById("status").textContent = "SIGNAL ACTIVE";. Select, dot into the property, assign the new string.

                                Example: a status div reading OFFLINE flips to SIGNAL ACTIVE the moment this line runs.

                                Your task: the status node is unresponsive. Select it by id and set its text to SIGNAL ACTIVE.
                                TEXT,
                            'broken_code' => "/* Preview HTML:\n<div id=\"status\">OFFLINE</div>\n*/\n\n// DOM node is unresponsive\n// Select and update the element below\n\n// <div id=\"status\">OFFLINE</div>",
                            'solution_code' => "document.getElementById(\"status\").textContent = \"SIGNAL ACTIVE\";\n\n// <div id=\"status\">OFFLINE</div>",
                            'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ DOM UPDATE:</div><div id='status' style='color:#d9a94f;margin-top:.5rem;border:1px solid #d9a94f;padding:.5rem'>SIGNAL ACTIVE</div><div style='color:#6fc49a;margin-top:1rem;font-size:.85rem'>✓ DOM node updated successfully.</div></div>",
                            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'getElementById'], ['type' => 'contains_any', 'values' => ['"status"', "'status'"]], ['type' => 'contains', 'value' => 'textContent'], ['type' => 'contains_any', 'values' => ['"SIGNAL ACTIVE"', "'SIGNAL ACTIVE'"]]]),
                            'hints' => json_encode(['Use document.getElementById("id") to select an element.', 'The textContent property sets the text inside an element.', 'Syntax: element.textContent = "new text";']),
                        ],
                        [
                            'title' => 'Event_Listener_Rewire',
                            'difficulty' => 'HARD',
                            'points' => 120,
                            'description' => <<<'TEXT'
                                Pages respond through event listeners: a registration that says when X happens on this element, run that function. The addEventListener method takes the event name as a string, click for buttons, plus a callback function holding the response. Without the registration, the button sits dead no matter how correct the handler is.

                                Syntax: btn.addEventListener("click", function() { console.log("FIRED"); });. Element, method, quoted event name, callback.

                                Example: wiring the emergency button to log EMERGENCY PROTOCOL ACTIVATED makes every click announce itself.

                                Your task: the button reference exists but nothing listens. Register the click listener that logs the activation line.
                                TEXT,
                            'broken_code' => "/* Preview HTML:\n<button id=\"emergencyBtn\" type=\"button\">Activate emergency protocol</button>\n*/\n\n// Button is unresponsive — wire it up\nconst btn = document.getElementById(\"emergencyBtn\");\n\n// Add your event listener here",
                            'solution_code' => "const btn = document.getElementById(\"emergencyBtn\");\nbtn.addEventListener(\"click\", function() {\n  console.log(\"EMERGENCY PROTOCOL ACTIVATED\");\n});",
                            'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ EVENT WIRED:</div><div style='color:#d9a94f;margin-top:.5rem'>btn.addEventListener(\"click\", handler)</div><div style='color:#6fc49a;margin-top:1rem;font-size:.85rem'>✓ Emergency button is now responsive.</div></div>",
                            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'addEventListener'], ['type' => 'contains_any', 'values' => ['"click"', "'click'"]], ['type' => 'contains', 'value' => 'EMERGENCY PROTOCOL ACTIVATED']]),
                            'hints' => json_encode(['addEventListener takes two arguments: event name and a callback function.', 'Syntax: element.addEventListener("click", function() { ... })', 'The event name "click" must be a string.']),
                        ],
                        [
                            'title' => 'Fetch_Transmission_Protocol',
                            'difficulty' => 'HARD',
                            'points' => 150,
                            'description' => <<<'TEXT'
                                Network requests are asynchronous: the code keeps running while the response travels. The async keyword marks a function allowed to pause, await fetch(url) sends the request and waits for the reply, and await response.json() parses the JSON body into usable data. Each await must sit inside an async function or the script fails to parse.

                                Syntax: async function fetchSignal() { const response = await fetch("/api/signal"); const data = await response.json(); return data; }. Three awaits in order: send, parse, return.

                                Example: calling fetchSignal().then(data => console.log(data)) logs whatever the uplink returns.

                                Preview boundary: this sandbox cannot request the application API. A fetch failure here is expected; use a separate served project with an API to observe a successful response. This exercise checks your source structure on the server.

                                Your task: the uplink subroutine is missing. Define the async fetchSignal function that fetches, parses, and returns the signal data.
                                TEXT,
                            'broken_code' => "// Uplink subroutine missing\n// Define fetchSignal here\n\nfetchSignal().then(data => console.log(data));",
                            'solution_code' => "async function fetchSignal() {\n  const response = await fetch(\"/api/signal\");\n  const data = await response.json();\n  return data;\n}\n\nfetchSignal().then(data => console.log(data));",
                            'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ ASYNC UPLINK:</div><div style='color:#d9a94f;margin-top:.5rem;line-height:1.8'>async function fetchSignal() {<br>&nbsp;&nbsp;const response = await fetch(\"/api/signal\");<br>&nbsp;&nbsp;const data = await response.json();<br>&nbsp;&nbsp;return data;<br>}</div><div style='color:#6fc49a;margin-top:1rem;font-size:.85rem'>✓ Fetch transmission protocol restored.</div></div>",
                            'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['async', 'fetchSignal', 'await', 'fetch', '/api/signal', '.json()', 'return']]]),
                            'hints' => json_encode(['Mark the function with async to use await inside it.', 'await fetch(url) sends the HTTP request and waits.', 'Call await response.json() to parse the JSON body.']),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Expansion sections appended after the base course content. New missions
     * continue the per-course order, so existing progress is untouched.
     *
     * @return list<array{title: string, description: string, missions: list<array<string, mixed>>}>
     */
    private function extraSections(int $courseOrder): array
    {
        return match ($courseOrder) {
            1 => $this->htmlExtraSections(),
            2 => $this->cssExtraSections(),
            3 => $this->jsExtraSections(),
            default => [],
        };
    }

    /** @return list<array{title: string, description: string, missions: list<array<string, mixed>>}> */
    private function htmlExtraSections(): array
    {
        return [
            [
                'title' => 'Text Semantics',
                'description' => 'Meaningful inline elements: emphasis, quotations, and code samples.',
                'missions' => [
                    [
                        'title' => 'Emphasis_Markers',
                        'difficulty' => 'EASY',
                        'points' => 60,
                        'description' => <<<'TEXT'
                            Not all bold and italic text is equal. The strong element marks important content and em marks stressed emphasis, and assistive technology announces both with extra weight. The b and i tags only change appearance with no meaning attached, so prefer the semantic pair whenever the words actually matter.

                            Syntax: <strong>Warning:</strong> and <em>Do not disconnect.</em>. Same shape as b and i, but carrying meaning.

                            Example: a warning line uses strong for the label and em for the instruction that must be followed.

                            Your task: the alert below uses presentational tags. Replace them with strong and em so the warning keeps its meaning, not just its looks.
                            TEXT,
                        'broken_code' => '<b>Warning:</b> <i>Do not disconnect.</i>',
                        'solution_code' => '<strong>Warning:</strong> <em>Do not disconnect.</em>',
                        'target_html' => '<strong>Warning:</strong> <em>Do not disconnect.</em>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<strong>', '</strong>', '<em>', '</em>']]]),
                        'hints' => json_encode(['strong marks importance, em marks stress.', 'They look like b and i but carry meaning.', 'Keep the same text, change only the tags.']),
                    ],
                    [
                        'title' => 'Code_Sample_Block',
                        'difficulty' => 'EASY',
                        'points' => 60,
                        'description' => <<<'TEXT'
                            Code samples need two tags together. The code element marks text as code inline, and the pre element preserves whitespace and line breaks exactly as written, which is what keeps terminal output aligned. Wrapping code in pre stops the browser from collapsing the spacing that makes output readable.

                            Syntax: <pre><code>ls -la /signal</code></pre>. The pre keeps the layout, the code marks the meaning.

                            Example: a command readout renders in monospace with its spacing intact.

                            Your task: the command output sits in a plain paragraph and loses its shape. Rebuild it as a pre block containing a code element.
                            TEXT,
                        'broken_code' => '<p>ls -la /signal</p>',
                        'solution_code' => '<pre><code>ls -la /signal</code></pre>',
                        'target_html' => '<pre><code>ls -la /signal</code></pre>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<pre>', '<code>', 'ls -la /signal']]]),
                        'hints' => json_encode(['pre preserves whitespace exactly.', 'code marks the text as code.', 'Nest code inside pre.']),
                    ],
                ],
            ],
            [
                'title' => 'Page Semantics',
                'description' => 'Landmark elements and document metadata: real page structure.',
                'missions' => [
                    [
                        'title' => 'Semantic_Layout',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Semantic landmarks describe what each page region is for. The header holds introductory content, nav wraps navigation links, main holds the primary content (exactly one per page), and footer closes with secondary information. Screen readers offer these as jump points, which a pile of divs can never provide.

                            Syntax: <header>, <nav>, <main>, and <footer> each wrap their region, replacing the generic div.

                            Example: a page skeleton reads header, nav, main, footer from top to bottom instead of four anonymous boxes.

                            Your task: the layout below is four meaningless divs. Replace them with the four landmark elements in page order.
                            TEXT,
                        'broken_code' => "<div>Site header</div>\n<div>Nav links</div>\n<div>Main story</div>\n<div>Footer notes</div>",
                        'solution_code' => "<header>Site header</header>\n<nav>Nav links</nav>\n<main>Main story</main>\n<footer>Footer notes</footer>",
                        'target_html' => '<header>Site header</header><nav>Nav links</nav><main>Main story</main><footer>Footer notes</footer>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<header>', '<nav>', '<main>', '<footer>']]]),
                        'hints' => json_encode(['header, nav, main, footer replace the four divs.', 'Keep the text, change only the tags.', 'Order follows the page: header first, footer last.']),
                    ],
                    [
                        'title' => 'Head_Metadata',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            The head holds invisible instructions about the page. The charset meta declares the text encoding (UTF-8 covers nearly every character), and the viewport meta tells mobile browsers to match the device width instead of shrinking a desktop layout. Without these two lines, pages risk garbled text and broken mobile rendering.

                            Syntax: <meta charset="UTF-8"> and <meta name="viewport" content="width=device-width, initial-scale=1">, both void elements inside head.

                            Example: a correct head holds the title plus both meta lines.

                            Your task: the head below has a title but no metadata. Add the charset and viewport declarations.
                            TEXT,
                        'broken_code' => "<head>\n  <title>Signal Page</title>\n</head>",
                        'solution_code' => "<head>\n  <meta charset=\"UTF-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n  <title>Signal Page</title>\n</head>",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a;color:#6fc49a;font-family:monospace'><div>✓ charset declared</div><div>✓ viewport configured</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['charset', 'viewport', 'width=device-width']]]),
                        'hints' => json_encode(['charset declares the text encoding.', 'viewport controls mobile rendering.', 'Both are void meta elements in head.']),
                    ],
                ],
            ],
            [
                'title' => 'Forms and Media',
                'description' => 'Collecting input and embedding media: forms, choices, and figures.',
                'missions' => [
                    [
                        'title' => 'Signal_Form',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            A form groups controls into one submittable unit. The form element names the destination with action, each label describes one control, and the for attribute must match the control's id so clicking the label focuses the field. The required attribute blocks empty submissions before anything reaches a server.

                            Syntax: <form action="/register"> with <label for="callsign"> plus a matching <input id="callsign" required>.

                            Example: a signup form posts to /register with a labeled, required callsign field.

                            Your task: the loose input below belongs to no form and has no label. Wrap it in a /register form with a matched label and make it required.
                            TEXT,
                        'broken_code' => '<input type="text" name="callsign">',
                        'solution_code' => "<form action=\"/register\">\n  <label for=\"callsign\">Callsign</label>\n  <input id=\"callsign\" name=\"callsign\" type=\"text\" required>\n</form>",
                        'target_html' => '<form action="/register"><label for="callsign">Callsign</label><input id="callsign" name="callsign" type="text" required></form>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<form', '<label', 'for=', 'required']]]),
                        'hints' => json_encode(['form wraps the controls with an action.', 'label for must match the input id.', 'required blocks empty submits.']),
                    ],
                    [
                        'title' => 'Choice_Controls',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Radio buttons offer exactly one choice from a set. Every radio in the group shares one name, which is what links them so selecting one deselects the rest. Each still needs its own label, or users cannot tell the options apart or click them reliably.

                            Syntax: two <input type="radio" name="band"> elements with different values and a label each.

                            Example: a frequency picker offers UHF and VHF under the single name band.

                            Your task: the band picker below uses two unrelated text fields. Replace them with a labeled UHF/VHF radio pair sharing one name.
                            TEXT,
                        'broken_code' => "<input type=\"text\" name=\"band1\">\n<input type=\"text\" name=\"band2\">",
                        'solution_code' => "<label><input type=\"radio\" name=\"band\" value=\"UHF\"> UHF</label>\n<label><input type=\"radio\" name=\"band\" value=\"VHF\"> VHF</label>",
                        'target_html' => '<label><input type="radio" name="band" value="UHF"> UHF</label><label><input type="radio" name="band" value="VHF"> VHF</label>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['type="radio"', 'name="band"', 'UHF', 'VHF']], ['type' => 'count_tag', 'tag' => 'input', 'count' => 2, 'operator' => 'gte']]),
                        'hints' => json_encode(['Radios in one group share a single name.', 'Each option needs its own label.', 'Values UHF and VHF tell the options apart.']),
                    ],
                    [
                        'title' => 'Media_Feed',
                        'difficulty' => 'HARD',
                        'points' => 100,
                        'description' => <<<'TEXT'
                            The figure element binds an image to its caption so the two travel as one unit. Inside it, img carries the picture with a descriptive alt, and figcaption holds the visible caption text. A bare img with no caption leaves readers guessing what they are looking at.

                            Syntax: <figure> wrapping <img src alt> plus <figcaption>caption</figcaption>.

                            Example: a dish photo with the caption Array Dish 7, back online.

                            Your task: the feed image below has no description or caption. Rebuild it as a figure with alt text and a figcaption.
                            TEXT,
                        'broken_code' => '<img src="dish.png">',
                        'solution_code' => "<figure>\n  <img src=\"dish.png\" alt=\"Array dish 7\">\n  <figcaption>Array Dish 7, back online.</figcaption>\n</figure>",
                        'target_html' => '<figure><img src="dish.png" alt="Array dish 7"><figcaption>Array Dish 7, back online.</figcaption></figure>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<figure>', '<figcaption>', 'alt=']]]),
                        'hints' => json_encode(['figure groups image and caption.', 'img still needs descriptive alt text.', 'figcaption holds the visible caption.']),
                    ],
                ],
            ],
        ];
    }

    /** @return list<array{title: string, description: string, missions: list<array<string, mixed>>}> */
    private function cssExtraSections(): array
    {
        return [
            [
                'title' => 'Selectors and Cascade',
                'description' => 'Targeting the right elements: classes, combinators, and the cascade.',
                'missions' => [
                    [
                        'title' => 'Selector_Targeting',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Class selectors target by label, and the descendant combinator (a plain space) narrows to elements inside another. Writing .panel .title styles only titles sitting within a panel, leaving every other title on the page untouched. An element selector like p would paint all of them.

                            Syntax: .panel .title { color: red; }. Dot, parent class, space, child class.

                            Example: panel titles turn red while standalone titles keep their color.

                            Your task: the rule below paints every paragraph. Rewrite the selector so only titles inside a panel turn red.
                            TEXT,
                        'broken_code' => "p {\n  color: red;\n}",
                        'solution_code' => ".panel .title {\n  color: red;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a'><div style='border:1px dashed #a9b0a5;padding:.5rem'><span style='color:red;font-family:monospace'>Panel title (red)</span></div><p style='color:#a9b0a5;font-family:monospace;margin:.5rem 0 0'>Plain paragraph (untouched)</p></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['.panel', '.title', 'color']]]),
                        'hints' => json_encode(['Start both parts with a dot for classes.', 'A space between them means descendant.', 'The p selector is too broad.']),
                    ],
                    [
                        'title' => 'Cascade_Order',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            When two rules collide, the cascade breaks the tie in order: more specific selectors beat weaker ones, and between equals the later rule wins. An ID selector outranks any number of classes, which is why #emergency overrides .msg no matter where the two appear.

                            Syntax: keep .msg { color: gray; } and add #emergency { color: red; }. Specificity does the rest.

                            Example: an element carrying both classes renders red because the ID rule wins.

                            Your task: the emergency message below renders gray. Add an ID rule that forces it red through specificity.
                            TEXT,
                        'broken_code' => ".msg {\n  color: gray;\n}",
                        'solution_code' => ".msg {\n  color: gray;\n}\n#emergency {\n  color: red;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a'><span style='color:red;font-family:monospace'>EMERGENCY (red wins)</span></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['#emergency', 'color', 'red']]]),
                        'hints' => json_encode(['ID selectors start with #.', 'An ID beats any class selector.', 'Keep the old rule, add the stronger one.']),
                    ],
                ],
            ],
            [
                'title' => 'Modern Layout',
                'description' => 'Two-dimensional layout plus the box model details that make it predictable.',
                'missions' => [
                    [
                        'title' => 'Grid_Tracks',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            CSS Grid lays children out in rows and columns at once. Setting display: grid makes the parent a grid container, and grid-template-columns defines the tracks. The repeat() function writes repeating patterns compactly, and the fr unit splits leftover space into fractions.

                            Syntax: .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }. Three equal columns with gaps.

                            Example: three status cells sit side by side, each taking one third of the row.

                            Your task: the status row below stacks as blocks. Convert it to a three-column grid with gaps.
                            TEXT,
                        'broken_code' => ".grid {\n  display: block;\n}",
                        'solution_code' => ".grid {\n  display: grid;\n  grid-template-columns: repeat(3, 1fr);\n  gap: 1rem;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a;display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;font-family:monospace'><span style='color:#6fc49a'>CELL 1</span><span style='color:#6fc49a'>CELL 2</span><span style='color:#6fc49a'>CELL 3</span></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['display', 'grid', 'repeat', '1fr']]]),
                        'hints' => json_encode(['display: grid enables the grid.', 'repeat(3, 1fr) means three equal tracks.', 'gap adds gutters between cells.']),
                    ],
                    [
                        'title' => 'Box_Sizing',
                        'difficulty' => 'EASY',
                        'points' => 60,
                        'description' => <<<'TEXT'
                            By default, width sets only the content box, so padding and border pile on top and the element grows wider than declared. The border-box value flips the math: padding and border count inward, and the declared width is the final width. Pair it with margin: 0 auto and a fixed-width block centers itself horizontally.

                            Syntax: .card { width: 300px; padding: 20px; box-sizing: border-box; margin: 0 auto; }.

                            Example: a 300px card with 20px padding stays exactly 300px wide and sits centered.

                            Your task: the card below overflows because padding adds to its width. Apply border-box sizing and center it with auto margins.
                            TEXT,
                        'broken_code' => ".card {\n  width: 300px;\n  padding: 20px;\n  background: #171b21;\n}",
                        'solution_code' => ".card {\n  width: 300px;\n  padding: 20px;\n  background: #171b21;\n  box-sizing: border-box;\n  margin: 0 auto;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a'><div style='width:300px;max-width:100%;padding:20px;background:#1c1f26;box-sizing:border-box;margin:0 auto;color:#6fc49a;font-family:monospace'>CENTERED CARD</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['box-sizing', 'border-box']]]),
                        'hints' => json_encode(['box-sizing: border-box keeps width final.', 'margin: 0 auto centers a fixed-width block.', 'Keep the existing declarations.']),
                    ],
                    [
                        'title' => 'Rounded_Shadow',
                        'difficulty' => 'EASY',
                        'points' => 60,
                        'description' => <<<'TEXT'
                            Two properties lift flat boxes off the page. The border-radius rounds corners (50% turns a square into a circle), and box-shadow paints a soft offset copy behind the element using horizontal offset, vertical offset, blur, and color.

                            Syntax: .badge { border-radius: 8px; box-shadow: 0 0 12px #6fc49a; }.

                            Example: a status badge with rounded corners and a green glow reads as lit rather than printed.

                            Your task: the badge below is a sharp flat square. Round its corners and give it a green glow shadow.
                            TEXT,
                        'broken_code' => ".badge {\n  background: #6fc49a;\n  color: #14161b;\n  padding: 0.4rem 0.8rem;\n}",
                        'solution_code' => ".badge {\n  background: #6fc49a;\n  color: #14161b;\n  padding: 0.4rem 0.8rem;\n  border-radius: 8px;\n  box-shadow: 0 0 12px #6fc49a;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a'><span style='background:#6fc49a;color:#14161b;padding:0.4rem 0.8rem;border-radius:8px;box-shadow:0 0 12px #6fc49a;font-family:monospace'>ONLINE</span></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['border-radius', 'box-shadow']]]),
                        'hints' => json_encode(['border-radius rounds the corners.', 'box-shadow needs offsets, blur, and color.', 'Glow: 0 0 12px with the signal color.']),
                    ],
                ],
            ],
            [
                'title' => 'Motion and Variables',
                'description' => 'Keyframe animation and reusable theme tokens.',
                'missions' => [
                    [
                        'title' => 'Keyframe_Pulse',
                        'difficulty' => 'HARD',
                        'points' => 120,
                        'description' => <<<'TEXT'
                            Keyframes describe an animation as a timeline. The @keyframes block names the motion and lists its stops (from/to, or percentages), and the animation shorthand on the element names it back with a duration and repeat count. Infinite repeats the loop until the page closes.

                            Syntax: @keyframes pulse { from { opacity: 1; } to { opacity: 0.3; } } plus .lamp { animation: pulse 2s infinite; }.

                            Example: a signal lamp fading between full and dim brightness reads as alive.

                            Your task: the lamp below is static. Define the pulse keyframes and attach a 2-second infinite animation.
                            TEXT,
                        'broken_code' => ".lamp {\n  background: #6fc49a;\n  width: 2rem;\n  height: 2rem;\n}",
                        'solution_code' => "@keyframes pulse {\n  from { opacity: 1; }\n  to { opacity: 0.3; }\n}\n.lamp {\n  background: #6fc49a;\n  width: 2rem;\n  height: 2rem;\n  animation: pulse 2s infinite;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a'><div style='background:#6fc49a;width:2rem;height:2rem;animation:pulse 2s infinite'></div><div style='color:#6fc49a;font-family:monospace;margin-top:.75rem;font-size:.85rem'>Pulsing lamp</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['@keyframes', 'pulse', 'animation']]]),
                        'hints' => json_encode(['@keyframes names the motion timeline.', 'from/to define the start and end states.', 'animation: name duration repeat attaches it.']),
                    ],
                    [
                        'title' => 'Theme_Tokens',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Custom properties store reusable values under a -- name. Declared once on :root so every rule inherits them, they are read back with var(), which also takes a fallback used when the token is missing. One edit to the token repaints every consumer, which beats hunting hex codes file-wide.

                            Syntax: :root { --signal: #6fc49a; } then .lamp { color: var(--signal, green); }.

                            Example: signal lamps across the page all track one token, with green as the safety fallback.

                            Your task: the lamp below hardcodes its color. Move the value into a --signal token on :root and consume it with a fallback.
                            TEXT,
                        'broken_code' => ".lamp {\n  color: #6fc49a;\n}",
                        'solution_code' => ":root {\n  --signal: #6fc49a;\n}\n.lamp {\n  color: var(--signal, green);\n}",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a'><span style='color:#6fc49a;font-family:monospace'>TOKEN LAMP</span></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => [':root', '--signal', 'var(']]]),
                        'hints' => json_encode(['Custom properties start with --.', ':root makes the token global.', 'var(--signal, green) reads it with a fallback.']),
                    ],
                ],
            ],
        ];
    }

    /** @return list<array{title: string, description: string, missions: list<array<string, mixed>>}> */
    private function cssGapSections(): array
    {
        return [
            [
                'title' => 'Typography',
                'description' => 'Readable type: stacks, rhythm, styling, and fluid sizing.',
                'missions' => [
                    [
                        'title' => 'Type_Setting',
                        'difficulty' => 'EASY',
                        'points' => 60,
                        'description' => <<<'TEXT'
                            Type settings make text readable. The font-family lists preferred faces first with generic fallbacks last, font-weight controls boldness in steps like 400 and 700, and line-height sets the spacing between lines as a unitless multiple. A stack of monospace, Consolas, monospace survives missing fonts everywhere.

                            Syntax: .copy { font-family: monospace, Consolas, monospace; font-weight: 400; line-height: 1.8; }.

                            Example: lesson body set in a monospace stack at normal weight with generous leading.

                            Your task: the copy block below has no type settings. Give it the stack, normal weight, and 1.8 line height.
                            TEXT,
                        'broken_code' => ".copy {\n  color: #e6e9de;\n}",
                        'solution_code' => ".copy {\n  color: #e6e9de;\n  font-family: monospace, Consolas, monospace;\n  font-weight: 400;\n  line-height: 1.8;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a'><p style='color:#e6e9de;font-family:monospace;line-height:1.8;margin:0'>Readable copy block.</p></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['font-family', 'font-weight', 'line-height']]]),
                        'hints' => json_encode(['Stacks list favorites first, generic last.', 'font-weight 400 is normal, 700 is bold.', 'line-height 1.8 means roomy leading.']),
                    ],
                    [
                        'title' => 'Text_Style',
                        'difficulty' => 'EASY',
                        'points' => 60,
                        'description' => <<<'TEXT'
                            Text styling shapes voice and layout. The text-align property lines blocks left, center, or right, text-decoration underlines links or strikes deletions, text-transform forces uppercase without retyping, and letter-spacing opens tracking for labels and kickers.

                            Syntax: .kicker { text-align: center; letter-spacing: 0.12em; text-transform: uppercase; }.

                            Example: a centered uppercase kicker with wide tracking above a headline.

                            Your task: the kicker below is plain left-aligned text. Center it, uppercase it, and track it out.
                            TEXT,
                        'broken_code' => ".kicker {\n  color: #a9b0a5;\n  font-size: 0.7rem;\n}",
                        'solution_code' => ".kicker {\n  color: #a9b0a5;\n  font-size: 0.7rem;\n  text-align: center;\n  text-transform: uppercase;\n  letter-spacing: 0.12em;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a'><p style='color:#a9b0a5;font-size:0.7rem;text-align:center;text-transform:uppercase;letter-spacing:0.12em;font-family:monospace;margin:0'>SYSTEM KICKER</p></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['text-align', 'text-transform', 'letter-spacing']]]),
                        'hints' => json_encode(['text-align centers the block.', 'text-transform changes case without retyping.', 'letter-spacing opens the tracking.']),
                    ],
                    [
                        'title' => 'Fluid_Sizing',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Fixed pixels break on small screens, so modern sizing stays relative. The rem unit tracks the root font size, percentages track the parent, and viewport units track the window itself. The clamp() function bounds the result: clamp(min, preferred, max) grows fluidly but never past its limits.

                            Syntax: .hero { font-size: clamp(1.5rem, 4vw, 3rem); width: 90%; max-width: 60rem; }.

                            Example: a headline that scales with the viewport yet never drops below readable or exceeds its measure.

                            Your task: the hero below is locked at 48px. Convert it to a clamped fluid size between 1.5rem and 3rem.
                            TEXT,
                        'broken_code' => ".hero {\n  font-size: 48px;\n}",
                        'solution_code' => ".hero {\n  font-size: clamp(1.5rem, 4vw, 3rem);\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a'><p style='color:#e6e9de;font-size:clamp(1.5rem,4vw,3rem);font-family:monospace;margin:0'>Fluid hero</p></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['clamp(', '1.5rem', '3rem']]]),
                        'hints' => json_encode(['clamp takes min, preferred, max.', 'vw units track the viewport width.', 'rem tracks the root font size.']),
                    ],
                ],
            ],
            [
                'title' => 'Selectors in Depth',
                'description' => 'Precise targeting: combinators, attribute selectors, and state pseudo-classes.',
                'missions' => [
                    [
                        'title' => 'Combinators',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Combinators describe relationships between elements. The child combinator (>) hits only direct children, the adjacent sibling (+) hits the very next sibling, and attribute selectors like input[type="text"] filter by attribute value. Each narrows the match without adding classes to the markup.

                            Syntax: .menu > li { ... } for direct children, h2 + p { ... } for the paragraph right after a heading, input[type="text"] { ... } for text fields only.

                            Example: styling direct menu items, the intro paragraph after each heading, and text inputs in one stylesheet.

                            Your task: the rules below style too broadly. Rewrite them as a child selector, an adjacent-sibling selector, and an attribute selector.
                            TEXT,
                        'broken_code' => "li {\n  color: #6fc49a;\n}\np {\n  margin-top: 0;\n}\ninput {\n  border: 1px solid #6fc49a;\n}",
                        'solution_code' => ".menu > li {\n  color: #6fc49a;\n}\nh2 + p {\n  margin-top: 0;\n}\ninput[type=\"text\"] {\n  border: 1px solid #6fc49a;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a;font-family:monospace;color:#6fc49a'><div>.menu &gt; li, h2 + p, input[type]</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['>', '+', '[type=']]]),
                        'hints' => json_encode(['> means direct children only.', '+ means the very next sibling.', '[type="text"] filters by attribute.']),
                    ],
                    [
                        'title' => 'Pseudo_States',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Pseudo-classes style elements by state instead of markup. The :hover rule fires under the pointer, :focus-visible shows a ring for keyboard users without bothering mouse users, and :checked plus :not() target toggled inputs and everything except a match. The ::before pseudo-element injects decorative content from CSS, keeping it out of the document.

                            Syntax: a:hover { ... }, :focus-visible { outline: ... }, input:checked + label { ... }, .tag::before { content: "> "; }.

                            Example: links that glow on hover, keyboard focus rings, checked-option labels, and prefixed tags.

                            Your task: the stylesheet below has no state rules. Add hover, focus-visible, checked, and a ::before marker rule.
                            TEXT,
                        'broken_code' => "a {\n  color: #9c97e8;\n}\n.tag {\n  color: #a9b0a5;\n}",
                        'solution_code' => "a {\n  color: #9c97e8;\n}\na:hover {\n  color: #6fc49a;\n}\n:focus-visible {\n  outline: 3px solid #9c97e8;\n}\ninput:checked + label {\n  color: #6fc49a;\n}\n.tag::before {\n  content: \"> \";\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a;font-family:monospace;color:#9c97e8'><div>&gt; tagged link (hover me)</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => [':hover', ':focus-visible', ':checked', '::before']]]),
                        'hints' => json_encode([':hover fires under the pointer.', ':focus-visible serves keyboard users.', '::before injects decorative content.']),
                    ],
                ],
            ],
            [
                'title' => 'Polish and Access',
                'description' => 'Motion with transforms, rich backgrounds, and form styling that stays accessible.',
                'missions' => [
                    [
                        'title' => 'Transform_Move',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Transforms move, resize, and rotate without disturbing layout flow. The translate function shifts position, scale grows or shrinks, and rotate turns, all pivoting around transform-origin. Paired with a transition, a hover lift feels physical instead of snappy.

                            Syntax: .card { transition: transform 0.2s ease; } .card:hover { transform: translateY(-4px) scale(1.02); }.

                            Example: a panel that lifts and grows slightly under the pointer.

                            Your task: the card below snaps on hover with no motion. Add the transform transition and the lift-and-grow hover rule.
                            TEXT,
                        'broken_code' => ".card {\n  background: #1c1f26;\n  padding: 1rem;\n}\n.card:hover {\n  background: #242830;\n}",
                        'solution_code' => ".card {\n  background: #1c1f26;\n  padding: 1rem;\n  transition: transform 0.2s ease;\n}\n.card:hover {\n  background: #242830;\n  transform: translateY(-4px) scale(1.02);\n}",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a'><div style='background:#1c1f26;padding:1rem;color:#6fc49a;font-family:monospace;transition:transform 0.2s ease'>Hover card (lifts)</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['transform', 'translateY', 'scale']]]),
                        'hints' => json_encode(['transform moves without reflowing layout.', 'translateY lifts, scale grows.', 'Transition the transform property.']),
                    ],
                    [
                        'title' => 'Gradient_Background',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Gradients blend colors across a surface without image files. The linear-gradient function takes a direction and color stops, while background-size and background-position control how any background image fills its box. One declaration can carry atmosphere an entire image used to provide.

                            Syntax: .banner { background: linear-gradient(180deg, #171b21, #242830); }.

                            Example: a banner fading from the deep pad up to a lighter plate behind its headline.

                            Your task: the flat banner below needs depth. Give it a top-to-bottom gradient across two stops.
                            TEXT,
                        'broken_code' => ".banner {\n  background: #171b21;\n  padding: 2rem;\n}",
                        'solution_code' => ".banner {\n  background: linear-gradient(180deg, #171b21, #242830);\n  padding: 2rem;\n}",
                        'target_html' => "<div style='background:linear-gradient(180deg,#171b21,#242830);padding:2rem;border:1px solid #6fc49a;color:#6fc49a;font-family:monospace'>GRADIENT BANNER</div>",
                        'validate_rule' => json_encode([['type' => 'regex', 'pattern' => 'linear-gradient(?=[^)]*#(?:001100|171[Bb]21))(?=[^)]*#(?:003300|242830))']]),
                        'hints' => json_encode(['linear-gradient blends across a direction.', '180deg runs top to bottom.', 'List two or more color stops.']),
                    ],
                    [
                        'title' => 'Form_Focus',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Form controls must stay visibly focused. Styling inputs and buttons is welcome, but removing the native outline without a replacement strands keyboard users. The :focus-visible selector shows a strong ring for keyboard focus only, and :disabled styles the unavailable state so it reads as inactive rather than broken.

                            Syntax: input { border: ...; } input:focus-visible { outline: 3px solid #9c97e8; } button:disabled { opacity: 0.5; }.

                            Example: dark inputs with a cyan keyboard ring and dimmed disabled buttons.

                            Your task: the controls below have no focus or disabled styling. Add an accessible focus-visible ring and a dimmed disabled state.
                            TEXT,
                        'broken_code' => "input {\n  background: #171b21;\n  color: #e6e9de;\n  padding: 0.6rem;\n}\nbutton {\n  background: #6fc49a;\n  color: #14161b;\n  padding: 0.6rem 1rem;\n}",
                        'solution_code' => "input {\n  background: #171b21;\n  color: #e6e9de;\n  padding: 0.6rem;\n  border: 1px solid #6fc49a;\n}\ninput:focus-visible {\n  outline: 3px solid #9c97e8;\n}\nbutton:disabled {\n  opacity: 0.5;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a;font-family:monospace'><div style='border:1px solid #6fc49a;padding:.6rem;color:#e6e9de'>Tab to me (cyan ring)</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => [':focus-visible', 'outline', ':disabled']]]),
                        'hints' => json_encode([':focus-visible fires for keyboard focus.', 'The outline must be strong and visible.', ':disabled styles the inactive state.']),
                    ],
                ],
            ],
        ];
    }

    /** @return list<array{title: string, description: string, missions: list<array<string, mixed>>}> */
    private function jsExtraSections(): array
    {
        return [
            [
                'title' => 'Strings and Control',
                'description' => 'Working with text and branching: template literals, switch, and while loops.',
                'missions' => [
                    [
                        'title' => 'Template_Lines',
                        'difficulty' => 'EASY',
                        'points' => 60,
                        'description' => <<<'TEXT'
                            Template literals build strings with embedded values. Backticks open the string instead of quotes, and ${expression} slots drop any value inline, no plus signs or spacing bugs. They also span multiple lines, which quoted strings cannot do.

                            Syntax: `STATUS: ${status} - SECTOR ${sector}`. Backticks outside, ${} placeholders inside.

                            Example: with status ONLINE and sector 7, the template evaluates to STATUS: ONLINE - SECTOR 7.

                            Your task: the status line below concatenates with a missing space. Rebuild it as one template literal.
                            TEXT,
                        'broken_code' => "let status = \"ONLINE\";\nlet sector = 7;\nlet line = \"STATUS:\" + status + \"- SECTOR\" + sector;\nconsole.log(line);",
                        'solution_code' => "let status = \"ONLINE\";\nlet sector = 7;\nlet line = `STATUS: \${status} - SECTOR \${sector}`;\nconsole.log(line);",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>STATUS: ONLINE - SECTOR 7</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['`', '${status}', '${sector}']]]),
                        'hints' => json_encode(['Template literals use backticks, not quotes.', '${} embeds any value inline.', 'One template replaces the whole chain.']),
                    ],
                    [
                        'title' => 'Switch_Board',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            A switch picks one branch from many by matching a single value. Each case labels one outcome, break stops the fall into the next case, and default catches everything unmatched. It reads cleaner than a long if/else chain when every branch tests the same variable.

                            Syntax: switch (band) { case "UHF": ...; break; case "VHF": ...; break; default: ...; }.

                            Example: routing the values UHF, VHF, and anything else to three different log lines.

                            Your task: the dispatcher below is an empty stub. Write the switch that logs UHF LINK, VHF LINK, or NO SIGNAL by default.
                            TEXT,
                        'broken_code' => "let band = \"VHF\";\n// Dispatch on band here\nconsole.log(\"ready\");",
                        'solution_code' => "let band = \"VHF\";\nswitch (band) {\n  case \"UHF\":\n    console.log(\"UHF LINK\");\n    break;\n  case \"VHF\":\n    console.log(\"VHF LINK\");\n    break;\n  default:\n    console.log(\"NO SIGNAL\");\n}",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>VHF LINK</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['switch', 'case', 'break', 'default']]]),
                        'hints' => json_encode(['switch (band) selects on one value.', 'Each case needs a break.', 'default catches the rest.']),
                    ],
                    [
                        'title' => 'While_Watch',
                        'difficulty' => 'EASY',
                        'points' => 60,
                        'description' => <<<'TEXT'
                            A while loop repeats while its condition stays true, checking before every pass. Unlike for, it carries no counter of its own, so the body must move toward the exit, or the loop never ends. Countdowns are the classic shape: start high, stop above zero, decrement inside.

                            Syntax: let n = 3; while (n > 0) { console.log(n); n--; }. Condition first, progress inside.

                            Example: starting at 3 prints 3, 2, 1, then exits.

                            Your task: the watch below never counts. Write the countdown loop that logs 3 down to 1.
                            TEXT,
                        'broken_code' => "let n = 3;\n// Count down from 3 here",
                        'solution_code' => "let n = 3;\nwhile (n > 0) {\n  console.log(n);\n  n--;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem;line-height:1.8'>3<br>2<br>1</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['while', 'n > 0', 'n--']]]),
                        'hints' => json_encode(['while checks its condition each pass.', 'The body must decrement toward the exit.', 'Log n before decrementing.']),
                    ],
                ],
            ],
            [
                'title' => 'Data Tools',
                'description' => 'Transforming collections and guarding parsing: map/filter, destructuring, and try/catch with JSON.',
                'missions' => [
                    [
                        'title' => 'Map_Filter',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            The filter method keeps the elements passing a test, and map transforms each survivor, both returning new arrays without touching the original. Chaining them reads as a pipeline: take readings, drop the negatives, double the rest. Arrow functions keep each step to one line.

                            Syntax: const clean = readings.filter(r => r >= 0).map(r => r * 2);.

                            Example: [-5, 10, -2, 20] becomes [20, 40]: negatives dropped, survivors doubled.

                            Your task: the readings pipeline below is missing. Build the filter-then-map chain that yields doubled non-negative values.
                            TEXT,
                        'broken_code' => "let readings = [-5, 10, -2, 20];\n// Filter negatives, double the rest\nconsole.log(readings);",
                        'solution_code' => "let readings = [-5, 10, -2, 20];\nconst clean = readings.filter(r => r >= 0).map(r => r * 2);\nconsole.log(clean);",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>[ 20, 40 ]</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['filter', 'map', '=>']]]),
                        'hints' => json_encode(['filter keeps elements passing the test.', 'map transforms each survivor.', 'Chain: filter first, then map.']),
                    ],
                    [
                        'title' => 'Destructure_Spread',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Destructuring unpacks values straight into names, and spread pours one collection into another. Writing const { name, band } = operator pulls two properties in one line, and [name, ...backup] builds a new array starting with name plus every backup member. Both avoid manual index juggling.

                            Syntax: const { name, band } = operator; const crew = [name, ...backup];.

                            Example: operator CHEN on UHF band plus two backups becomes a three-person crew list.

                            Your task: the crew assembly below is manual and broken. Destructure the operator and spread the backup list into the crew array.
                            TEXT,
                        'broken_code' => "let operator = { name: \"CHEN\", band: \"UHF\" };\nlet backup = [\"REYES\", \"OKAFOR\"];\nlet crew = [];\nconsole.log(crew.length);",
                        'solution_code' => "let operator = { name: \"CHEN\", band: \"UHF\" };\nlet backup = [\"REYES\", \"OKAFOR\"];\nconst { name, band } = operator;\nconst crew = [name, ...backup];\nconsole.log(crew.length);",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>3</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['{ name', '} = operator', '...backup']]]),
                        'hints' => json_encode(['const { name } = operator unpacks a property.', '...backup spreads the array inline.', 'Build crew from name plus the spread.']),
                    ],
                    [
                        'title' => 'Try_Catch_JSON',
                        'difficulty' => 'HARD',
                        'points' => 110,
                        'description' => <<<'TEXT'
                            External data cannot be trusted, and JSON.parse throws on malformed input instead of returning a polite error. Wrapping the parse in try/catch keeps one bad payload from killing the script: the try block attempts the work, and catch runs only when it throws, receiving the error for logging or fallback.

                            Syntax: try { const data = JSON.parse(payload); } catch (e) { console.log("BAD PAYLOAD"); }.

                            Example: a corrupt uplink packet logs the fallback line while valid packets parse normally.

                            Your task: the parser below crashes on bad input. Guard the JSON.parse call with try/catch that logs BAD PAYLOAD on failure.
                            TEXT,
                        'broken_code' => "let payload = \"{corrupt\";\nconst data = JSON.parse(payload);\nconsole.log(data);",
                        'solution_code' => "let payload = \"{corrupt\";\ntry {\n  const data = JSON.parse(payload);\n  console.log(data);\n} catch (e) {\n  console.log(\"BAD PAYLOAD\");\n}",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>BAD PAYLOAD</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['try', 'catch', 'JSON.parse', 'BAD PAYLOAD']]]),
                        'hints' => json_encode(['try wraps the risky parse call.', 'catch runs only when parsing throws.', 'Log the fallback inside catch.']),
                    ],
                ],
            ],
            [
                'title' => 'Browser Depth',
                'description' => 'Living in the browser: multi-element selection, timers, and local storage.',
                'missions' => [
                    [
                        'title' => 'Query_All',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Real pages repeat elements, and querySelectorAll grabs every match at once as a list. The classList API then toggles styling per element without string surgery on class names: add("on") attaches the class, and forEach walks the list applying it everywhere.

                            Syntax: document.querySelectorAll(".lamp").forEach(el => el.classList.add("on"));. Select all, walk each, add the class.

                            Example: three dark lamps light up together once every one carries on.

                            Your task: the lamp array below stays dark. Select every .lamp and add the on class to each.
                            TEXT,
                        'broken_code' => "/* Preview HTML:\n<div class=\"lamp\">Lamp 1</div>\n<div class=\"lamp\">Lamp 2</div>\n<div class=\"lamp\">Lamp 3</div>\n*/\n\n// Three .lamp elements stay dark\n// Light them all up here",
                        'solution_code' => 'document.querySelectorAll(".lamp").forEach(el => el.classList.add("on"));',
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ LAMPS:</div><div style='color:#d9a94f;margin-top:.5rem'>3 lamps ON</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['querySelectorAll', 'classList', 'forEach']]]),
                        'hints' => json_encode(['querySelectorAll returns every match.', 'classList.add attaches a class.', 'forEach walks the whole list.']),
                    ],
                    [
                        'title' => 'Timer_Beacon',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Timers schedule repeated work. The setInterval call runs a callback every N milliseconds and returns an id for that timer, and clearInterval(id) stops it. A counter that clears its own timer after three ticks is the standard self-stopping beacon pattern.

                            Syntax: const timer = setInterval(tick, 1000); with clearInterval(timer); inside tick once done.

                            Example: a beacon logging three pings then going silent.

                            Your task: the beacon below never fires and never stops. Schedule the tick every second and clear the timer after the third ping.
                            TEXT,
                        'broken_code' => "let pings = 0;\nfunction tick() {\n  pings++;\n  console.log(\"ping \" + pings);\n}\n// Schedule and stop here",
                        'solution_code' => "let pings = 0;\nfunction tick() {\n  pings++;\n  console.log(\"ping \" + pings);\n  if (pings >= 3) {\n    clearInterval(timer);\n  }\n}\nconst timer = setInterval(tick, 1000);",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ BEACON:</div><div style='color:#d9a94f;margin-top:.5rem;line-height:1.8'>ping 1<br>ping 2<br>ping 3<br>(silent)</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['setInterval', 'clearInterval', 'tick']]]),
                        'hints' => json_encode(['setInterval runs a callback repeatedly.', 'Store its return value as the timer id.', 'clearInterval stops it after 3 pings.']),
                    ],
                    [
                        'title' => 'Store_Local',
                        'difficulty' => 'HARD',
                        'points' => 100,
                        'description' => <<<'TEXT'
                            The localStorage object persists small strings in the browser across visits. Its setItem stores under a key and getItem reads back, but only strings survive, so objects travel through JSON.stringify on the way in and JSON.parse on the way out. Note the boundary: this is a browser learning concept only. CodeQuest grades and progress always live server-side, where no client edit can touch them.

                            Syntax: localStorage.setItem("callsign", JSON.stringify({ name: "CHEN" })); then JSON.parse(localStorage.getItem("callsign")).

                            Example: saving the operator record and reading back its name prints CHEN.

                            Preview boundary: localStorage requires a normal page origin and is intentionally unavailable in this sandbox. The preview reports a SecurityError. Try persistence in your own served project; CodeQuest checks this exercise’s source structure on the server.

                            Your task: the callsign below never persists. Store the record object and read its name back from storage.
                            TEXT,
                        'broken_code' => "let record = { name: \"CHEN\" };\n// Persist and reload here\nconsole.log(record.name);",
                        'solution_code' => "let record = { name: \"CHEN\" };\nlocalStorage.setItem(\"callsign\", JSON.stringify(record));\nconst saved = JSON.parse(localStorage.getItem(\"callsign\"));\nconsole.log(saved.name);",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ STORAGE:</div><div style='color:#d9a94f;margin-top:.5rem'>CHEN (persisted)</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['localStorage', 'setItem', 'getItem']]]),
                        'hints' => json_encode(['setItem stores a string under a key.', 'Stringify objects before storing.', 'Parse the stored string when reading.']),
                    ],
                ],
            ],
        ];
    }

    /** @return list<array{title: string, description: string, missions: list<array<string, mixed>>}> */
    private function jsGapSections(): array
    {
        return [
            [
                'title' => 'Scope and Functions',
                'description' => 'Where variables live and how functions carry memory: scope rules, closures, and arrows.',
                'missions' => [
                    [
                        'title' => 'Block_Scope',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Scope decides where a variable exists. The var keyword leaks across blocks in the old function-scoped style, while let and const stay inside the nearest braces. That difference is why modern code avoids var: a loop counter declared with let disappears after the loop, but one declared with var lingers and collides.

                            Syntax: { let inside = 1; } keeps inside private to the block, while var escapes it.

                            Example: preferring let and const everywhere, reserving var for legacy code you read but never write.

                            Your task: the snippet below leaks its counter through var. Convert both declarations to block-scoped let and const.
                            TEXT,
                        'broken_code' => "var sector = 7;\nvar limit = 3;\nconsole.log(sector + limit);",
                        'solution_code' => "const sector = 7;\nlet limit = 3;\nconsole.log(sector + limit);",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>10</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['const sector', 'let limit']]]),
                        'hints' => json_encode(['const suits values never reassigned.', 'let suits values that change.', 'var has no place in new code.']),
                    ],
                    [
                        'title' => 'Closure_Counter',
                        'difficulty' => 'HARD',
                        'points' => 110,
                        'description' => <<<'TEXT'
                            A closure is a function that remembers the variables around its birth. When an outer function declares a count and returns an inner function that bumps it, every call shares that one private count. Nothing outside can touch it directly, which makes closures the simplest way to hold state without objects.

                            Syntax: function makeCounter() { let n = 0; return function () { n++; return n; }; }.

                            Example: const next = makeCounter(); next() returns 1, then 2, then 3, each call resuming where the last left off.

                            Your task: the counter factory below returns a dead function. Fill it so each call increments and returns the shared count.
                            TEXT,
                        'broken_code' => "function makeCounter() {\n  let n = 0;\n  return function () {\n    return 0;\n  };\n}\nconst next = makeCounter();\nconsole.log(next());",
                        'solution_code' => "function makeCounter() {\n  let n = 0;\n  return function () {\n    n++;\n    return n;\n  };\n}\nconst next = makeCounter();\nconsole.log(next());",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>1</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['return function', 'n++', 'return n']]]),
                        'hints' => json_encode(['The inner function outlives the outer call.', 'n++ moves the shared count forward.', 'Return n so callers see it.']),
                    ],
                    [
                        'title' => 'Arrow_Callbacks',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Arrow functions compress callbacks to their essence. The expression (x) => x * 2 means take x, return double, with no function keyword and no braces for single expressions. Array methods and event wiring both take callbacks, so arrows appear wherever JavaScript reacts to something.

                            Syntax: readings.forEach(r => console.log(r));. Parameter, fat arrow, body.

                            Example: logging every reading with one line instead of a three-line function expression.

                            Your task: the loop below uses a bulky anonymous function. Rewrite the callback as an arrow.
                            TEXT,
                        'broken_code' => "let readings = [10, 20, 30];\nreadings.forEach(function (r) {\n  console.log(r);\n});",
                        'solution_code' => "let readings = [10, 20, 30];\nreadings.forEach(r => console.log(r));",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem;line-height:1.8'>10<br>20<br>30</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['forEach', '=>']]]),
                        'hints' => json_encode(['Drop function and keep the parameter.', '=> separates parameter from body.', 'Single expressions skip the braces.']),
                    ],
                ],
            ],
            [
                'title' => 'Data Mastery',
                'description' => 'Finding, reducing, inspecting, and blueprinting data.',
                'missions' => [
                    [
                        'title' => 'Find_Reduce',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Two methods finish the collection toolkit. The find method returns the first element passing a test (or undefined when none does), and reduce folds a whole array into one value by carrying an accumulator through each element. Together with map and filter, they cover nearly every loop you will ever write.

                            Syntax: readings.find(r => r > 15) returns 20; readings.reduce((sum, r) => sum + r, 0) totals them from a zero start.

                            Example: locating the first strong reading and summing the set in two lines.

                            Your task: the summary below is hand-rolled and wrong. Compute firstOver with find and total with reduce.
                            TEXT,
                        'broken_code' => "let readings = [10, 20, 30];\nlet firstOver = 0;\nlet total = 0;\nconsole.log(firstOver, total);",
                        'solution_code' => "let readings = [10, 20, 30];\nconst firstOver = readings.find(r => r > 15);\nconst total = readings.reduce((sum, r) => sum + r, 0);\nconsole.log(firstOver, total);",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>20 60</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['find', 'reduce', '=>']]]),
                        'hints' => json_encode(['find returns the first passing element.', 'reduce carries an accumulator across elements.', 'Start the total at 0.']),
                    ],
                    [
                        'title' => 'Object_Tools',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Objects can be inspected as data. Object.keys lists the property names, Object.values lists their values, and Object.entries lists both as pairs. Bracket notation reads properties whose names arrive as variables, and nesting reaches deeper with chained dots.

                            Syntax: Object.keys(station) gives ["name", "band"]; station["band"] reads the same as station.band.

                            Example: auditing a station record by listing its keys, then reading the nested frequency.

                            Your task: the audit below is hardcoded. Derive the key list with Object.keys and read the nested band value.
                            TEXT,
                        'broken_code' => "let station = { name: \"Relay 7\", radio: { band: \"UHF\" } };\nconsole.log(\"keys?\");\nconsole.log(\"band?\");",
                        'solution_code' => "let station = { name: \"Relay 7\", radio: { band: \"UHF\" } };\nconsole.log(Object.keys(station));\nconsole.log(station.radio.band);",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem;line-height:1.8'>[ 'name', 'radio' ]<br>UHF</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['Object.keys', 'station.radio.band']]]),
                        'hints' => json_encode(['Object.keys returns the property names.', 'Chained dots reach nested values.', 'station.radio.band reads two levels down.']),
                    ],
                    [
                        'title' => 'Class_Blueprint',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            A class is a blueprint for making similar objects. The constructor runs once per new instance to store its data, and methods defined on the class are shared by every instance. Classes suit entities with both state and behavior; plain data without behavior stays an object literal.

                            Syntax: class Operator { constructor(name) { this.name = name; } report() { return this.name + " READY"; } } then new Operator("CHEN").

                            Example: stamping operator instances that each report their own readiness.

                            Your task: the factory function below works but hides the pattern. Rewrite it as an Operator class with a reporting method.
                            TEXT,
                        'broken_code' => "function makeOperator(name) {\n  return { name: name };\n}\nconst op = makeOperator(\"CHEN\");\nconsole.log(op.name);",
                        'solution_code' => "class Operator {\n  constructor(name) {\n    this.name = name;\n  }\n  report() {\n    return this.name + \" READY\";\n  }\n}\nconst op = new Operator(\"CHEN\");\nconsole.log(op.report());",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>CHEN READY</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['class Operator', 'constructor', 'new Operator']]]),
                        'hints' => json_encode(['class names the blueprint.', 'constructor stores per-instance data.', 'new Operator builds the instance.']),
                    ],
                ],
            ],
            [
                'title' => 'Browser Fluency',
                'description' => 'Building nodes, guarding forms, and delegating events.',
                'missions' => [
                    [
                        'title' => 'Build_Nodes',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Pages can grow new elements at runtime. The createElement call builds a detached node, textContent fills it, appendChild grafts it onto a parent, and remove() prunes it. The querySelector call finds the parent by any CSS selector, which makes id lookups and class lookups spell the same way.

                            Syntax: const li = document.createElement("li"); li.textContent = "UHF"; document.querySelector("#bands").appendChild(li);.

                            Example: adding a band entry to the live list without reloading.

                            Your task: the list below never grows. Create the li, fill it, and append it to the #bands list.
                            TEXT,
                        'broken_code' => "/* Preview HTML:\n<ul id=\"bands\"></ul>\n*/\n\n// <ul id=\"bands\"></ul> present on the page\n// Add a UHF entry here",
                        'solution_code' => "const li = document.createElement(\"li\");\nli.textContent = \"UHF\";\ndocument.querySelector(\"#bands\").appendChild(li);",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ LIST:</div><div style='color:#d9a94f;margin-top:.5rem'>• UHF (appended)</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['createElement', 'appendChild', 'querySelector']]]),
                        'hints' => json_encode(['createElement builds a detached node.', 'textContent fills it safely.', 'querySelector finds the parent, appendChild grafts.']),
                    ],
                    [
                        'title' => 'Form_Guard',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            JavaScript validates forms before they travel. The submit event fires on the form itself, reading values through .value on each control. Calling preventDefault on the event stops the page reload so the script can check lengths and patterns first, accepting or rejecting with a message.

                            Syntax: form.addEventListener("submit", function (e) { e.preventDefault(); if (input.value.length < 3) { ... } });.

                            Example: a callsign form that refuses entries shorter than three characters without reloading.

                            Your task: the form below submits unchecked. Listen for submit, prevent the reload, and reject short callsigns.
                            TEXT,
                        'broken_code' => "/* Preview HTML:\n<form id=\"signup\">\n<label for=\"callsign\">Callsign</label>\n<input id=\"callsign\" name=\"callsign\">\n<button type=\"submit\">Check callsign</button>\n</form>\n*/\n\nconst form = document.querySelector(\"#signup\");\nconst input = document.querySelector(\"#callsign\");\n// Validate on submit here",
                        'solution_code' => "const form = document.querySelector(\"#signup\");\nconst input = document.querySelector(\"#callsign\");\nform.addEventListener(\"submit\", function (e) {\n  e.preventDefault();\n  if (input.value.length < 3) {\n    console.log(\"TOO SHORT\");\n  } else {\n    console.log(\"ACCEPTED\");\n  }\n});",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ FORM:</div><div style='color:#d9a94f;margin-top:.5rem'>submit guarded, short rejected</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['addEventListener', '"submit"', 'preventDefault', '.value']]]),
                        'hints' => json_encode(['Listen for submit on the form.', 'preventDefault stops the reload.', 'Read input.value to validate.']),
                    ],
                    [
                        'title' => 'Event_Bubble',
                        'difficulty' => 'HARD',
                        'points' => 110,
                        'description' => <<<'TEXT'
                            Events bubble: a click on a child travels up through every ancestor, so one listener on a parent can serve a whole list. The event object names the true origin as target versus currentTarget, the element holding the listener. This delegation pattern handles items added later with zero extra wiring.

                            Syntax: list.addEventListener("click", function (e) { if (e.target.tagName === "LI") { ... } });.

                            Example: a band list where one parent listener reports whichever item was clicked, including items appended afterward.

                            Your task: the list below wires nothing. Delegate one click listener on the parent that reports the clicked item's text.
                            TEXT,
                        'broken_code' => "/* Preview HTML:\n<ul id=\"bands\"><li>UHF</li><li>VHF</li></ul>\n*/\n\nconst list = document.querySelector(\"#bands\");\n// Report clicks on any item here",
                        'solution_code' => "const list = document.querySelector(\"#bands\");\nlist.addEventListener(\"click\", function (e) {\n  console.log(e.target.textContent);\n});",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ DELEGATION:</div><div style='color:#d9a94f;margin-top:.5rem'>one listener, every item covered</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['addEventListener', '"click"', 'e.target']]]),
                        'hints' => json_encode(['One listener on the parent covers all children.', 'e.target is the true click origin.', 'Bubbling carries the event upward.']),
                    ],
                ],
            ],
            [
                'title' => 'Async and Modules',
                'description' => 'Promises in depth and the module syntax for sharing code.',
                'missions' => [
                    [
                        'title' => 'Promise_Chain',
                        'difficulty' => 'HARD',
                        'points' => 110,
                        'description' => <<<'TEXT'
                            A promise represents a future value with three states: pending, fulfilled, or rejected. The then method runs on fulfillment, catch runs on rejection, and checking response.ok before parsing keeps HTTP failures (like 404s) from masquerading as good data.

                            Syntax: fetch("/api/signal").then(r => { if (!r.ok) throw new Error("down"); return r.json(); }).then(d => console.log(d)).catch(e => console.log("FAILED"));.

                            Example: an uplink that parses good responses and reports FAILED on anything else.

                            Preview boundary: application API requests are unavailable in this sandbox. The catch handler should report FAILED here; use your own served API to test a successful response.

                            Your task: the chain below parses blindly. Add the ok check that throws on HTTP failure and the catch that reports it.
                            TEXT,
                        'broken_code' => "fetch(\"/api/signal\")\n  .then(r => r.json())\n  .then(d => console.log(d));",
                        'solution_code' => "fetch(\"/api/signal\")\n  .then(r => {\n    if (!r.ok) {\n      throw new Error(\"down\");\n    }\n    return r.json();\n  })\n  .then(d => console.log(d))\n  .catch(e => console.log(\"FAILED\"));",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ CHAIN:</div><div style='color:#d9a94f;margin-top:.5rem'>ok checked, failures caught</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['.then', '.catch', 'r.ok', 'throw']]]),
                        'hints' => json_encode(['then handles the fulfilled path.', 'Check r.ok before parsing the body.', 'catch closes the chain on failure.']),
                    ],
                    [
                        'title' => 'Module_Syntax',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Modules split code across files with explicit sharing. The export keyword publishes a binding from its file, and import names it in another: import { band } from "./radio.js". Unlike script tags that share one global scope implicitly, modules keep everything private except what they export. CodeQuest validates the syntax the same server-side way as every mission; the browser preview shows module source as text rather than executing it.

                            Syntax: export const band = "UHF"; in radio.js, then import { band } from "./radio.js"; where needed.

                            Example: a radio module publishing its band constant for the dashboard to import.

                            Your task: the two files below share nothing. Export the band constant from the radio module and import it by name.
                            TEXT,
                        'broken_code' => "/* Preview source only */\n\n// radio.js\nconst band = \"UHF\";\n\n// dashboard.js\nconsole.log(\"band?\");",
                        'solution_code' => "// radio.js\nexport const band = \"UHF\";\n\n// dashboard.js\nimport { band } from \"./radio.js\";\nconsole.log(band);",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ MODULES:</div><div style='color:#d9a94f;margin-top:.5rem'>band shared across files</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['export', 'import', './radio.js']]]),
                        'hints' => json_encode(['export publishes a binding.', 'import names it with braces.', 'The path points at the source file.']),
                    ],
                ],
            ],
        ];
    }

    /** @return list<array{title: string, description: string, missions: list<array<string, mixed>>}> */
    private function htmlGapSections(): array
    {
        return [
            [
                'title' => 'Forms in Depth',
                'description' => 'Real-world form controls: multi-line text, menus, varied input types, and grouped structure.',
                'missions' => [
                    [
                        'title' => 'Textarea_Select',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Some answers do not fit one line. The textarea element takes multi-line input with rows and cols sizing it, while select offers a dropdown menu of option children, one marked selected to preselect it. Both pair with label exactly like inputs do.

                            Syntax: <textarea name="notes" rows="4" cols="40"></textarea> and <select name="band"><option>UHF</option><option selected>VHF</option></select>.

                            Example: a contact form pairing a message box with a topic menu.

                            Your task: the form below has neither. Add a 4-by-40 textarea named notes and a band select whose VHF option is preselected.
                            TEXT,
                        'broken_code' => "<form action=\"/contact\">\n  <label for=\"msg\">Message</label>\n  <input id=\"msg\" name=\"msg\" type=\"text\">\n</form>",
                        'solution_code' => "<form action=\"/contact\">\n  <label for=\"msg\">Message</label>\n  <textarea id=\"msg\" name=\"notes\" rows=\"4\" cols=\"40\"></textarea>\n  <label for=\"band\">Band</label>\n  <select id=\"band\" name=\"band\">\n    <option>UHF</option>\n    <option selected>VHF</option>\n  </select>\n</form>",
                        'target_html' => '<form action="/contact"><label>Message</label><div>[textarea 4x40]</div><label>Band</label><div>[select: UHF, VHF selected]</div></form>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<textarea', '<select', '<option', 'selected']]]),
                        'hints' => json_encode(['textarea replaces the single-line input.', 'select wraps option children.', 'selected preselects one option.']),
                    ],
                    [
                        'title' => 'Input_Types',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            The type attribute turns one element into many controls. Email keyboards itself on phones and validates format, number accepts only quantities with min/max bounds, and date opens a calendar picker. Each type also brings free validation: a malformed email blocks submit before any script runs.

                            Syntax: <input type="email" name="email" required>, <input type="number" name="qty" min="1" max="9">, <input type="date" name="day">.

                            Example: a signup row asking for email, quantity, and date with three purpose-built controls.

                            Your task: the three text fields below are all generic. Convert them to email, number (1 to 9), and date types.
                            TEXT,
                        'broken_code' => "<input type=\"text\" name=\"email\">\n<input type=\"text\" name=\"qty\">\n<input type=\"text\" name=\"day\">",
                        'solution_code' => "<input type=\"email\" name=\"email\" required>\n<input type=\"number\" name=\"qty\" min=\"1\" max=\"9\">\n<input type=\"date\" name=\"day\">",
                        'target_html' => '<div>[email field] [number 1-9 field] [date picker]</div>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['type="email"', 'type="number"', 'type="date"']]]),
                        'hints' => json_encode(['type="email" validates format for free.', 'number pairs with min and max.', 'type="date" opens a calendar picker.']),
                    ],
                    [
                        'title' => 'Form_Structure',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Long forms need grouping. The fieldset element boxes related controls and its legend child captions the box, which screen readers announce when entering the group. The datalist element offers suggested values for a text input through the list attribute, suggesting without restricting. A submit button posts the form.

                            Syntax: <fieldset><legend>Access</legend> controls...</fieldset>, <input list="bands"> with <datalist id="bands">, <button type="submit">Send</button>.

                            Example: an access form grouping credentials with legend, suggesting callsigns, and submitting.

                            Your task: the flat controls below need structure. Group them in a fieldset with legend, attach a datalist to the text input, and add a submit button.
                            TEXT,
                        'broken_code' => "<form action=\"/access\">\n  <label for=\"u\">User</label>\n  <input id=\"u\" name=\"user\" type=\"text\">\n  <label for=\"p\">Pass</label>\n  <input id=\"p\" name=\"pass\" type=\"password\">\n</form>",
                        'solution_code' => "<form action=\"/access\">\n  <fieldset>\n    <legend>Access</legend>\n    <label for=\"u\">User</label>\n    <input id=\"u\" name=\"user\" type=\"text\" list=\"callsigns\">\n    <datalist id=\"callsigns\">\n      <option value=\"CHEN\"></option>\n      <option value=\"REYES\"></option>\n    </datalist>\n    <label for=\"p\">Pass</label>\n    <input id=\"p\" name=\"pass\" type=\"password\">\n    <button type=\"submit\">Send</button>\n  </fieldset>\n</form>",
                        'target_html' => '<form action="/access"><fieldset><legend>Access</legend><div>[user + suggestions]</div><div>[password]</div><button>Send</button></fieldset></form>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<fieldset', '<legend', '<datalist', 'type="submit"']]]),
                        'hints' => json_encode(['fieldset groups, legend captions the group.', 'datalist id must match the input list.', 'A submit button posts the form.']),
                    ],
                ],
            ],
            [
                'title' => 'Media and Interactivity',
                'description' => 'Audio, video, native disclosure widgets, and safe embedding.',
                'missions' => [
                    [
                        'title' => 'Audio_Video',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            The audio and video elements embed playable media with native controls. Each takes source children so the browser picks the first format it supports, and the track child adds captions for deaf viewers. Text between the tags is the fallback shown only when media cannot play at all.

                            Syntax: <video controls><source src="brief.mp4" type="video/mp4"><track kind="captions" src="brief.vtt" srclang="en">No video support.</video>.

                            Example: a briefing player with a captioned source and a plain-text fallback.

                            Your task: the briefing below is a dead link. Replace it with a captioned video player carrying a fallback line.
                            TEXT,
                        'broken_code' => '<a href="brief.mp4">Watch briefing</a>',
                        'solution_code' => "<video controls>\n  <source src=\"brief.mp4\" type=\"video/mp4\">\n  <track kind=\"captions\" src=\"brief.vtt\" srclang=\"en\" label=\"English\">\n  Briefing unavailable in this browser.\n</video>",
                        'target_html' => '<div>[video player with captions]</div>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<video', '<source', '<track', 'controls']]]),
                        'hints' => json_encode(['video wraps source children.', 'track kind="captions" adds subtitles.', 'controls shows the native player.']),
                    ],
                    [
                        'title' => 'Details_Dialog',
                        'difficulty' => 'EASY',
                        'points' => 60,
                        'description' => <<<'TEXT'
                            The details element is a native collapsible: its summary child stays visible as the toggle, and everything after it hides until opened. No script is needed for the basic behavior, which makes it the accessible choice for FAQs and spoiler content. The dialog element goes further as a true modal, though opening it needs one line of JavaScript.

                            Syntax: <details><summary>Readout</summary>Hidden content.</details>.

                            Example: a spoiler block hiding the access code behind its summary.

                            Your task: the access code below sits fully exposed. Wrap it in a details element toggled by a summary.
                            TEXT,
                        'broken_code' => '<p>Access code: 7-7-0</p>',
                        'solution_code' => "<details>\n  <summary>Access code</summary>\n  <p>7-7-0</p>\n</details>",
                        'target_html' => '<details><summary>Access code</summary><p>7-7-0</p></details>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<details>', '<summary>', '7-7-0']]]),
                        'hints' => json_encode(['details wraps the whole block.', 'summary is the visible toggle.', 'Hidden content follows the summary.']),
                    ],
                    [
                        'title' => 'Safe_Embed',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            The iframe element embeds another page inside yours, which is powerful and risky in equal measure: embedded content can run scripts and track readers. The sandbox attribute strips privileges, and an empty sandbox blocks scripts entirely. Same-origin embeds you control may earn back narrow rights; third-party embeds should stay locked down, and the title attribute keeps the frame announced to screen readers.

                            Syntax: <iframe srcdoc="<p>Sector map</p>" title="Sector map" sandbox></iframe>. Never grant allow-scripts and allow-same-origin together to untrusted content.

                            Example: a sector map embedded from a self-contained document with an empty sandbox and a descriptive title. The map is carried inline with srcdoc, so the example renders on its own without depending on another page.

                            Your task: the bare embed below runs unrestricted. Add an empty sandbox and a title so it is locked down and announced.
                            TEXT,
                        'broken_code' => '<iframe srcdoc="<p>Sector map</p>"></iframe>',
                        'solution_code' => '<iframe srcdoc="<p>Sector map</p>" title="Sector map" sandbox></iframe>',
                        'target_html' => '<div>[sandboxed sector map frame]</div>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<iframe', 'sandbox', 'title=']]]),
                        'hints' => json_encode(['sandbox strips embedded privileges.', 'An empty sandbox blocks scripts.', 'title announces the frame.']),
                    ],
                ],
            ],
            [
                'title' => 'Precise Markup',
                'description' => 'Tables with real headers, definition lists, and global attributes.',
                'missions' => [
                    [
                        'title' => 'Table_Semantics',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Data tables need more than cells. The caption names the table, thead and tbody separate headers from data, and th marks header cells with a scope of col or row so screen readers announce each value with its header. The colspan attribute lets one cell span two columns, as section dividers often need.

                            Syntax: <table><caption>..</caption><thead><tr><th scope="col">..</th></tr></thead><tbody>..</tbody></table>.

                            Example: a roster table captioned Shift Roster with scoped column headers and a spanning note row.

                            Your task: the plain grid below has no headers. Rebuild it with caption, thead and tbody, scoped th cells, and a two-column note.
                            TEXT,
                        'broken_code' => "<table>\n  <tr><td>Name</td><td>Band</td></tr>\n  <tr><td>Chen</td><td>UHF</td></tr>\n</table>",
                        'solution_code' => "<table>\n  <caption>Shift Roster</caption>\n  <thead>\n    <tr><th scope=\"col\">Name</th><th scope=\"col\">Band</th></tr>\n  </thead>\n  <tbody>\n    <tr><td>Chen</td><td>UHF</td></tr>\n    <tr><td colspan=\"2\">All operators accounted for.</td></tr>\n  </tbody>\n</table>",
                        'target_html' => '<table><caption>Shift Roster</caption><thead><tr><th>Name</th><th>Band</th></tr></thead><tbody><tr><td>Chen</td><td>UHF</td></tr><tr><td colspan="2">All accounted for.</td></tr></tbody></table>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<caption>', '<thead>', '<tbody>', '<th', 'scope=', 'colspan']]]),
                        'hints' => json_encode(['caption names the whole table.', 'th with scope labels headers for readers.', 'colspan stretches one cell across columns.']),
                    ],
                    [
                        'title' => 'Description_Lists',
                        'difficulty' => 'EASY',
                        'points' => 60,
                        'description' => <<<'TEXT'
                            A description list pairs terms with their explanations. The dl element wraps the list, each dt names one term, and each dd holds its description. It fits glossaries, metadata blocks, and FAQ answers far better than a table or a stack of paragraphs.

                            Syntax: <dl> with <dt>Term</dt> followed by <dd>Meaning.</dd>, repeated per entry.

                            Example: a signal glossary defining UHF and VHF in two pairs.

                            Your task: the glossary below is loose paragraphs. Rebuild it as a dl with two term-description pairs.
                            TEXT,
                        'broken_code' => '<p>UHF: short range.</p><p>VHF: long range.</p>',
                        'solution_code' => "<dl>\n  <dt>UHF</dt>\n  <dd>Short range.</dd>\n  <dt>VHF</dt>\n  <dd>Long range.</dd>\n</dl>",
                        'target_html' => '<dl><dt>UHF</dt><dd>Short range.</dd><dt>VHF</dt><dd>Long range.</dd></dl>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<dl>', '<dt>', '<dd>']]]),
                        'hints' => json_encode(['dl wraps the whole glossary.', 'dt names each term.', 'dd holds each meaning.']),
                    ],
                    [
                        'title' => 'Element_Attributes',
                        'difficulty' => 'EASY',
                        'points' => 60,
                        'description' => <<<'TEXT'
                            Global attributes work on nearly every element. The id gives one unique hook for scripts and labels, class attaches reusable styling labels, title adds a hover tooltip, hidden removes the element from rendering entirely, and data-* stores private values scripts can read. The tabindex controls keyboard focus: 0 joins the natural tab order, -1 removes an element from it for script-managed focus. Positive values hijack the order and should stay unused.

                            Syntax: <div id="panel" class="box" title="Status" data-sector="7" tabindex="0">..</div>, hidden alone when concealed.

                            Example: a status box carrying identity, styling, tooltip, data, and keyboard access in one tag.

                            Your task: the bare box below needs hooks. Add an id, a class, a title, a data-sector value, and tabindex 0.
                            TEXT,
                        'broken_code' => '<div>Status</div>',
                        'solution_code' => '<div id="panel" class="box" title="Status panel" data-sector="7" tabindex="0">Status</div>',
                        'target_html' => '<div id="panel" class="box" title="Status panel">Status</div>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['id=', 'class=', 'title=', 'data-sector', 'tabindex']]]),
                        'hints' => json_encode(['id is unique per page, class is reusable.', 'data-* stores script-readable values.', 'tabindex 0 joins the tab order; never use positive values.']),
                    ],
                ],
            ],
        ];
    }

    /**
     * The Knowledge Check content: one check per mission.
     *
     * A check is the mission's concept check. It is required by default (the
     * admin screen can still make an individual check optional), it gates
     * the coding challenge, and every question is grounded in what that
     * mission teaches. The prompts, the explanations and the distractor
     * options are authored per mission, never generated as filler.
     */
    private function knowledgeChecks(): array
    {
        return [
            [
                'course_order' => 1,
                'mission_order' => 13,
                'order_num' => 1,
                'title' => 'Semantic HTML choices',
                'instructions' => 'Pick the markup that carries meaning, not just appearance.',
                'questions' => [
                    [
                        'prompt' => 'A warning label must sound urgent to screen readers. Which markup is correct?',
                        'explanation' => 'strong carries importance that assistive technology announces; b only changes appearance.',
                        'options' => ['<strong>Warning</strong>', '<b>Warning</b>', '<span class="bold">Warning</span>'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which element wraps the primary navigation links?',
                        'explanation' => 'nav marks the navigation landmark; div says nothing about purpose.',
                        'options' => ['<div>', '<nav>', '<span>'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'A page has two headlines. Which structure is correct?',
                        'explanation' => 'One h1 per page keeps the document outline clean; the second headline steps down to h2.',
                        'options' => ['Two h1 elements', 'One h1 followed by one h2', 'No headings, styled divs only'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 15,
                'order_num' => 1,
                'title' => 'Form labeling basics',
                'instructions' => 'Accessible forms connect every control to its label.',
                'questions' => [
                    [
                        'prompt' => 'How does a label activate its text input?',
                        'explanation' => 'Matching for and id values link the pair, so clicking the label focuses the field.',
                        'options' => ['label for matches input id', 'label wraps any nearby input automatically', 'Labels never activate inputs'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which attribute blocks empty form submission?',
                        'explanation' => 'required tells the browser to refuse the submit until the field has a value.',
                        'options' => ['placeholder', 'required', 'readonly'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'A choice offers exactly one of UHF or VHF. Which control fits?',
                        'explanation' => 'Radios sharing one name form a single-choice group; checkboxes allow many.',
                        'options' => ['Two checkboxes', 'Two radios sharing one name', 'Two text inputs'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 12,
                'order_num' => 1,
                'title' => 'Cascade reasoning',
                'instructions' => 'Predict which rule wins when selectors collide.',
                'questions' => [
                    [
                        'prompt' => '.msg sets gray, #emergency sets red, one element has both. Which color wins?',
                        'explanation' => 'An ID selector outranks any class selector regardless of order.',
                        'options' => ['Gray, first rule wins', 'Red, the ID wins', 'Browser default'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'Two identical .note rules set different colors. Which applies?',
                        'explanation' => 'Equal specificity falls back to source order: the later rule wins.',
                        'options' => ['The first rule', 'The later rule', 'Neither, it is an error'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'Which selector targets only titles inside a panel?',
                        'explanation' => 'The descendant combinator .panel .title scopes the match to panel children.',
                        'options' => ['p', '.panel .title', '.title, .panel'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 13,
                'order_num' => 1,
                'title' => 'Layout choices',
                'instructions' => 'Match the layout tool to the job.',
                'questions' => [
                    [
                        'prompt' => 'A three-column status row with equal tracks needs which approach?',
                        'explanation' => 'Grid with repeat(3, 1fr) defines two-dimensional tracks in one declaration.',
                        'options' => ['Three floated divs', 'display: grid with repeat(3, 1fr)', 'Three absolutely positioned divs'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'A row of nav items must center vertically. Which pair does it?',
                        'explanation' => 'display: flex makes the row; align-items: center centers across it.',
                        'options' => ['display: flex with align-items: center', 'display: block with text-align: left', 'float: left on each item'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the fr unit represent?',
                        'explanation' => 'One fr is one share of the leftover space after fixed tracks.',
                        'options' => ['A fixed pixel count', 'One share of leftover space', 'The font size'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 14,
                'order_num' => 1,
                'title' => 'Array method behavior',
                'instructions' => 'Know what each method returns and whether it mutates.',
                'questions' => [
                    [
                        'prompt' => 'What does readings.filter(r => r >= 0) return?',
                        'explanation' => 'filter returns a new array with only the passing elements; the original is untouched.',
                        'options' => ['It deletes failing elements in place', 'A new array with passing elements', 'The count of passing elements'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'What does readings.map(r => r * 2) return?',
                        'explanation' => 'map returns a new array with the function applied to every element.',
                        'options' => ['A new transformed array', 'The same array, doubled in place', 'A single summed value'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which pattern doubles only the non-negative values?',
                        'explanation' => 'Filter first to drop negatives, then map to double the survivors.',
                        'options' => ['map then filter', 'filter then map', 'forEach with push'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 10,
                'order_num' => 1,
                'title' => 'Async ordering',
                'instructions' => 'Reason about code that waits for the network.',
                'questions' => [
                    [
                        'prompt' => 'Where may await appear?',
                        'explanation' => 'await is only valid inside a function marked async.',
                        'options' => ['Anywhere in the file', 'Only inside an async function', 'Only at the top level'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'What does fetch() hand back immediately?',
                        'explanation' => 'fetch returns a promise for the future response; await unwraps it.',
                        'options' => ['The parsed JSON body', 'A promise for the response', 'The HTTP status code'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'A JSON.parse call may receive corrupt input. How is the script kept alive?',
                        'explanation' => 'try/catch around the parse converts the throw into a handled fallback path.',
                        'options' => ['Check typeof first', 'Wrap the parse in try/catch', 'Parse twice and compare'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 20,
                'order_num' => 1,
                'title' => 'Form reasoning',
                'instructions' => 'Choose controls and attributes that fit the data.',
                'questions' => [
                    [
                        'prompt' => 'A message field needs multiple lines. Which control fits?',
                        'explanation' => 'textarea takes multi-line input; a text input holds one line only.',
                        'options' => ['input type="text"', 'textarea', 'select'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'A quantity field accepts 1 through 9. Which markup fits?',
                        'explanation' => 'type="number" with min and max constrains the range natively.',
                        'options' => ['input type="text"', 'input type="number" min="1" max="9"', 'input type="date"'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'Related credentials need a visible group caption. Which pair does it?',
                        'explanation' => 'fieldset groups the controls and legend captions the group for assistive tech.',
                        'options' => ['div and span', 'fieldset and legend', 'form and input'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 22,
                'order_num' => 1,
                'title' => 'Selector matching',
                'instructions' => 'Predict which elements a selector matches.',
                'questions' => [
                    [
                        'prompt' => 'Which elements does .menu > li match?',
                        'explanation' => 'The child combinator matches only direct children, not deeper nesting.',
                        'options' => ['Every li anywhere inside', 'Only direct li children', 'The menu itself'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'When does input:checked + label apply?',
                        'explanation' => 'It matches the label immediately following a checked input.',
                        'options' => ['Any label on the page', 'The label right after a checked input', 'All inputs'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'Why prefer :focus-visible over :focus for the ring?',
                        'explanation' => 'focus-visible fires for keyboard focus, sparing mouse users the noise.',
                        'options' => ['It is shorter to type', 'It targets keyboard focus specifically', 'It works without CSS'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 21,
                'order_num' => 1,
                'title' => 'Scope reasoning',
                'instructions' => 'Track where variables live and what remembers them.',
                'questions' => [
                    [
                        'prompt' => 'A counter declared with let inside a block is read outside it. What happens?',
                        'explanation' => 'let stays inside the nearest braces, so the outer read fails.',
                        'options' => ['It reads fine', 'ReferenceError, it is block-scoped', 'It reads as undefined'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'A returned inner function still bumps its birth count. What is this called?',
                        'explanation' => 'A closure remembers the variables around its birth across calls.',
                        'options' => ['Hoisting', 'A closure', 'Recursion'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'Why is var discouraged in new code?',
                        'explanation' => 'var leaks across blocks in function scope, inviting collisions.',
                        'options' => ['It is slower', 'It ignores block boundaries', 'It forbids reassignment'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 28,
                'order_num' => 1,
                'title' => 'Event reasoning',
                'instructions' => 'Reason about listeners, defaults, and bubbling.',
                'questions' => [
                    [
                        'prompt' => 'A submit handler must validate first. What stops the reload?',
                        'explanation' => 'preventDefault on the submit event keeps the page alive for checks.',
                        'options' => ['return false anywhere', 'e.preventDefault() in the handler', 'Removing the form tag'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'One parent listener serves items added later. Why does it work?',
                        'explanation' => 'Events bubble upward, so the parent sees clicks from future children too.',
                        'options' => ['Listeners copy themselves', 'Events bubble to ancestors', 'The DOM replays clicks'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'In a delegated handler, which property names the clicked item?',
                        'explanation' => 'target is the true origin; currentTarget is the listening parent.',
                        'options' => ['e.currentTarget', 'e.target', 'e.parent'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 29,
                'order_num' => 1,
                'title' => 'Media reasoning',
                'instructions' => 'Choose markup that stays accessible when media fails.',
                'questions' => [
                    [
                        'prompt' => 'Why wrap the image in picture with a source child?',
                        'explanation' => 'The browser picks the first supported source; the img remains the fallback.',
                        'options' => ['It loads faster always', 'First supported source wins, img is the fallback', 'It removes the need for alt'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'What is the track child for?',
                        'explanation' => 'Captions make the media usable without sound.',
                        'options' => ['Higher resolution', 'Captions for viewers without sound', 'Faster buffering'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'An untrusted page is embedded. Which iframe is safe?',
                        'explanation' => 'An empty sandbox blocks scripts; title keeps it announced.',
                        'options' => ['No sandbox, scripts allowed', 'Empty sandbox with a title', 'allow-scripts with allow-same-origin'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 16,
                'order_num' => 1,
                'title' => 'Motion access',
                'instructions' => 'Animate without excluding anyone.',
                'questions' => [
                    [
                        'prompt' => 'A pulsing lamp bothers motion-sensitive readers. What helps?',
                        'explanation' => 'prefers-reduced-motion lets the stylesheet still the animation for those readers.',
                        'options' => ['A faster pulse', '@media (prefers-reduced-motion) stilling it', 'Brighter colors'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'Why pair the transform with a transition?',
                        'explanation' => 'The transition spreads the change over time instead of snapping.',
                        'options' => ['It speeds up rendering', 'It animates the change smoothly', 'It fixes the layout'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'State is shown by color alone. What is missing?',
                        'explanation' => 'Color-only state excludes color-blind readers; pair it with text or shape.',
                        'options' => ['Nothing, color suffices', 'A text or shape cue alongside', 'A darker background'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 1,
                'order_num' => 1,
                'title' => 'Heading levels',
                'instructions' => 'Headings tell the browser how important a line of text is.',
                'questions' => [
                    [
                        'prompt' => 'How many heading levels does HTML offer, from h1 to h6?',
                        'explanation' => 'HTML provides six levels, h1 through h6, and h1 marks the single most important headline on the page.',
                        'options' => ['Three levels, h1 to h3', 'Six levels, h1 to h6', 'Twelve levels, h1 to h12'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'How many h1 elements should a page carry?',
                        'explanation' => 'Every page should have exactly one clear h1; search engines and screen readers use it to understand the page structure.',
                        'options' => ['Exactly one', 'One per section', 'As many as the content needs'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Why do headings matter to screen readers?',
                        'explanation' => 'Screen readers build a document outline from heading levels, so a heading describes the structure a reader is moving through.',
                        'options' => ['They let a reader jump between regions of the page', 'They make the text render larger', 'They add the page title to the browser tab'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 2,
                'order_num' => 1,
                'title' => 'Link destinations',
                'instructions' => 'The href attribute is what makes text a working link.',
                'questions' => [
                    [
                        'prompt' => 'Which attribute holds the destination of a link?',
                        'explanation' => 'The anchor element wraps clickable text and its href attribute holds the destination: a path, a full URL, or a page fragment.',
                        'options' => ['src', 'href', 'target'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'What happens to an anchor with no href attribute?',
                        'explanation' => 'Without href the text looks like a link but goes nowhere — the element is not a link at all.',
                        'options' => ['It links to the current page', 'It looks like a link but goes nowhere', 'The browser removes it from the page'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'Which is a valid href value?',
                        'explanation' => 'A path like /safe, a full URL, or a page fragment are all valid destinations.',
                        'options' => ['/safe', 'safe', 'go to safe'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 3,
                'order_num' => 1,
                'title' => 'Image attributes',
                'instructions' => 'Images carry everything the browser needs in attributes.',
                'questions' => [
                    [
                        'prompt' => 'Which element is a void element with no closing tag?',
                        'explanation' => 'img is a void element: it has no closing tag and cannot wrap text.',
                        'options' => ['img', 'figure', 'picture'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the alt attribute carry?',
                        'explanation' => 'alt carries a text description used when the image cannot load and read aloud by screen readers.',
                        'options' => ['The image file path', 'A text description of the image', 'The image dimensions'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'Which attribute points at the image file?',
                        'explanation' => 'The src attribute points at the image file; everything else about the image is presentation or description.',
                        'options' => ['src', 'alt', 'href'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 4,
                'order_num' => 1,
                'title' => 'Paragraph blocks',
                'instructions' => 'Paragraphs turn a wall of words into scannable blocks.',
                'questions' => [
                    [
                        'prompt' => 'Which element groups running text into a readable block?',
                        'explanation' => 'The p element groups running text into readable blocks, and browsers add vertical space between them automatically.',
                        'options' => ['p', 'span', 'br'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does a paragraph do to the line after it?',
                        'explanation' => 'A paragraph is block-level, so it always starts on a new line and stretches the full width available.',
                        'options' => ['Continues on the same line', 'Starts on a new line and fills the width', 'Collapses into a single character'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'Where does the space between two paragraphs come from?',
                        'explanation' => 'Browsers add vertical space between p elements automatically, which is what makes the copy scannable.',
                        'options' => ['From the browser default margins', 'From a <br> tag you must add', 'From the class attribute'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 5,
                'order_num' => 1,
                'title' => 'List structure',
                'instructions' => 'Lists need two tags working together.',
                'questions' => [
                    [
                        'prompt' => 'Which element marks one item inside a list?',
                        'explanation' => 'ul marks the list itself and each li marks one item inside it; the two tags work together.',
                        'options' => ['li', 'ul', 'ol'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'When should a list use ol instead of ul?',
                        'explanation' => 'Use ul when the order does not matter and ol with the same li items when the sequence matters, like ranked steps.',
                        'options' => ['When the order of the items matters', 'When the items are links', 'When the list has more than three items'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which markup is a correct unordered list?',
                        'explanation' => 'A ul on its own line with one li per item is the structure the browser expects.',
                        'options' => ['<ul><li>One</li><li>Two</li></ul>', '<li><ul>One</ul></li>', '<ul><li>One</ul>'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 6,
                'order_num' => 1,
                'title' => 'Input fields',
                'instructions' => 'The input element collects a single line of user data.',
                'questions' => [
                    [
                        'prompt' => 'Is the input element a void element?',
                        'explanation' => 'Like img, input is void: it has no closing tag and everything about it lives in attributes.',
                        'options' => ['Yes, it has no closing tag', 'No, it wraps its label', 'Only when type="text"'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which attribute decides what a field accepts?',
                        'explanation' => 'The type attribute decides what the field accepts: text, password, number and more.',
                        'options' => ['name', 'type', 'placeholder'],
                        'correct' => 1,
                    ],
                    [
                        'prompt' => 'What does the name attribute do?',
                        'explanation' => 'name labels the value for the server; placeholder only shows grey hint text that vanishes when typing starts.',
                        'options' => ['Shows hint text inside the field', 'Labels the value for the server', 'Sets the maximum length'],
                        'correct' => 1,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 7,
                'order_num' => 1,
                'title' => 'Button labels',
                'instructions' => 'Buttons trigger actions, and the label says what will happen.',
                'questions' => [
                    [
                        'prompt' => 'How is the button element written?',
                        'explanation' => 'The tag is button, spelled in full, and it is a normal paired element.',
                        'options' => ['<button>Label text</button>', '<button />', '<btn>Label text</btn>'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Where does a button’s visible label belong?',
                        'explanation' => 'Unlike input, button is a paired element, so the visible label sits between the opening and closing tags.',
                        'options' => ['Between the opening and closing tags', 'In a value attribute', 'In a label attribute'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What should a button label say?',
                        'explanation' => 'The label should say what happens when the button is pressed, so the action is predictable.',
                        'options' => ['What happens when it is pressed', 'A category name', 'The element name'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 8,
                'order_num' => 1,
                'title' => 'Table structure',
                'instructions' => 'Cells live in rows, and rows live in the table.',
                'questions' => [
                    [
                        'prompt' => 'Which element holds one cell of a table?',
                        'explanation' => 'table wraps everything, each tr marks one row, and each td holds one cell inside its row.',
                        'options' => ['td', 'tr', 'table'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which element marks one row?',
                        'explanation' => 'Each tr marks one row, and the cells sit inside it.',
                        'options' => ['tr', 'td', 'th'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which nesting is correct?',
                        'explanation' => 'Read it inside out: cells live in rows, rows live in the table.',
                        'options' => ['<table><tr><td>Cell</td></tr></table>', '<table><td><tr>Cell</td></table>', '<tr><table><td>Cell</td></table></tr>'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 9,
                'order_num' => 1,
                'title' => 'Div containers',
                'instructions' => 'A div is a plain box; the class attribute is the label on it.',
                'questions' => [
                    [
                        'prompt' => 'What meaning does the div element carry on its own?',
                        'explanation' => 'The div is a generic block container with no meaning of its own.',
                        'options' => ['None, it is a generic block container', 'It marks the main content', 'It marks navigation'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the class attribute add to a div?',
                        'explanation' => 'class attaches a reusable label that CSS and JavaScript can target — the sticker that says what the box is for.',
                        'options' => ['A reusable label other code can target', 'The page title', 'The element’s size'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How do you label a div for styling?',
                        'explanation' => 'Think of div as a plain box and class as the sticker that says what the box is for.',
                        'options' => ['<div class="container">Content</div>', '<div id="container">Content</div>', '<div title="container">Content</div>'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 10,
                'order_num' => 1,
                'title' => 'Page skeleton',
                'instructions' => 'A complete page follows one skeleton: doctype, html, head, body.',
                'questions' => [
                    [
                        'prompt' => 'What does the DOCTYPE declaration do?',
                        'explanation' => 'The DOCTYPE html declaration tells the browser to use standards mode.',
                        'options' => ['Tells the browser to use standards mode', 'Names the page title', 'Loads the stylesheet'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which element holds the invisible metadata?',
                        'explanation' => 'head holds invisible metadata like the title, and body holds everything the reader sees.',
                        'options' => ['head', 'body', 'main'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What happens when a page is missing a layer?',
                        'explanation' => 'A page missing any layer may still render, but it renders unpredictably.',
                        'options' => ['It renders unpredictably', 'The browser refuses to show it', 'Nothing changes at all'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 11,
                'order_num' => 1,
                'title' => 'Meaningful emphasis',
                'instructions' => 'Prefer the semantic pair when the words actually matter.',
                'questions' => [
                    [
                        'prompt' => 'Which element marks important content?',
                        'explanation' => 'strong marks important content and em marks stressed emphasis; assistive technology announces both with extra weight.',
                        'options' => ['strong', 'b', 'i'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What is wrong with b and i?',
                        'explanation' => 'The b and i tags only change appearance with no meaning attached.',
                        'options' => ['They only change appearance, with no meaning attached', 'They are deprecated', 'They cannot nest'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which markup marks a warning that must sound urgent?',
                        'explanation' => 'A warning that matters is important content, so strong is the right element.',
                        'options' => ['<strong>Warning</strong>', '<b>Warning</b>', '<i>Warning</i>'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 12,
                'order_num' => 1,
                'title' => 'Code samples',
                'instructions' => 'Two tags keep code readable: code for meaning, pre for spacing.',
                'questions' => [
                    [
                        'prompt' => 'Which element marks text as code inline?',
                        'explanation' => 'The code element marks text as code inline, and the pre element preserves whitespace and line breaks exactly as written.',
                        'options' => ['code', 'pre', 'samp'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the pre element add?',
                        'explanation' => 'pre preserves whitespace and line breaks exactly as written, which is what keeps terminal output aligned.',
                        'options' => ['It preserves whitespace and line breaks', 'It runs the code', 'It marks the language'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Why wrap code in pre?',
                        'explanation' => 'Wrapping code in pre stops the browser from collapsing the spacing that makes output readable.',
                        'options' => ['To stop the browser collapsing the spacing', 'To add syntax colours', 'To make the block scrollable'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 14,
                'order_num' => 1,
                'title' => 'Head metadata',
                'instructions' => 'The head holds invisible instructions about the page.',
                'questions' => [
                    [
                        'prompt' => 'Which meta declares the text encoding?',
                        'explanation' => 'The charset meta declares the text encoding, and UTF-8 covers nearly every character.',
                        'options' => ['<meta charset="UTF-8">', '<meta name="viewport">', '<meta name="description">'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the viewport meta tell mobile browsers?',
                        'explanation' => 'The viewport meta tells mobile browsers to match the device width instead of shrinking a desktop layout.',
                        'options' => ['To match the device width', 'To render in desktop mode', 'To enlarge all text'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What happens without these two head lines?',
                        'explanation' => 'Without the charset and viewport lines, pages risk garbled text and broken mobile layouts.',
                        'options' => ['Garbled text and a broken mobile layout', 'A slower page load only', 'Nothing at all'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 16,
                'order_num' => 1,
                'title' => 'Radio groups',
                'instructions' => 'One shared name is what links a radio group together.',
                'questions' => [
                    [
                        'prompt' => 'What links radio buttons into one group?',
                        'explanation' => 'Every radio in the group shares one name, which is what links them so selecting one deselects the rest.',
                        'options' => ['They share one name attribute', 'They share one id attribute', 'They sit inside the same div'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How many choices may a radio group offer?',
                        'explanation' => 'Radio buttons offer exactly one choice from a set.',
                        'options' => ['Exactly one', 'Any number', 'Only two'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What else does each radio need?',
                        'explanation' => 'Each still needs its own label, or users cannot tell the options apart or click them reliably.',
                        'options' => ['Its own label', 'Its own form', 'A value attribute'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 17,
                'order_num' => 1,
                'title' => 'Figures and captions',
                'instructions' => 'A figure binds an image to its caption so the two travel together.',
                'questions' => [
                    [
                        'prompt' => 'Which element binds an image to its caption?',
                        'explanation' => 'The figure element binds an image to its caption so the two travel as one unit.',
                        'options' => ['figure', 'caption', 'picture'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which child of figure holds the visible caption text?',
                        'explanation' => 'Inside figure, img carries the picture with a descriptive alt and figcaption holds the visible caption text.',
                        'options' => ['figcaption', 'alt', 'caption'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What is missing when a picture is a bare img with no caption?',
                        'explanation' => 'A bare img with no caption leaves readers guessing what they are looking at.',
                        'options' => ['Readers have no idea what the image shows', 'The image fails to load', 'The page loses its layout'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 18,
                'order_num' => 1,
                'title' => 'Long answers and dropdowns',
                'instructions' => 'Not every answer fits on one line.',
                'questions' => [
                    [
                        'prompt' => 'Which element takes multi-line input?',
                        'explanation' => 'The textarea element takes multi-line input, with rows and cols sizing it.',
                        'options' => ['textarea', 'input', 'select'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which element offers a dropdown menu?',
                        'explanation' => 'select offers a dropdown menu of option children, one marked selected to preselect it.',
                        'options' => ['select', 'textarea', 'datalist'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How is the preselected option marked?',
                        'explanation' => 'One option child is marked selected to preselect it.',
                        'options' => ['With the selected attribute', 'With a checked attribute', 'With a value of "1"'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 19,
                'order_num' => 1,
                'title' => 'Input types',
                'instructions' => 'One element becomes many controls through the type attribute.',
                'questions' => [
                    [
                        'prompt' => 'Which type brings a phone keypad and an email keyboard?',
                        'explanation' => 'Email keyboards itself on phones and validates format, and the tel type brings a phone keypad.',
                        'options' => ['email and tel', 'number and date', 'text and password'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the number type accept?',
                        'explanation' => 'The number type accepts only quantities, with min and max bounds.',
                        'options' => ['Only quantities, bounded by min and max', 'Any characters', 'Only whole numbers'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the type attribute buy besides the control?',
                        'explanation' => 'Each type also brings free validation: a malformed email blocks submit before any script runs.',
                        'options' => ['Free validation before any script runs', 'A second submit button', 'A hidden field'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 21,
                'order_num' => 1,
                'title' => 'Audio and video',
                'instructions' => 'Native media elements come with controls and captions.',
                'questions' => [
                    [
                        'prompt' => 'Which children do media elements take?',
                        'explanation' => 'Each takes source children so the browser picks the first format it supports.',
                        'options' => ['source children, one per format', 'figure children', 'track children only'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which child adds captions for deaf viewers?',
                        'explanation' => 'The track child adds captions for deaf viewers.',
                        'options' => ['track', 'source', 'caption'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'When is the text between the media tags shown?',
                        'explanation' => 'Text between the tags is the fallback shown only when media cannot play at all.',
                        'options' => ['Only when the media cannot play at all', 'Always, under the player', 'Never'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 22,
                'order_num' => 1,
                'title' => 'Collapsibles and modals',
                'instructions' => 'details is native; dialog goes further.',
                'questions' => [
                    [
                        'prompt' => 'Which element is the native collapsible?',
                        'explanation' => 'The details element is a native collapsible: its summary child stays visible as the toggle, and everything after it hides until opened.',
                        'options' => ['details with a summary child', 'dialog with a label', 'div with a hidden class'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Why is details the accessible choice for FAQs?',
                        'explanation' => 'No script is needed for the basic behavior, which makes it the accessible choice for FAQs and spoiler content.',
                        'options' => ['It needs no script for the basic behaviour', 'It can be styled with any colour', 'It loads faster'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which element goes further, as a true modal?',
                        'explanation' => 'The dialog element goes further as a true modal, though opening it needs one line of JavaScript.',
                        'options' => ['dialog', 'details', 'summary'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 23,
                'order_num' => 1,
                'title' => 'Embedding safely',
                'instructions' => 'Embedded content runs with the privileges you grant it.',
                'questions' => [
                    [
                        'prompt' => 'Which attribute strips privileges from embedded content?',
                        'explanation' => 'The sandbox attribute strips privileges, and an empty sandbox blocks scripts entirely.',
                        'options' => ['sandbox', 'allow', 'referrer'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does an empty sandbox do?',
                        'explanation' => 'An empty sandbox blocks scripts entirely, which is the safest setting for third-party content.',
                        'options' => ['It blocks scripts entirely', 'It allows everything', 'It disables the frame border'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Why can granting both allow-scripts and allow-same-origin be risky?',
                        'explanation' => 'Same-origin embeds you control may earn back narrow rights, but combining them lets the frame act as the page itself.',
                        'options' => ['The embedded page can then act as if it were your own page', 'The frame becomes invisible', 'The page loses its title'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 24,
                'order_num' => 1,
                'title' => 'Table semantics',
                'instructions' => 'Data tables need headers a screen reader can announce.',
                'questions' => [
                    [
                        'prompt' => 'Which attribute tells a reader which header a cell belongs to?',
                        'explanation' => 'th marks header cells with a scope of col or row so screen readers announce each value with its header.',
                        'options' => ['scope on the th cell', 'alt on the table', 'name on the table'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which elements separate headers from data?',
                        'explanation' => 'thead and tbody separate headers from data, and caption names the table.',
                        'options' => ['thead and tbody', 'header and footer', 'main and nav'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the colspan attribute do?',
                        'explanation' => 'The colspan attribute lets one cell span two columns, as section dividers often need.',
                        'options' => ['Lets one cell span two columns', 'Adds a second header row', 'Aligns the cell to the top'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 25,
                'order_num' => 1,
                'title' => 'Description lists',
                'instructions' => 'A description list pairs terms with their explanations.',
                'questions' => [
                    [
                        'prompt' => 'Which element wraps a description list?',
                        'explanation' => 'The dl element wraps the list, each dt names one term, and each dd holds its description.',
                        'options' => ['dl', 'ul', 'table'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which element names a term?',
                        'explanation' => 'Each dt names one term and the following dd holds its description.',
                        'options' => ['dt', 'dd', 'li'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Where does a description list fit better than a table?',
                        'explanation' => 'It fits glossaries, metadata blocks and FAQ answers far better than a table or a stack of paragraphs.',
                        'options' => ['Glossaries, metadata blocks and FAQ answers', 'Spreadsheet data', 'Image grids'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 26,
                'order_num' => 1,
                'title' => 'Global attributes',
                'instructions' => 'Global attributes work on nearly every element.',
                'questions' => [
                    [
                        'prompt' => 'Which attribute removes an element from rendering entirely?',
                        'explanation' => 'The hidden attribute removes the element from rendering entirely, and data-* stores private values scripts can read.',
                        'options' => ['hidden', 'title', 'tabindex'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the data-* attribute family do?',
                        'explanation' => 'data-* stores private values scripts can read without inventing a new attribute.',
                        'options' => ['Stores private values scripts can read', 'Sets the element’s colour', 'Declares the document type'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which attribute adds a hover tooltip?',
                        'explanation' => 'title adds a hover tooltip, id gives one unique hook, and class attaches reusable styling labels.',
                        'options' => ['title', 'id', 'class'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 27,
                'order_num' => 1,
                'title' => 'Toggles, uploads and sliders',
                'instructions' => 'Each input type carries its own behaviour.',
                'questions' => [
                    [
                        'prompt' => 'Which attribute pre-ticks a checkbox?',
                        'explanation' => 'Checkboxes toggle one option each, and the checked attribute pre-ticks one.',
                        'options' => ['checked', 'selected', 'value'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which attribute allows several files at once?',
                        'explanation' => 'File inputs accept uploads, with multiple allowing several files at once.',
                        'options' => ['multiple', 'accept-list', 'enctype'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which attributes keep a range slider’s value valid without script?',
                        'explanation' => 'Range sliders pick from a span, bounded by min, max and step so the value stays valid without any script.',
                        'options' => ['min, max and step', 'size and maxlength', 'rows and cols'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 28,
                'order_num' => 1,
                'title' => 'Specialised text inputs',
                'instructions' => 'Specialise the type; constrain the value.',
                'questions' => [
                    [
                        'prompt' => 'Which attribute holds a regex the value must match?',
                        'explanation' => 'The pattern attribute holds a regex the value must match, and password masks with minlength enforcing length.',
                        'options' => ['pattern', 'minlength', 'maxlength'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the hidden input type do?',
                        'explanation' => 'Hidden inputs ferry server values invisibly while the submit posts them.',
                        'options' => ['Carries a server value invisibly through the submit', 'Hides the field from the user only', 'Stores a password'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which input type styles itself natively for search?',
                        'explanation' => 'The tel type brings a phone keypad, url validates links, and search styles natively.',
                        'options' => ['search', 'url', 'email'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 1,
                'mission_order' => 30,
                'order_num' => 1,
                'title' => 'Table footers and row spans',
                'instructions' => 'Tables close with a footer and merge repeated cells.',
                'questions' => [
                    [
                        'prompt' => 'Which element holds summary rows after the body?',
                        'explanation' => 'The tfoot element holds summary rows after the body.',
                        'options' => ['tfoot', 'thead', 'caption'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the rowspan attribute do?',
                        'explanation' => 'The rowspan attribute stretches one cell across several rows, which suits values repeated down a column like a shared operator name.',
                        'options' => ['Stretches one cell across several rows', 'Adds a second header row', 'Pins the column to the left'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Where does tfoot sit in the table?',
                        'explanation' => 'tfoot comes after tbody, closing the table with summary rows.',
                        'options' => ['After tbody', 'Before thead', 'Inside a tr'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 1,
                'order_num' => 1,
                'title' => 'Colour values',
                'instructions' => 'Three interchangeable forms, one result.',
                'questions' => [
                    [
                        'prompt' => 'Which property paints text?',
                        'explanation' => 'The color property paints text; background-color paints the surface behind it.',
                        'options' => ['color', 'background-color', 'fill'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which are the three interchangeable colour forms?',
                        'explanation' => 'Values come as a named colour like green, a hex code like #008000, or an rgb() function like rgb(0, 128, 0).',
                        'options' => ['Named, hex and rgb()', 'Named, hsl() and cmyk()', 'Hex, rgba() and keyword'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How do browsers treat the three forms?',
                        'explanation' => 'Browsers treat all three identically, so pick whichever reads clearest in context.',
                        'options' => ['Identically', 'With different precision', 'Only hex is valid in CSS'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 2,
                'order_num' => 1,
                'title' => 'Background colour',
                'instructions' => 'Text and surface are painted independently.',
                'questions' => [
                    [
                        'prompt' => 'Which property paints the surface behind the text?',
                        'explanation' => 'background-color paints the surface behind the text.',
                        'options' => ['background-color', 'color', 'border-color'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What is the relationship between color and background-color?',
                        'explanation' => 'The two properties are independent: setting one never affects the other.',
                        'options' => ['Independent; setting one never affects the other', 'Identical in effect', 'background-color overrides color'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which pairing suits light text on a near-black field?',
                        'explanation' => 'Terminal-style designs lean on this pairing constantly: light text on a near-black field.',
                        'options' => ['color: #eee with background-color: #111', 'color: #111 with background-color: #eee', 'Both set to #000'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 3,
                'order_num' => 1,
                'title' => 'Font size',
                'instructions' => 'Size is the first lever on readability.',
                'questions' => [
                    [
                        'prompt' => 'Which property sets how large type renders?',
                        'explanation' => 'The font-size property sets how large type renders.',
                        'options' => ['font-size', 'font-weight', 'line-height'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which unit gives exact control while learning?',
                        'explanation' => 'Pixels give exact control while learning; larger projects later switch to relative units.',
                        'options' => ['px', 'rem', 'em'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Roughly what size is body copy?',
                        'explanation' => '16px is roughly body copy, and 32px is a strong headline.',
                        'options' => ['16px', '8px', '64px'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 4,
                'order_num' => 1,
                'title' => 'Padding',
                'instructions' => 'Padding is the element’s own space, inside its border.',
                'questions' => [
                    [
                        'prompt' => 'What is padding?',
                        'explanation' => 'Padding is the space between an element’s content and its border.',
                        'options' => ['Space between content and border', 'Space outside the border', 'Space between two elements'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Why does the background stretch across the padding?',
                        'explanation' => 'Padding belongs to the element, so the background stretches across it.',
                        'options' => ['Padding belongs to the element, so the background covers it', 'The background always covers the whole page', 'Padding is painted separately'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does padding: 20px apply?',
                        'explanation' => 'One value like 20px applies to all four sides at once.',
                        'options' => ['All four sides', 'Only the top and bottom', 'Only the left and right'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 5,
                'order_num' => 1,
                'title' => 'Borders',
                'instructions' => 'Width, style and color in one declaration.',
                'questions' => [
                    [
                        'prompt' => 'Which three values make up the border shorthand?',
                        'explanation' => 'The border shorthand draws the outline in one declaration: width, style, then color.',
                        'options' => ['Width, style and color', 'Size, radius and shadow', 'Style, colour and margin'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which border style suits a technical or warning feel?',
                        'explanation' => 'Solid gives a clean line; dashed gives a technical or warning feel.',
                        'options' => ['dashed', 'solid', 'none'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Does the order of the three values matter?',
                        'explanation' => 'The three values can technically come in any order, but width-style-color is the convention.',
                        'options' => ['Not technically, though width-style-color is the convention', 'Yes, colour must come first', 'Yes, style must come last'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 6,
                'order_num' => 1,
                'title' => 'Flexbox alignment',
                'instructions' => 'One axis for the items, another for their alignment.',
                'questions' => [
                    [
                        'prompt' => 'What turns a parent into a flex container?',
                        'explanation' => 'Setting display: flex on a parent turns it into a flex container and its children become flex items laid out in a row by default.',
                        'options' => ['display: flex on the parent', 'display: grid on the parent', 'flex: 1 on the parent'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which property controls the cross axis?',
                        'explanation' => 'align-items controls the cross axis: center pulls every item to the vertical middle.',
                        'options' => ['align-items', 'justify-content', 'flex-wrap'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How are flex items laid out by default?',
                        'explanation' => 'Children become flex items laid out in a row by default.',
                        'options' => ['In a row', 'In a column', 'In a grid'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 7,
                'order_num' => 1,
                'title' => 'Opacity',
                'instructions' => 'Transparency on a scale from 0 to 1.',
                'questions' => [
                    [
                        'prompt' => 'What does opacity: 0 do to an element?',
                        'explanation' => 'At 0 the element is fully invisible but still occupies layout space.',
                        'options' => ['Makes it invisible while it still occupies space', 'Removes it from the layout', 'Hides it behind other content'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does opacity: 1 mean?',
                        'explanation' => 'At 1 the element is fully solid; values between fade it proportionally.',
                        'options' => ['Fully solid', 'Half transparent', 'Invisible'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Where is a mid-scale opacity useful?',
                        'explanation' => 'Values between fade it proportionally, which is handy for disabled states and overlays.',
                        'options' => ['Disabled states and overlays', 'Print stylesheets only', 'Hover states only'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 8,
                'order_num' => 1,
                'title' => 'Absolute positioning',
                'instructions' => 'Absolute coordinates are measured from the nearest positioned ancestor.',
                'questions' => [
                    [
                        'prompt' => 'What does position: absolute measure its coordinates from?',
                        'explanation' => 'Absolute coordinates are measured from the nearest positioned ancestor.',
                        'options' => ['The nearest positioned ancestor', 'The viewport', 'The document body'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Why does the pattern use two rules?',
                        'explanation' => 'The parent gets position: relative to become the anchor, and the child gets position: absolute with coordinates.',
                        'options' => ['The parent becomes the anchor and the child is placed in it', 'One rule is for mobile and one for desktop', 'The second rule adds a transition'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which property turns an element into that anchor?',
                        'explanation' => 'position: relative makes the parent the anchor for its absolutely positioned child.',
                        'options' => ['position: relative', 'position: static', 'position: sticky'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 9,
                'order_num' => 1,
                'title' => 'Transitions',
                'instructions' => 'Name the property, the duration and the easing.',
                'questions' => [
                    [
                        'prompt' => 'What does a transition need named?',
                        'explanation' => 'A transition names the property, the duration and the easing curve.',
                        'options' => ['The property, the duration and the easing', 'Only the duration', 'A keyframes name'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does transition: background 0.3s ease do?',
                        'explanation' => 'The background shifts spread over three tenths of a second, slowing gently at the end.',
                        'options' => ['The background shifts over three tenths of a second', 'The background never changes', 'The element grows for 0.3s'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does a transition replace?',
                        'explanation' => 'A transition animates a property change instead of snapping instantly.',
                        'options' => ['Snapping straight to the new value', 'The element’s layout position', 'The value of the property'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 10,
                'order_num' => 1,
                'title' => 'Media queries',
                'instructions' => 'Styles that apply only when the viewport agrees.',
                'questions' => [
                    [
                        'prompt' => 'What does a media query do?',
                        'explanation' => 'A media query applies styles only when the viewport meets a condition.',
                        'options' => ['Applies styles only when the viewport meets a condition', 'Always applies styles', 'Downloads a stylesheet'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does @media (max-width: 600px) target?',
                        'explanation' => 'The max-width form targets small screens: everything inside activates on phones and narrow windows.',
                        'options' => ['Small screens and narrow windows', 'Large screens only', 'Print only'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What happens to the desktop layout inside that block?',
                        'explanation' => 'The desktop layout is left untouched, so responsive design is a series of small overrides.',
                        'options' => ['It is left untouched', 'It is replaced entirely', 'It becomes the only layout'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 11,
                'order_num' => 1,
                'title' => 'Class and descendant selectors',
                'instructions' => 'Target by label, then narrow by context.',
                'questions' => [
                    [
                        'prompt' => 'What does the descendant combinator do?',
                        'explanation' => 'The descendant combinator (a plain space) narrows to elements inside another.',
                        'options' => ['Narrows to elements inside another', 'Selects the next sibling only', 'Selects every element'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does .panel .title match?',
                        'explanation' => '.panel .title styles only titles sitting within a panel, leaving every other title untouched.',
                        'options' => ['Only titles inside a .panel', 'Every .title on the page', 'Only the first .title'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the element selector p match?',
                        'explanation' => 'An element selector like p would paint all paragraphs on the page.',
                        'options' => ['All p elements on the page', 'Only p elements with a class', 'The first p element'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 14,
                'order_num' => 1,
                'title' => 'Box sizing',
                'instructions' => 'Decide whether padding counts inward or outward.',
                'questions' => [
                    [
                        'prompt' => 'By default, what does width describe?',
                        'explanation' => 'By default, width sets only the content box, so padding and border pile on top and the element grows wider than declared.',
                        'options' => ['Only the content box', 'The final outer width', 'The width excluding padding'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does box-sizing: border-box change?',
                        'explanation' => 'border-box flips the math: padding and border count inward and the declared width is the final width.',
                        'options' => ['Padding and border count inward', 'Padding is ignored', 'The width becomes automatic'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which pairing centres a fixed-width box?',
                        'explanation' => 'Pair it with margin: 0 auto and a fixed width to centre the box.',
                        'options' => ['margin: 0 auto', 'position: absolute', 'text-align: center'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 15,
                'order_num' => 1,
                'title' => 'Corners and shadows',
                'instructions' => 'Two properties lift a flat box off the page.',
                'questions' => [
                    [
                        'prompt' => 'Which property rounds the corners?',
                        'explanation' => 'border-radius rounds corners, and 50% turns a square into a circle.',
                        'options' => ['border-radius', 'box-shadow', 'outline'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does a box-shadow value describe?',
                        'explanation' => 'box-shadow paints a soft offset copy behind the element using horizontal offset, vertical offset, blur and colour.',
                        'options' => ['Horizontal offset, vertical offset, blur and colour', 'The shadow’s opacity and radius', 'The element’s margin and padding'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which value turns a square into a circle?',
                        'explanation' => 'A border-radius of 50% turns a square into a circle.',
                        'options' => ['50%', '1px', '0'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 17,
                'order_num' => 1,
                'title' => 'Custom properties',
                'instructions' => 'Store a value once, repaint every consumer.',
                'questions' => [
                    [
                        'prompt' => 'How is a custom property named?',
                        'explanation' => 'Custom properties store reusable values under a -- name.',
                        'options' => ['With a -- prefix', 'With a $ prefix', 'With a @ prefix'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Where are tokens usually declared so every rule inherits them?',
                        'explanation' => 'Declared once on :root so every rule inherits them.',
                        'options' => ['On :root', 'On body', 'In every rule that uses them'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How is a token read back, and what if it is missing?',
                        'explanation' => 'They are read back with var(), which also takes a fallback used when the token is missing.',
                        'options' => ['var(), with an optional fallback', 'get(), with a required fallback', 'token(), with no fallback'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 18,
                'order_num' => 1,
                'title' => 'Type settings',
                'instructions' => 'Face, weight and leading, in that order.',
                'questions' => [
                    [
                        'prompt' => 'What does font-family do?',
                        'explanation' => 'font-family lists preferred faces first with generic fallbacks last.',
                        'options' => ['Lists preferred faces first with generic fallbacks last', 'Sets the size of the type', 'Sets the weight of the type'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which values does font-weight take?',
                        'explanation' => 'font-weight controls boldness in steps like 400 and 700.',
                        'options' => ['Steps like 400 and 700', 'Only bold and normal', 'A percentage only'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How is line-height written as a unitless multiple?',
                        'explanation' => 'line-height sets the spacing between lines as a unitless multiple, such as 1.5.',
                        'options' => ['As a unitless multiple such as 1.5', 'As a pixel value only', 'As a percentage of the font size'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 19,
                'order_num' => 1,
                'title' => 'Text styling',
                'instructions' => 'Shape the voice and the layout of the text.',
                'questions' => [
                    [
                        'prompt' => 'Which property forces uppercase without retyping?',
                        'explanation' => 'text-transform forces uppercase without retyping.',
                        'options' => ['text-transform', 'font-variant', 'text-decoration'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does letter-spacing open?',
                        'explanation' => 'letter-spacing opens tracking for labels and kickers.',
                        'options' => ['Tracking between letters', 'The line height', 'The word spacing'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which property strikes through a deletion?',
                        'explanation' => 'text-decoration underlines links or strikes deletions.',
                        'options' => ['text-decoration', 'text-align', 'text-overflow'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 20,
                'order_num' => 1,
                'title' => 'Fluid sizing',
                'instructions' => 'Size relative to something, and bound the result.',
                'questions' => [
                    [
                        'prompt' => 'Which unit tracks the root font size?',
                        'explanation' => 'The rem unit tracks the root font size, percentages track the parent, and viewport units track the window.',
                        'options' => ['rem', 'px', 'pt'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does clamp(min, preferred, max) do?',
                        'explanation' => 'clamp() bounds the result: the value grows fluidly but never leaves the range.',
                        'options' => ['Grows fluidly between a minimum and a maximum', 'Fixes the value at the preferred size', 'Rounds the value to the nearest pixel'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Why do fixed pixels break on small screens?',
                        'explanation' => 'Fixed pixels break on small screens, so modern sizing stays relative.',
                        'options' => ['They cannot adapt to the viewport', 'They load more slowly', 'They lose their font size'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 21,
                'order_num' => 1,
                'title' => 'Combinators and attribute selectors',
                'instructions' => 'Each combinator narrows the match.',
                'questions' => [
                    [
                        'prompt' => 'What does the child combinator do?',
                        'explanation' => 'The child combinator (>) hits only direct children.',
                        'options' => ['Hits only direct children', 'Hits every descendant', 'Hits the next sibling'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the adjacent sibling combinator hit?',
                        'explanation' => 'The adjacent sibling (+) hits the very next sibling.',
                        'options' => ['The very next sibling', 'Every later sibling', 'The parent'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does input[type="text"] filter by?',
                        'explanation' => 'Attribute selectors like input[type="text"] filter by attribute value.',
                        'options' => ['The attribute’s value', 'The element’s position', 'Its class name'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 23,
                'order_num' => 1,
                'title' => 'Transforms',
                'instructions' => 'Move without disturbing the flow.',
                'questions' => [
                    [
                        'prompt' => 'What does a transform leave untouched?',
                        'explanation' => 'Transforms move, resize and rotate without disturbing layout flow.',
                        'options' => ['Layout flow', 'The element’s colour', 'The document’s scrollbar'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which transform function grows or shrinks an element?',
                        'explanation' => 'translate shifts position, scale grows or shrinks, and rotate turns.',
                        'options' => ['scale', 'translate', 'rotate'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the transform pivot around?',
                        'explanation' => 'All of them pivot around transform-origin.',
                        'options' => ['transform-origin', 'the top-left corner only', 'the element’s margin box centre'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 24,
                'order_num' => 1,
                'title' => 'Gradients and backgrounds',
                'instructions' => 'Atmosphere without an image file.',
                'questions' => [
                    [
                        'prompt' => 'What does linear-gradient take?',
                        'explanation' => 'The linear-gradient function takes a direction and colour stops.',
                        'options' => ['A direction and colour stops', 'A file path and a size', 'A radius and a colour'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What do background-size and background-position control?',
                        'explanation' => 'background-size and background-position control how any background image fills its box.',
                        'options' => ['How a background image fills its box', 'The colour behind the image', 'The border of the element'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What is the advantage of a gradient over an image?',
                        'explanation' => 'One declaration can carry atmosphere an entire image would need.',
                        'options' => ['One declaration can carry atmosphere', 'It always renders faster', 'It scales without blur'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 25,
                'order_num' => 1,
                'title' => 'Focus and disabled states',
                'instructions' => 'Never remove the outline without a replacement.',
                'questions' => [
                    [
                        'prompt' => 'Which selector shows a focus ring for keyboard users only?',
                        'explanation' => ':focus-visible shows a ring for keyboard users without bothering mouse users.',
                        'options' => [':focus-visible', ':hover', ':active'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What happens if the native outline is removed with no replacement?',
                        'explanation' => 'Removing the native outline without a replacement strands keyboard users.',
                        'options' => ['Keyboard users lose the focus indicator', 'The page scrolls faster', 'The form submits earlier'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which selector styles the unavailable control?',
                        'explanation' => ':disabled styles the unavailable state of a control.',
                        'options' => [':disabled', ':enabled', ':required'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 26,
                'order_num' => 1,
                'title' => 'Broad selectors',
                'instructions' => 'Resets, groups and positional picks.',
                'questions' => [
                    [
                        'prompt' => 'What does the universal selector match?',
                        'explanation' => 'The universal selector matches everything, which is what resets use.',
                        'options' => ['Everything', 'Only block elements', 'Only elements with a class'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the general sibling combinator reach?',
                        'explanation' => 'The general sibling combinator reaches all later siblings, not just the adjacent one.',
                        'options' => ['All later siblings', 'Only the next sibling', 'Only the first sibling'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What can nth-child pick?',
                        'explanation' => 'nth-child picks positions like every even row.',
                        'options' => ['Positions, such as every even row', 'Attribute values', 'The last element only'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 27,
                'order_num' => 1,
                'title' => 'Background layers',
                'instructions' => 'The image sits on top of the colour.',
                'questions' => [
                    [
                        'prompt' => 'What does the url() function do?',
                        'explanation' => 'The url() function points at the file.',
                        'options' => ['Points at the file', 'Sets the background colour', 'Repeats the image'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does background-size: cover do?',
                        'explanation' => 'size cover scales the image to fill without stretching.',
                        'options' => ['Scales it to fill without stretching', 'Scales it to fit inside without cropping', 'Repeats the image across the box'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What shows if the background image fails to load?',
                        'explanation' => 'When the image fails, the colour underneath still shows.',
                        'options' => ['The colour underneath still shows', 'Nothing at all', 'A broken-image icon'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 28,
                'order_num' => 1,
                'title' => 'Computed sizes',
                'instructions' => 'Compute the size instead of fixing it.',
                'questions' => [
                    [
                        'prompt' => 'What does calc() do?',
                        'explanation' => 'The calc() function mixes units in one expression.',
                        'options' => ['Mixes units in one expression', 'Rounds a value to a grid', 'Computes the colour blend'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What do min() and max() do?',
                        'explanation' => 'min() and max() pick bounds from candidates.',
                        'options' => ['Pick bounds from candidates', 'Round to the nearest grid line', 'Set the stacking order'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does aspect-ratio hold?',
                        'explanation' => 'aspect-ratio holds a box proportion, such as 16/9 for video frames.',
                        'options' => ['A box proportion, such as 16/9', 'The element’s opacity', 'The border thickness'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 29,
                'order_num' => 1,
                'title' => 'Wrapping and sharing space',
                'instructions' => 'Let the row wrap, then share what is left.',
                'questions' => [
                    [
                        'prompt' => 'Which property lets flex items flow onto new lines?',
                        'explanation' => 'flex-wrap lets items flow onto new lines instead of crushing.',
                        'options' => ['flex-wrap', 'flex-direction', 'flex-basis'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does justify-content: space-between do?',
                        'explanation' => 'justify-content spreads items; space-between pushes the first and last to the edges.',
                        'options' => ['Pushes the first and last items to the edges', 'Centres every item', 'Adds space above the row'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does flex-grow do?',
                        'explanation' => 'flex-grow lets one item absorb the leftover room.',
                        'options' => ['Lets one item absorb the leftover room', 'Sets the gap between items', 'Forces items onto one line'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 2,
                'mission_order' => 30,
                'order_num' => 1,
                'title' => 'Fixed and sticky layers',
                'instructions' => 'Some layers ignore the scroll.',
                'questions' => [
                    [
                        'prompt' => 'What does position: fixed pin to?',
                        'explanation' => 'Fixed positioning pins to the viewport itself, which is what persistent bars need.',
                        'options' => ['The viewport', 'The nearest positioned ancestor', 'The document body'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How does position: sticky behave?',
                        'explanation' => 'Sticky toggles between flowing and pinned at a threshold, which is what section headers use.',
                        'options' => ['It flows until a threshold, then pins', 'It is always pinned', 'It is removed from the flow entirely'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What is needed for z-index to stack layers?',
                        'explanation' => 'Stacking only works on positioned elements.',
                        'options' => ['The elements must be positioned', 'The elements must have a class', 'The parent must be a grid'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 1,
                'order_num' => 1,
                'title' => 'Declaring a variable',
                'instructions' => 'A name worth reading beats a name you have to decode.',
                'questions' => [
                    [
                        'prompt' => 'Which keyword declares a variable?',
                        'explanation' => 'The let keyword declares one, the equals sign assigns it.',
                        'options' => ['let', 'set', 'make'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How is a text value written in JavaScript?',
                        'explanation' => 'Strings, text values, always sit inside quotes, single or double.',
                        'options' => ['Inside quotes', 'Without quotes', 'Inside brackets'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What should a variable name say?',
                        'explanation' => 'The name you choose should say what the value means.',
                        'options' => ['What the value means', 'Where the value came from', 'The type of the value'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 2,
                'order_num' => 1,
                'title' => 'Arithmetic operators',
                'instructions' => 'The code still runs when the operator is wrong.',
                'questions' => [
                    [
                        'prompt' => 'Which operator divides?',
                        'explanation' => 'Arithmetic operators compute with numbers: + adds, - subtracts, * multiplies, / divides.',
                        'options' => ['/', '+', '*'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Why is the wrong operator so dangerous?',
                        'explanation' => 'The code still runs and only the result is wrong, so the bug hides until someone checks the numbers.',
                        'options' => ['The code still runs and only the result is wrong', 'The page stops loading', 'The browser reports an error'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does 6 / 2 evaluate to?',
                        'explanation' => 'The division operator divides, so 6 / 2 is 3.',
                        'options' => ['3', '8', '12'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 3,
                'order_num' => 1,
                'title' => 'Conditional branches',
                'instructions' => 'One equals sign is the whole difference.',
                'questions' => [
                    [
                        'prompt' => 'What does an if statement do?',
                        'explanation' => 'An if statement runs a block only when its condition is true, with an optional else for the false path.',
                        'options' => ['Runs a block only when its condition is true', 'Runs a block a fixed number of times', 'Declares a new variable'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What is the difference between > and >=?',
                        'explanation' => '> means strictly greater, while >= means greater than or equal.',
                        'options' => ['> is strictly greater, >= includes equality', '> includes equality, >= does not', 'They are the same'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which keyword handles the false path?',
                        'explanation' => 'The optional else runs when the condition is false.',
                        'options' => ['else', 'then', 'otherwise'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 4,
                'order_num' => 1,
                'title' => 'For loop headers',
                'instructions' => 'Three parts, separated by semicolons.',
                'questions' => [
                    [
                        'prompt' => 'How many parts does a for loop header have?',
                        'explanation' => 'Its header holds three parts separated by semicolons: an initializer, a condition, and an increment.',
                        'options' => ['Three: initializer, condition, increment', 'Two: condition and increment', 'One: a block of statements'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'When is the condition checked?',
                        'explanation' => 'The condition is checked before each pass, which is what stops the loop.',
                        'options' => ['Before each pass', 'After each pass', 'Only once at the start'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the initializer do?',
                        'explanation' => 'The initializer runs once, setting up the counter the loop will step.',
                        'options' => ['It runs once, setting up the counter', 'It runs on every pass', 'It runs after the loop ends'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 5,
                'order_num' => 1,
                'title' => 'Functions and parameters',
                'instructions' => 'Package work under a name, then hand it values.',
                'questions' => [
                    [
                        'prompt' => 'What do the parentheses after a function name hold?',
                        'explanation' => 'Parameters listed in parentheses receive caller values.',
                        'options' => ['The parameters that receive caller values', 'The function’s body', 'The return type'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which keyword sends a result back out?',
                        'explanation' => 'return sends a result back out of the function.',
                        'options' => ['return', 'yield out', 'send'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the toUpperCase string method do?',
                        'explanation' => 'toUpperCase returns the uppercased copy of a string.',
                        'options' => ['Returns the uppercased copy', 'Changes the string in place', 'Adds a space to the string'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 6,
                'order_num' => 1,
                'title' => 'Arrays',
                'instructions' => 'An ordered list in one variable.',
                'questions' => [
                    [
                        'prompt' => 'What brackets an array literal?',
                        'explanation' => 'Square brackets wrap the elements, commas separate them.',
                        'options' => ['Square brackets', 'Curly braces', 'Parentheses'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the length property report?',
                        'explanation' => 'The length property reports how many elements there are.',
                        'options' => ['How many elements there are', 'The type of the elements', 'The memory used'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'At which index does the first element sit?',
                        'explanation' => 'Indexing starts at zero, so the first element is at index 0.',
                        'options' => ['Zero', 'One', 'It depends on the array'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 7,
                'order_num' => 1,
                'title' => 'Objects',
                'instructions' => 'Named properties in one record.',
                'questions' => [
                    [
                        'prompt' => 'What brackets an object literal?',
                        'explanation' => 'Curly braces wrap key-value pairs separated by commas.',
                        'options' => ['Curly braces', 'Square brackets', 'Parentheses'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How are string values written in an object?',
                        'explanation' => 'String values in quotes, numbers and booleans bare.',
                        'options' => ['In quotes', 'Bare', 'In parentheses'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which notation reads one property back out?',
                        'explanation' => 'Dot notation like operatorRecord.name reads a single property back out.',
                        'options' => ['Dot notation', 'Slash notation', 'Question-mark notation'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 8,
                'order_num' => 1,
                'title' => 'Reaching into the DOM',
                'instructions' => 'The DOM is the browser’s live model of the page.',
                'questions' => [
                    [
                        'prompt' => 'What does the DOM stand for?',
                        'explanation' => 'The DOM is the browser’s live model of the page.',
                        'options' => ['The browser’s live model of the page', 'A file format for styling', 'A server-side template engine'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which call reaches an element by its id?',
                        'explanation' => 'document.getElementById reaches into the DOM by id and returns the element object.',
                        'options' => ['document.getElementById', 'document.queryById', 'document.getElement'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does assigning to textContent do?',
                        'explanation' => 'Assigning to textContent rewrites what the reader sees without touching the markup around it.',
                        'options' => ['Rewrites the text the reader sees', 'Rebuilds the whole page', 'Adds a new element'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 9,
                'order_num' => 1,
                'title' => 'Event listeners',
                'instructions' => 'Say when X happens, then run this.',
                'questions' => [
                    [
                        'prompt' => 'Which method registers a listener?',
                        'explanation' => 'addEventListener registers a listener: when X happens on this element, run that function.',
                        'options' => ['addEventListener', 'onEvent', 'bindClick'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What is the first argument of addEventListener?',
                        'explanation' => 'The event name as a string, click for buttons, plus a callback function holding the response.',
                        'options' => ['The event name as a string', 'The callback function', 'The target element'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the callback function hold?',
                        'explanation' => 'The callback holds the response that runs when the event fires.',
                        'options' => ['The response to run when the event fires', 'The element’s styles', 'The page title'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 11,
                'order_num' => 1,
                'title' => 'Template literals',
                'instructions' => 'Backticks, slots, and real line breaks.',
                'questions' => [
                    [
                        'prompt' => 'Which quotes open a template literal?',
                        'explanation' => 'Backticks open the string instead of quotes.',
                        'options' => ['Backticks', 'Single quotes', 'Double quotes'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How do you drop a value into a template literal?',
                        'explanation' => '${expression} slots drop any value inline, with no plus signs or spacing bugs.',
                        'options' => ['${expression} slots', 'Concatenation with +', 'A placeholder function'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What can a template literal do that a quoted string cannot?',
                        'explanation' => 'Template literals span multiple lines, which quoted strings cannot do.',
                        'options' => ['Span multiple lines', 'Store numbers without quotes', 'Call functions by themselves'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 12,
                'order_num' => 1,
                'title' => 'Switch statements',
                'instructions' => 'One value, many branches.',
                'questions' => [
                    [
                        'prompt' => 'What does a switch match on?',
                        'explanation' => 'A switch picks one branch from many by matching a single value.',
                        'options' => ['A single value', 'A range of values', 'The type of a variable'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which keyword stops the fall into the next case?',
                        'explanation' => 'break stops the fall into the next case.',
                        'options' => ['break', 'stop', 'exit'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which branch catches everything unmatched?',
                        'explanation' => 'default catches everything unmatched.',
                        'options' => ['default', 'otherwise', 'fallback'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 13,
                'order_num' => 1,
                'title' => 'While loops',
                'instructions' => 'The body has to move toward the exit.',
                'questions' => [
                    [
                        'prompt' => 'When is a while condition checked?',
                        'explanation' => 'A while loop repeats while its condition stays true, checking before every pass.',
                        'options' => ['Before every pass', 'After every pass', 'Only once'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does a while loop carry of its own?',
                        'explanation' => 'Unlike for, it carries no counter of its own, so the body must move toward the exit.',
                        'options' => ['No counter of its own', 'A counter of its own', 'A list of its own'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What happens if the body never moves toward the exit?',
                        'explanation' => 'The loop never ends, which is why countdowns are the classic shape.',
                        'options' => ['The loop never ends', 'The loop runs once', 'The condition is ignored'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 15,
                'order_num' => 1,
                'title' => 'Destructuring and spread',
                'instructions' => 'Unpack values into names, pour collections together.',
                'questions' => [
                    [
                        'prompt' => 'What does destructuring do?',
                        'explanation' => 'Destructuring unpacks values straight into names.',
                        'options' => ['Unpacks values straight into names', 'Copies an object deeply', 'Removes a property'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does the spread syntax do?',
                        'explanation' => 'Spread pours one collection into another.',
                        'options' => ['Pours one collection into another', 'Removes the last element', 'Sorts the values'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does const { name, band } = operator pull out?',
                        'explanation' => 'It pulls two properties in one line, each into its own name.',
                        'options' => ['Two properties in one line', 'The whole record as a string', 'Only the first property'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 16,
                'order_num' => 1,
                'title' => 'Parsing risky data',
                'instructions' => 'One bad payload must not kill the script.',
                'questions' => [
                    [
                        'prompt' => 'What does JSON.parse do with malformed input?',
                        'explanation' => 'JSON.parse throws on malformed input instead of returning a polite error.',
                        'options' => ['Throws', 'Returns null', 'Returns an empty object'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which block attempts the work?',
                        'explanation' => 'The try block attempts the work, and catch runs only when it throws.',
                        'options' => ['try', 'catch', 'finally'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does try/catch buy you?',
                        'explanation' => 'Wrapping the parse in try/catch keeps one bad payload from killing the script.',
                        'options' => ['One bad payload cannot kill the script', 'Faster parsing', 'Automatic type coercion'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 17,
                'order_num' => 1,
                'title' => 'Selecting every match',
                'instructions' => 'Grab the list once, then walk it.',
                'questions' => [
                    [
                        'prompt' => 'What does querySelectorAll return?',
                        'explanation' => 'querySelectorAll grabs every match at once as a list.',
                        'options' => ['Every match as a list', 'The first match only', 'A count of the matches'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which classList method attaches a class?',
                        'explanation' => 'classList toggles styling per element without string surgery: add("on") attaches the class.',
                        'options' => ['add', 'push', 'append'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which method walks a list applying something to each element?',
                        'explanation' => 'forEach walks the list, applying the change to every element.',
                        'options' => ['forEach', 'join', 'find'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 18,
                'order_num' => 1,
                'title' => 'Timers',
                'instructions' => 'Schedule work, and be ready to stop it.',
                'questions' => [
                    [
                        'prompt' => 'What does setInterval do?',
                        'explanation' => 'setInterval runs a callback every N milliseconds and returns an id for that timer.',
                        'options' => ['Runs a callback every N milliseconds and returns an id', 'Runs a callback once after N milliseconds', 'Delays a single call until the page loads'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How do you stop an interval?',
                        'explanation' => 'clearInterval(id) stops it.',
                        'options' => ['clearInterval with the timer id', 'stopInterval with the callback', 'The timer stops itself'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What is the standard self-stopping pattern?',
                        'explanation' => 'A counter that clears its own timer after three ticks is the standard self-stopping beacon pattern.',
                        'options' => ['A counter that clears its own timer after three ticks', 'A single setTimeout call', 'A for loop with a break'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 19,
                'order_num' => 1,
                'title' => 'localStorage',
                'instructions' => 'Small strings that survive the visit.',
                'questions' => [
                    [
                        'prompt' => 'What does localStorage persist?',
                        'explanation' => 'localStorage persists small strings in the browser across visits.',
                        'options' => ['Small strings, across visits', 'Objects, without conversion', 'Only the current page state'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Why do objects need converting before they are stored?',
                        'explanation' => 'Only strings survive, so objects travel through JSON.stringify on the way in and JSON.parse on the way out.',
                        'options' => ['Only strings survive storage', 'Storage rejects large values', 'Storage is read-only for objects'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which pair stores and reads a value?',
                        'explanation' => 'setItem stores under a key and getItem reads it back.',
                        'options' => ['setItem and getItem', 'put and take', 'write and fetch'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 20,
                'order_num' => 1,
                'title' => 'Block scope',
                'instructions' => 'Where a variable exists is part of the contract.',
                'questions' => [
                    [
                        'prompt' => 'How does var differ from let and const?',
                        'explanation' => 'var leaks across blocks in the old function-scoped style, while let and const stay inside the nearest braces.',
                        'options' => ['var leaks across blocks; let and const stay inside the block', 'var is block-scoped; let is global', 'They scope identically'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Why does modern code avoid var?',
                        'explanation' => 'That difference is why modern code avoids var: a loop counter declared with let disappears at the end of the loop.',
                        'options' => ['Because let and const keep values inside the block they were declared in', 'Because var cannot be reassigned', 'Because var is slower'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does a block-scoped loop counter do after the loop?',
                        'explanation' => 'A counter declared with let disappears once the loop ends, so it cannot leak into later code.',
                        'options' => ['It disappears with the block', 'It survives as a global', 'It keeps counting'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 22,
                'order_num' => 1,
                'title' => 'Arrow functions',
                'instructions' => 'A callback without the ceremony.',
                'questions' => [
                    [
                        'prompt' => 'What does the arrow function (x) => x * 2 mean?',
                        'explanation' => 'It means take x, return double, with no function keyword and no braces for single expressions.',
                        'options' => ['Take x, return double', 'Declare an empty function', 'Multiply the array by two'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Where do arrow functions appear most?',
                        'explanation' => 'Array methods and event wiring both take callbacks, so arrows appear wherever callbacks go.',
                        'options' => ['Wherever a callback is expected', 'Only in class constructors', 'Only inside loops'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does an arrow function with a single expression omit?',
                        'explanation' => 'No function keyword and no braces for single expressions.',
                        'options' => ['The function keyword and the braces', 'The parameter list', 'The return value'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 23,
                'order_num' => 1,
                'title' => 'find and reduce',
                'instructions' => 'Two ways to finish with a collection.',
                'questions' => [
                    [
                        'prompt' => 'What does find return?',
                        'explanation' => 'find returns the first element passing a test, or undefined when none does.',
                        'options' => ['The first element passing a test', 'An array of every match', 'The index of the match'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does reduce do?',
                        'explanation' => 'reduce folds a whole array into one value by carrying an accumulator through each element.',
                        'options' => ['Folds the array into one value with an accumulator', 'Returns the largest element', 'Removes duplicates'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which methods complete the collection toolkit with find and reduce?',
                        'explanation' => 'Together with map and filter, they finish the toolkit.',
                        'options' => ['map and filter', 'push and pop', 'slice and splice'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 24,
                'order_num' => 1,
                'title' => 'Inspecting objects',
                'instructions' => 'Treat an object as data and read it.',
                'questions' => [
                    [
                        'prompt' => 'What does Object.keys return?',
                        'explanation' => 'Object.keys lists the property names.',
                        'options' => ['The property names', 'The property values', 'Both, as pairs'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does Object.entries return?',
                        'explanation' => 'Object.entries lists both as pairs.',
                        'options' => ['Key-value pairs', 'Only the values', 'Only the names'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How do you read a property whose name arrives as a variable?',
                        'explanation' => 'Bracket notation reads properties whose names arrive as variables.',
                        'options' => ['With bracket notation', 'With a dot', 'With a colon'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 25,
                'order_num' => 1,
                'title' => 'Classes',
                'instructions' => 'A blueprint for making similar objects.',
                'questions' => [
                    [
                        'prompt' => 'When does the constructor run?',
                        'explanation' => 'The constructor runs once per new instance to store its data.',
                        'options' => ['Once per new instance', 'Once per class definition', 'Every time a method is called'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What do methods defined on a class have in common?',
                        'explanation' => 'Methods defined on the class are shared by every instance.',
                        'options' => ['They are shared by every instance', 'They run only once', 'They are copied into each object'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'When does a class earn its keep?',
                        'explanation' => 'Classes suit entities with both state and behaviour; plain data without behaviour does not need one.',
                        'options' => ['When the entity has both state and behaviour', 'When the data is large', 'When the fields are dynamic'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 26,
                'order_num' => 1,
                'title' => 'Building nodes',
                'instructions' => 'Create, fill, graft, prune.',
                'questions' => [
                    [
                        'prompt' => 'What does createElement build?',
                        'explanation' => 'createElement builds a detached node — it exists but is not on the page yet.',
                        'options' => ['A detached node', 'A node already in the page', 'A copy of an existing node'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which call grafts a node onto a parent?',
                        'explanation' => 'appendChild grafts the node onto a parent.',
                        'options' => ['appendChild', 'textContent', 'querySelector'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which call prunes a node from the page?',
                        'explanation' => 'remove() prunes it.',
                        'options' => ['remove()', 'detach()', 'deleteNode()'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 27,
                'order_num' => 1,
                'title' => 'Guarding a form',
                'instructions' => 'Check the values before the page travels.',
                'questions' => [
                    [
                        'prompt' => 'Which event fires on the form itself?',
                        'explanation' => 'The submit event fires on the form itself, reading values through .value on each control.',
                        'options' => ['submit', 'click', 'change'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does preventDefault do on the submit event?',
                        'explanation' => 'Calling preventDefault stops the page reload so the script can check lengths and patterns first.',
                        'options' => ['Stops the page reload', 'Clears the form fields', 'Submits the form twice'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How do you read a control’s current value?',
                        'explanation' => 'You read values through .value on each control.',
                        'options' => ['Through .value', 'Through .text', 'Through .name'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 29,
                'order_num' => 1,
                'title' => 'Promise chains',
                'instructions' => 'A future value in one of three states.',
                'questions' => [
                    [
                        'prompt' => 'What are the three promise states?',
                        'explanation' => 'A promise represents a future value with three states: pending, fulfilled, or rejected.',
                        'options' => ['Pending, fulfilled, rejected', 'Open, closed, failed', 'Waiting, done, error'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which method runs on fulfillment?',
                        'explanation' => 'then runs on fulfillment.',
                        'options' => ['then', 'catch', 'finally'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What should you check before parsing a response?',
                        'explanation' => 'Checking response.ok before parsing keeps HTTP failures like 404s from masquerading as good data.',
                        'options' => ['response.ok', 'response.json', 'response.statusText only'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 30,
                'order_num' => 1,
                'title' => 'Modules',
                'instructions' => 'Share code on purpose, and only on purpose.',
                'questions' => [
                    [
                        'prompt' => 'Which keyword publishes a binding from a module?',
                        'explanation' => 'The export keyword publishes a binding from its file.',
                        'options' => ['export', 'publish', 'share'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does an import statement name?',
                        'explanation' => 'import names it in another file: import { band } from "./radio.js".',
                        'options' => ['A binding from another file', 'A global variable', 'A CSS file'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'How do modules differ from plain script tags?',
                        'explanation' => 'Unlike script tags that share one global scope implicitly, modules keep every file’s scope to itself.',
                        'options' => ['Modules keep each file’s scope to itself', 'Modules run faster', 'Modules cannot import functions'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 31,
                'order_num' => 1,
                'title' => 'Strict comparisons',
                'instructions' => 'Loose equality coerces types and lies.',
                'questions' => [
                    [
                        'prompt' => 'What does "7" == 7 evaluate to?',
                        'explanation' => 'Loose equality coerces types and lies: "7" == 7 passes while meaning different things.',
                        'options' => ['true', 'false', '"7"'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does strict equality check?',
                        'explanation' => 'Strict equality checks value and type together.',
                        'options' => ['Value and type together', 'Value only', 'Type only'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does typeof give you?',
                        'explanation' => 'typeof names any value’s type for debugging.',
                        'options' => ['The type of a value', 'Its value', 'Its length'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 32,
                'order_num' => 1,
                'title' => 'for-of loops',
                'instructions' => 'Walk the values, not the indexes.',
                'questions' => [
                    [
                        'prompt' => 'What does the for-of loop give you?',
                        'explanation' => 'The for-of loop walks values directly with no index bookkeeping.',
                        'options' => ['The values directly', 'The indexes only', 'The keys of an object'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which keyword exits the loop early?',
                        'explanation' => 'break exits early and continue skips one pass.',
                        'options' => ['break', 'stop', 'skip'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'When is the classic indexed for still the right tool?',
                        'explanation' => 'Reserve the classic indexed for for the rare cases that need the index itself.',
                        'options' => ['When you need the index itself', 'When iterating an object', 'When the values are strings'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 33,
                'order_num' => 1,
                'title' => 'Iterating object data',
                'instructions' => 'Turn a static record into iterable data.',
                'questions' => [
                    [
                        'prompt' => 'What does Object.values pull out?',
                        'explanation' => 'Object.values pulls every value into an array.',
                        'options' => ['Every value into an array', 'Every key into an array', 'Only the string values'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does Object.entries give you?',
                        'explanation' => 'Object.entries pairs each key with its value for looping.',
                        'options' => ['Key-value pairs for looping', 'Only the values', 'A sorted list of keys'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Why does this turn a record into report-ready data?',
                        'explanation' => 'These three turn static records into iterable data for reports and logs.',
                        'options' => ['They make the record iterable for reports and logs', 'They sort the record by key', 'They make the values immutable'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 34,
                'order_num' => 1,
                'title' => 'Input events',
                'instructions' => 'Inputs fire their own events.',
                'questions' => [
                    [
                        'prompt' => 'Which event streams every keystroke?',
                        'explanation' => 'The input event streams every keystroke for live feedback.',
                        'options' => ['input', 'change', 'keydown'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'When does the change event fire?',
                        'explanation' => 'change fires when a value commits on blur or selection.',
                        'options' => ['When a value commits on blur or selection', 'On every keystroke', 'When the form is submitted'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Which event sees physical keys, including Escape?',
                        'explanation' => 'keydown sees physical keys including Escape.',
                        'options' => ['keydown', 'keypress', 'keyup only'],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'course_order' => 3,
                'mission_order' => 35,
                'order_num' => 1,
                'title' => 'Reading a whole form',
                'instructions' => 'One call instead of a query per control.',
                'questions' => [
                    [
                        'prompt' => 'How is FormData constructed?',
                        'explanation' => 'Constructed with the form element, it reads the whole form at once by control name.',
                        'options' => ['With the form element', 'With a control name', 'With a JSON string'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'What does its get() method return?',
                        'explanation' => 'Its get() method returns one named value, which beats querying every input individually in long forms.',
                        'options' => ['One named value', 'Every value as an object', 'The list of control names'],
                        'correct' => 0,
                    ],
                    [
                        'prompt' => 'Why is FormData better in a long form?',
                        'explanation' => 'It beats querying every input individually in long forms.',
                        'options' => ['You do not have to query every input individually', 'It validates each field', 'It uploads the form for you'],
                        'correct' => 0,
                    ],
                ],
            ],
        ];
    }

    private function htmlFinishSections(): array
    {
        return [
            [
                'title' => 'Input Variety',
                'description' => 'Beyond text fields: toggles, uploads, sliders, and specialized text types.',
                'missions' => [
                    [
                        'title' => 'Toggle_Upload_Inputs',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Checkboxes toggle one option each, and the checked attribute pre-ticks one. File inputs accept uploads, with multiple allowing several files at once. Range sliders pick from a span, bounded by min, max, and step so the value stays valid without any script.

                            Syntax: <input type="checkbox" checked>, <input type="file" multiple>, <input type="range" min="0" max="100" step="10">.

                            Example: an alert toggle pre-checked, a multi-file log upload, and a bounded power slider.

                            Your task: the three generic text fields below are wrong tools. Convert them to a checked checkbox, a multiple file input, and a 0-to-100 step-10 range.
                            TEXT,
                        'broken_code' => "<input type=\"text\" name=\"alerts\">\n<input type=\"text\" name=\"log\">\n<input type=\"text\" name=\"power\">",
                        'solution_code' => "<label><input type=\"checkbox\" name=\"alerts\" checked> Alerts</label>\n<input type=\"file\" name=\"log\" multiple>\n<input type=\"range\" name=\"power\" min=\"0\" max=\"100\" step=\"10\">",
                        'target_html' => '<div>[x] Alerts [choose files] [slider 0-100]</div>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['type="checkbox"', 'checked', 'type="file"', 'multiple', 'type="range"']]]),
                        'hints' => json_encode(['checked pre-ticks the checkbox.', 'multiple allows several files.', 'min/max/step bound the slider.']),
                    ],
                    [
                        'title' => 'Text_Input_Depth',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Text-like inputs specialize by purpose. The tel type brings a phone keypad, url validates links, search styles natively, and password masks with minlength enforcing length. The pattern attribute holds a regex the value must match, and hidden inputs ferry server values invisibly while submit posts the form.

                            Syntax: <input type="tel" pattern="[0-9]{3}-[0-9]{4}">, <input type="password" minlength="8">, <input type="hidden" name="token" value="abc">.

                            Example: a contact row with phone pattern, site URL, search box, long-enough password, hidden token, and submit.

                            Your task: the six plain text fields below need purpose. Convert them to tel with pattern, url, search, minlength password, hidden token, and submit.
                            TEXT,
                        'broken_code' => "<input type=\"text\" name=\"phone\">\n<input type=\"text\" name=\"site\">\n<input type=\"text\" name=\"q\">\n<input type=\"text\" name=\"pw\">\n<input type=\"text\" name=\"token\">\n<input type=\"text\" name=\"go\">",
                        'solution_code' => "<input type=\"tel\" name=\"phone\" pattern=\"[0-9]{3}-[0-9]{4}\">\n<input type=\"url\" name=\"site\">\n<input type=\"search\" name=\"q\">\n<input type=\"password\" name=\"pw\" minlength=\"8\">\n<input type=\"hidden\" name=\"token\" value=\"abc\">\n<button type=\"submit\">Send</button>",
                        'target_html' => '<div>[tel] [url] [search] [password] [submit]</div>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['type="tel"', 'pattern', 'type="url"', 'type="hidden"', 'type="submit"']]]),
                        'hints' => json_encode(['tel pairs with a pattern regex.', 'password pairs with minlength.', 'hidden carries invisible values.']),
                    ],
                ],
            ],
            [
                'title' => 'Media and Tables Finish',
                'description' => 'Responsive images and complete table footers.',
                'missions' => [
                    [
                        'title' => 'Media_Picture',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            The picture element serves different images per screen. Its source children carry media conditions with srcset files, and the browser takes the first matching one. The plain img at the end stays mandatory as the fallback for old browsers and as the alt carrier.

                            Syntax: <picture> with <source media="(max-width: 600px)" srcset="dish-small.png"> then the full <img>.

                            Example: phones fetch the small dish image while desktops take the full one, and both keep the alt text.

                            Your task: the single dish image below serves everyone the heavy file. Wrap it in a picture with a small-screen source.
                            TEXT,
                        'broken_code' => '<img src="dish.png" alt="Array dish 7">',
                        'solution_code' => "<picture>\n  <source media=\"(max-width: 600px)\" srcset=\"dish-small.png\">\n  <img src=\"dish.png\" alt=\"Array dish 7\">\n</picture>",
                        'target_html' => '<div>[responsive dish image]</div>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<picture>', '<source', 'media=', '<img']]]),
                        'hints' => json_encode(['source carries the media condition.', 'srcset names the small file.', 'Keep the img as fallback with alt.']),
                    ],
                    [
                        'title' => 'Table_Foot_Rowspan',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Tables close with a footer and merge repeated cells. The tfoot element holds summary rows after the body, and the rowspan attribute stretches one cell across several rows, which suits values repeated down a column like a shared operator name.

                            Syntax: <tfoot><tr><td>..</td></tr></tfoot> after tbody, and <td rowspan="2"> for the merged cell.

                            Example: a roster whose footer totals the shift and whose operator cell spans both band rows.

                            Your task: the table below ends at the body with a duplicated name. Add a footer row and merge the repeated cell with rowspan 2.
                            TEXT,
                        'broken_code' => "<table>\n  <tbody>\n    <tr><td>Chen</td><td>UHF</td></tr>\n    <tr><td>Chen</td><td>VHF</td></tr>\n  </tbody>\n</table>",
                        'solution_code' => "<table>\n  <tbody>\n    <tr><td rowspan=\"2\">Chen</td><td>UHF</td></tr>\n    <tr><td>VHF</td></tr>\n  </tbody>\n  <tfoot>\n    <tr><td colspan=\"2\">Two bands, one operator.</td></tr>\n  </tfoot>\n</table>",
                        'target_html' => '<table><tbody><tr><td rowspan="2">Chen</td><td>UHF</td></tr><tr><td>VHF</td></tr></tbody><tfoot><tr><td>Two bands, one operator.</td></tr></tfoot></table>',
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['<tfoot>', 'rowspan']]]),
                        'hints' => json_encode(['tfoot closes the table after tbody.', 'rowspan merges down repeated cells.', 'Remove the duplicated name cell.']),
                    ],
                ],
            ],
        ];
    }

    /** @return list<array{title: string, description: string, missions: list<array<string, mixed>>}> */
    private function cssFinishSections(): array
    {
        return [
            [
                'title' => 'Selector Depth',
                'description' => 'Matching anything: universal, grouping, general siblings, nth patterns, and generated content.',
                'missions' => [
                    [
                        'title' => 'Selector_Depth',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Broad selectors tame repetitive markup. The universal selector matches everything for resets, commas group headings that share styling, the general sibling combinator reaches all later siblings (not just the adjacent one), nth-child picks positions like every even row, and ::after injects markers without extra elements.

                            Syntax: * { ... }, h1, h2 { ... }, h2 ~ p { ... }, tr:nth-child(even) { ... }, .tag::after { content: "!"; }.

                            Example: one rule resetting margins, one styling both headlines, one dimming every paragraph after a heading, zebra rows, and flagged tags.

                            Your task: the stylesheet below styles one headline and one row only. Broaden it with a reset, a grouped headline rule, a general-sibling rule, an even-row rule, and an ::after marker.
                            TEXT,
                        'broken_code' => "h1 {\n  color: #6fc49a;\n}\ntr {\n  background: #171b21;\n}",
                        'solution_code' => "* {\n  margin: 0;\n}\nh1, h2 {\n  color: #6fc49a;\n}\nh2 ~ p {\n  color: #a9b0a5;\n}\ntr:nth-child(even) {\n  background: #1c1f26;\n}\n.tag::after {\n  content: \"!\";\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a;font-family:monospace;color:#6fc49a'><div>Headlines, zebra rows, flagged tags</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['*', 'h1, h2', '~', 'nth-child', '::after']]]),
                        'hints' => json_encode(['* matches everything for resets.', 'Commas group selectors sharing a rule.', '~ reaches all later siblings.']),
                    ],
                ],
            ],
            [
                'title' => 'Backgrounds and Sizing',
                'description' => 'Image backgrounds and modern math: layers, calc, min/max, and media boxes.',
                'missions' => [
                    [
                        'title' => 'Background_Layers',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Background images layer over background colors. The url() function points at the file, no-repeat stops the tiling, position centers the single copy, and size cover scales it to fill without stretching. When the image fails, the color underneath still shows, which is why both belong together.

                            Syntax: .panel { background-color: #171b21; background-image: url("grid.png"); background-repeat: no-repeat; background-position: center; background-size: cover; }.

                            Example: a panel with a centered, covering grid texture over its dark base.

                            Your task: the panel below has color but no texture. Layer the grid image centered and covering without tiling.
                            TEXT,
                        'broken_code' => ".panel {\n  background-color: #171b21;\n  padding: 2rem;\n}",
                        'solution_code' => ".panel {\n  background-color: #171b21;\n  background-image: url(\"grid.png\");\n  background-repeat: no-repeat;\n  background-position: center;\n  background-size: cover;\n  padding: 2rem;\n}",
                        'target_html' => "<div style='background-color:#171b21;background-image:url(grid.png);background-repeat:no-repeat;background-position:center;background-size:cover;padding:2rem;border:1px solid #6fc49a;color:#6fc49a;font-family:monospace'>TEXTURED PANEL</div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['background-image', 'no-repeat', 'center', 'cover']]]),
                        'hints' => json_encode(['url() points at the image file.', 'no-repeat stops the tiling.', 'cover scales to fill the box.']),
                    ],
                    [
                        'title' => 'Modern_Sizing',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Modern CSS computes sizes instead of fixing them. The calc() function mixes units in one expression, min() and max() pick bounds from candidates, and aspect-ratio holds a box proportion (16/9 for video frames) while object-fit decides how replaced content like images fills it.

                            Syntax: width: min(100%, 40rem); padding: calc(1rem + 2vw); aspect-ratio: 16 / 9; object-fit: cover;.

                            Example: a media card capped at readable width with fluid padding and a true 16:9 frame.

                            Your task: the card below uses fixed sizes. Convert the width to a min() bound, pad with calc, and lock the frame with aspect-ratio.
                            TEXT,
                        'broken_code' => ".card {\n  width: 800px;\n  padding: 16px;\n}\n.frame {\n  width: 800px;\n  height: 450px;\n}",
                        'solution_code' => ".card {\n  width: min(100%, 40rem);\n  padding: calc(1rem + 2vw);\n}\n.frame {\n  aspect-ratio: 16 / 9;\n  object-fit: cover;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a;font-family:monospace;color:#6fc49a'><div>min() card, calc padding, 16:9 frame</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['min(', 'calc(', 'aspect-ratio']]]),
                        'hints' => json_encode(['min() caps the width fluidly.', 'calc mixes units in one expression.', 'aspect-ratio holds box proportions.']),
                    ],
                ],
            ],
            [
                'title' => 'Layout Depth',
                'description' => 'Flexible rows and pinned layers: wrapping flex and fixed/sticky positioning.',
                'missions' => [
                    [
                        'title' => 'Flex_Wrap',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Real flex rows wrap and share space unevenly. The flex-wrap property lets items flow onto new lines instead of crushing, justify-content spreads them (space-between pushes first and last to the edges), and flex-grow lets one item absorb the leftover room.

                            Syntax: .row { display: flex; flex-wrap: wrap; justify-content: space-between; } .lead { flex-grow: 2; }.

                            Example: a toolbar whose lead item takes double space while the rest spread edge to edge across lines.

                            Your task: the row below neither wraps nor spreads. Add wrapping, edge-to-edge justification, and double growth on the lead item.
                            TEXT,
                        'broken_code' => ".row {\n  display: flex;\n  gap: 1rem;\n}\n.lead {\n  background: #1c1f26;\n}",
                        'solution_code' => ".row {\n  display: flex;\n  flex-wrap: wrap;\n  justify-content: space-between;\n  gap: 1rem;\n}\n.lead {\n  background: #1c1f26;\n  flex-grow: 2;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a;display:flex;gap:1rem;font-family:monospace;color:#6fc49a'><span>LEAD x2</span><span>ITEM</span><span>ITEM</span></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['flex-wrap', 'justify-content', 'flex-grow']]]),
                        'hints' => json_encode(['flex-wrap lets rows flow on.', 'space-between pushes edges outward.', 'flex-grow shares leftover room.']),
                    ],
                    [
                        'title' => 'Position_Fixed',
                        'difficulty' => 'HARD',
                        'points' => 110,
                        'description' => <<<'TEXT'
                            Some layers ignore scrolling. Fixed positioning pins to the viewport itself for persistent bars, sticky toggles between flowing and pinned at a threshold for section headers, and z-index stacks overlapping layers with higher values on top. Stacking only works on positioned elements, which is the detail everyone forgets.

                            Syntax: .bar { position: fixed; top: 0; z-index: 50; } .sub { position: sticky; top: 0; }.

                            Example: a top status bar pinned over content with sticky section headers sliding beneath it.

                            Your task: the bar below scrolls away and the headers never stick. Pin the bar fixed with stacking and make headers sticky.
                            TEXT,
                        'broken_code' => ".bar {\n  background: #171b21;\n  padding: 1rem;\n}\n.sub {\n  background: #1c1f26;\n  padding: 0.5rem;\n}",
                        'solution_code' => ".bar {\n  background: #171b21;\n  padding: 1rem;\n  position: fixed;\n  top: 0;\n  left: 0;\n  right: 0;\n  z-index: 50;\n}\n.sub {\n  background: #1c1f26;\n  padding: 0.5rem;\n  position: sticky;\n  top: 0;\n}",
                        'target_html' => "<div style='background:#171b21;padding:1rem;border:1px solid #6fc49a;font-family:monospace;color:#6fc49a'><div>pinned bar, sticky headers</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['fixed', 'sticky', 'z-index']]]),
                        'hints' => json_encode(['fixed pins to the viewport.', 'sticky flows until its threshold.', 'z-index stacks positioned layers.']),
                    ],
                ],
            ],
        ];
    }

    /** @return list<array{title: string, description: string, missions: list<array<string, mixed>>}> */
    private function jsFinishSections(): array
    {
        return [
            [
                'title' => 'Logic Precision',
                'description' => 'Exact comparisons and controlled iteration: strict equality and for-of loops.',
                'missions' => [
                    [
                        'title' => 'Strict_Logic',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Loose equality coerces types and lies: "7" == 7 passes while meaning different things. Strict equality checks value and type together, the ternary picks between two values in one expression, and typeof names any value's type for debugging. Together they remove the guesswork from conditions.

                            Syntax: n === 7 ? "LUCKY" : "ORDINARY" with typeof label confirming the string.

                            Example: tagging the number 7 as lucky only when it is truly numeric seven.

                            Your task: the check below uses loose equality and an if block. Rewrite it strict with a ternary and log the result type.
                            TEXT,
                        'broken_code' => "const input = \"7\";\nconst n = Number(input);\nlet label = \"?\";\nif (n == 7) {\n  label = \"LUCKY\";\n}\nconsole.log(label);",
                        'solution_code' => "const input = \"7\";\nconst n = Number(input);\nconst label = n === 7 ? \"LUCKY\" : \"ORDINARY\";\nconsole.log(typeof label + \":\" + label);",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem'>string:LUCKY</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['===', '?', 'typeof']]]),
                        'hints' => json_encode(['=== checks value and type together.', 'Ternary picks between two values inline.', 'typeof names the result type.']),
                    ],
                    [
                        'title' => 'For_Of_Control',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            The for-of loop walks values directly with no index bookkeeping. Paired with break to exit early and continue to skip one pass, it reads like the intent: scan the readings, skip negatives, stop past the cap. Reserve classic indexed for for the rare cases needing positions.

                            Syntax: for (const r of readings) { if (r < 0) continue; if (r > 25) break; console.log(r); }.

                            Example: printing 10 and 20 from the set while skipping -5 and stopping past 25.

                            Your task: the scanner below logs everything blindly. Rewrite it as a for-of that skips negatives and breaks past 25.
                            TEXT,
                        'broken_code' => "let readings = [10, -5, 20, -1, 30];\nfor (let i = 0; i < readings.length; i++) {\n  console.log(readings[i]);\n}",
                        'solution_code' => "let readings = [10, -5, 20, -1, 30];\nfor (const r of readings) {\n  if (r < 0) {\n    continue;\n  }\n  if (r > 25) {\n    break;\n  }\n  console.log(r);\n}",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem;line-height:1.8'>10<br>20</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => [' of ', 'continue', 'break']]]),
                        'hints' => json_encode(['for-of walks values, not indexes.', 'continue skips one pass.', 'break exits the whole loop.']),
                    ],
                ],
            ],
            [
                'title' => 'Objects in Depth',
                'description' => 'Reading objects as data: entries, values, and bracket access.',
                'missions' => [
                    [
                        'title' => 'Object_Entries',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Object.values pulls every value into an array, Object.entries pairs each key with its value for looping, and bracket notation reads properties whose names arrive as variables. These three turn static records into iterable data for reports and logs.

                            Syntax: Object.values(station) for values, for (const [k, v] of Object.entries(station)) for pairs, station[key] for dynamic reads.

                            Example: dumping a station record as key=value lines without naming each property.

                            Your task: the audit below hardcodes its output. Derive the values list and loop the entries instead.
                            TEXT,
                        'broken_code' => "let station = { name: \"Relay 7\", band: \"UHF\" };\nconsole.log(\"Relay 7, UHF\");",
                        'solution_code' => "let station = { name: \"Relay 7\", band: \"UHF\" };\nconsole.log(Object.values(station).join(\", \"));\nfor (const [k, v] of Object.entries(station)) {\n  console.log(k + \"=\" + v);\n}",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ OUTPUT:</div><div style='color:#d9a94f;margin-top:.5rem;line-height:1.8'>Relay 7, UHF<br>name=Relay 7<br>band=UHF</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['Object.values', 'Object.entries']]]),
                        'hints' => json_encode(['Object.values lists every value.', 'Object.entries pairs keys with values.', 'Loop the pairs with for-of.']),
                    ],
                ],
            ],
            [
                'title' => 'Events in Depth',
                'description' => 'Listening precisely: input events and the FormData API.',
                'missions' => [
                    [
                        'title' => 'Input_Events',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Beyond clicks, inputs fire their own events. The input event streams every keystroke for live feedback, change fires when a value commits on blur or selection, and keydown sees physical keys including Escape. Inside any handler, currentTarget is the element holding the listener, unlike target which may be a deeper child.

                            Syntax: input.addEventListener("input", function (e) { ... e.currentTarget.value ... }); plus a change listener beside it.

                            Example: echoing the callsign live while logging a saved line on commit.

                            Your task: the field below listens to nothing. Add input and change listeners that read through currentTarget.
                            TEXT,
                        'broken_code' => "/* Preview HTML:\n<label for=\"callsign\">Callsign</label>\n<input id=\"callsign\" name=\"callsign\">\n*/\n\nconst input = document.querySelector(\"#callsign\");\n// React to typing here",
                        'solution_code' => "const input = document.querySelector(\"#callsign\");\ninput.addEventListener(\"input\", function (e) {\n  console.log(e.currentTarget.value);\n});\ninput.addEventListener(\"change\", function () {\n  console.log(\"saved\");\n});",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ EVENTS:</div><div style='color:#d9a94f;margin-top:.5rem'>live echo, commit saved</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['"input"', '"change"', 'currentTarget']]]),
                        'hints' => json_encode(['input streams every keystroke.', 'change fires on commit.', 'currentTarget is the listening element.']),
                    ],
                    [
                        'title' => 'Form_Data',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            The FormData API reads a whole form at once by control name. Constructed with the form element, its get() method returns one named value, which beats querying every input individually in long forms. It pairs naturally with the guarded submit pattern: prevent the reload, read through FormData, then validate.

                            Syntax: const data = new FormData(form); data.get("callsign") returns that field.

                            Example: a signup submit reading the callsign through FormData after preventing reload.

                            Your task: the submit below queries nothing. Read the callsign through FormData and log it.
                            TEXT,
                        'broken_code' => "/* Preview HTML:\n<form id=\"signup\">\n<label for=\"callsign\">Callsign</label>\n<input id=\"callsign\" name=\"callsign\">\n<button type=\"submit\">Read callsign</button>\n</form>\n*/\n\nconst form = document.querySelector(\"#signup\");\nform.addEventListener(\"submit\", function (e) {\n  e.preventDefault();\n  console.log(\"?\");\n});",
                        'solution_code' => "const form = document.querySelector(\"#signup\");\nform.addEventListener(\"submit\", function (e) {\n  e.preventDefault();\n  const data = new FormData(form);\n  console.log(data.get(\"callsign\"));\n});",
                        'target_html' => "<div style='background:#171b21;padding:1.5rem;border:1px solid #6fc49a;font-family:monospace'><div style='color:#6fc49a'>▶ FORMDATA:</div><div style='color:#d9a94f;margin-top:.5rem'>callsign read by name</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['FormData', '.get(']]]),
                        'hints' => json_encode(['new FormData(form) wraps the whole form.', '.get("name") reads one field.', 'It pairs with preventDefault.']),
                    ],
                ],
            ],
        ];
    }
}
