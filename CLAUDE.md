<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

# Project-specific

This is a Laravel 13 app ("CodeQuest"), a retro-terminal learning platform backed by a pre-existing `system404` MariaDB schema. Before planning or editing, read the matching rule file in `.ai/rules/` (the index maps file paths to rules) — it holds load-bearing constraints not obvious from the code.

## Database safety (critical)

- **Never run `php artisan migrate` against the production `system404` DB.** The `2026_09_03_the404_*` migrations reflect tables that already exist in prod (`Schema::create` would fail). They run **only** via `RefreshDatabase` against the PHPUnit in-memory SQLite connection (`phpunit.xml`). Keep them in sync with prod columns instead.
- Production `.env` sets `SESSION_DRIVER=database` / `CACHE_STORE=database`, but real production `system404` DB has NO framework tables at all (verified 2026-09-12: only the 5 the404_* tables exist — users, courses, missions, progress, activity; no sessions/cache/jobs/migrations). `php artisan migrate` against prod would create the migrations table as a side effect — apply schema changes via the exact DDL, with explicit approval. See `.ai/rules/general.md`.
- Domain models over `the404_*` tables may lack `updated_at` — set `$timestamps = false` (see `app/Models/Progress.php`), don't expect Eloquent to write it.
- **Optional schema must degrade, never crash.** The schema is applied on its own approved step, so a deployed database can lag the code. Probe optional columns/tables through `App\Support\SchemaAvailability` (cached per process; it logs the pending migration). A missing column omits the attribute, a missing hidden-test table keeps the legacy pattern-only Boss path. Never let an optional column turn a write into a 500, and never silently downgrade a graded verdict.

## Repo-specific conventions

- **Laravel 13 quirk:** `FormRequest::safe()` requires an array — `$request->safe(['username'])` / `safe()->only(...)`, not `safe('username')` (throws TypeError).
- **Tailwind v4 is CSS-first** — no `tailwind.config.*`. The design system is `@theme` retro-terminal tokens in `resources/css/app.css`. Fonts come via `laravel-vite-plugin` `bunny()` helper (`resources/js/app.js`, `app.css`).
- **Layout:** pages `@extends('layouts.app', ['role' => 'student'|'instructor'])` with `@section('title')` / `@section('content')`. Never `<x-app-layout>`.
- **Routes:** `/login` is guest-only, `/logout` + `/dashboard` are auth-only (see `.ai/rules/routes.md`). Every Phase 1 placeholder route is now a real controller, so route names are the contract — keep them when extending a page.
- **Controllers:** thin feature controllers under `app/Http/Controllers/` (`Auth`, `Dashboard`, `Mission`, `Assessment`, `Report`, `Admin*`); `Shell` serves the `/shell` design reference. Business logic lives in `app/Services/` (`ActivityService`, `DashboardService`), not fat controllers.

## Domain invariants (settled)

