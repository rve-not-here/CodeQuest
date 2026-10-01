# Curriculum coverage

Source of truth for what CodeQuest teaches. Generated from
`database/seeders/CurriculumContentSeeder.php`, which owns all lesson
content. A topic counts as covered only when a real mission teaches it
with starter code, a solution, and a passing validation rule.

Counts queried from disposable SQLite: 3 courses, 36 sections, 95 missions,
12 Knowledge Checks, 36 questions, and 108 options.

## HTML Fundamentals, 30 missions in 11 sections

| Topic | Section | Mission | Coverage |
|---|---|---|---|
| h1 headings | Text and Structure | Header_Reconstruction | Full |
| Links, href | Text and Structure | Hyperlink_Restoration | Full |
| img, src, alt | Text and Structure | Image_Feed_Restoration | Full |
| Paragraphs | Text and Structure | Paragraph_Broadcast | Full |
| ul, li | Lists and Input | List_Structure_Repair | Full |
| text input, placeholder | Lists and Input | Input_Field_Calibration | Full |
| button element | Lists and Input | Control_Button_Repair | Full |
| table, tr, td | Layout and Pages | Table_Grid_Reconstruction | Full |
| div, class | Layout and Pages | Container_Structure | Full |
| doctype, html, head, body | Layout and Pages | Full_Page_Structure | Full |
| strong, em vs b, i | Text Semantics | Emphasis_Markers | Full |
| pre, code | Text Semantics | Code_Sample_Block | Full |
| header, nav, main, footer | Page Semantics | Semantic_Layout | Full |
| meta charset, viewport | Page Semantics | Head_Metadata | Full |
| form, label, required | Forms and Media | Signal_Form | Full |
| radio groups | Forms and Media | Choice_Controls | Full |
| figure, figcaption | Forms and Media | Media_Feed | Full |

Partial: h2-h6 mentioned, not drilled. The HTML Boss Challenge covers h1, nav links, and a registration form.

| Topic | Section | Mission | Coverage |
|---|---|---|---|
| textarea, select, option | Forms in Depth | Textarea_Select | Full |
| email, number, date inputs | Forms in Depth | Input_Types | Full |
| fieldset, legend, datalist, submit | Forms in Depth | Form_Structure | Full |
| audio, video, track, fallback | Media and Interactivity | Audio_Video | Full |
| details, summary | Media and Interactivity | Details_Dialog | Full |
| iframe, sandbox, title | Media and Interactivity | Safe_Embed | Full |
| caption, thead, tbody, th, scope, colspan | Precise Markup | Table_Semantics | Full |
| dl, dt, dd | Precise Markup | Description_Lists | Full |
| id, class, title, hidden, data-*, tabindex | Precise Markup | Element_Attributes | Full |

Excluded as obsolete: font, center, marquee, frameset, big, tt, acronym. None appear in lessons. Dialog scripting is excluded: the element is not drilled because opening it needs JavaScript taught later.

| Topic | Section | Mission | Coverage |
|---|---|---|---|
| checkbox, file, range inputs | Input Variety | Toggle_Upload_Inputs | Full |
| tel, url, search, password, hidden, submit | Input Variety | Text_Input_Depth | Full |
| picture, source, srcset | Media and Tables Finish | Media_Picture | Full |
| tfoot, rowspan | Media and Tables Finish | Table_Foot_Rowspan | Full |

## CSS Styling, 30 missions in 12 sections

| Topic | Section | Mission | Coverage |
|---|---|---|---|
| color, named and hex | Text and Color | Color_Signal_Restore | Full |
| background-color | Text and Color | Background_Blackout_Fix | Full |
| font-size, px | Text and Color | Font_Frequency_Calibration | Full |
| padding | Box Model and Layout | Spacing_Shield_Repair | Full |
| border shorthand | Box Model and Layout | Border_Perimeter_Restore | Full |
| display flex, align-items | Box Model and Layout | Flexbox_Grid_Alignment | Full |
| opacity | Effects and Responsive | Opacity_Cloak_Removal | Full |
| absolute positioning | Effects and Responsive | Position_Anchor_Lock | Full |
| transition | Effects and Responsive | Transition_Signal_Smooth | Full |
| media queries | Effects and Responsive | Responsive_Frequency_Lock | Full |
| class, descendant selectors | Selectors and Cascade | Selector_Targeting | Full |
| specificity, source order | Selectors and Cascade | Cascade_Order | Full |
| grid, repeat, fr | Modern Layout | Grid_Tracks | Full |
| box-sizing, auto margins | Modern Layout | Box_Sizing | Full |
| border-radius, box-shadow | Modern Layout | Rounded_Shadow | Full |
| keyframes, animation | Motion and Variables | Keyframe_Pulse | Full |
| custom properties, var | Motion and Variables | Theme_Tokens | Full |

