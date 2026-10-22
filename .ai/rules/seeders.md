---
paths:
  - database/seeders/CurriculumContentSeeder.php
---

# Seeders

## The curriculum is authored in the Engineer's Log palette
The curriculum's console panels used the old System 404 terminal palette (neon #33ff00 on #001100) — invisible while the app only printed the target as source, but glaring once the lesson panel renders the expected result. The curriculum is now authored in the world's inks: panel grounds #171b21 (the code ground) and #1c1f26 (the sheet), #242830 (the raised surface), signal green #6fc49a (the pass ink), amber #d9a94f (the warning ink), violet #9c97e8 (the stamp ink), pale text #e6e9de, muted #a9b0a5, and #f4f6ed for text on ink. Two rules were widened so nothing that used to pass stops passing: the colour rule now also accepts the world's greens (#6fc49a, #2e6b4f), the background rule also accepts #000, #000000, #14161b and #171b21, and the gradient rule became a regex that accepts either the old (#001100/#003300) or the new (#171b21/#242830) stop pair. The named CSS colours the lessons teach (green, red, black, gray) are left alone — they are the lesson's subject, not the system's palette. Re-key the curriculum in this seeder, never in the database by hand; the seeder upserts by order_num and title, so re-running it is how the change reaches a database.

## Seeded examples are self-contained and never point at app routes
A seeded starter or solution must render on its own: use an inline srcdoc document instead of a src that points at an application route. The app's CSP sets frame-ancestors 'none' and the student preview frame is fully sandboxed, so a src-based embed both 404s and is blocked. Curriculum corrections go through the seeder, which bumps the mission version when material fields change.

## A Knowledge Check is required by default
The seeder authors checks with is_required = true: a check is the mission's concept check and gates the coding challenge. The admin screen can still make an individual check optional. is_required is a material field, so the change is a curriculum revision that bumps the check version; attempts keep their own knowledge_check_version snapshot and never change meaning.

## Every mission owns a required Knowledge Check
knowledgeChecks() carries one authored check per mission (95 checks, 285 questions). A check is the mission's concept check: required by default, three questions grounded in what the mission teaches, one correct option each with an explanation, and no prompt repeated across the curriculum. CurriculumCheckCoverageTest enforces the invariant, so a mission may never ship without one and a check may never carry filler. Every check change is a curriculum revision: the version moves and existing attempts keep their own snapshot.
