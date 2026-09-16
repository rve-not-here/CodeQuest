# XP as a spendable economy

XP is not just a score that goes up. Students earn it by completing missions, lose it on wrong submissions, and spend it on hints and solution reveals. The economy is deliberately constrained: no shop, no items, no complex spending. XP buys learning assistance and nothing else.

Progressive costs (seed defaults, configurable without deploy):

| Action | XP |
|--------|----|
| Mission completed | +mission.points |
| Wrong submission | -10 |
| Hint 1 | -5 |
| Hint 2 | -10 |
| Hint 3 | -15 |
| Solution reveal | -30 |

XP never drops below 0. The balance is derived from the transaction log, not stored as a separate column.

## Transaction schema

The `the404_xp_transactions` table records every XP change:

| Column | Type | Notes |
|--------|------|-------|
| id | unsigned int, auto-increment | PK |
| user_id | unsigned int, required | FK → the404_users.id, restrictOnDelete |
| mission_id | unsigned int, required | FK → the404_missions.id, restrictOnDelete |
| amount | int, required | Signed: positive for earnings, negative for deductions |
| type | varchar(40), required | mission_completed, wrong_submission, hint_used, solution_revealed |
| description | text, nullable | Human-readable explanation |
| created_at | timestamp | When the transaction happened |

Indexes: user_id, (user_id + created_at), type.

**Balance**: `SELECT SUM(amount) FROM the404_xp_transactions WHERE user_id = :id`.

**Relationship to progress.pts_earned**: Progress still records pts_earned for quick per-mission access. It's a cache. The transaction log is authoritative. If they diverge, the log wins.

## Cascade behavior

Both foreign keys use `restrictOnDelete()`. The XP transaction log is an audit record. Deleting a user or mission should not silently destroy the audit trail. An admin must resolve associated transactions before removing the source entity.

## Why varchar, not enum

The schema uses enum for stable, closed sets: role, status, difficulty. XP transaction types are different. The economy is designed to expand: new earning methods, new spending categories, possibly assessment XP in the future. Adding a new type through seed data is a config change. Adding a new enum value requires an ALTER TABLE. We use varchar(40) here so new transaction types don't need a migration. This is a deliberate deviation from the enum pattern elsewhere in the schema, not an inconsistency.

## Status

This schema is approved but not yet migrated. The `the404_xp_transactions` table does not exist in the database. Creating it is implementation work for a later phase.