Partial: positioning depth beyond fixed, sticky, and z-index. The CSS Boss Challenge covers background, color, and flex menu layout.

| Topic | Section | Mission | Coverage |
|---|---|---|---|
| universal, grouping, sibling, nth-child, after | Selector Depth | Selector_Depth | Full |
| background images, repeat, position, size | Backgrounds and Sizing | Background_Layers | Full |
| calc, min/max, aspect-ratio, object-fit | Backgrounds and Sizing | Modern_Sizing | Full |
| flex-wrap, justify-content, flex-grow | Layout Depth | Flex_Wrap | Full |
| fixed, sticky, z-index | Layout Depth | Position_Fixed | Full |

| Topic | Section | Mission | Coverage |
|---|---|---|---|
| font stacks, weight, line-height | Typography | Type_Setting | Full |
| text-align, decoration, transform, spacing | Typography | Text_Style | Full |
| rem, vw, clamp | Typography | Fluid_Sizing | Full |
| child, sibling, attribute selectors | Selectors in Depth | Combinators | Full |
| hover, focus-visible, checked, before | Selectors in Depth | Pseudo_States | Full |
| translate, scale, rotate | Polish and Access | Transform_Move | Full |
| linear-gradient | Polish and Access | Gradient_Background | Full |
| focus rings, disabled states | Polish and Access | Form_Focus | Full |

## JavaScript Scripting, 35 missions in 13 sections

| Topic | Section | Mission | Coverage |
|---|---|---|---|
| let, strings | Variables and Logic | Variable_Signal_Declare | Full |
| arithmetic operators | Variables and Logic | Arithmetic_Core_Repair | Full |
| if, else, comparisons | Variables and Logic | Conditional_Gate_Restore | Full |
| for loops | Loops and Functions | Loop_Transmitter_Repair | Full |
| declarations, return | Loops and Functions | Function_Protocol_Rebuild | Full |
| arrays, length | Loops and Functions | Array_Database_Reconstruct | Full |
| object literals | Loops and Functions | Object_Record_Restoration | Full |
| getElementById, textContent | Browser and Network | DOM_Node_Activation | Full |
| addEventListener, click | Browser and Network | Event_Listener_Rewire | Full |
| async, await, fetch, json | Browser and Network | Fetch_Transmission_Protocol | Full |
| template literals | Strings and Control | Template_Lines | Full |
| switch, break, default | Strings and Control | Switch_Board | Full |
| while loops | Strings and Control | While_Watch | Full |
| filter, map | Data Tools | Map_Filter | Full |
| destructuring, spread | Data Tools | Destructure_Spread | Full |
| try, catch, JSON.parse | Data Tools | Try_Catch_JSON | Full |
| querySelectorAll, classList | Browser Depth | Query_All | Full |
| setInterval, clearInterval | Browser Depth | Timer_Beacon | Full |
| localStorage, serialization | Browser Depth | Store_Local | Full |

Partial: ternary depth, closures beyond the counter pattern, classes beyond one blueprint. The JS Boss Challenge covers functions, event wiring, and script structure.

| Topic | Section | Mission | Coverage |
|---|---|---|---|
| strict equality, ternary, typeof | Logic Precision | Strict_Logic | Full |
| for-of, break, continue | Logic Precision | For_Of_Control | Full |
| Object.values, Object.entries | Objects in Depth | Object_Entries | Full |
| input/change events, currentTarget | Events in Depth | Input_Events | Full |
| FormData, named reads | Events in Depth | Form_Data | Full |

| Topic | Section | Mission | Coverage |
|---|---|---|---|
| block scope, let, const, var legacy | Scope and Functions | Block_Scope | Full |
| closures | Scope and Functions | Closure_Counter | Full |
| arrow functions, callbacks | Scope and Functions | Arrow_Callbacks | Full |
| find, reduce | Data Mastery | Find_Reduce | Full |
| Object.keys, nested access | Data Mastery | Object_Tools | Full |
| class, constructor, methods | Data Mastery | Class_Blueprint | Full |
| createElement, append, querySelector | Browser Fluency | Build_Nodes | Full |
| submit events, preventDefault | Browser Fluency | Form_Guard | Full |
| bubbling, delegation, target | Browser Fluency | Event_Bubble | Full |
| promise chains, ok checks, catch | Async and Modules | Promise_Chain | Full |
| export, import syntax | Async and Modules | Module_Syntax | Full |