- **Boss threshold:** valid config is `passing_score` 1..100 plus a structurally valid `grading_rule`. `AssessmentService::evaluateAttempt` fails closed (warning, failed verdict, 0 score, no XP) on anything else; a zero threshold is never a lenient pass. Admin writes reject `<1`. Migration `2026_10_22_000001` sets the default to 70 and deliberately does not rewrite existing rows.
- **Boss grading:** `AssessmentService` combines pattern rules with hidden behavioural tests (`the404_assessment_behavior_tests`, graded through `AssessmentGradingService`/`GraderClient`). Pass requires valid config, ≥1 check, combined score ≥ threshold, every pattern rule, and every hidden test. An assessment with no hidden tests keeps the legacy pattern-only path. Grader unavailability leaves the attempt `submitted` with neutral feedback — no verdict, XP, or Activity row — and a resubmit re-evaluates. `grading_rule` is an audited admin write validated by `ValidationService::isValidDefinition` and version-bumped on change.
- **Required Knowledge Check:** `KnowledgeCheckGate` is the domain prerequisite for submit, draft save, hint and solution reveal. A submitted attempt clears it (pass not required); already-completed missions are exempt. Do not add controller-only enforcement. Admins manage the title/instructions/required flag/status on the new `admin.courses.missions.checks` screen; question content stays on the release path. Checks are authored **required** by the seeder — the check is the mission's concept check. The lesson's result dialog and rail apply a **client-side 70% pass gate** for the challenge CTA (`KnowledgeCheckService::PASS_SCORE`); this is presentation gating, not a change to the domain gate.
- **Every mission owns a check:** the seeder carries one check per mission (95 checks, 285 questions), each grounded in what that mission teaches. `CurriculumCheckCoverageTest` holds the invariant: no mission may ship without a published check, every check has three well-formed questions with exactly one correct option and an explanation, and no prompt repeats across the curriculum.
- **Knowledge Check in the lesson:** the check closes the main column as a ruled checkpoint band beneath the reading column and the bench (`resources/views/lesson.blade.php`), so a long answer form gets the desktop width it needs without reading as a pasted card. It opens **in place**: `mission.knowledge-check.start/submit/retry` re-render the lesson, so the student never leaves the page. An open attempt folds the brief into a "The brief" disclosure; the shared form goes two-column from 1201px and stacks below it. After submission, a native result dialog auto-opens with the score, pass status, breakdown, pass score and actions; a failed result locks its Proceed CTA and the rail (which offers Retry required check), while Review Answers closes the dialog and highlights the wrong inline answers. The dialog is presentation only — the server gate remains submitted-only. The challenge workspace carries **no** check — its Code / Preview / Instructions shape is a binding commitment.
- **Theme = newest of device and account:** the head script applies the theme before first paint and compares the device's choice (`cq-theme` + `cq-theme-at`) with the account's (value + `saved_at`); the **newer** wins, so a choice made just before navigating is never overwritten by an account write that has not landed yet.
- **Next activity:** `DashboardService::nextMission()` is the single resolver — drafts first, then `(order_num, id)`. `ResumeService`, `LearningPathService::nextMission()` and recommendations delegate to it.
- **Timeline vocabulary:** one Boss type string (`assessment_passed`; the award ledger row keeps its +XP), `grading_unavailable` excluded as infrastructure, `(timestamp, id)` ordering, and the 50-beat view discloses its true total. Boss verdict `Activity` rows exist for ledger parity but are composed from `AssessmentAttempt`, so they are excluded from the timeline's Activity source.
- **Performance Summary:** `/performance` joins the three result types from `CourseProgressService`, `AssessmentService` attempt history and `KnowledgeCheckService::courseRegister()`. Never re-derive completion/pass/correct there.
- **Challenge analytics:** `grading_unavailable` Activity rows carry a nullable `mission_id`; they count as attempts (not failures or completions), and rows without a mission id stay unattributed rather than being guessed from message text.
- **Competency:** mapped Boss evidence is the third component — ever passed = 100%, attempted without a pass = 0%, no attempt = no Boss component — averaged with KC and challenge evidence. Read `AssessmentAttempt.skill_keys`, never the live pivot.

## Commands

- `composer run dev` — full dev environment (artisan dev + vite).
- `npm run build` / `npm run dev` — frontend assets; if a Vite manifest error appears, build first.
- `php artisan test --compact --filter=<test>` or pass a file path — run a single test.
- Run `vendor/bin/pint --dirty --format agent` after editing PHP.
- Inspect routes with `php artisan route:list`, config with `php artisan config:show <path>`.

## Tools & agents

- `laravel-boost` MCP is wired via `opencode.json` / `.mcp.json` — prefer `database-schema` / `database-query` (read-only) over raw SQL or tinker.
- `AGENTS.md` and `CLAUDE.md` are kept in sync with the Boost guidelines; edit both when changing shared guidance, or use the `record-rule` flow to write `.ai/rules/` files.
- Engineering skills config (`docs/agents/`, issue-tracker/triage/domain-docs) lives under `docs/agents/` and is read by the `triage`, `to-tickets`, `to-spec`, and `wayfinder` skills.

## Agent skills

### Issue tracker

Local markdown — issues live as files under `.scratch/<feature>/`. See `docs/agents/issue-tracker.md`.

### Triage labels

Default canonical labels: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context — `CONTEXT.md` + `docs/adr/` at the repo root. See `docs/agents/domain.md`.
