---
paths:
  - 'database/migrations/the404_*.php'
  - 'database/migrations/**'
  - 'database/migrations/*knowledge_check*'
---

# Migrations

## the404_* migrations mirror prod for the TEST DB only
The 2026_09_03 the404_* migrations exist solely to build the PHPUnit SQLite in-memory schema via RefreshDatabase. Never run `php artisan migrate` against the production MariaDB `system404` DB to apply them — those tables already exist there (Schema::create would fail). Tests target the sqlite connection and are the only place they run. Keep them in sync with production `the404_*` columns.

## XP ledger alter keeps mission FK on SQLite ->change()
The 2026_09_04_120343 alter makes the404_xp_transactions.mission_id nullable and adds a nullable assessment_id FK (exactly one of the two populated, enforced in XpService, not by a source_type column). On SQLite, Laravel's ->change() rebuilds the table but the migration grammar preserves BOTH the rebuilt mission FK and the new assessment FK (verified via .schema). On MariaDB ->change() is an in-place MODIFY that retains the existing FK; the down() restores mission_id to NOT NULL and drops assessment_id. Never run this against prod system404 without explicit approval.

## Achievement tables: unique pair, restrict FKs, created_at ledger
2026_09_05_000001/000002 add the404_achievements (slug unique 40, timestamps; mutable catalog) and the404_user_achievements (user_id + achievement_id, unique (user_id, achievement_id), only created_at useCurrent). Both foreign keys are restrictOnDelete (audit-record reasoning like xp_transactions/assessment_attempts). These migrations reflect tables that may already exist in prod — they run only under RefreshDatabase against the in-memory SQLite test schema, never via migrate on the real system404 DB.

## the404_admin_audit is the append-only admin audit store
2026_09_11_000002 creates the404_admin_audit: the dedicated admin audit store (approved §28), distinct from the404_activity/TimelineService. Append-only (no updated_at; only created_at useCurrent), created_at stays OUT of AdminAudit #[Fillable], admin_user_id restrictOnDelete so history survives account deactivation. AdminAuditService::recent (created_at desc, id desc) is the US-702 console read; record() landed in US-704 as the ONLY writer (action vocabulary user.role.change / user.status.change; every change → 'success' row, every refusal → 'failed' row).

## the404_notifications/announcements migrations: restrict FKs, dedupe unique, no updated_at
2026_09_12_000001 creates the404_notifications (US-801): user_id restrictOnDelete (deliberately NOT cascade — matches achievement/attempt/audit precedent for audit/dispute history; cascade would silently destroy notification history if user deletion ever ships), type string(40) NOT enum, data text nullable JSON (route+params payload, never raw URLs), dedupe_key string(100) nullable, unique (user_id, dedupe_key) as the duplicate-prevention backstop (NULL dedupe_keys — frequency-governed recurring notifications — are distinct under the unique index), read_at nullable, created_at useCurrent only, no updated_at. 2026_09_12_000002 creates the404_announcements: created_by restrictOnDelete, audience enum(all|students|teachers|admins), status enum(draft|published|archived), published_at null set on first publish, created_at+updated_at. Both run only under RefreshDatabase on SQLite; never against prod system404 without explicit approval.

## Retain Knowledge Check history
Knowledge Check curriculum and attempt/response foreign keys use restrict-on-delete. Responses snapshot prompts, selected/correct answers, and explanations at submission; do not overwrite or cascade-delete educational history.