Module note: the challenge preview shows module source as text rather than executing it, since the behavioral runner and preview frame have no module loader. The mission teaches the syntax honestly and says so in the lesson.

## Knowledge Checks, 12 checks, 36 questions

| Check | Mission | Focus |
|---|---|---|
| Semantic HTML choices | Semantic_Layout | strong vs b, nav landmark, heading hierarchy |
| Form labeling basics | Signal_Form | label pairing, required, radio groups |
| Cascade reasoning | Cascade_Order | specificity, source order, descendant scope |
| Layout choices | Grid_Tracks | grid vs flex, fr units, centering |
| Array method behavior | Map_Filter | filter and map returns, chaining order |
| Async ordering | Fetch_Transmission_Protocol | async scope, promise shape, try/catch |

All checks are optional and formative. No required gates were added.

| Check | Mission | Focus |
|---|---|---|
| Form reasoning | Form_Structure | control choice, number bounds, grouping |
| Selector matching | Pseudo_States | child scope, checked labels, focus-visible |
| Scope reasoning | Closure_Counter | block scope, closures, var legacy |
| Event reasoning | Event_Bubble | preventDefault, bubbling, target |
| Media reasoning | Media_Picture | picture fallback, captions, sandbox |
| Motion access | Keyframe_Pulse | reduced motion, smooth change, non-color cues |

## Remaining gaps, biggest first

- HTML dialog scripting: the element is excluded from drills because opening it needs JavaScript taught later.
- CSS shrink and advanced grid placement (spanning, auto-fit): mentioned nowhere as missions.
- JS some/every array methods: filter, map, find, and reduce are drilled; these two are not.
- JS classes beyond one blueprint and modules beyond syntax: intentionally small.
- JS keyboard events beyond change/input: keydown appears only in hints and explanations.

## Intentional exclusions

- Deprecated HTML (font, center, marquee, frameset, big, tt, acronym): obsolete, never taught as normal.
- Experimental and vendor-specific CSS and APIs: unstable, excluded.
- Node.js and server-side JavaScript: outside this browser course.
- Framework concepts: no framework taught here.
- document.write and eval: unsafe, never taught.
- Inline event handlers as preferred practice: taught as legacy only by omission; missions use addEventListener.
- Positive tabindex values: explicitly discouraged in the attributes lesson.

## JavaScript behavioral grading

Part 2 audits all 35 JavaScript missions in the existing 13 sections.
CurriculumContentSeeder owns the hidden definitions, matching them by mission
and test order on reruns. Points, difficulty, starter code, solutions,
ordering, course gates, and Boss Challenge grading are unchanged.

| Seeded database measure | Count |
|---|---:|
| Courses | 3 |
| Sections | 36 |
| Missions | 95 |
| JavaScript missions | 35 |
| Missions with active behavior tests | 21 |
| Behavior-test rows | 28 |
| Function rows | 10 |
| Console rows | 18 |
| Structural-only JavaScript missions | 4 |
| Future-DOM/browser JavaScript missions | 10 |
| Knowledge Checks | 12 |
| Questions | 36 |
| Options | 108 |

Counts come from database queries in JavaScriptCurriculumBehaviorTest using
RefreshDatabase and PHPUnit's disposable in-memory SQLite connection after
seeding CurriculumContentSeeder. They describe the test curriculum, not
production or the developer's normal database. Reseeding preserves IDs,
definitions and student history without adding duplicates.

Classifications are exclusive: 2 behavioral-function, 19 hybrid
structural+behavioral, 4 structural-only, and 10 future-DOM. Console rows
belong to 18 hybrids. The class hybrid uses function cases. Seven hybrids
also inspect required data through function probes.

