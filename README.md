# CodeQuest

A Laravel learning platform where students learn HTML, CSS, and JavaScript by writing code from scratch. Students work through courses of challenges, take formative Knowledge Checks, and prove a course with a Boss Challenge. Progress, XP, competency, and the unified timeline are derived from recorded evidence — never from client-supplied verdicts.

## Requirements

- PHP 8.3+ (the app runs on PHP 8.5 in development)
- Composer
- Node.js and npm
- MariaDB/MySQL for application use; SQLite in-memory for the test suite

## Setup

```sh
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
```

Configure the database in `.env`. The application schema lives in migrations under `database/migrations/`.

> **Production database safety.** The `2026_09_03_the404_*` migrations describe tables that already exist in the production `system404` schema. They run in tests via `RefreshDatabase` against in-memory SQLite, but **never run `php artisan migrate` against production**. Applying new migrations there is a separately approved deployment step.

## Development

```sh
composer run dev     # Artisan dev server + Vite
npm run dev          # Vite only
npm run build        # production asset build
```

The app's design tokens are CSS-first Tailwind v4 (`resources/css/app.css`). There is no `tailwind.config.*`. Fonts are installed through the Vite plugin's Bunny helper.

## Grading

- Structural validation matches submitted source against authored rules.
- Hidden behavioural tests run in an isolated grader service; see [docs/BEHAVIORAL_GRADING.md](docs/BEHAVIORAL_GRADING.md).
- Boss Challenges combine structural rules and hidden tests. An assessment with no hidden tests keeps the legacy pattern-only path.
- A grader outage is infrastructure, not a student failure: the attempt stays submitted with no verdict or XP.

Set `CODEQUEST_GRADER_URL`, `CODEQUEST_GRADER_TOKEN`, and `CODEQUEST_GRADER_TIMEOUT_MS` to reach the grader. There is deliberately no in-process execution fallback.

## Tests and static analysis

```sh
php artisan test --compact              # PHPUnit (SQLite in-memory)
vendor/bin/phpstan analyse --memory-limit=1G --no-progress
vendor/bin/pint --dirty --format agent
npm run icons:check
npm run build
sh grader/test/integration-container.sh # container sandbox acceptance
```

The suite includes a Chromium browser job, a disposable MariaDB job, and a
grader container job in CI. MariaDB-only tests skip locally when the driver
is unavailable. The container suite needs podman or docker; strict mode
(`CODEQUEST_REQUIRE_SANDBOX_TESTS=1`) additionally requires a rootless
runtime so a missing sandbox cannot read as acceptance.

## Documentation

- [CONTEXT.md](CONTEXT.md) — domain vocabulary and invariants
- [docs/adr/](docs/adr/) — architecture decisions
- [docs/BEHAVIORAL_GRADING.md](docs/BEHAVIORAL_GRADING.md) — grader architecture and Boss scoring
- [AGENTS.md](AGENTS.md) — guidance for coding agents working in this repository
