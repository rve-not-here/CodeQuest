---
paths:
  - app/Http/Requests/AdminUserStoreRequest.php
  - app/Http/Requests/AdminCourseUpdateRequest.php
  - app/Http/Requests/AdminMissionUpdateRequest.php
  - 'app/Http/Requests/Admin*Request.php'
  - 'app/Http/Requests/**'
  - 'app/Http/Requests/{ActivityFeedRequest,StudentOverviewRequest}.php'
  - 'app/Http/Requests/{AdminAssessmentUpdateRequest,AdminMissionUpdateRequest}.php'
---

# Requests

## User write requests reject pre-hashed passwords; update whitelists role/status (US-704)
US-703 password rule: a NEW account/updated password must be plaintext. Both AdminUserStoreRequest and AdminUserUpdateRequest carry a rejectPreHashedPassword() closure that fails values starting with a $2y$/$2a$/$2b$/argon2/etc. hash prefix, so a pre-hashed password is rejected at validation — never double-hashed silently. The model's 'hashed' cast derives the stored hash; creation min length is 8 (login itself has no min). Since US-704 update also carries OPTIONAL 'role' (Rule::in(UserService::ROLES) = student/teacher/admin/operator — full set, operator assignable on existing accounts) and 'status' (Rule::in(active|inactive)); both are nullable so legacy payloads without them still pass. Server-determined: an out-of-set value fails validation here, and UserService throws InvalidArgumentException as defense-in-depth. The guarded write path enforces self-protection and the ≥2-active-admin floor on top of this shape validation.

## Course update request validates the catalog shape; status against the STATUSES allow list (US-705)
AdminCourseUpdateRequest (US-705) validates: name required <=128; slug required <=64 kebab-case /^[a-z0-9-]+$/ + unique in the404_courses ignoring the course itself; type required free string <=16 (CompetencyService::nameFor degrades gracefully for unknown types); description nullable string; status required Rule::in(CourseService::STATUSES) (active|locked|draft — server-determined, no free strings); order_num required integer min:0. The update whitelists ONLY these keys — there is no create (no store route exists; create/delete are deliberately out of scope per §14 no-destructive posture) and nothing in the payload can touch the404_progress.

## AdminMissionUpdateRequest: int-coerce section_id in prepareForValidation, never accept solution_code/validate_rule
AdminMissionUpdateRequest (US-707) whitelists exactly the 9 editable mission fields and must NEVER include rules for solution_code or validate_rule (view-only this story; safe() strips them so a crafted payload cannot reach the service). section_id arrives from the HTML <select> as a numeric string, so prepareForValidation() casts it to int when non-null — the service's is_int guard would otherwise reject every real browser submission (ConvertEmptyStringsToNull turns "" into null, skipping the cast). difficulty uses Rule::in(AdminMissionService::DIFFICULTIES); points/order_num integer min:0 (Rule::integer vs digits_fits_in_32_bits note: use integer, not digits form, to keep 0 acceptable); section_id nullable integer. The PhpStan type flow: Larastan types Eloquent primary keys as plain 'int' (never int<0,max>), so reassigning from $section->id does NOT satisfy a typed int<0,max>|null column — instead keep the input value and add an explicit $sectionId < 0 guard in the service; the range-narrowed value then flows to the assignment cleanly without casts or @phpstan-ignore.

## Assessment update payload coerces passing_score to int
AdminAssessmentUpdateRequest mirrors the mission update payload shape: rules() allow-lists ONLY title/description/instructions/passing_score/status (grading_rule is deliberately absent so a crafted payload can't write it), and prepareForValidation casts passing_score to int because HTMLElement#number submits a numeric string but the guarded service requires a real integer (same coercion as section_id on missions).

## AdminActivityFeedRequest: validated filters, no date anchoring
AdminActivityFeedRequest (US-709) validates the /admin/activity filters server-side: actor (nullable integer Rule::exists the404_users.id), action (nullable Rule::in AdminAuditService::ACTIONS), result (nullable Rule::in success|failed), from/to (nullable dates). The to>=from ordering check lives in withValidator (like ActivityFeedRequest) so a missing bound never trips the comparison — an 'after_or_equal:from' rule misbehaves when 'from' is absent since safe()/merge keeps absent keys out. Unlike ActivityFeedRequest there is no prepareForValidation date anchoring (no computed span to bound), so from/to stay optional and the view must ?? default them.

## Teacher filters validate against active classroom scope
Teacher-facing student and course filters must validate against ClassroomAccessService's current authorized IDs. A foreign existing ID and a nonexistent ID should produce the same validation outcome, and neither may reach the feed or roster. Admin filters retain fleet-wide existence validation.

## Normalize only valid integer strings before validation
HTML number/select values may be converted to integers before service calls, but only when the input is a digit string accepted by FILTER_VALIDATE_INT. Leave arrays, decimal strings, nonnumeric strings, and overflowing values unchanged so Form Request integer rules reject them instead of silently changing passing_score or section_id.