| JS order | Mission | Strategy | Objective and reason |
|---|---|---|---|
| 1 | Variable_Signal_Declare | hybrid structural+behavioral | let declaration, stored ONLINE value, actual output |
| 2 | Arithmetic_Core_Repair | hybrid structural+behavioral | Addition, original operands and computed total, output 150 |
| 3 | Conditional_Gate_Restore | hybrid structural+behavioral | if and >= threshold, granted output for the given clearance |
| 4 | Loop_Transmitter_Repair | hybrid structural+behavioral | for syntax and ordered numeric output 1 through 5 |
| 5 | Function_Protocol_Rebuild | behavioral-function | Reusable uppercase greeting independent of declaration style |
| 6 | Array_Database_Reconstruct | hybrid structural+behavioral | Array literal, all three callsigns in order, count output |
| 7 | Object_Record_Restoration | hybrid structural+behavioral | Object literal, typed name/clearance/active properties, name output |
| 8 | DOM_Node_Activation | future-DOM | Live element selection and text update need browser execution |
| 9 | Event_Listener_Rewire | future-DOM | Click dispatch and listener response need browser execution |
| 10 | Fetch_Transmission_Protocol | structural-only | Network fixtures are absent; retain async/fetch validator |
| 11 | Template_Lines | hybrid structural+behavioral | Template placeholders and actual formatted line |
| 12 | Switch_Board | hybrid structural+behavioral | switch/case/break/default syntax and VHF output for the given band |
| 13 | While_Watch | hybrid structural+behavioral | while syntax and countdown entries 3, 2, 1 |
| 14 | Map_Filter | hybrid structural+behavioral | filter/map, doubled non-negative results, unchanged source readings |
| 15 | Destructure_Spread | hybrid structural+behavioral | Destructuring/spread, unpacked values and assembled crew |
| 16 | Try_Catch_JSON | hybrid structural+behavioral | try/catch and JSON.parse, actual bad-payload fallback |
| 17 | Query_All | future-DOM | Node collections and classList need browser execution |
| 18 | Timer_Beacon | future-DOM | Scheduling/cancellation are absent from the runner environment |
| 19 | Store_Local | future-DOM | Persistent browser storage is absent from the runner environment |
| 20 | Block_Scope | structural-only | Explicit const/let objective; numeric output cannot prove block scope |
| 21 | Closure_Counter | behavioral-function | Repeated calls increment private state; independent factories start fresh |
| 22 | Arrow_Callbacks | hybrid structural+behavioral | Explicit arrow callback objective and ordered reading output |
| 23 | Find_Reduce | hybrid structural+behavioral | find/reduce methods, actual first matching value and sum |
| 24 | Object_Tools | hybrid structural+behavioral | Object.keys objective and actual key list/nested band output |
| 25 | Class_Blueprint | hybrid structural+behavioral | Class/constructor objective and reports from different instances |
| 26 | Build_Nodes | future-DOM | Node construction and attachment need browser execution |
| 27 | Form_Guard | future-DOM | Submit cancellation and input validation need browser execution |
| 28 | Event_Bubble | future-DOM | Event propagation and delegation need browser execution |
| 29 | Promise_Chain | structural-only | Network response/error fixtures are absent; retain promise validator |
| 30 | Module_Syntax | structural-only | Two-file import/export cannot execute in the single-script runner |
| 31 | Strict_Logic | hybrid structural+behavioral | ===, ternary and typeof objectives, actual typed label output |
| 32 | For_Of_Control | hybrid structural+behavioral | for-of/continue/break objectives, ordered filtered output |
| 33 | Object_Entries | hybrid structural+behavioral | Object.values/entries objectives and actual derived report lines |
| 34 | Input_Events | future-DOM | Input/change dispatch and currentTarget need browser execution |
| 35 | Form_Data | future-DOM | Form controls and FormData need browser execution |

### Hidden cases and limits

Greeting cases cover different casing and an empty string. Counter cases
interleave two counters and repeat fresh construction to detect shared state.
Class cases use three names. Required-data probes inspect student bindings
without supplying the missing computation. Console expectations are ordered
argument arrays, including numeric and array values, not rendered preview
strings.

All 21 behavioral reference solutions and alternate fixtures pass. Alternate
fixtures include arrow greetings, expression closures, different loop
variables, reordered object properties, traditional array callbacks,
template-based class methods, console.info and different whitespace. Every
starter fails combined grading. Commented solutions fail actual runner
checks. Constant greetings/reports, dead console strings, wrong greeting
logic, shared counter state, empty registries with fake counts, and
incomplete records fail.

Most console lessons are fixed top-level scripts, not parameterized helpers.
The current contract cannot substitute other bands, clearances, arrays or
payloads. Tests verify the stated example and relevant stored data, but
cannot prove unexecuted branches or generality. Syntax checks are text
patterns, not AST analysis: dead syntax can satisfy a syntax rule when
separate code produces the right fixed output. Full resistance to that
attack remains unsupported. No lesson was rewritten to invent a function
contract, and no browser, network, timer, storage or module execution was
added.

Configurations remain server-side under the existing hidden model field and
private grader request. Students receive sanitized results only. Failed
checks do not complete missions or award XP. First passes use the existing
reward and next-mission flow; repeat passes do not award XP. An unavailable
grader fails closed without an XP deduction.
