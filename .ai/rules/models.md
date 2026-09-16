---
paths:
  - app/Models/Progress.php
  - 'app/Models/**'
---

# Models

## Progress has no updated_at
the404_progress has no `updated_at` column; the Progress model sets `$timestamps = false` so Eloquent doesn't try to write it. Follow the same pattern for any model over the404 tables that lack updated_at (e.g. the404_activity).

## XP ledger rows carry exactly one source FK (mission XOR assessment), enforced in XpService
XpTransaction #[Fillable] includes assessment_id; each row references EXACTLY ONE source: mission_id (Phase 3 mission rows, assessment_id null) or assessment_id (US-412 assessment rows, mission_id null). No source_type column (§25). XpService::record() enforces the exactly-one invariant and throws InvalidArgumentException if both or neither are set — never bypass it by writing both FKs.

## UserAchievement is a created_at-only award ledger
the404_user_achievements mirrors xp_transactions: an award row is inserted once and never edited, so UserAchievement sets $timestamps = false and casts only created_at. Follow the same pattern for any future event/ledger tables over the404_* — do not expect Eloquent to maintain updated_at there.

## Notification model: timestamps off, datetime/array casts, factory forceFill for created_at
Notification model maps the404_notifications with $timestamps = false (append-only, no updated_at — same as AdminAudit/UserAchievement). created_at and read_at cast to datetime, data cast to array (server-built route+params payload). BelongsTo User on user_id. The NotificationFactory forceFills so trusted fixtures can set created_at (like AssessmentAttemptFactory) — created_at is not fillable.
