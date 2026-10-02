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

                    $seededMission = Mission::query()->updateOrCreate(
                        ['course_id' => $course->id, 'order_num' => $missionOrder],
                        array_merge($mission, ['section_id' => $section->id]),
                    );

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
     * Formative checks for key sections. All optional: they verify
     * understanding without gating unrelated missions. Matched by course and
     * mission order, questions by order, options by order, so reruns update
     * instead of duplicating.
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

            $check = KnowledgeCheck::query()->updateOrCreate(
                ['mission_id' => $mission->id, 'order_num' => $checkData['order_num']],
                [
                    'title' => $checkData['title'],
                    'instructions' => $checkData['instructions'],
                    'is_required' => false,
                    'status' => KnowledgeCheck::STATUS_PUBLISHED,
                ],
            );

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
                            'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00;color:#33ff00;font-family:monospace;line-height:2'><div style='color:#ffb000'>✓ DOCTYPE declared</div><div>✓ html wraps everything</div><div>✓ head contains title</div><div>✓ body contains h1</div></div>",
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
                            'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00'><p style='color:green;font-family:monospace;margin:0'>SIGNAL ONLINE — color restored.</p></div>",
                            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'color'], ['type' => 'contains_any', 'values' => ['green', '#008000', '#0f0', 'rgb(0,128,0)', 'rgb(0, 128, 0)']]]),
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
                            'target_html' => "<div style='background:black;padding:1.5rem;border:1px solid #33ff00'><span style='color:#33ff00;font-family:monospace'>DISPLAY BLACKOUT RESOLVED</span></div>",
                            'validate_rule' => json_encode([['type' => 'contains', 'value' => 'background-color'], ['type' => 'contains_any', 'values' => ['black', '#000', 'rgb(0,0,0)', 'rgb(0, 0, 0)']]]),
                            'hints' => json_encode(['background-color sets the element background.', 'Syntax: background-color: value;', 'black or #000000 are both valid values.']),
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
                            'broken_code' => "h1 {\n  color: #33ff00;\n}",
                            'solution_code' => "h1 {\n  color: #33ff00;\n  font-size: 32px;\n}",
                            'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00'><h1 style='color:#33ff00;font-size:32px;margin:0;font-family:monospace'>BROADCAST HEADER</h1></div>",
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
                            'broken_code' => ".box {\n  border: 2px solid #33ff00;\n  background: #001100;\n}",
                            'solution_code' => ".box {\n  border: 2px solid #33ff00;\n  background: #001100;\n  padding: 20px;\n}",
                            'target_html' => "<div style='border:2px solid #33ff00;background:#001100;padding:20px'><span style='color:#33ff00;font-family:monospace'>CONTENT WITH PADDING</span></div>",
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
                            'broken_code' => ".panel {\n  background: #001a1a;\n  padding: 1rem;\n}",
                            'solution_code' => ".panel {\n  background: #001a1a;\n  padding: 1rem;\n  border: 2px solid cyan;\n}",
                            'target_html' => "<div style='background:#001a1a;padding:1rem;border:2px solid cyan'><span style='color:cyan;font-family:monospace'>PERIMETER SECURED</span></div>",
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
                            'broken_code' => ".nav {\n  background: #001100;\n  padding: 1rem;\n  gap: 1rem;\n}",
                            'solution_code' => ".nav {\n  background: #001100;\n  padding: 1rem;\n  gap: 1rem;\n  display: flex;\n  align-items: center;\n}",
                            'target_html' => "<div style='background:#001100;padding:1rem;display:flex;align-items:center;gap:1rem;border:1px solid #33ff00'><span style='color:#33ff00;font-family:monospace'>NAV</span><span style='color:#ffb000;font-family:monospace'>ITEM 1</span><span style='color:#ffb000;font-family:monospace'>ITEM 2</span><span style='color:#ffb000;font-family:monospace'>ITEM 3</span></div>",
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
                            'broken_code' => ".container {\n  position: relative;\n  background: #001100;\n  padding: 2rem;\n  border: 1px solid #33ff00;\n}\n.badge {\n  background: #33ff00;\n  color: #000;\n  padding: 0.3rem 0.6rem;\n  font-size: 0.75rem;\n}",
                            'solution_code' => ".container {\n  position: relative;\n  background: #001100;\n  padding: 2rem;\n  border: 1px solid #33ff00;\n}\n.badge {\n  background: #33ff00;\n  color: #000;\n  padding: 0.3rem 0.6rem;\n  font-size: 0.75rem;\n  position: absolute;\n  top: 0;\n  right: 0;\n}",
                            'target_html' => "<div style='position:relative;background:#001100;padding:2rem;border:1px solid #33ff00'><span style='background:#33ff00;color:#000;padding:0.3rem 0.6rem;font-size:0.75rem;position:absolute;top:0;right:0;font-family:monospace'>LIVE</span><span style='color:#33ff00;font-family:monospace'>Container content</span></div>",
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
                            'broken_code' => ".btn {\n  background: #1a8000;\n  color: #fff;\n  padding: 0.8rem 1.5rem;\n  border: none;\n  cursor: pointer;\n}\n.btn:hover {\n  background: #33ff00;\n  color: #000;\n}",
                            'solution_code' => ".btn {\n  background: #1a8000;\n  color: #fff;\n  padding: 0.8rem 1.5rem;\n  border: none;\n  cursor: pointer;\n  transition: background 0.3s ease;\n}",
                            'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00'><button style='background:#1a8000;color:#fff;padding:0.8rem 1.5rem;border:none;cursor:pointer;transition:background 0.3s ease;font-family:monospace' onmouseover=\"this.style.background='#33ff00';this.style.color='#000'\" onmouseout=\"this.style.background='#1a8000';this.style.color='#fff'\">HOVER ME</button></div>",
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
                            'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00;margin-bottom:.5rem'>✓ Grid: 2-col on desktop</div><div style='color:#ffb000'>✓ @media (max-width:600px) → display:block</div><div style='color:#33ff00;margin-top:.5rem;font-size:.85rem'>Responsive layout secured.</div></div>",
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
                            'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>ONLINE</div><div style='color:#33ff00;margin-top:1rem;font-size:.85rem'>✓ Variable declared and assigned.</div></div>",
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
                            'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>150</div><div style='color:#33ff00;margin-top:1rem;font-size:.85rem'>✓ Arithmetic core restored.</div></div>",
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
                            'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>ACCESS GRANTED</div><div style='color:#33ff00;margin-top:1rem;font-size:.85rem'>✓ Gate condition restored.</div></div>",
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
                            'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem;line-height:1.8'>1<br>2<br>3<br>4<br>5</div><div style='color:#33ff00;margin-top:1rem;font-size:.85rem'>✓ Loop transmitter restored.</div></div>",
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
                            'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>WELCOME, CHEN</div><div style='color:#33ff00;margin-top:1rem;font-size:.85rem'>✓ Greeting protocol online.</div></div>",
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
                            'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>3</div><div style='color:#33ff00;margin-top:1rem;font-size:.85rem'>✓ Registry loaded: 3 operators.</div></div>",
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
                            'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>CHEN</div><div style='color:#33ff00;margin-top:1rem;font-size:.85rem'>✓ Operator record restored.</div></div>",
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
                            'broken_code' => "// DOM node is unresponsive\n// Select and update the element below\n\n// <div id=\"status\">OFFLINE</div>",
                            'solution_code' => "document.getElementById(\"status\").textContent = \"SIGNAL ACTIVE\";\n\n// <div id=\"status\">OFFLINE</div>",
                            'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ DOM UPDATE:</div><div id='status' style='color:#ffb000;margin-top:.5rem;border:1px solid #ffb000;padding:.5rem'>SIGNAL ACTIVE</div><div style='color:#33ff00;margin-top:1rem;font-size:.85rem'>✓ DOM node updated successfully.</div></div>",
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
                            'broken_code' => "// Button is unresponsive — wire it up\nconst btn = document.getElementById(\"emergencyBtn\");\n\n// Add your event listener here",
                            'solution_code' => "const btn = document.getElementById(\"emergencyBtn\");\nbtn.addEventListener(\"click\", function() {\n  console.log(\"EMERGENCY PROTOCOL ACTIVATED\");\n});",
                            'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ EVENT WIRED:</div><div style='color:#ffb000;margin-top:.5rem'>btn.addEventListener(\"click\", handler)</div><div style='color:#33ff00;margin-top:1rem;font-size:.85rem'>✓ Emergency button is now responsive.</div></div>",
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

                                Your task: the uplink subroutine is missing. Define the async fetchSignal function that fetches, parses, and returns the signal data.
                                TEXT,
                            'broken_code' => "// Uplink subroutine missing\n// Define fetchSignal here\n\nfetchSignal().then(data => console.log(data));",
                            'solution_code' => "async function fetchSignal() {\n  const response = await fetch(\"/api/signal\");\n  const data = await response.json();\n  return data;\n}\n\nfetchSignal().then(data => console.log(data));",
                            'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ ASYNC UPLINK:</div><div style='color:#ffb000;margin-top:.5rem;line-height:1.8'>async function fetchSignal() {<br>&nbsp;&nbsp;const response = await fetch(\"/api/signal\");<br>&nbsp;&nbsp;const data = await response.json();<br>&nbsp;&nbsp;return data;<br>}</div><div style='color:#33ff00;margin-top:1rem;font-size:.85rem'>✓ Fetch transmission protocol restored.</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00;color:#33ff00;font-family:monospace'><div>✓ charset declared</div><div>✓ viewport configured</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00'><div style='border:1px dashed #559260;padding:.5rem'><span style='color:red;font-family:monospace'>Panel title (red)</span></div><p style='color:#91a198;font-family:monospace;margin:.5rem 0 0'>Plain paragraph (untouched)</p></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00'><span style='color:red;font-family:monospace'>EMERGENCY (red wins)</span></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00;display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;font-family:monospace'><span style='color:#33ff00'>CELL 1</span><span style='color:#33ff00'>CELL 2</span><span style='color:#33ff00'>CELL 3</span></div>",
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
                        'broken_code' => ".card {\n  width: 300px;\n  padding: 20px;\n  background: #001100;\n}",
                        'solution_code' => ".card {\n  width: 300px;\n  padding: 20px;\n  background: #001100;\n  box-sizing: border-box;\n  margin: 0 auto;\n}",
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00'><div style='width:300px;max-width:100%;padding:20px;background:#0a140d;box-sizing:border-box;margin:0 auto;color:#33ff00;font-family:monospace'>CENTERED CARD</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['box-sizing', 'border-box']]]),
                        'hints' => json_encode(['box-sizing: border-box keeps width final.', 'margin: 0 auto centers a fixed-width block.', 'Keep the existing declarations.']),
                    ],
                    [
                        'title' => 'Rounded_Shadow',
                        'difficulty' => 'EASY',
                        'points' => 60,
                        'description' => <<<'TEXT'
                            Two properties lift flat boxes off the page. The border-radius rounds corners (50% turns a square into a circle), and box-shadow paints a soft offset copy behind the element using horizontal offset, vertical offset, blur, and color.

                            Syntax: .badge { border-radius: 8px; box-shadow: 0 0 12px #33ff00; }.

                            Example: a status badge with rounded corners and a green glow reads as lit rather than printed.

                            Your task: the badge below is a sharp flat square. Round its corners and give it a green glow shadow.
                            TEXT,
                        'broken_code' => ".badge {\n  background: #33ff00;\n  color: #000;\n  padding: 0.4rem 0.8rem;\n}",
                        'solution_code' => ".badge {\n  background: #33ff00;\n  color: #000;\n  padding: 0.4rem 0.8rem;\n  border-radius: 8px;\n  box-shadow: 0 0 12px #33ff00;\n}",
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00'><span style='background:#33ff00;color:#000;padding:0.4rem 0.8rem;border-radius:8px;box-shadow:0 0 12px #33ff00;font-family:monospace'>ONLINE</span></div>",
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
                        'broken_code' => ".lamp {\n  background: #33ff00;\n  width: 2rem;\n  height: 2rem;\n}",
                        'solution_code' => "@keyframes pulse {\n  from { opacity: 1; }\n  to { opacity: 0.3; }\n}\n.lamp {\n  background: #33ff00;\n  width: 2rem;\n  height: 2rem;\n  animation: pulse 2s infinite;\n}",
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00'><div style='background:#33ff00;width:2rem;height:2rem;animation:pulse 2s infinite'></div><div style='color:#33ff00;font-family:monospace;margin-top:.75rem;font-size:.85rem'>Pulsing lamp</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['@keyframes', 'pulse', 'animation']]]),
                        'hints' => json_encode(['@keyframes names the motion timeline.', 'from/to define the start and end states.', 'animation: name duration repeat attaches it.']),
                    ],
                    [
                        'title' => 'Theme_Tokens',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Custom properties store reusable values under a -- name. Declared once on :root so every rule inherits them, they are read back with var(), which also takes a fallback used when the token is missing. One edit to the token repaints every consumer, which beats hunting hex codes file-wide.

                            Syntax: :root { --signal: #33ff00; } then .lamp { color: var(--signal, green); }.

                            Example: signal lamps across the page all track one token, with green as the safety fallback.

                            Your task: the lamp below hardcodes its color. Move the value into a --signal token on :root and consume it with a fallback.
                            TEXT,
                        'broken_code' => ".lamp {\n  color: #33ff00;\n}",
                        'solution_code' => ":root {\n  --signal: #33ff00;\n}\n.lamp {\n  color: var(--signal, green);\n}",
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00'><span style='color:#33ff00;font-family:monospace'>TOKEN LAMP</span></div>",
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
                        'broken_code' => ".copy {\n  color: #e1ece4;\n}",
                        'solution_code' => ".copy {\n  color: #e1ece4;\n  font-family: monospace, Consolas, monospace;\n  font-weight: 400;\n  line-height: 1.8;\n}",
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00'><p style='color:#e1ece4;font-family:monospace;line-height:1.8;margin:0'>Readable copy block.</p></div>",
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
                        'broken_code' => ".kicker {\n  color: #559260;\n  font-size: 0.7rem;\n}",
                        'solution_code' => ".kicker {\n  color: #559260;\n  font-size: 0.7rem;\n  text-align: center;\n  text-transform: uppercase;\n  letter-spacing: 0.12em;\n}",
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00'><p style='color:#559260;font-size:0.7rem;text-align:center;text-transform:uppercase;letter-spacing:0.12em;font-family:monospace;margin:0'>SYSTEM KICKER</p></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00'><p style='color:#e1ece4;font-size:clamp(1.5rem,4vw,3rem);font-family:monospace;margin:0'>Fluid hero</p></div>",
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
                        'broken_code' => "li {\n  color: #33ff00;\n}\np {\n  margin-top: 0;\n}\ninput {\n  border: 1px solid #33ff00;\n}",
                        'solution_code' => ".menu > li {\n  color: #33ff00;\n}\nh2 + p {\n  margin-top: 0;\n}\ninput[type=\"text\"] {\n  border: 1px solid #33ff00;\n}",
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00;font-family:monospace;color:#33ff00'><div>.menu &gt; li, h2 + p, input[type]</div></div>",
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
                        'broken_code' => "a {\n  color: #49d8e8;\n}\n.tag {\n  color: #91a198;\n}",
                        'solution_code' => "a {\n  color: #49d8e8;\n}\na:hover {\n  color: #33ff00;\n}\n:focus-visible {\n  outline: 3px solid #49d8e8;\n}\ninput:checked + label {\n  color: #33ff00;\n}\n.tag::before {\n  content: \"> \";\n}",
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00;font-family:monospace;color:#49d8e8'><div>&gt; tagged link (hover me)</div></div>",
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
                        'broken_code' => ".card {\n  background: #0b1610;\n  padding: 1rem;\n}\n.card:hover {\n  background: #0d1b12;\n}",
                        'solution_code' => ".card {\n  background: #0b1610;\n  padding: 1rem;\n  transition: transform 0.2s ease;\n}\n.card:hover {\n  background: #0d1b12;\n  transform: translateY(-4px) scale(1.02);\n}",
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00'><div style='background:#0b1610;padding:1rem;color:#33ff00;font-family:monospace;transition:transform 0.2s ease'>Hover card (lifts)</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['transform', 'translateY', 'scale']]]),
                        'hints' => json_encode(['transform moves without reflowing layout.', 'translateY lifts, scale grows.', 'Transition the transform property.']),
                    ],
                    [
                        'title' => 'Gradient_Background',
                        'difficulty' => 'MEDIUM',
                        'points' => 80,
                        'description' => <<<'TEXT'
                            Gradients blend colors across a surface without image files. The linear-gradient function takes a direction and color stops, while background-size and background-position control how any background image fills its box. One declaration can carry atmosphere an entire image used to provide.

                            Syntax: .banner { background: linear-gradient(180deg, #001100, #003300); }.

                            Example: a banner fading from near-black to deep green behind its headline.

                            Your task: the flat banner below needs depth. Give it a top-to-bottom gradient across two greens.
                            TEXT,
                        'broken_code' => ".banner {\n  background: #001100;\n  padding: 2rem;\n}",
                        'solution_code' => ".banner {\n  background: linear-gradient(180deg, #001100, #003300);\n  padding: 2rem;\n}",
                        'target_html' => "<div style='background:linear-gradient(180deg,#001100,#003300);padding:2rem;border:1px solid #33ff00;color:#33ff00;font-family:monospace'>GRADIENT BANNER</div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['linear-gradient', '#001100', '#003300']]]),
                        'hints' => json_encode(['linear-gradient blends across a direction.', '180deg runs top to bottom.', 'List two or more color stops.']),
                    ],
                    [
                        'title' => 'Form_Focus',
                        'difficulty' => 'MEDIUM',
                        'points' => 90,
                        'description' => <<<'TEXT'
                            Form controls must stay visibly focused. Styling inputs and buttons is welcome, but removing the native outline without a replacement strands keyboard users. The :focus-visible selector shows a strong ring for keyboard focus only, and :disabled styles the unavailable state so it reads as inactive rather than broken.

                            Syntax: input { border: ...; } input:focus-visible { outline: 3px solid #49d8e8; } button:disabled { opacity: 0.5; }.

                            Example: dark inputs with a cyan keyboard ring and dimmed disabled buttons.

                            Your task: the controls below have no focus or disabled styling. Add an accessible focus-visible ring and a dimmed disabled state.
                            TEXT,
                        'broken_code' => "input {\n  background: #08110b;\n  color: #e1ece4;\n  padding: 0.6rem;\n}\nbutton {\n  background: #33ff00;\n  color: #000;\n  padding: 0.6rem 1rem;\n}",
                        'solution_code' => "input {\n  background: #08110b;\n  color: #e1ece4;\n  padding: 0.6rem;\n  border: 1px solid #33ff00;\n}\ninput:focus-visible {\n  outline: 3px solid #49d8e8;\n}\nbutton:disabled {\n  opacity: 0.5;\n}",
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00;font-family:monospace'><div style='border:1px solid #33ff00;padding:.6rem;color:#e1ece4'>Tab to me (cyan ring)</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>STATUS: ONLINE - SECTOR 7</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>VHF LINK</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem;line-height:1.8'>3<br>2<br>1</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>[ 20, 40 ]</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>3</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>BAD PAYLOAD</div></div>",
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
                        'broken_code' => "// Three .lamp elements stay dark\n// Light them all up here",
                        'solution_code' => 'document.querySelectorAll(".lamp").forEach(el => el.classList.add("on"));',
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ LAMPS:</div><div style='color:#ffb000;margin-top:.5rem'>3 lamps ON</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ BEACON:</div><div style='color:#ffb000;margin-top:.5rem;line-height:1.8'>ping 1<br>ping 2<br>ping 3<br>(silent)</div></div>",
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

                            Your task: the callsign below never persists. Store the record object and read its name back from storage.
                            TEXT,
                        'broken_code' => "let record = { name: \"CHEN\" };\n// Persist and reload here\nconsole.log(record.name);",
                        'solution_code' => "let record = { name: \"CHEN\" };\nlocalStorage.setItem(\"callsign\", JSON.stringify(record));\nconst saved = JSON.parse(localStorage.getItem(\"callsign\"));\nconsole.log(saved.name);",
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ STORAGE:</div><div style='color:#ffb000;margin-top:.5rem'>CHEN (persisted)</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>10</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>1</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem;line-height:1.8'>10<br>20<br>30</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>20 60</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem;line-height:1.8'>[ 'name', 'radio' ]<br>UHF</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>CHEN READY</div></div>",
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
                        'broken_code' => "// <ul id=\"bands\"></ul> present on the page\n// Add a UHF entry here",
                        'solution_code' => "const li = document.createElement(\"li\");\nli.textContent = \"UHF\";\ndocument.querySelector(\"#bands\").appendChild(li);",
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ LIST:</div><div style='color:#ffb000;margin-top:.5rem'>• UHF (appended)</div></div>",
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
                        'broken_code' => "const form = document.querySelector(\"#signup\");\nconst input = document.querySelector(\"#callsign\");\n// Validate on submit here",
                        'solution_code' => "const form = document.querySelector(\"#signup\");\nconst input = document.querySelector(\"#callsign\");\nform.addEventListener(\"submit\", function (e) {\n  e.preventDefault();\n  if (input.value.length < 3) {\n    console.log(\"TOO SHORT\");\n  } else {\n    console.log(\"ACCEPTED\");\n  }\n});",
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ FORM:</div><div style='color:#ffb000;margin-top:.5rem'>submit guarded, short rejected</div></div>",
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
                        'broken_code' => "const list = document.querySelector(\"#bands\");\n// Report clicks on any item here",
                        'solution_code' => "const list = document.querySelector(\"#bands\");\nlist.addEventListener(\"click\", function (e) {\n  console.log(e.target.textContent);\n});",
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ DELEGATION:</div><div style='color:#ffb000;margin-top:.5rem'>one listener, every item covered</div></div>",
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

                            Your task: the chain below parses blindly. Add the ok check that throws on HTTP failure and the catch that reports it.
                            TEXT,
                        'broken_code' => "fetch(\"/api/signal\")\n  .then(r => r.json())\n  .then(d => console.log(d));",
                        'solution_code' => "fetch(\"/api/signal\")\n  .then(r => {\n    if (!r.ok) {\n      throw new Error(\"down\");\n    }\n    return r.json();\n  })\n  .then(d => console.log(d))\n  .catch(e => console.log(\"FAILED\"));",
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ CHAIN:</div><div style='color:#ffb000;margin-top:.5rem'>ok checked, failures caught</div></div>",
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
                        'broken_code' => "// radio.js\nconst band = \"UHF\";\n\n// dashboard.js\nconsole.log(\"band?\");",
                        'solution_code' => "// radio.js\nexport const band = \"UHF\";\n\n// dashboard.js\nimport { band } from \"./radio.js\";\nconsole.log(band);",
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ MODULES:</div><div style='color:#ffb000;margin-top:.5rem'>band shared across files</div></div>",
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

                            Syntax: <iframe src="/map" title="Sector map" sandbox></iframe>. Never grant allow-scripts and allow-same-origin together to untrusted content.

                            Example: an internal sector map embedded with an empty sandbox and a descriptive title.

                            Your task: the bare embed below runs unrestricted. Add an empty sandbox and a title so it is locked down and announced.
                            TEXT,
                        'broken_code' => '<iframe src="/map"></iframe>',
                        'solution_code' => '<iframe src="/map" title="Sector map" sandbox></iframe>',
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
     * @return list<array{course_order: int, mission_order: int, order_num: int, title: string, instructions: string, questions: list<array{prompt: string, explanation: string, options: list<string>, correct: int}>}>
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
        ];
    }

    /** @return list<array{title: string, description: string, missions: list<array<string, mixed>>}> */
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
                        'broken_code' => "h1 {\n  color: #33ff00;\n}\ntr {\n  background: #001100;\n}",
                        'solution_code' => "* {\n  margin: 0;\n}\nh1, h2 {\n  color: #33ff00;\n}\nh2 ~ p {\n  color: #91a198;\n}\ntr:nth-child(even) {\n  background: #0a140d;\n}\n.tag::after {\n  content: \"!\";\n}",
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00;font-family:monospace;color:#33ff00'><div>Headlines, zebra rows, flagged tags</div></div>",
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

                            Syntax: .panel { background-color: #001100; background-image: url("grid.png"); background-repeat: no-repeat; background-position: center; background-size: cover; }.

                            Example: a panel with a centered, covering grid texture over its dark base.

                            Your task: the panel below has color but no texture. Layer the grid image centered and covering without tiling.
                            TEXT,
                        'broken_code' => ".panel {\n  background-color: #001100;\n  padding: 2rem;\n}",
                        'solution_code' => ".panel {\n  background-color: #001100;\n  background-image: url(\"grid.png\");\n  background-repeat: no-repeat;\n  background-position: center;\n  background-size: cover;\n  padding: 2rem;\n}",
                        'target_html' => "<div style='background-color:#001100;background-image:url(grid.png);background-repeat:no-repeat;background-position:center;background-size:cover;padding:2rem;border:1px solid #33ff00;color:#33ff00;font-family:monospace'>TEXTURED PANEL</div>",
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
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00;font-family:monospace;color:#33ff00'><div>min() card, calc padding, 16:9 frame</div></div>",
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
                        'broken_code' => ".row {\n  display: flex;\n  gap: 1rem;\n}\n.lead {\n  background: #0a140d;\n}",
                        'solution_code' => ".row {\n  display: flex;\n  flex-wrap: wrap;\n  justify-content: space-between;\n  gap: 1rem;\n}\n.lead {\n  background: #0a140d;\n  flex-grow: 2;\n}",
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00;display:flex;gap:1rem;font-family:monospace;color:#33ff00'><span>LEAD x2</span><span>ITEM</span><span>ITEM</span></div>",
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
                        'broken_code' => ".bar {\n  background: #040806;\n  padding: 1rem;\n}\n.sub {\n  background: #0a140d;\n  padding: 0.5rem;\n}",
                        'solution_code' => ".bar {\n  background: #040806;\n  padding: 1rem;\n  position: fixed;\n  top: 0;\n  left: 0;\n  right: 0;\n  z-index: 50;\n}\n.sub {\n  background: #0a140d;\n  padding: 0.5rem;\n  position: sticky;\n  top: 0;\n}",
                        'target_html' => "<div style='background:#001100;padding:1rem;border:1px solid #33ff00;font-family:monospace;color:#33ff00'><div>pinned bar, sticky headers</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem'>string:LUCKY</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem;line-height:1.8'>10<br>20</div></div>",
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
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ OUTPUT:</div><div style='color:#ffb000;margin-top:.5rem;line-height:1.8'>Relay 7, UHF<br>name=Relay 7<br>band=UHF</div></div>",
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
                        'broken_code' => "const input = document.querySelector(\"#callsign\");\n// React to typing here",
                        'solution_code' => "const input = document.querySelector(\"#callsign\");\ninput.addEventListener(\"input\", function (e) {\n  console.log(e.currentTarget.value);\n});\ninput.addEventListener(\"change\", function () {\n  console.log(\"saved\");\n});",
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ EVENTS:</div><div style='color:#ffb000;margin-top:.5rem'>live echo, commit saved</div></div>",
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
                        'broken_code' => "const form = document.querySelector(\"#signup\");\nform.addEventListener(\"submit\", function (e) {\n  e.preventDefault();\n  console.log(\"?\");\n});",
                        'solution_code' => "const form = document.querySelector(\"#signup\");\nform.addEventListener(\"submit\", function (e) {\n  e.preventDefault();\n  const data = new FormData(form);\n  console.log(data.get(\"callsign\"));\n});",
                        'target_html' => "<div style='background:#001100;padding:1.5rem;border:1px solid #33ff00;font-family:monospace'><div style='color:#33ff00'>▶ FORMDATA:</div><div style='color:#ffb000;margin-top:.5rem'>callsign read by name</div></div>",
                        'validate_rule' => json_encode([['type' => 'contains_all', 'values' => ['FormData', '.get(']]]),
                        'hints' => json_encode(['new FormData(form) wraps the whole form.', '.get("name") reads one field.', 'It pairs with preventDefault.']),
                    ],
                ],
            ],
        ];
    }
}
