# CodeQuest Design References

## Authority

`docs/CODEQUEST_MASTER_SPEC.md` is the authoritative specification for:

- product behavior
- domain rules
- authorization
- learning progression
- assessments
- XP
- UX contracts
- accessibility
- responsive behavior
- QA requirements

## Approved focused workspace prototype

The owner approved the [focused Coding Challenge prototype](coding-workspace-preview/README.md) on 2026-10-15.

Its runnable source is in `docs/design/coding-workspace-preview/`. It retains Mission Atlas and uses Code / Preview tabs sharing one full-width work area. Lesson context and the example are directly visible; mobile adds an Instructions tab. It has compact controls, a separate Hint action, no footer toolbar or Save button, and confirmation before Run / Submit.

This is the current approved visual reference for that focused workspace. It is not production application code. Authentication, scoring, draft persistence, progression, sandbox rules and other domain behavior remain governed by the master specification and implemented backend. The research and document review are recorded in [the system UI review](2026-10-14-system-ui-review.md) and [reference research](2026-10-14-reference-project-research.md).

## Earlier Challenge Workspace Reference

`docs/design/references/challenge-workspace-reference.html` is an earlier visual and interaction reference. Its sidebar preview and persistent footer arrangement are superseded by the approved focused prototype above. The list below describes historical reference coverage, not the newly approved composition.

It is a reference for:

- overall workspace composition
- Instructions / Editor / Preview layout
- editor file-tab placement
- Preview / Test Results organization
- persistent footer action area
- Run / Submit grouping
- Hint / Review Lesson placement
- focused coding-workspace hierarchy

It is NOT authoritative for:

- final CodeQuest color palette
- final typography
- final visual tokens
- business logic
- XP rules
- progression rules
- validation behavior
- authorization
- assessment behavior

The reference HTML is not production application code.

When the reference and master specification conflict, the master specification wins.
