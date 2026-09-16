---
paths:
  - app/Http/Middleware/EnsureUserIsActive.php
---

# Middleware

## Account status is a per-request sign-in gate, never a learning-state field
Deactivated-account lockout (US-701): EnsureUserIsActive is appended to the web middleware group in bootstrap/app.php, so it runs on EVERY web request after the session starts. An authenticated user whose status !== 'active' is logged out, the session invalidated + token regenerated, and bounced to route('login') with an 'account' error — deactivation takes effect on the next request, not at next login. Login itself also refuses inactive accounts after Auth::attempt succeeds, with a distinct 'account' error. 'status' must stay OUT of User #[Fillable] (only the admin service sets it), and no course/XP/competency service reads user.status. The 2026_09_11 migration default 'active' keeps existing accounts active.
