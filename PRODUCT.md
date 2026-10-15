# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

CodeQuest serves three roles with equal product importance:

- Students learn HTML, CSS, and JavaScript through lessons, coding missions, Boss Challenges, progress tracking, and rewards.
- Teachers monitor student progress, assessment results, activity, competency, and students who need attention.
- Administrators manage accounts, curriculum, announcements, audits, analytics, and system status.

## Product purpose

CodeQuest guides learners from instruction through practice, server-validated coding work, assessment, and course completion. It gives teachers a current view of learning progress and gives administrators controlled tools for maintaining the platform.

Success means each role can identify its next important action quickly, understand system state without decoding decorative UI, and complete routine work on desktop or mobile.

## Positioning

CodeQuest combines a freeCodeCamp-style learning path, boot.dev-style progression, and a server-authoritative assessment system inside the System 404 terminal fiction. Preview output never determines correctness. The server owns validation, progression, XP, competency, and unlock state.

## Operating context

Students move between a dashboard, learning path, missions, a code editor, assessments, progress views, recommendations, achievements, and notifications. Teachers work from fleet dashboards, student records, activity feeds, course analytics, and attention queues. Administrators use dense management tables, edit forms, audit logs, analytics, announcements, and system checks.

The student Challenge is a focused workspace adopted on 2026-10-15: Code and Preview share one full-width work area behind tabs with Code selected first, Instructions sit alongside that area on desktop and become a tab at narrow widths, Hint stays separate from the lesson text, and the footer toolbar and visible Save button are gone. Drafts are saved explicitly through the Ctrl/Command+S shortcut rather than automatically. Boss Challenge and Try It Yourself keep their original three-pane workspace.

## Capabilities and constraints

- Preserve all existing routes, role boundaries, content, forms, data, and server-authoritative behavior.
- Preserve the terms Mission, Challenge, Assessment, Boss Challenge, XP, Competency, and System 404.
- Keep the Laravel 13, Blade, Vite, and Tailwind CSS v4 implementation.
- Maintain responsive layouts and accessible keyboard interaction.
- Student code runs only in the existing sandboxed preview. The server validates submissions through the allow-listed rule engine.
- The adopted student Challenge workspace is a binding commitment. Preserve its shared Code/Preview work area, Code-first default, desktop Instructions pane and mobile Instructions tab, separate Hint, and the removal of the footer toolbar and Save button. Boss Challenge and Try It Yourself remain on the three-pane workspace.
- Run and Submit are separate, separately confirmed actions. Run updates only the sandboxed preview and never grades, spends XP, or records a submission.
- Draft saving is explicit through the existing draft endpoint and the Ctrl/Command+S shortcut, not automatic.
- The grading service is a required production dependency, configured through `CODEQUEST_GRADER_URL` and `CODEQUEST_GRADER_TOKEN`. Where it is unreachable, submission must fail without grading, spending XP, or changing progress; this is expected behavior, not a defect.
- Do not change database structure or backend behavior as part of the redesign.
- Give student, teacher, and administrator surfaces equal visual attention. Their information density may differ according to the work they support.

## Brand commitments

Keep the CodeQuest name, System 404 fiction, retro-computing character, and learning-through-repair premise. No current layout, palette, component style, typography, or decorative treatment is protected. The redesign may replace the current visual system completely, and the visual world is still an open decision.

The binding Challenge workspace commitment above is a functional and behavioral constraint, not a visual style. It fixes what the screen does and how it separates work from evaluation; it does not fix palette, typography, iconography, or control styling.

## Evidence on hand

The repository contains working Blade screens for all three roles, real application copy, PHPUnit coverage, the CodeQuest master specification, and the current Tailwind design tokens. There are no approved logos, photography, illustrations, customer claims, testimonials, or benchmark data. Future design work must not fabricate them.

## Product principles

- Make the next action obvious without hiding supporting context.
- Use one coherent visual language across all roles.
- Let role-specific tasks determine density and hierarchy.
- Keep learning and assessment state legible and trustworthy.
- Use the System 404 fiction to clarify interaction, not obstruct it.

## Accessibility and inclusion

The interface must work with keyboard navigation, visible focus states, reduced motion, readable contrast, semantic tables, and responsive layouts from mobile through desktop.
