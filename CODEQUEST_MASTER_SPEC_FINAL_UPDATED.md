# CodeQuest — Complete Phase Specification

> **Project:** CodeQuest: An Integrated Gamified Learning and Assessment System  
> **Architecture:** Laravel 13 + PHP 8.5 + MariaDB + Blade + JavaScript + CodeMirror 6  
> **Legacy Reference:** `~/system404`  
> **Laravel Project:** `~/codequest`  
> **Final Planned Phase:** Phase 12  
> **Specification Status:** Implementation-Grade Master Specification  
> **Revision:** 2026-09-16 — formative Knowledge Check integration, Classroom-scoped teacher monitoring, W3Schools + freeCodeCamp + Boot.dev learning model, separate-mode student UX, and domain-contract hardening

---

# Project Direction

CodeQuest is intentionally built from four complementary influences while remaining its own academic system.

```text
W3SCHOOLS-STYLE
CONCISE CONCEPT LEARNING + SIMPLE EXAMPLES + TRY-IT-YOURSELF PRACTICE
+
EXISTING QUIZ/EXAMINATION SYSTEM
LIGHTWEIGHT FORMATIVE KNOWLEDGE CHECKS + OBJECTIVE CONCEPT VERIFICATION
+
FREECODECAMP-STYLE
LEARNING BY WRITING REAL CODE + IMMEDIATE VALIDATION + RETRY
+
BOOT.DEV-STYLE
SEQUENTIAL PROGRESSION + XP + UNLOCKS + VISIBLE GAMIFICATION
+
SYSTEM 404
CRT / EMERGENCY TERMINAL IDENTITY + MISSION-BASED PRESENTATION
+
CODEQUEST
BOSS CHALLENGE ASSESSMENT + COMPETENCY + TEACHER MONITORING + REPORTING
=
CODEQUEST
```

These products are design references, not products to clone. CodeQuest owns its own rules, data model, assessment process, academic monitoring, and progression logic.

## Inspiration Boundaries

### W3Schools influence — teaching layer
CodeQuest borrows concise beginner-friendly explanations, focused syntax examples, simple demonstrations, reference-style clarity, and a low-friction "Try It Yourself" experience.

CodeQuest does **not** become a static tutorial/reference website. Lessons must lead into active coding practice.

### Existing quiz/examination system influence — formative knowledge-check layer
CodeQuest retains the useful objective-assessment capability of the existing online quiz/examination system, but integrates it as lightweight **Knowledge Checks** inside the unified CodeQuest learning flow rather than preserving a separate quiz/exam application experience.

Knowledge Checks verify concept understanding through objective questions. They are formative learning evidence, not a replacement for practical coding work and not a second course-completion authority.

CodeQuest V1 must not recreate a large generic LMS-style exam subsystem around this feature. The Boss Challenge remains the formal course-level practical assessment.

### freeCodeCamp influence — practice layer
CodeQuest borrows learning-by-doing, frequent coding challenges, immediate feedback, cumulative practice, and the expectation that students produce code instead of merely selecting answers.

CodeQuest does **not** copy freeCodeCamp curriculum, content, branding, or validation implementation.

### Boot.dev influence — progression/gamification layer
CodeQuest borrows sequential progression, visible advancement, XP, locked/unlocked learning nodes, milestone satisfaction, and a game-like learning path.

This does **not** automatically add streaks, leaderboards, hearts/energy, daily XP caps, paid progression, social competition, or any other mechanic unless separately approved.

### System 404 influence — identity layer
CodeQuest preserves the CRT/emergency-terminal visual identity, phosphor styling, mission language, system-state messaging, and retro technical atmosphere.

The visual theme must support learning rather than reduce readability or accessibility.

### CodeQuest-owned academic layer
CodeQuest itself owns the integration of formative Knowledge Checks, practical Coding Challenges, the summative Boss Challenge, competency, teacher monitoring, recommendations, notifications, reports, auditability, classrooms/enrollment, and institutional use.

The three evidence layers are intentionally distinct:

```text
KNOWLEDGE CHECK
→ Do I understand the concept?

CODING CHALLENGE
→ Can I apply the concept in code?

BOSS CHALLENGE
→ Can I integrate multiple concepts in a formal practical assessment?
```

Knowledge Checks and Coding Challenges support learning. The Boss Challenge remains the single summative course assessment that can complete the course.

## Authoritative Learning Flow

```text
Dashboard
→ Learning Path
→ Course
→ Section
→ Lesson
→ Learn Concept
→ See Example
→ Try / Experiment
→ Knowledge Check when configured
→ Challenge
→ Write code from scratch
→ Run / Preview
→ Submit
→ Server-side validation
→ Fail / Student-safe Feedback / Retry
OR
→ Pass
→ XP + Progress
→ Next Challenge
→ Required Challenges Complete
→ Boss Challenge
→ Pass
→ Course Complete
→ Next Course Unlocked
→ Competency / Achievement / Timeline / Recommendation
```

## Educational Philosophy

```text
LEARN
→ UNDERSTAND
→ SEE
→ TRY
→ CHECK UNDERSTANDING
→ PRACTICE
→ WRITE CODE
→ TEST
→ FAIL SAFELY
→ FIX
→ PASS
→ PROGRESS
→ MASTER
→ ASSESS
→ REFLECT
→ IMPROVE
```

The normal CodeQuest lesson should teach briefly, demonstrate clearly, allow experimentation, and then require the student to apply the concept in code.

## Canonical Lesson Anatomy

A standard programming lesson should be able to contain:

```text
LESSON TITLE
→ What You Will Learn
→ Concise Concept Explanation
→ Syntax / Pattern
→ Worked Example
→ Explanation of the Example
→ Try It Yourself / Experiment Area
→ Common Mistake or Important Note
→ Knowledge Check when configured
→ Challenge
→ Feedback / Retry
→ Completion / Next Learning Node
```

Not every lesson needs every optional element, but lessons must not degrade into long passive reading followed by unrelated exercises.

# Core Product Rules

1. Internal domain term = `Mission`.
2. Student-facing term = `Challenge`.
3. Students write code from scratch for authoritative coding challenges unless a specific learning activity explicitly provides starter code.
4. Preview is never proof of completion.
5. Server-side validation is authoritative.
6. Client-side JavaScript never controls XP, completion, unlocks, competency, assessment eligibility, assessment results, achievements, recommendations, or course completion.
7. Draft, Progress, and Attempt are separate concepts.
8. A mission completion reward is granted only on the first authoritative transition from incomplete to completed.
9. Re-submitting an already completed mission cannot farm mission-completion XP.
10. A genuine failed authoritative submission deducts 10 XP unless a specifically approved activity defines otherwise.
11. XP cannot go below zero.
12. XP changes must be auditable and idempotent where duplicate requests are possible.
13. XP is not a grade.
14. Hints and solution reveal may cost XP.
15. `solution_code` must never be exposed in normal student payloads.
16. `validate_rule` and protected grading internals must never be exposed in normal student payloads.
17. Exactly one **summative course assessment** exists per course and it is the Boss Challenge.
18. Boss Challenge is separate from ordinary missions and formative Knowledge Checks.
19. Required missions gate assessment eligibility.
20. Passing the Boss Challenge completes the course according to the accepted Phase 4 rules.
21. Course completion unlocks the next course according to the progression service.
22. Competency derives from actual authoritative learning and assessment data.
23. Competency has one authoritative calculation path.
24. Teachers monitor; they do not become content administrators.
25. Admins manage the platform; they do not automatically manipulate learning outcomes.
26. Operators are retained as a separate role and receive only explicitly defined operational permissions.
27. Notifications communicate state; they do not create authoritative learning state.
28. Recommendations never bypass progression or authorization.
29. Reports describe authoritative data; they do not become another source of truth.
30. Do not duplicate XP, progress, assessment, competency, achievement, recommendation, notification, or audit systems unnecessarily.
31. Published curriculum changes must preserve historical integrity.
32. Existing completions are never silently invalidated by curriculum edits.
33. Reward-producing and state-transition operations must resist duplicate requests and race conditions.
34. Administrative audit records are append-oriented and not ordinary editable history.
35. Historical attempts and XP transactions are retained unless a separately approved retention policy requires otherwise.
36. Derived dashboard/report values should remain derived unless persistence is justified by an authoritative responsibility.
37. Do not modify `~/system404` unless explicitly instructed.
38. Do not run migrations against the real `system404` database without explicit approval.
39. Preserve existing authoritative data during integration.
40. Preserve the CodeQuest CRT/System 404 identity without sacrificing usability, accessibility, or editor readability.
41. W3Schools, freeCodeCamp, and Boot.dev are inspiration sources, not hidden feature requirements.
42. Test counts are evidence, not acceptance criteria by themselves.
43. Major domain rules must be traceable to the service or phase that enforces them.
44. Production deployment requires both database safety and application rollback planning.
45. Anything beyond Phase 12 is CodeQuest v2, maintenance, or separately approved extension work.
46. Knowledge Checks are formative concept checks integrated into the learning flow; they are not a second summative assessment system.
47. Knowledge Checks never replace required practical Coding Challenges for Boss Challenge eligibility.
48. Knowledge Check scoring, attempts, answer keys, and completion state are server-authoritative.
49. Knowledge Check answer keys and protected scoring configuration are never exposed in normal student payloads before submission.
50. Knowledge Check evidence may contribute to competency and recommendations only through the authoritative competency/recommendation services.
51. Teachers may monitor Knowledge Check performance only for students/classes they are authorized to view.
52. The existing quiz/examination system is integrated conceptually and, where approved, by reusable data/logic; CodeQuest remains one Laravel application and must not operate as two disconnected applications.

# Global Domain Contracts

These contracts apply across all phases. A phase may implement or consume them, but it must not redefine them inconsistently.

## 1. Terminology and Source-of-Truth Rule

Before adding a new table, service, status, aggregate, or cache, answer:

```text
What authoritative fact does this component own?
```

If another subsystem already owns that fact, reuse or derive it instead of creating a competing source of truth.

Authoritative examples:

```text
Mission completion        → Progress / Mission completion domain
XP balance and changes    → XP service + XP transaction ledger
Assessment result         → Assessment/Boss Challenge domain
Course completion         → Course progression domain
Competency                → Competency service
Achievement grant         → Achievement service
Notification read state   → Notification domain
Admin action history      → Audit domain
```

Reports, dashboards, recommendations, and UI components consume authoritative data. They do not redefine it.

## 2. Role and Permission Boundary

Canonical roles:

```text
student
teacher
admin
operator
```

High-level permission matrix:

| Capability | Student | Teacher | Admin | Operator |
|---|---|---|---|---|
| Perform own learning activities | Yes | No | No | No |
| Submit own Challenge/Boss Challenge | Yes | No | No | No |
| View own code/drafts/attempts | Yes | No by default | Controlled | Controlled |
| View authorized student progress | Own only | Yes | Yes | Operational only if approved |
| View academic competency/reports | Own only | Authorized students/courses | Yes | Only if explicitly required |
| Manage curriculum | No | No | Yes | No by default |
| Manage user roles | No | No | Yes | Only explicitly delegated operational roles |
| Arbitrarily edit XP/completion/results | No | No | No normal control | No normal control |
| View admin audit trail | No | Limited if approved | Yes | Yes where operationally required |
| Configure deployment/system operations | No | No | Limited application settings | Yes where explicitly defined |

Exact permissions must be implemented through Laravel authorization policies/gates, not UI hiding alone.

The `operator` role exists for technical/system operations. It must not silently inherit academic authority from `admin` or `teacher`.

### Classroom / Enrollment Authorization

Teacher visibility must be based on explicit academic relationships rather than broad role membership.

Canonical relationship:

```text
Admin
→ creates/manages Classroom
→ assigns Teacher(s)
→ enrolls Students
→ assigns Course(s)
→ Teacher monitors only authorized Classroom/Student scope
```

Use a distinct term such as `Classroom` or `ClassGroup`; do not reuse curriculum `Section` for academic class grouping.

The canonical data model should account for:

```text
classrooms
academic_terms where used
classroom_teacher assignments
classroom_student enrollments
classroom_course assignments
```

A teacher must not gain visibility into all students merely because the account has role `teacher`.

## 3. Learning Hierarchy and Ordering

Version 1 uses an ordered curriculum model:

```text
Course
→ ordered Sections
→ ordered Lessons
→ ordered Missions / Challenges
```

Use explicit position/order fields where required.

Required vs optional learning items must be represented explicitly. Do not introduce a general arbitrary prerequisite graph unless separately approved.

Course gating remains authoritative server behavior.

## 4. Lesson Contract

The teaching layer should favor concise, focused instruction followed by active practice.

A lesson may contain structured learning content such as:

```text
title
learning objectives
concept explanation
syntax/example blocks
worked example
try-it-yourself content
common mistake/note
linked Challenge(s)
```

Learning content is not itself proof of completion unless the curriculum explicitly defines a completion rule. Challenge completion remains server-authoritative.

## 4A. Knowledge Check Contract

Knowledge Checks are short formative concept assessments embedded in the normal lesson flow. They reuse the useful objective-assessment idea from the existing online quiz/examination system without recreating that system as a separate application.

Primary purpose:

```text
Knowledge Check   → verify concept understanding
Coding Challenge  → verify practical application
Boss Challenge    → verify integrated course-level practical mastery
```

Knowledge Checks may appear after explanation/examples/Try It Yourself and before or between Coding Challenges when pedagogically useful.

Initial V1 question types should remain intentionally small and objectively gradable. Recommended initial scope:

```text
single-choice multiple choice
true/false only if explicitly approved and unambiguous
short objective identification only if deterministic validation is defined
```

Do not add essay grading, AI grading, subjective free-response grading, webcam proctoring, browser lockdown, or a generic exam-builder platform in V1.

A Knowledge Check definition should identify, where applicable:

```text
title
lesson/section/course relationship
question set/version
required vs optional status
skill/concept mapping
passing rule if a pass state is used
attempt policy
feedback policy
publication state
```

A Knowledge Check attempt should preserve enough evidence to reconstruct the result without exposing protected answer keys to the student before submission.

Conceptual authority flow:

```text
Student answers
→ Laravel receives authorized submission
→ server loads published Knowledge Check version
→ server scores objective answers
→ attempt/result recorded
→ student-safe feedback returned
→ competency/timeline/recommendation consumers may react
```

Critical rules:

- answer keys and protected scoring configuration are server-authoritative;
- client-side JavaScript may render the interaction but does not determine the trusted score;
- Knowledge Checks do not award course completion;
- Knowledge Checks do not unlock the next course;
- Knowledge Checks do not replace required Coding Challenges for Boss Challenge eligibility;
- if a Knowledge Check is configured as required for local lesson/section progression, that requirement must be explicit and server-authoritative;
- reattempt policy and whether the highest/latest/first score is displayed must be documented before implementation of that check type;
- Knowledge Check results may contribute to competency only through the shared CompetencyService formula;
- teachers see Knowledge Check performance only within authorized classroom/enrollment scope.

The Boss Challenge remains the single **summative course assessment**.

## 5. Attempt Lifecycle

Drafts are mutable unfinished work. Attempts are historical submission events.

Conceptual lifecycle:

```text
DRAFT
→ SUBMITTED
→ VALIDATED
→ PASSED | FAILED
```

A new authoritative Submit action creates a new attempt record where attempt history is required.

An attempt should preserve enough evidence to explain what happened, such as:

```text
user
mission/assessment/knowledge check
mission/assessment/knowledge-check version reference where applicable
submitted code or approved snapshot/reference
validation/scoring result
pass/fail
student-safe feedback snapshot where useful
XP effect reference where applicable
submitted_at / validated_at
```

Historical attempts are not edited into different outcomes through normal application flows.

A retry creates a new attempt; it does not rewrite the previous attempt.

## 6. Mission Validation DSL

Mission validation uses a deterministic, declarative rule format interpreted by trusted Laravel code.

Approved initial validation concepts:

```text
contains
contains_all
contains_any
count
count_tag
regex
exact_normalized
```

A validation rule must be versioned and structurally validated before publication.

Conceptual rule:

```json
{
  "version": 1,
  "operator": "all",
  "checks": [
    {
      "id": "heading-required",
      "type": "contains",
      "value": "<h1",
      "message": "Add the required heading."
    },
    {
      "id": "paragraph-required",
      "type": "contains",
      "value": "<p",
      "message": "Add the required paragraph."
    }
  ]
}
```

Top-level composition:

```text
all = every required child check passes
any = at least one child check passes
```

Nested Boolean expression systems, arbitrary executable expressions, and student-provided rule logic are out of scope unless separately approved.

Conceptual student-safe validation result:

```json
{
  "passed": false,
  "feedback": [
    {
      "check": "heading-required",
      "passed": true,
      "message": "Heading requirement passed."
    },
    {
      "check": "paragraph-required",
      "passed": false,
      "message": "Add the required paragraph."
    }
  ]
}
```

Student responses must not expose hidden regexes, protected validator configuration, solutions, answer keys, or grading internals.

Never use `eval`, `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, or `popen` to execute student submissions.

## 7. Preview Sandbox Contract

The normal web preview flow is:

```text
CodeMirror
→ sandboxed iframe
→ srcdoc
→ visual preview
```

The iframe must use the least privilege required for the lesson. Capabilities such as scripts, forms, modals, popups, navigation, downloads, and same-origin access must not be enabled casually.

If JavaScript lessons require scripts, enable only the minimum sandbox capability necessary and test that the preview cannot escape into or manipulate the parent application.

Preview remains non-authoritative.

## 8. XP Economy and Anti-Abuse

First authoritative mission completion:

```text
INCOMPLETE
→ PASSED
→ mark completion
→ award trusted mission.points once
```

Replaying or re-submitting an already completed mission:

```text
successful resubmission → no additional mission-completion XP
failed experiment       → no reversal of historical completion
```

Failed authoritative submission:

```text
new_xp = max(0, current_xp - 10)
```

unless a specifically approved rule for that activity says otherwise.

XP reward, deduction, and spending must flow through one authoritative XP service/ledger path.

Each transaction should capture at minimum:

```text
user
amount
transaction type
source type
source identifier
reason
timestamp
resulting balance or reconstructable balance
idempotency/event reference where applicable
```

Suggested transaction types:

```text
MISSION_REWARD
FAILED_SUBMISSION
HINT_PURCHASE
SOLUTION_REVEAL
ACHIEVEMENT_REWARD
ASSESSMENT_REWARD if explicitly approved
ADMINISTRATIVE_CORRECTION only through exceptional audited maintenance flow, not ordinary UI
```

## 9. Transaction and Idempotency Boundaries

Authoritative state transitions that must not partially apply should use database transactions where appropriate.

Conceptual successful mission submission:

```text
BEGIN TRANSACTION
  create immutable attempt result
  transition mission progress if first completion
  create XP transaction if eligible
  record core authoritative activity/event
COMMIT
```

Secondary consumers such as notifications, recommendation refreshes, or exports may react afterward as long as they cannot create a contradictory authoritative learning state.

Duplicate clicks, retries, refreshes, queue retries, and concurrent requests must not double-award XP, double-complete courses, or double-grant achievements.

Important unique/idempotent transitions include:

```text
mission completion reward
Boss Challenge pass effect
course completion
next-course unlock
achievement grant
notification for immutable one-time events
```

## 10. Concurrency Protection

Critical transitions must be safe across multiple browser tabs and concurrent requests.

Use appropriate combinations of:

```text
database transactions
unique constraints
row locking where justified
idempotency keys/event identifiers
compare-and-transition checks
```

Client-side button disabling is UX only and is never the concurrency guarantee.

## 11. Boss Challenge Contract

The term **assessment** in the accepted Phase 4 contract means the summative course-level practical assessment. Formative Knowledge Checks introduced elsewhere do not create a second Phase 4 assessment authority.

Phase 4 is complete and accepted. This master specification must not silently redesign its established scoring, retry, attempt-history, eligibility, pass/fail, completion, or unlock behavior.

When later phases need Boss Challenge behavior, they must consume the accepted Phase 4 implementation/tests/services rather than inventing a second assessment interpretation.

Any proposed change to Phase 4 behavior requires explicit approval and regression review.

## 12. Curriculum Publication and Versioning

Curriculum lifecycle should use explicit states such as:

```text
DRAFT
PUBLISHED
ARCHIVED
```

Draft curriculum is not student-visible.

Published curriculum with historical student activity should favor versioning or archival over destructive mutation.

Material changes include changes to:

```text
instructions
starter code/content
validation rules
mission points
required/optional status
skill mapping
Boss Challenge criteria
assessment pass requirements
```

Historical attempts retain the rule/version under which they were evaluated where practical.

Existing completion must never be silently invalidated.

Material curriculum changes must be auditable.

## 13. Canonical Domain/Data Model Planning

Before schema-heavy Phases 5–10, maintain a canonical ERD/database specification covering at minimum the responsibilities of:

```text
users
courses
sections
lessons if persisted separately
missions
mission versions where required
mission drafts
mission attempts
progress
xp transactions
Knowledge Checks
Knowledge Check questions/options or equivalent versioned definition
Knowledge Check attempts/responses/results
Boss Challenges / assessments
assessment attempts
course completion/progression
skills/concepts
mission-skill mapping
assessment-skill mapping where applicable
achievements
user achievements
activity/timeline events
competency source/mapping data
recommendation state only if persistence is justified
notifications
announcements
admin audit events
```

Not every concept requires its own table. Derived state should remain derived unless persistence has a clear reason.

## 14. Skill Mapping and Competency Ownership

Weak-skill identification requires explicit curriculum mapping.

Conceptually:

```text
Skill / Concept
↔ Knowledge Check question(s) where applicable
↔ Mission(s)
↔ Boss Challenge criteria where applicable
```

Competency architecture:

```text
Knowledge Check Results
+
Challenge Completion / Attempts
+
Boss Challenge Assessment Evidence
+
Curriculum Skill Mapping
        ↓
CompetencyService
        ↓
Student UI
Teacher Monitoring
Recommendations
Reports / Analytics
```

There must be one authoritative competency calculation service/path.

Before Phase 9 is accepted, the competency formula and denominator/weighting rules must be documented and tested. Percentages must never be invented independently in views or reports.

Phase 10 reports consume competency; they do not independently redefine it.

## 15. Achievement Contract

Achievements represent meaningful system-authoritative milestones.

Achievement definitions should be declarative and testable rather than arbitrary scripts.

Each achievement definition should specify, where applicable:

```text
key/name
criteria type
criteria parameters
one-time/repeatable behavior
XP reward if any
visibility
active state
```

Granting must be idempotent.

Before implementing retroactive achievement grants, explicitly decide whether newly introduced achievements should scan historical authoritative data. Do not silently assume retroactive behavior.

## 16. Shared Domain Event Vocabulary

Use consistent event names so activity, achievements, notifications, recommendations, and analytics do not each invent separate meanings.

Initial vocabulary may include:

```text
KNOWLEDGE_CHECK_ATTEMPTED
KNOWLEDGE_CHECK_PASSED
KNOWLEDGE_CHECK_FAILED
MISSION_ATTEMPTED
MISSION_FAILED
MISSION_COMPLETED
XP_CHANGED
HINT_PURCHASED
SOLUTION_REVEALED
ASSESSMENT_UNLOCKED
ASSESSMENT_ATTEMPTED
ASSESSMENT_FAILED
ASSESSMENT_PASSED
COURSE_COMPLETED
NEXT_COURSE_UNLOCKED
ACHIEVEMENT_EARNED
CURRICULUM_PUBLISHED
SYSTEM_ANNOUNCEMENT_PUBLISHED
```

An event describes something that happened. It must not become an uncontrolled second source of truth.

## 17. Attention and Recommendation Configuration

Phase 6 attention rules and Phase 9 recommendation rules must use documented configuration rather than unrelated magic numbers scattered through services.

Configuration may include:

```text
ATTENTION_REPEATED_FAILURE_THRESHOLD
ATTENTION_ASSESSMENT_FAILURE_THRESHOLD
ATTENTION_INACTIVITY_DAYS
ATTENTION_STALLED_PROGRESS_DAYS
RECOMMENDATION_REVIEW_FAILURE_THRESHOLD
RECOMMENDATION_PRACTICE_FAILURE_THRESHOLD
RECOMMENDATION_RETRY_THRESHOLD
```

Exact values must be approved/documented before the relevant stories are accepted.

Attention and recommendation services may interpret the same authoritative performance data differently, but they must not duplicate or contradict the underlying state.

## 18. Notification Deduplication and Reminder Policy

Notifications must have a deduplication strategy for immutable events.

Conceptual uniqueness:

```text
recipient + event_type + source_type + source_id
```

where appropriate.

Learning reminders must define:

```text
trigger condition
minimum interval
maximum frequency
suppression condition
resolved condition
```

Do not create multiple unread reminders for the same unchanged condition unless explicitly designed.

## 19. Queue and Scheduler Boundary

Use Laravel scheduling/queues when work is delayed, periodic, retryable, or too expensive for the request/response path.

Possible candidates:

```text
learning reminders
large report exports
non-critical notification delivery
maintenance/reconciliation tasks
approved aggregate refreshes
```

Queue work must be idempotent where retries are possible.

The core learning transaction must not depend on an unreliable asynchronous job to become authoritative.

## 20. Analytics Metric Definitions

Every reported rate/average must define its numerator, denominator, population, and time range.

Examples that require exact definitions before acceptance:

```text
knowledge-check accuracy/pass rate
assessment pass rate
challenge completion rate
average attempts
retry count
active student count
stalled student count
course completion rate
```

Do not let each dashboard or export calculate a differently named version of the same metric.

## 21. Time and Timezone Rules

Store authoritative timestamps consistently, preferably UTC at the database/application boundary.

Display and academic date calculations use the configured application/user timezone as explicitly defined.

Inactivity, reminders, daily analytics, report filters, and timeline grouping must use the same documented timezone rules.

## 22. Abuse and Resource Limits

Security configuration should define reasonable limits for:

```text
student code/request size
submission frequency
login attempts
hint/solution reveal actions
report/export requests
announcement publishing actions
```

Limits must be high enough for normal learning but prevent accidental or abusive resource exhaustion.

## 23. Deletion, Archival, and Retention

Default behavior for data with academic/audit history should favor deactivation or archival over destructive deletion.

Examples:

```text
users with history         → deactivate unless approved deletion policy applies
published curriculum       → archive/version rather than destructive delete
attempts                   → retain
XP ledger                  → retain
assessment history         → retain
admin audit records        → retain/append-oriented
```

Foreign-key cascade choices must match these rules and be explicitly reviewed.

## 24. Administrative Audit Contract

Administrative audit events should answer:

```text
WHO
WHAT
WHEN
TARGET
RESULT
RELEVANT CONTEXT
```

Normal admin UI must not provide arbitrary edit/delete controls for historical audit events.

## 25. Error Contract

Service/API responses used by JavaScript should expose stable machine-readable error codes plus safe user messages.

Conceptual example:

```json
{
  "ok": false,
  "code": "INSUFFICIENT_XP",
  "message": "You do not have enough XP to reveal this solution."
}
```

Useful domain codes may include:

```text
MISSION_LOCKED
MISSION_ALREADY_COMPLETED
VALIDATION_FAILED
INSUFFICIENT_XP
ASSESSMENT_LOCKED
COURSE_LOCKED
STALE_STATE
DUPLICATE_ACTION
FORBIDDEN
RATE_LIMITED
```

Internal exceptions, SQL errors, secrets, hidden validators, and stack traces must not be exposed to students.

## 26. Test Strategy

Use multiple test layers as appropriate:

```text
Unit tests
Domain/service tests
Feature/HTTP tests
Authorization tests
Database/integrity tests
Cross-module integration tests
Security regression tests
End-to-end acceptance tests
```

Test counts are evidence only. Acceptance depends on required behavior being proven, not on maximizing assertion totals.

Maintain deterministic fixtures/seed scenarios such as:

```text
student_fresh
student_mid_course
student_boss_unlocked
student_course_completed
student_low_xp
student_with_failures
teacher
authorized_teacher_with_students
admin
operator
```

Test data must not contaminate production data.

## 27. Accessibility Contract for CRT UI

The visual identity must respect accessibility requirements.

At minimum:

```text
keyboard navigation
visible focus
semantic labels
sufficient contrast
reduced-motion support
no required flicker
scanlines/glow never obscure editor or lesson text
responsive zoom/reflow
CodeMirror keyboard accessibility
```

`prefers-reduced-motion` should disable or significantly reduce non-essential CRT animations/flicker.

## 28. Definition of Ready

Before a story that changes domain behavior or schema begins implementation, confirm:

```text
requirements are resolved
authoritative source of truth is identified
authorization is defined
schema impact is identified
migration/data impact is reviewed
transaction/idempotency impact is considered
security/privacy impact is considered
test plan is known
dependencies are known
open product-rule questions are resolved or explicitly deferred
```

A story with unresolved major behavior should not silently invent the missing product rule during coding.

## 29. Deployment and Rollback Contract

Production release flow:

```text
PRE-DEPLOYMENT VERIFICATION
→ DATABASE BACKUP
→ VERIFY BACKUP
→ RECORD CURRENT RELEASE
→ REVIEW MIGRATIONS
→ DEPLOY APPLICATION
→ RUN APPROVED MIGRATIONS
→ VERIFY DATA
→ RUN SMOKE / SECURITY / INTEGRITY CHECKS
→ ACCEPT RELEASE
```

If verification fails:

```text
STOP/ISOLATE AFFECTED TRAFFIC OR USE MAINTENANCE MODE
→ PRESERVE LOGS / FAILURE EVIDENCE
→ ROLLBACK APPLICATION RELEASE
→ DETERMINE DATABASE COMPATIBILITY
→ APPLY APPROVED DOWN/RESTORE PROCEDURE ONLY IF REQUIRED
→ VERIFY DATA INTEGRITY
→ RUN SMOKE TESTS
→ RESTORE SERVICE
```

Database restoration/destructive rollback is never automatic and requires explicit approval.

Record release metadata such as:

```text
release identifier
Git commit/tag
deployment timestamp
migration set
environment
operator
verification result
rollback result if applicable
```

## 30. Invariant Traceability

Maintain a trace from major rules to the services/phases that enforce them.

Examples:

```text
Server-side validation authoritative
→ Phase 3
→ MissionValidationService

XP never below zero / XP auditable
→ Phase 3 + Phase 5
→ XpService

Boss Challenge gates course completion
→ Phase 4
→ accepted assessment/progression services

Competency derives from real data
→ Phase 5 + Phase 9
→ CompetencyService

Notifications do not create learning state
→ Phase 8
→ NotificationService

Reports reuse authoritative calculations
→ Phase 10
→ Reporting layer consuming domain services
```

Keep the final names aligned with the actual implementation rather than forcing these example class names if the accepted architecture uses different names.

---

# Phase 1 — Design System & Application Shell

## Objective
Establish the visual foundation and reusable application shell.

## Scope
- global layout
- role-aware navigation shell
- responsive structure
- typography
- CRT styling
- phosphor green visual system
- buttons
- status indicators
- progress bars
- panels
- alerts
- loading/focus states
- reusable Blade components
- light mode support

## Visual Direction
Preserve `--phosphor: #33ff00`, monospace typography, scanlines, glow, terminal panels, and System 404 messaging.

Avoid generic SaaS styling, purple/blue AI dashboards, glassmorphism, excessive rounded cards, and meaningless icons.

The learning interface must visually distinguish teaching content, examples, Try It Yourself areas, formative Knowledge Checks, Challenges, locked nodes, completed nodes, Boss Challenges, feedback, and authoritative system state without abandoning the CRT identity.

## Canonical Student Experience & Interface Contract

This section is authoritative for the primary student-facing learning experience.

CodeQuest combines ideas from Boot.dev, W3Schools, and freeCodeCamp, but their interfaces must **not be rendered as competing full experiences on the same screen**.

The design rule is:

```text
BOOT.DEV-INSPIRED EXPERIENCE
answers: WHERE AM I? WHAT IS NEXT? WHAT HAVE I UNLOCKED?
→ Learning Path screen

W3SCHOOLS-INSPIRED EXPERIENCE
answers: WHAT AM I LEARNING? HOW DOES IT WORK? SHOW ME A SIMPLE EXAMPLE.
→ Lesson screen

KNOWLEDGE-CHECK EXPERIENCE
answers: DO I UNDERSTAND THE CONCEPT BEFORE I APPLY IT?
→ Short formative Knowledge Check when configured

FREECODECAMP-INSPIRED EXPERIENCE
answers: WHAT MUST I BUILD? WHERE DO I WRITE? WHAT HAPPENED WHEN I RAN/SUBMITTED IT?
→ Challenge Workspace

CODEQUEST-OWNED EXPERIENCE
answers: DID I AUTHORITATIVELY PASS? WHAT XP/PROGRESS/COMPETENCY CHANGED? WHAT IS MY NEXT ACADEMIC STEP?
→ Validation, Result, Progression, Boss Challenge, Competency, Monitoring

SYSTEM 404
answers: WHAT DOES CODEQUEST FEEL LIKE?
→ Visual identity across all screens
```

The experiences are sequential and context-preserving, not simultaneously maximized.

### Non-Negotiable Focus Rule

The following must never appear as three full-size competing regions during ordinary student use:

```text
FULL LEARNING PATH
+
FULL LESSON ARTICLE
+
FULL CHALLENGE EDITOR
```

Do not build a permanent three-column student screen that tries to show all three at once.

When a student enters a new stage, the previous stage yields visual priority.

Allowed carry-over context is intentionally lightweight:

```text
breadcrumb
course/section/challenge title
progress percentage
XP balance
small objective summary
requirements
Back to Path / Back to Lesson action
```

The full Learning Path should not remain beside the editor. The full lesson article should not remain beside the editor. The student should be able to focus on the current cognitive task.

---

### Canonical Student Screen Map

The normal student journey is:

```text
DASHBOARD
   ↓
LEARNING PATH
   ↓
LESSON
   ↓
TRY / EXPERIMENT
   ↓
KNOWLEDGE CHECK (WHEN CONFIGURED)
   ↓
START CHALLENGE
   ↓
CHALLENGE WORKSPACE
   ↓
FAILURE FEEDBACK / RETRY
        OR
SUCCESS RESULT
   ↓
CONTINUE
   ↓
UPDATED LEARNING PATH
   ↓
NEXT LESSON / CHALLENGE
   ↓
REQUIRED CHALLENGES COMPLETE
   ↓
BOSS CHALLENGE
   ↓
COURSE COMPLETION
```

A student may navigate backward where permitted, but authoritative progression remains server controlled.

---

## Screen A — Student Dashboard

### Purpose

The Dashboard answers:

```text
Where should I continue?
What course am I currently working on?
How much progress have I made?
Is there anything important that needs my attention?
```

It is not the main learning-path map and not the coding workspace.

### Recommended Information Hierarchy

The dashboard should prioritize:

1. **Continue Learning** — the strongest call to action.
2. Current course and current learning position.
3. Overall/course progress.
4. XP and meaningful achievements.
5. Assessment/Boss Challenge state when relevant.
6. Important notification or recommendation summaries.
7. Recent meaningful learning activity.

Conceptual example:

```text
┌───────────────────────────────────────────────────────────────┐
│ CODEQUEST                                      XP 450   [USER]│
├───────────────────────────────────────────────────────────────┤
│ CURRENT OPERATION                                               │
│ HTML FUNDAMENTALS                                               │
│ Section 02 — Text Elements                                      │
│ Next: Paragraphs                                                │
│ Progress: ██████████░░░░ 48%                                    │
│                                                               │
│                     [ CONTINUE LEARNING ]                       │
├───────────────────────────────┬───────────────────────────────┤
│ RECENT PROGRESS               │ STATUS                         │
│ ✓ Headings                    │ Boss Challenge: LOCKED         │
│ ✓ Elements                    │ Competency: Developing         │
│ +50 XP this course            │ 1 recommendation               │
└───────────────────────────────┴───────────────────────────────┘
```

The Dashboard must not become a dense analytics page for students.

---

## Screen B — Learning Path

### Inspiration

Primarily Boot.dev-inspired progression, expressed through CodeQuest's own CRT/System 404 identity.

### Purpose

The Learning Path answers:

```text
Where am I in the course?
What have I completed?
What can I do now?
What is locked?
What is required before the Boss Challenge?
What comes after this?
```

This is the primary progression/navigation screen.

### Information Hierarchy

The student should understand the following within a few seconds:

1. Course identity.
2. Course completion percentage.
3. Current section.
4. Current node.
5. Completed nodes.
6. Available nodes.
7. Locked future nodes.
8. Boss Challenge location and eligibility state.

### Conceptual Layout

```text
┌───────────────────────────────────────────────────────────┐
│ ← COURSES      HTML FUNDAMENTALS             XP: 450      │
│ Progress: 48%                                             │
├───────────────────────────────────────────────────────────┤
│                                                           │
│ SECTION 01 // FOUNDATIONS                                 │
│                                                           │
│                    [✓] Introduction                        │
│                         │                                  │
│                    [✓] Elements                            │
│                         │                                  │
│                    [✓] Headings                            │
│                         │                                  │
│                    [●] Paragraphs                          │
│                         │                                  │
│                    [○] Links                               │
│                         │                                  │
│                    [🔒] Images                              │
│                                                           │
│ SECTION 02 // STRUCTURE                                   │
│                         │                                  │
│                    [🔒] Lists                               │
│                         │                                  │
│                    [🔒] Forms                               │
│                         │                                  │
│               ┌──────────────────┐                        │
│               │ 🔒 BOSS CHALLENGE│                        │
│               └──────────────────┘                        │
│                                                           │
└───────────────────────────────────────────────────────────┘
```

Emoji are only conceptual notation in the specification. The actual implementation should use CodeQuest icons/components and text labels.

### Node States

Every node must have a textual/state representation and must not rely on color alone.

Canonical conceptual states:

```text
COMPLETED
CURRENT
AVAILABLE
LOCKED
OPTIONAL        only if optional content is approved
BOSS_LOCKED
BOSS_AVAILABLE
BOSS_PASSED
```

Examples of student-facing state treatment:

```text
[✓] COMPLETED // HEADINGS
[●] CURRENT   // PARAGRAPHS
[○] AVAILABLE // LINKS
[LOCKED]      // IMAGES
[BOSS LOCKED] // FINAL ASSESSMENT
```

### Node Interaction

Selecting a valid available/current lesson node opens the Lesson screen.

Selecting a locked node must not silently fail. It should explain the gate in student-safe language, such as:

```text
LOCKED
Complete "Paragraphs" before continuing to "Links".
```

The client does not decide whether a node is truly unlocked. Server state is authoritative.

### Learning Path Density

The path should feel motivating and game-like, but it must remain readable.

Do not turn every learning item into a noisy badge, animation, particle effect, or oversized card.

The strongest visual states should be:

```text
current
newly completed
newly unlocked
Boss Challenge unlocked
course completed
```

---

## Screen C — Lesson

### Inspiration

Primarily W3Schools-inspired clarity plus freeCodeCamp-style movement toward immediate practice.

### Purpose

The Lesson answers:

```text
What is this concept?
Why does it matter?
What does the syntax look like?
Can I see a simple working example?
Can I experiment before I am assessed on it?
```

The Lesson is not the final submission workspace.

### Lesson Content Structure

Recommended order:

```text
Breadcrumb
Lesson Title
What You Will Learn
Short Concept Explanation
Syntax / Pattern
Simple Example
Example Explanation
Try It Yourself
Common Mistake / Note
Challenge Readiness
Start Challenge
```

### Conceptual Layout

```text
┌──────────────────────────────────────────────────────────────┐
│ ← PATH   HTML > TEXT ELEMENTS > PARAGRAPHS       XP: 450    │
├──────────────────────────────────────────────────────────────┤
│ HTML PARAGRAPHS                                               │
│                                                              │
│ WHAT YOU WILL LEARN                                           │
│ Use the <p> element to create paragraphs of text.             │
│                                                              │
│ CONCEPT                                                       │
│ The <p> element represents a paragraph in an HTML document.  │
│ Browsers normally add spacing around paragraphs.              │
│                                                              │
│ SYNTAX                                                        │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ <p>Your text here</p>                                    │ │
│ └──────────────────────────────────────────────────────────┘ │
│                                                              │
│ EXAMPLE                                                       │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ <p>System online.</p>                                    │ │
│ └──────────────────────────────────────────────────────────┘ │
│                                                              │
│ [ TRY IT YOURSELF ]                                          │
│                                                              │
│ COMMON MISTAKE                                                │
│ Do not forget the closing </p> tag.                           │
│                                                              │
│                         [ START CHALLENGE ]                    │
└──────────────────────────────────────────────────────────────┘
```

### Lesson Length

Prefer short sections, concrete examples, and progressive disclosure.

Avoid long passive textbook pages when the same idea can be explained in a few paragraphs and immediately practiced.

If a topic genuinely requires more explanation, break it into digestible sub-sections rather than creating one giant wall of text.

### Navigation

The Lesson should provide:

```text
Back to Path
Previous Lesson, where valid
Try It Yourself
Start Challenge
```

The strongest forward action should be `START CHALLENGE` once the student is ready.

---

## Screen D — Try It Yourself / Experiment Mode

### Purpose

Experiment Mode allows low-pressure manipulation of the concept before authoritative submission.

It answers:

```text
What happens if I change this?
Can I test the example myself?
Can I learn through experimentation without affecting completion?
```

### Rules

Experiment Mode:

```text
MAY run preview/output
MAY allow editable example code
MAY preserve temporary draft state where useful
DOES NOT complete the Challenge
DOES NOT award Challenge XP
DOES NOT unlock progression
DOES NOT replace server validation
```

### Presentation

It may appear as an expanded editor within the Lesson or as a focused experiment sub-screen.

It must never be visually confused with authoritative `SUBMIT CHALLENGE`.

Use language such as:

```text
TRY
RUN
PREVIEW
RESET EXAMPLE
```

Do not use `PASS`, `COMPLETE`, or equivalent authoritative language for experiment-only actions.

---

## Screen D2 — Knowledge Check

### Purpose

The Knowledge Check is a short, focused formative screen used to verify conceptual understanding before or between practical Coding Challenges. It should feel integrated with the Lesson, not like entering a separate examination website.

It answers:

```text
Do I understand the concept I just learned?
Which idea do I need to review before coding?
```

### Presentation

Preferred structure:

```text
← BACK TO LESSON                         HTML / HEADINGS

KNOWLEDGE CHECK
Question 2 of 5

Which element represents the highest-level HTML heading?

( ) <h1>
( ) <head>
( ) <header>
( ) <h6>

                     [ SUBMIT ANSWER ]
```

After authoritative submission, show concise student-safe feedback. Do not expose the hidden answer key or future unanswered questions unnecessarily.

### UX Rules

- Knowledge Checks are separate from the full Learning Path and separate from the Coding Challenge Workspace.
- Keep the screen compact and low-friction; it is formative, not a high-stakes examination ceremony.
- Progress such as `Question 2 of 5` may be displayed.
- The server determines trusted correctness/result.
- If retry is allowed, the UI must explain the retry state without implying the previous attempt disappeared.
- Knowledge Check results may update learning feedback/competency evidence, but they do not complete a course or replace practical Coding Challenges.
- After completion, the primary action should normally be `CONTINUE TO CHALLENGE`, `CONTINUE LESSON`, or another explicitly valid next learning node.

## Screen E — Challenge Workspace

### Inspiration

Primarily freeCodeCamp-inspired focused coding, adapted to CodeQuest's own learning and security rules.

### Purpose

The Challenge Workspace answers:

```text
What exactly must I build?
Where do I write my solution?
How can I preview/run it?
What feedback did I receive?
How do I submit it for authoritative validation?
```

Once the student enters this screen, the full Learning Path and the full Lesson article are removed from the main workspace.

### Challenge Context Header

Keep context compact:

```text
← BACK
HTML FUNDAMENTALS > TEXT ELEMENTS > PARAGRAPHS
MISSION 04
XP: 450
Draft: Saved / Saving / Unsaved
```

The header should not contain unrelated dashboard metrics.

### Desktop Workspace

Preferred conceptual composition:

```text
┌──────────────────────────────────────────────────────────────────────┐
│ ← BACK  HTML > TEXT > PARAGRAPHS              XP 450   DRAFT SAVED │
├───────────────────────────────┬──────────────────────────────────────┤
│ MISSION 04 // PARAGRAPHS      │ EDITOR                               │
│                               │                                      │
│ OBJECTIVE                     │ 1  <p>...</p>                         │
│ Create two paragraphs.        │ 2                                    │
│                               │ 3                                    │
│ REQUIREMENTS                  │                                      │
│ □ Create the first paragraph  │                                      │
│ □ Create the second paragraph │                                      │
│ □ Use valid paragraph tags    │                                      │
│                               │                                      │
│ [ HINT ]                      │                                      │
├───────────────────────────────┼──────────────────────────────────────┤
│ FEEDBACK / STATUS             │ PREVIEW / OUTPUT                      │
│ Awaiting submission           │                                      │
│                               │ rendered result                      │
│                               │                                      │
│                               │ [ RUN / PREVIEW ]  [ SUBMIT ]        │
└───────────────────────────────┴──────────────────────────────────────┘
```

This does not mean every implementation must use equal-width quadrants. The editor should usually receive the largest practical working area.

### Workspace Priorities

During a Challenge, visual priority is:

```text
1. Code editor / active coding task
2. Mission objective and requirements
3. Run/Preview and Submit actions
4. Validation feedback
5. Preview/output
6. Draft/save state
7. Secondary XP/progression context
```

The interface must not force the student to constantly scroll between requirements and the editor on normal desktop widths.

### Instructions Pane

The Challenge instructions pane should contain only information needed to solve the current Challenge:

```text
Challenge title
Objective
Requirements
Relevant constraints
Hint access
Student-safe feedback
```

It should not repeat the entire preceding Lesson article.

A small `Review Lesson` action may return the student to the Lesson without losing their Challenge draft.

### Code Editor

CodeMirror is the primary work surface.

Requirements:

```text
line numbers
syntax highlighting
indentation
bracket matching
keyboard support
clear focus state
accessible contrast
responsive sizing
preserved draft
CRT styling that never harms readability
```

Avoid decorative scanlines/glow directly over code if they reduce legibility.

### Run / Preview vs Submit

These controls must be unmistakably different in meaning.

`RUN / PREVIEW` means:

```text
show me what my current code does
```

`SUBMIT` means:

```text
send my current solution to the authoritative server validator
```

Preview must never create completion, XP, unlocks, or assessment results.

The Submit action should have greater semantic weight than Run/Preview but should not be dangerously easy to trigger accidentally.

### Save Draft

Draft saving must not be confused with submission.

Useful status text:

```text
SAVING...
DRAFT SAVED
UNSAVED CHANGES
SAVE FAILED — RETRY
```

Draft loss during normal navigation, panel changes, or responsive-layout changes is unacceptable.

### Preview / Output / Feedback

The output region may support contextual modes:

```text
PREVIEW
OUTPUT
FEEDBACK
```

Web content typically uses Preview.

Future language/course types may emphasize Output or test feedback instead.

Protected validator rules, hidden regexes, solution code, and assessment internals are never displayed.

---

## Screen F — Failed Submission State

### Purpose

A failed submission should help the student recover quickly without humiliating them or throwing them out of the workspace.

The student remains in the Challenge Workspace.

### Required Feedback

Show, where applicable:

```text
SUBMISSION FAILED
student-safe reason(s)
requirements currently satisfied
requirements still incomplete
XP effect, when a penalty actually occurred
Retry action / return focus to editor
```

Conceptual example:

```text
> VALIDATION FAILED

✓ First paragraph detected
✗ Second paragraph not found

XP CHANGE: -10
CURRENT XP: 440

Fix the remaining requirement and submit again.
```

Do not expose hidden validator implementation details.

### UX Behavior

After failure:

```text
keep current code
keep current preview
keep student in the Challenge
focus feedback accessibly
allow immediate editing and retry
```

Do not send the student back to the Learning Path after each failure.

---

## Screen G — Successful Submission / Mission Complete

### Purpose

Success deserves a distinct transition state because completion is one of the main motivational moments in CodeQuest.

Do not immediately replace the page with an unrelated dashboard.

### Information to Show

```text
MISSION COMPLETE
Challenge title
validation passed
XP earned, if first completion reward applies
updated XP balance
course/section progress change
achievement unlocked, only when applicable
newly unlocked learning item, when applicable
primary Continue action
secondary Review Solution/Code action only if appropriate
```

Conceptual example:

```text
┌──────────────────────────────────────────────┐
│ > MISSION COMPLETE                           │
│                                              │
│ HTML PARAGRAPHS                              │
│ VALIDATION ............ PASSED               │
│ XP EARNED ............. +25                  │
│ TOTAL XP .............. 465                  │
│ COURSE PROGRESS ....... 48% → 52%            │
│                                              │
│ NEXT NODE UNLOCKED: LINKS                    │
│                                              │
│              [ CONTINUE ]                    │
└──────────────────────────────────────────────┘
```

### Continue Behavior

For an ordinary Challenge, `CONTINUE` normally returns to the Learning Path with the newly completed/unlocked state visible.

This reinforces progression:

```text
Challenge Pass
→ Success State
→ Continue
→ Learning Path visibly updates
→ next current/available node becomes obvious
```

Do not auto-launch the next Challenge before the student can see that progression changed, unless a separately approved streamlined mode is introduced later.

---

## Screen H — Updated Learning Path After Completion

When returning from a successful Challenge, the Learning Path should visually communicate the result without becoming excessive.

Example:

```text
        [✓] Headings
             │
        [✓] Paragraphs   ← newly completed
             │
        [●] Links        ← newly current/available
             │
        [🔒] Images
```

A short one-time transition may highlight a newly completed or unlocked node.

Animations must be restrained, respect reduced-motion preferences, and must not delay navigation.

---

## Screen I — Boss Challenge Entry and Workspace

### Entry

When all required Challenges are complete, the Learning Path may present the Boss Challenge as a major milestone.

Locked state should explain remaining requirements.

Unlocked state should feel significant but academically serious.

Conceptual transition:

```text
REQUIRED CHALLENGES COMPLETE
        ↓
BOSS CHALLENGE UNLOCKED
        ↓
[ ENTER BOSS CHALLENGE ]
```

### Workspace

The Boss Challenge may reuse the focused Challenge Workspace shell, but it must be clearly identified as formal course-level assessment.

It must not be visually confused with ordinary practice.

Recommended distinctions include:

```text
BOSS CHALLENGE label
assessment instructions
assessment status
attempt information where allowed
clear Submit Assessment action
reduced casual/hint mechanics according to accepted Phase 4 rules
```

The accepted Phase 4 domain behavior remains authoritative.

---

## Global Student Navigation Rules

### Back Navigation

Provide explicit navigation such as:

```text
Back to Path
Back to Lesson
Review Lesson
Exit Challenge
```

When leaving a Challenge with unsaved work, follow the draft contract and avoid accidental data loss.

### Resume Learning

When the student returns later, `Continue Learning` should resolve to the most meaningful resumable state using authoritative progression/draft state.

Examples:

```text
unfinished Challenge with draft → Challenge Workspace
current lesson not yet challenged → Lesson
completed node with next unlocked → Learning Path / next lesson entry
Boss Challenge unlocked → Learning Path highlighting Boss milestone, unless product flow explicitly resumes assessment
```

Do not derive resume state from client-only memory.

### Breadcrumbs

Use breadcrumbs for context, not as a replacement for the Learning Path.

Example:

```text
HTML FUNDAMENTALS > TEXT ELEMENTS > PARAGRAPHS
```

They should be compact and actionable where appropriate.

---

## Responsive Student Experience

### Desktop

Desktop may show multiple panels **within the same current mode**, such as Challenge instructions + editor + preview.

It must not restore the prohibited full Path + full Lesson + full Editor composition.

### Tablet

Prioritize editor space.

Potential pattern:

```text
Challenge Header
[INSTRUCTIONS] [CODE] [PREVIEW] [FEEDBACK]
Active panel
Persistent Run / Submit controls where appropriate
```

A split instructions/editor view may be used in landscape if both remain comfortable.

### Mobile

On mobile, do not squeeze desktop panels into miniature columns.

Recommended Challenge navigation:

```text
[ TASK ] [ CODE ] [ PREVIEW ] [ FEEDBACK ]
```

The active panel fills the usable width.

The Learning Path remains a separate screen.

The Lesson remains a separate scrollable teaching screen.

CodeMirror must remain usable with the software keyboard visible.

Critical actions must not be obscured behind the keyboard or browser chrome.

### Responsive State Preservation

Changing orientation or viewport must not:

```text
discard drafts
reset editor content
clear feedback unnecessarily
cause authoritative re-submission
change completion state
```

---

## System 404 Visual Application

The CRT identity applies across all screens, but its intensity should adapt to the task.

### Learning Path

Can use stronger game-like terminal presentation:

```text
node rails
system markers
unlock pulses
status labels
mission codes
```

### Lesson

Use calmer typography and generous reading space.

CRT decoration should frame the material, not sit over every paragraph.

### Challenge Workspace

Prioritize editor readability above visual effects.

The editor, feedback, and preview must remain crisp and functional.

### Success State

May use a stronger System 404 moment:

```text
MISSION STATUS: COMPLETE
VALIDATION: PASSED
ACCESS NODE: UNLOCKED
```

without excessive flashing or motion.

### Failure State

Use clear warning/error treatment without making the screen feel punitive or alarming beyond what is necessary.

---

## Student-Facing Language Rules

Use consistent terminology:

```text
Mission          = internal/domain term where appropriate
Challenge        = normal student-facing practice term
Boss Challenge   = formal course-level practical assessment
Lesson           = teaching unit
Learning Path    = progression map
Run / Preview    = non-authoritative execution/visualization
Submit           = authoritative Challenge validation request
Draft            = unfinished saved work
Completed        = authoritative passed Challenge state
Locked           = unavailable due to progression rules
```

Do not casually interchange `Run`, `Submit`, `Save`, and `Complete`.

---

## Interaction Feedback Rules

Every significant action needs immediate visible feedback.

Examples:

```text
Run clicked            → preview/output loading/result
Save in progress       → SAVING...
Save successful        → DRAFT SAVED
Submit in progress     → VALIDATING...
Submit failed          → student-safe feedback
Submit passed          → success state
Hint purchased         → XP effect + hint reveal
Node locked            → reason
Network failure        → recoverable error state
```

Disable or guard duplicate destructive/state-changing actions while the authoritative request is in flight, while also enforcing server-side idempotency.

---

## Accessibility Contract for the Student UI

The visual identity must never make the application unusable.

Required considerations:

```text
keyboard navigation
visible focus
semantic headings and controls
labels for editor-adjacent actions
non-color state indicators
reduced motion
sufficient contrast
screen-reader understandable validation feedback
usable zoom
responsive text
editor accessibility
no critical information encoded only in animation
```

CRT flicker, glow, scanlines, and animation must be reduced or disabled when required by accessibility settings/preferences.

---

## Explicit Student UI Anti-Patterns

Do not implement:

```text
permanent full Learning Path beside the code editor
permanent full Lesson article beside the code editor
three cramped primary columns at ordinary laptop widths
a generic admin-dashboard card grid as the learning experience
huge amounts of decorative metrics during coding
preview styled as if it were submission success
client-only fake unlock animations that disagree with server state
locked nodes with no explanation
success that immediately disappears before the student sees progress
mobile layouts that require horizontal page scrolling
CRT effects that reduce editor/text readability
```

---

## Visual Hierarchy by Screen

```text
DASHBOARD
1. Continue Learning
2. Current course/progress
3. important state
4. secondary history/gamification

LEARNING PATH
1. Current node
2. completed/available/locked relationships
3. Boss Challenge
4. XP/course progress
5. secondary metadata

LESSON
1. concept title/objective
2. explanation/example
3. Try It Yourself
4. Start Challenge
5. secondary notes

CHALLENGE
1. editor/current task
2. requirements
3. Run/Submit
4. feedback
5. preview/output
6. secondary progression context

SUCCESS
1. completion confirmation
2. XP/progress change
3. newly unlocked state
4. Continue

FAILURE
1. actionable feedback
2. unmet requirements
3. return to editing/retry
4. XP effect if applicable
```

---

## Student UX Acceptance Scenarios

The UI is not considered complete merely because all routes render.

At minimum test these scenarios:

### Scenario 1 — Fresh Student

```text
login
→ Dashboard
→ Continue Learning
→ Learning Path
→ first available Lesson
→ Try It Yourself
→ Start Challenge
→ write code
→ Run
→ Submit
→ Pass
→ Success
→ Continue
→ updated Learning Path
```

### Scenario 2 — Failed Submission

```text
Challenge
→ invalid code
→ Submit
→ authoritative failure
→ student-safe feedback
→ code preserved
→ edit
→ Submit again
→ Pass
```

### Scenario 3 — Resume Draft

```text
Challenge
→ write partial solution
→ draft saved
→ leave
→ return later
→ Continue Learning
→ Challenge reopens
→ draft restored
```

### Scenario 4 — Locked Progression

```text
Learning Path
→ select locked node
→ reason shown
→ no client-side bypass
```

### Scenario 5 — Boss Unlock

```text
complete final required Challenge
→ Success
→ Continue
→ Learning Path
→ Boss Challenge visibly unlocked
→ enter formal assessment
```

### Scenario 6 — Mobile Challenge

```text
open Challenge on 360–430px viewport
→ Task readable
→ Code usable with keyboard
→ Preview reachable
→ Feedback reachable
→ Submit reachable
→ no horizontal page overflow
→ draft preserved across tabs/orientation
```

---

## Responsive Targets

`360px`, `390px`, `430px`, tablet, desktop.

No unintended horizontal scrolling.

## Definition of Done
- reusable application shell exists
- visual language is consistent
- responsive layouts work
- light mode works
- no business logic is embedded in views
- tests/formatting status are reported honestly


---

# Phase 2 — Laravel / Backend Foundation

## Objective
Establish Laravel architecture and safely integrate the existing MariaDB schema.

## Stack
- Laravel 13
- PHP 8.5
- MariaDB 12.x
- Blade
- JavaScript
- Vite

## Architecture

```text
HTTP Request
→ Route
→ Middleware / Authorization
→ Controller
→ Service
→ Repository / Eloquent
→ Model
→ MariaDB
```

Controllers remain thin. Services own behavior. Repositories own data access.

Cross-cutting domain actions must follow the Global Domain Contracts for transactions, idempotency, authorization, errors, auditability, and source-of-truth ownership.

## Existing Model Mapping

```text
User → the404_users
Course → the404_courses
Section → the404_sections
Mission → the404_missions
Progress → the404_progress
Activity → the404_activity
MissionDraft → the404_mission_drafts
XpTransaction → the404_xp_transactions
```

## Roles
`student`, `teacher`, `admin`, `operator`.

Do not remove `operator`.

## Database Safety
Never run `php artisan migrate` against the real `system404` database without explicit approval.

## Security Foundation
- authentication
- role authorization
- CSRF
- safe validation
- no trust in client `user_id`, role, points, unlock, or completion
- never expose password hashes or secrets

## Definition of Done
- DB connectivity works
- models correctly map legacy tables
- authentication works
- role handling works
- architecture is established
- legacy data is preserved


---

# Phase 3 — Student Learning & Mission System

## Epic
Student Learning & Mission System

## Product Intent
Phase 3 is where the W3Schools-style teaching layer, freeCodeCamp-style practice layer, Boot.dev-style progression layer, and System 404 identity meet in the student experience.

The student should not jump from a course title directly into an unexplained code editor. The normal flow is:

```text
LEARN THE CONCEPT
→ SEE A SMALL EXAMPLE
→ TRY / MODIFY AN EXAMPLE
→ ENTER A CHALLENGE
→ WRITE CODE
→ PREVIEW
→ SUBMIT
→ RECEIVE SERVER-AUTHORITATIVE FEEDBACK
→ RETRY OR PASS
→ PROGRESS
```

## Stories

```text
US-301 Course and Section Learning Navigation
US-302 Lesson / Challenge Learning Experience
US-303 CodeMirror Challenge Editor
US-304 Live Code Preview
US-305 Mission Submission and Server Validation
US-306 Failed Submission + XP Penalty
US-307 Successful Submission + XP Reward
US-308 Mission Progress
US-309 Mission Draft Persistence
US-310 Hint XP Spending
US-311 Solution Reveal XP Spending
US-312 Next Challenge Progression
US-313 Course Progress Summary
US-314 End-to-End Learning Flow
US-315 Formative Knowledge Checks
```

The existing story identifiers remain stable. The richer lesson anatomy is part of `US-302`, not a renumbering of the accepted story map.

## Learning Hierarchy

```text
Learning Path
→ Course
→ Section
→ Lesson
→ Learn / Example / Try
→ Knowledge Check(s) where configured
→ Mission / Challenge
→ Progress / Draft / Attempts
```

## Lesson Experience

Lessons should be concise and practice-oriented.

Recommended structure:

```text
Title
What You Will Learn
Short Explanation
Syntax / Pattern
Example
Example Explanation
Try It Yourself
Common Mistake / Note
Challenge
```

Long passive textbook-style pages should be avoided when the same concept can be taught through a short explanation and immediate coding activity.

`Try It Yourself` is exploratory. It may use the preview/editor experience but does not create authoritative completion unless explicitly linked to a Challenge submission.

## Formative Knowledge Checks

Knowledge Checks are integrated from the useful assessment capability of the existing online quiz/examination system, but are presented as a native part of CodeQuest rather than a separate quiz application.

They are primarily used to check concept understanding before or between practical Coding Challenges.

Example learning sequence:

```text
Lesson: HTML Headings
→ explanation
→ example
→ Try It Yourself
→ Knowledge Check
→ Coding Challenge
```

Knowledge Checks may be optional or required at a local lesson/section level when explicitly configured. Regardless of local configuration, they do not replace the required practical Coding Challenges that gate the Boss Challenge.

Knowledge Check scoring, attempts, answer protection, feedback, skill mapping, and teacher visibility follow the Global Knowledge Check Contract.

## Learning Path Presentation

The Learning Path is a dedicated primary screen. It is the Boot.dev-inspired progression experience and should not remain as a full navigation column inside the Challenge Workspace.

Its job is to communicate:

```text
current position
completed nodes
available nodes
locked nodes
section boundaries
course progress
Boss Challenge position/state
newly unlocked progression
```

Selecting an available/current node opens the corresponding Lesson.

When the student later enters the Challenge Workspace, the full path is replaced by compact context such as a breadcrumb and `Back to Path` action.

Canonical sequence:

```text
Learning Path
→ Lesson
→ Knowledge Check when configured
→ Challenge Workspace
→ Failure/Retry OR Success
→ Continue
→ Updated Learning Path
```

The updated Learning Path should make the completion/unlock transition visible so progression itself becomes part of the reward loop.

Example conceptual states:

```text
[✓] COMPLETED NODE
[●] CURRENT NODE
[ ] AVAILABLE NODE
[LOCKED] LOCKED NODE
[BOSS LOCKED] BOSS CHALLENGE
[BOSS READY] BOSS CHALLENGE
```

The implementation must use accessible text/state indicators and CodeQuest visual components rather than relying only on color or emoji.

## Challenge Workspace Layout

The Challenge Workspace is a dedicated focused coding screen. It should feel freeCodeCamp-inspired in its emphasis on instructions + code + immediate preview/feedback, but it remains a CodeQuest interface.

The full Learning Path is **not** displayed beside it.

The full Lesson article is **not** displayed beside it.

Carry over only what is necessary to solve the Challenge:

```text
breadcrumb
challenge title
objective
requirements
constraints
hints
student-safe validation feedback
XP balance if useful
draft state
Back to Path / Review Lesson
```

Preferred desktop composition:

```text
┌───────────────────────────────┬──────────────────────────────────────┐
│ TASK / REQUIREMENTS           │ CODEMIRROR EDITOR                    │
│                               │                                      │
│ Objective                     │ student's code                       │
│ Requirement checklist         │                                      │
│ Hints                         │                                      │
│ Feedback summary              │                                      │
├───────────────────────────────┼──────────────────────────────────────┤
│ VALIDATION / STATUS           │ PREVIEW / OUTPUT                      │
│                               │                                      │
│ current student-safe result   │ rendered/output result               │
│                               │ [ RUN / PREVIEW ]   [ SUBMIT ]       │
└───────────────────────────────┴──────────────────────────────────────┘
```

The editor should normally receive the largest practical working area. The exact panel proportions may change by challenge type and viewport.

On narrower screens, use tabs/stacked panels such as:

```text
[ TASK ] [ CODE ] [ PREVIEW ] [ FEEDBACK ]
```

The current draft must survive panel switching, responsive changes, and navigation to `Review Lesson`/`Back` according to the draft contract.

The student should be able to read the current requirement while coding, but should not be forced to process the entire Lesson or Learning Path simultaneously.

## Code Editor
Use CodeMirror 6 with syntax highlighting, line numbers, indentation, bracket matching, keyboard support, responsive behavior, accessible focus, and CRT styling.

Students write the target code themselves. Starter code may be used only when it serves a deliberate teaching objective and the authoritative requirements still require meaningful student work.

## Preview

```text
CodeMirror
→ sandboxed iframe
→ srcdoc
→ instant preview
```

Preview follows the Global Preview Sandbox Contract.

Preview is not proof of completion.

## Server Validation
Submission is validated by Laravel/server-side rules using the Global Mission Validation DSL.

Allowed initial validation concepts include:
`contains`, `contains_all`, `contains_any`, `count_tag`, `count`, `regex`, `exact_normalized`.

The student receives useful, safe feedback but never protected validator internals.

Never use `eval`, `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, or `popen` to execute student submissions.

## Attempts
Each authoritative submission creates or records a historical attempt according to the Global Attempt Lifecycle.

Retries create new attempts. Historical attempt results are not rewritten to make a later retry appear as the original result.

## Progress
Conceptual states:
`NOT STARTED`, `IN PROGRESS`, `COMPLETED`.

Saving code does not complete a mission.

The first authoritative pass transitions the mission to `COMPLETED`. Re-submission does not create duplicate completion.

## Drafts
Drafts are unfinished mutable code and must remain separate from progress and attempt history.

## XP
- first authoritative completion: trusted `mission.points`
- already-completed mission: no repeat completion XP
- genuine wrong authoritative submission: `-10 XP`
- XP cannot go below zero
- all XP changes flow through the authoritative XP service/ledger path

## Hints / Solution Reveal
Hints and solution reveal may spend XP. `solution_code` stays protected from normal student responses.

Hint/reveal actions must be authorized, audited through XP transactions where XP changes, rate-limited reasonably, and safe against duplicate charging.

## Progression
The backend determines whether the next learning node is available.

The UI may display lock/unlock state, but client code never authorizes progression.

## Accepted State
Phase 3 previously reported:
`128 tests passing / 269 assertions`.

Those test totals remain historical evidence. New refinements must not claim Phase 3 compliance merely by increasing test counts; the revised contracts must be covered where they affect behavior.

## Definition of Done
The lesson experience, Try It Yourself flow, formative Knowledge Checks, CodeMirror editor, preview, validation, attempts, penalties, one-time rewards, drafts, progress, hints, solution reveal, next-challenge flow, course summaries, and learning-path presentation work securely and consistently with the Global Domain Contracts.


---

# Phase 4 — Assessment / Boss Challenge

## Status
COMPLETE AND ACCEPTED.

## Objective
Provide formal course-level practical assessment.

## Core Rule
Exactly one **summative course assessment** exists per course and it is the Boss Challenge.

Formative Knowledge Checks may exist throughout lessons, but they are not Phase 4 summative assessments and cannot complete a course.

Boss Challenge is not a normal mission.

## Progression

```text
Required Missions Complete
→ Boss Challenge Unlocked
→ Practical Coding Assessment
→ PASS
→ Course Complete
→ Next Course Unlocked
```

Failure leads to feedback and retry.

## Server Authority
The backend determines:
- assessment eligibility
- scoring
- pass/fail
- course completion
- next-course unlock

## Integration
Boss Challenge results integrate with progress, XP, competency, activity, and later notifications/reports.

## Security
Protect assessment rules, solutions, grading internals, and historical results.

## Accepted Completion
Phase 4 included 14 stories, schema approval before migration, cross-cutting integration testing, security testing, and reconciled accounting ending at `248 / 639`.

Do not redesign Phase 4 unless explicitly requested.


---

# Phase 5 — Progress, XP, Achievements & Competency

## Objective
Strengthen progress, gamification, learning history, competency, and code-quality foundations.

## Main Areas
- PHPStan/Larastan baseline
- XP transaction ledger
- achievements
- unified timeline
- progress aggregation
- competency foundations

## XP Ledger
XP must be auditable. Reward, deduction, and spending all flow through authoritative server logic.

## Achievements
Achievements represent meaningful milestones and are system-authoritative. Achievement definitions and grants follow the Global Achievement Contract. Grants must be idempotent; retroactive granting must be an explicit product decision.

## Timeline
`/timeline` represents meaningful historical learning activity such as Knowledge Check completion/results where meaningful, challenge completion, Boss Challenge assessments, course completion, and achievements.

Do not flood it with page views or button clicks.

## Competency
Competency uses actual Knowledge Check, Coding Challenge, and Boss Challenge data plus explicit curriculum skill/concept mapping where skill-level competency is required. Never hard-code percentages.

Phase 5 establishes the authoritative competency calculation path/service foundation; later phases consume it rather than creating separate formulas. The exact accepted formula must be documented before Phase 9 final acceptance.

## Static Analysis
PHPStan/Larastan results must be reported honestly.

## Definition of Done
XP accounting is auditable and duplicate-safe, achievements and timeline work, the shared domain-event vocabulary is usable, competency has one authoritative calculation path, skill mapping requirements are represented, and static analysis is integrated.


---

# Phase 6 — Teacher / Instructor Monitoring

## Epic
Teacher / Instructor Monitoring System

## Stories

```text
US-601 Teacher Authorization
US-602 Student Overview
US-603 Student Progress Detail
US-604 Assessment Performance
US-605 Competency Monitoring
US-606 Learning Activity
US-607 Course Analytics
US-608 Students Needing Attention
US-609 Teacher Dashboard
US-610 Responsive Instructor UI
US-611 Teacher Security Review
US-612 End-to-End Instructor Monitoring
```

## Objective
Give teachers a read-oriented monitoring interface.

## Routes
`/students`, `/student-progress`, `/activity`.

## Teacher Dashboard
Show authorized classroom/student counts, active students, courses in progress, Knowledge Check and Boss Challenge performance, students needing attention, and meaningful recent activity.

## Student Overview
Show name/username, current course, section, progress, assessment state, competency, last activity, and attention indicator.

## Student Detail
Show course/section progress, Knowledge Check performance, Challenge progress/attempts, Boss Challenge result, competency, activity, and other meaningful performance data.

Do not expose source code by default.

## Attention Rules
May consider repeated failure, failed Boss Challenge, inactivity, stalled progress, or incomplete required learning. Rules must be deterministic, documented, testable, and driven by the shared attention configuration rather than hard-coded independently in dashboard queries.

Teachers see the reason for an attention state; the signal does not alter authoritative student progression.

## Teacher Restrictions
Teachers cannot alter XP, mission completion, assessment result, course unlock, competency, achievement state, or curriculum.

## Definition of Done
Teacher RBAC, student overview, detailed progress, assessment monitoring, competency, activity, attention signals, analytics, security, and responsive UI all work.


---

# Phase 7 — Admin & System Management

## Epic
Admin & System Management

## Stories

```text
US-701 Admin Authorization
US-702 Admin Dashboard
US-703 User Management
US-704 User Role Management
US-705 Course Management
US-706 Section Management
US-707 Mission / Challenge Management
US-708 Assessment Administration
US-709 Administrative Audit Trail
US-710 System Analytics
US-711 System Status
US-712 Admin Security Review
US-713 End-to-End Administrative Integration
```

## Objective
Allow admins to manage the platform without turning the admin interface into unrestricted database access.

## Admin Capabilities
- dashboard
- users
- roles
- courses
- sections
- lessons and formative Knowledge Checks where approved
- missions/challenges where approved
- Boss Challenge assessment configuration where approved
- classrooms/enrollments and teacher assignments where approved
- system activity
- audit
- analytics
- system status
- announcements in later integration

## Important Boundary
`course.status` is not the same thing as an individual student's progression state.

## Curriculum Lifecycle
Admin curriculum management follows `DRAFT → PUBLISHED → ARCHIVED` behavior where applicable. Published content with student history should be versioned or archived rather than destructively overwritten/deleted. Material validation, points, requirement, skill-mapping, or assessment changes must preserve historical integrity.

## Audit Trail
Administrative actions should answer WHO, WHAT, WHEN, TARGET, RESULT, and relevant context. Audit records are append-oriented through normal application flows; the admin UI must not offer ordinary controls for rewriting historical audit events.

## Admin Restrictions
Do not provide ordinary controls for arbitrary XP changes, manual passing, manual course completion, fake competency, or falsified assessment results.

## Definition of Done
Admin RBAC, safe user/role management, curriculum management, assessment administration, auditability, system status, analytics, and security protections work.


---

# Phase 8 — Notifications & Learning Engagement

## Epic
System Integration, Notifications & Learning Engagement

## Stories

```text
US-801 Notification Authorization
US-802 Notification Center
US-803 Read and Unread Notifications
US-804 Student Learning Notifications
US-805 Assessment Notifications
US-806 Teacher Attention Notifications
US-807 System Announcements
US-808 Notification Event Integration
US-809 Learning Reminders
US-810 Dashboard Notification Integration
US-811 Notification Privacy and Security
US-812 End-to-End Notification Integration
```

## Objective
Connect system modules through meaningful notifications without creating a social messaging platform.

## Possible Notification Types

```text
KNOWLEDGE_CHECK_COMPLETED where notification is pedagogically useful
MISSION_COMPLETED
ASSESSMENT_UNLOCKED
ASSESSMENT_PASSED
ASSESSMENT_FAILED
COURSE_COMPLETED
NEXT_COURSE_UNLOCKED
ACHIEVEMENT_EARNED
DRAFT_REMINDER
LEARNING_REMINDER
TEACHER_ATTENTION
SYSTEM_ANNOUNCEMENT
ACCOUNT_EVENT
```

## Flow

```text
Meaningful Event
→ Notification Rule
→ Authorized Recipient
→ Notification Record
→ UI
→ Read / Unread
```

## Notification Center
Route: `/notifications`.

Show unread count, type, title, message, timestamp, read state, and safe internal action.

## Reminders
Must be deterministic, documented, testable, and non-spammy. Define trigger condition, minimum interval, maximum frequency, suppression condition, and resolution condition. Periodic reminders should use Laravel scheduling/queues where appropriate.

## Announcements
Admin may publish targeted system announcements. Drafts must remain hidden.

## Duplicate Prevention
Immutable one-time notifications should use a stable event/source identity so browser retries, queue retries, or repeated event handling do not create duplicates.

## Critical Rule
Notifications communicate state; they never create XP, completion, assessment, or unlock state.

## Definition of Done
Authorization, notification center, read/unread state, learning notifications, teacher alerts, announcements, duplicate prevention, privacy, and dashboard integration work.


---

# Phase 9 — Personalized Learning & Competency Intelligence

## Epic
Personalized Learning & Competency Intelligence

## Stories

```text
US-901 Personalized Learning Authorization
US-902 Recommendation Engine
US-903 Explainable Recommendations
US-904 Competency Calculation
US-905 Skill-Level Competency
US-906 Weak Skill Identification
US-907 Student Recommendation UI
US-908 Learning Path Integration
US-909 Teacher Recommendation Visibility
US-910 Recommendation Security
US-911 Recommendation Performance
US-912 End-to-End Personalized Learning
```

## Objective
Use real performance data to provide deterministic and explainable recommendations.

This is not an AI/ML recommendation engine.

## Inputs
- Knowledge Check performance
- challenge completion
- failed attempts
- Boss Challenge assessment scores/attempts
- competency
- current course/section
- incomplete requirements

## Recommendation Types
`CONTINUE`, `REVIEW`, `PRACTICE`, `RETRY`, `ASSESSMENT_READY`.

## Explainability
Every recommendation includes a student-safe reason based on documented authoritative signals. Recommendation thresholds come from shared configuration rather than hidden magic numbers.

## Critical Rule
Recommendation ≠ Authorization.

Recommendations never bypass course gating or assessment eligibility.

## Competency
Competency may be displayed at course, section, and skill/concept levels. Percentages must be calculated by the authoritative Competency service/path using documented skill mappings and a documented deterministic formula.

Phase 9 may refine presentation and interpretation, but it must not create a second competency formula separate from Phase 5 foundations.

## Teacher Integration
Teachers may see recommendations and reasoning, but cannot forge them.

## Out of Scope
No ML, LLM tutoring, predictive grades, hidden academic scoring, or adaptive course skipping.

## Definition of Done
Recommendations use real data, explain themselves, weak skills are identifiable, competency is centralized, UI integration works, and progression cannot be bypassed.


---

# Phase 10 — Reports, Analytics & Academic Insights

## Epic
Reports, Analytics & Academic Insights

## Stories

```text
US-1001 Report Authorization
US-1002 Student Progress Report
US-1003 Teacher Student Report
US-1004 Teacher Course Report
US-1005 Assessment Analytics
US-1006 Challenge Analytics
US-1007 Competency Report
US-1008 Admin System Report
US-1009 Report Filtering
US-1010 Report Export
US-1011 Report Performance
US-1012 Report Security
US-1013 End-to-End Reporting
```

## Objective
Present authoritative data as useful descriptive reports.

## Student Reports
May include progress, Boss Challenge results, XP, achievements, competency, timeline, recommendations.

## Teacher Reports
Detailed student reports and course-level aggregate reports.

## Admin Reports
System-wide user, learning, gamification, assessment, and engagement analytics.

## Knowledge Check Analytics
Where educationally useful, report question/check attempts, correct/incorrect responses, pass or completion rate when a pass state is configured, repeated misconceptions, and skill/concept patterns. Metrics must remain descriptive and privacy-authorized.

## Boss Challenge Assessment Analytics
Attempts, passes, failures, average score, pass rate, retry count, completion.

Each metric must define its population, numerator/denominator where relevant, time range, and treatment of repeat attempts.

## Challenge Analytics
Completions, failure attempts, completion rate, average attempts, and challenge difficulty indicators.

Challenge metrics must likewise use shared metric definitions so dashboards, CSV exports, and PDFs cannot disagree about the same named measure.

## Filtering
Support validated date ranges and relevant student/course/status filters.

## Export
Recommended initial formats: CSV and PDF.

Exports must enforce the same authorization as the UI.

## Authoritative Service Reuse
Reports consume the authoritative Progress, XP, Assessment, Competency, Recommendation, Achievement, and Timeline data/services. Report queries may aggregate authoritative records but must not recreate competing business formulas.

## Performance
Use aggregate queries, pagination, eager loading, proper indexes, profiling, and queues for large exports where justified. Avoid N+1 and long synchronous export requests.

## Out of Scope
No predictive analytics, AI grading, arbitrary report builder, data warehouse, or BI platform clone.

## Definition of Done
Student, teacher, and admin reports work; filters/exports are authorized; performance is controlled; reports reuse authoritative services.


---

# Phase 11 — Security, Performance & System Hardening

## Epic
Production Hardening & Quality Assurance

## Stories

```text
US-1101 Authentication Hardening
US-1102 RBAC Audit
US-1103 IDOR Audit
US-1104 Input / Mass Assignment Audit
US-1105 Student Code Security
US-1106 Assessment / XP Security
US-1107 Database Integrity
US-1108 Query Performance
US-1109 Frontend Performance
US-1110 Error Handling / Logging
US-1111 Static Analysis
US-1112 Accessibility / Responsive Audit
US-1113 Full Regression Suite
US-1114 Security Integration Test
```

## Objective
Add no major product feature. Harden everything already built.

## Security Review
Audit authentication, RBAC, IDOR, CSRF, mass assignment, SQL injection, XSS, output escaping, code preview, assessment security, XP security, teacher privacy, admin privilege, notification privacy, and report privacy.

## Student Code Security
Review sandbox permissions and prevent parent access or unsafe execution. Verify the exact iframe sandbox privileges required by each supported lesson type and reject unnecessary privileges. Review code/request-size limits and submission rate limits.

## Validation Regression
Ensure no student submission path can use `eval`, `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, or `popen`.

## Database Integrity
Review foreign keys, uniqueness, indexes, nullability, cascades, transaction boundaries, idempotency constraints, race-condition protection, archival/retention behavior, and historical integrity.

## Performance
Profile major student, teacher, admin, notification, and report pages.

## Static Analysis / Formatting
Run PHPStan/Larastan and Pint. Report actual results.

## Accessibility
Review keyboard navigation, focus, labels, semantics, contrast, reduced motion, editor accessibility, zoom/reflow, and CRT-specific effects. `prefers-reduced-motion` must suppress or substantially reduce non-essential flicker/animation, and scanlines/glow must never make lesson/editor text unreadable.

## Definition of Done
Security audits pass, N+1/performance issues are reviewed, DB integrity is checked, static analysis and formatting are run, responsive/accessibility reviews are complete, and the full regression suite passes.


---

# Phase 12 — Final Integration, Deployment & Project Completion

## Epic
CodeQuest Final Integration & Release

## Stories

```text
US-1201 Final Student Workflow
US-1202 Final Teacher Workflow
US-1203 Final Admin Workflow
US-1204 Cross-Role Integration
US-1205 Data Reconciliation
US-1206 Production Configuration
US-1207 Deployment Process
US-1208 Database Backup / Migration Verification
US-1209 Final Documentation
US-1210 Final Security Verification
US-1211 Final Performance Verification
US-1212 Final Responsive / Accessibility Verification
US-1213 Final Regression Suite
US-1214 Release Acceptance
```

## Objective
Prove CodeQuest operates as one integrated system. No major new feature belongs here.

## Final Student Flow

```text
Authentication
→ Dashboard
→ Learning Path
→ Course
→ Section
→ Lesson
→ Learn / Example / Try
→ Knowledge Check when configured
→ Challenge
→ CodeMirror
→ Preview
→ Server Validation
→ XP / Progress
→ Recommendation
→ Required Missions Complete
→ Boss Challenge
→ Assessment Result
→ Course Completion
→ Next Course Unlock
→ Competency
→ Achievement
→ Timeline
→ Notification
→ Report
```

## Final Student Acceptance Test
Test login, resume learning, lesson/Try flow, Knowledge Check submission/feedback, drafts, preview, wrong submission penalty, hint spending, correct submission reward, progress, Boss Challenge unlock, assessment pass, next-course unlock, competency, achievements, timeline, recommendations, notifications, and reports.

## Final Teacher Acceptance Test
Verify teacher dashboard, authorized classroom/student lookup, Knowledge Check performance, progress, challenge completion, Boss Challenge result, competency, activity, recommendations, attention state, reports, and read-only protection.

## Final Admin Acceptance Test
Verify admin dashboard, users, roles, classrooms/enrollments, courses, sections, lessons, Knowledge Checks, challenges, Boss Challenge assessment configuration, activity, announcements, reports, system status, audit trail, and protection against corrupting learning state.

## Cross-Role Test

```text
ADMIN configures valid curriculum
→ STUDENT completes learning
→ SYSTEM records XP/progress
→ SYSTEM computes competency/recommendations
→ STUDENT passes Boss Challenge
→ SYSTEM completes course
→ TEACHER sees correct state
→ ADMIN sees correct aggregates
→ REPORTS match
→ NOTIFICATIONS match
```

## Data Reconciliation
Reconcile Knowledge Check results, progress, XP, Boss Challenge assessments, course completion, competency, achievements, timeline, notifications, reports, teacher analytics, and admin analytics.

## Deployment Documentation
Prepare:
`README.md`, `CODEQUEST_MASTER_SPEC.md`, `ARCHITECTURE.md`, `DATABASE.md`, `SECURITY.md`, `DEPLOYMENT.md`, `TESTING.md`, `USER_GUIDE.md`, `TEACHER_GUIDE.md`, `ADMIN_GUIDE.md`.

## Environment / Deployment
Document PHP, Composer, MariaDB, Node.js, npm, Laravel, web server, `.env.example`, install/build steps, database setup, and production verification.

## Deployment / Backup / Rollback

```text
PRE-DEPLOYMENT VERIFICATION
→ BACKUP
→ VERIFY BACKUP
→ RECORD CURRENT RELEASE
→ REVIEW MIGRATIONS
→ DEPLOY APPLICATION
→ MIGRATE ONLY AFTER APPROVAL
→ VERIFY DATA
→ SMOKE / SECURITY / INTEGRITY CHECKS
→ ACCEPT RELEASE
```

If verification fails, preserve logs/evidence, roll back the application release, assess database compatibility, and use an explicitly approved down/restore process only when required. Never apply production migrations or destructive rollback blindly.

Release metadata must record the deployed commit/tag, migration set, environment, operator, verification result, and rollback result if applicable.

## Final Scope Freeze
During acceptance testing: no new major features. Only bug, security, integrity, accessibility, performance, and documentation fixes.

## Final Status Values

```text
CODEQUEST COMPLETE
CODEQUEST FUNCTIONALLY COMPLETE — PRODUCTION MIGRATION PENDING
CODEQUEST PARTIALLY COMPLETE
CODEQUEST BLOCKED
```

## Definition of Done
All student, teacher, admin, assessment, gamification, competency, notification, reporting, security, performance, accessibility, deployment, and documentation requirements are verified by real tests and review.


---

# Final Product Invariants

```text
1. Students learn a concept before or alongside the coding task; CodeQuest is not just an editor.
2. Students write meaningful code for authoritative coding Challenges.
3. Server-side validation is authoritative.
4. Preview is never proof of completion.
5. Drafts, Progress, and Attempts remain separate.
6. Attempts preserve historical submission outcomes rather than being rewritten by retries.
7. First mission completion may award trusted mission XP once; repeated passes cannot farm completion XP.
8. XP is not a grade.
9. XP never goes below zero.
10. XP changes are auditable and duplicate-safe.
11. Protected solutions and validation/grading internals are not exposed to students.
12. Student code is never arbitrarily executed on the server.
13. Boss Challenge is separate from normal missions and formative Knowledge Checks.
14. Exactly one summative Boss Challenge assessment exists per course.
15. Required challenges gate the Boss Challenge.
16. Passing the Boss Challenge completes the course according to the accepted Phase 4 rules.
17. Course completion unlocks the next course through authoritative server logic.
18. Teachers monitor; they do not become CMS administrators.
19. Admins manage the platform; ordinary admin controls do not falsify learning outcomes.
20. Operators remain a distinct least-privilege operational role.
21. Competency derives from real student data and explicit curriculum mapping.
22. Competency has one authoritative calculation path.
23. Recommendations are explainable and cannot bypass progression.
24. Notifications communicate state; they do not create learning state.
25. Reports describe authoritative data and reuse domain calculations.
26. Client JavaScript is never the security boundary.
27. Authoritative transitions use transactions/idempotency/concurrency protection as required.
28. Published curriculum changes preserve historical attempts/completions and are auditable.
29. Historical audit records are append-oriented through normal application flows.
30. Derived analytics do not become competing sources of truth.
31. `~/system404` remains untouched unless explicitly approved.
32. Real database migrations require explicit approval.
33. Existing authoritative data must be preserved.
34. No unnecessary duplicate domain systems.
35. Preserve the CodeQuest CRT/System 404 identity without sacrificing accessibility.
36. W3Schools inspires concise teaching and examples; it is not copied.
37. freeCodeCamp inspires hands-on coding practice and retry; it is not copied.
38. Boot.dev inspires progression and gamification; unlisted mechanics are not automatically included.
39. CodeQuest owns assessment, competency, monitoring, reporting, and institutional integration.
40. Phase 12 closes the initial lifecycle; major new features belong to v2 or separately approved extensions.
```

# Final Phase Map

```text
Phase 1  — Design System & Application Shell
Phase 2  — Laravel / Backend Foundation
Phase 3  — Student Learning & Mission System
Phase 4  — Assessment / Boss Challenge
Phase 5  — Progress / XP / Achievements / Competency
Phase 6  — Teacher / Instructor Monitoring
Phase 7  — Admin & System Management
Phase 8  — Notifications & Learning Engagement
Phase 9  — Personalized Learning & Competency Intelligence
Phase 10 — Reports, Analytics & Academic Insights
Phase 11 — Security, Performance & System Hardening
Phase 12 — Final Integration, Deployment & Project Completion
```

# Final Product Identity

CodeQuest is:

```text
A TEACHING SYSTEM
that explains concepts concisely and clearly.

A HANDS-ON LEARNING SYSTEM
that requires students to apply concepts by writing code.

A GAMIFIED PROGRESSION SYSTEM
that makes advancement, XP, milestones, and unlocks visible.

AN ASSESSMENT SYSTEM
where the Boss Challenge verifies course-level practical learning.

A COMPETENCY SYSTEM
that interprets real learning and assessment evidence.

A MONITORING SYSTEM
that gives teachers an authorized view of student progress without allowing them to falsify outcomes.

AN ADMINISTRATIVE SYSTEM
that manages curriculum and platform state without becoming unrestricted database access.

AN INTEGRATED SYSTEM
not a collection of disconnected modules.
```

The design equation is:

```text
W3SCHOOLS
concise teaching + examples + try-it-yourself
+
FREECODECAMP
hands-on code writing + validation + retry
+
BOOT.DEV
sequential progression + XP + unlocks + game feel
+
SYSTEM 404
CRT identity + mission atmosphere
+
CODEQUEST
Boss Challenge + competency + monitoring + reporting
=
CODEQUEST
```

Final learner lifecycle:

```text
LEARN
↓
UNDERSTAND
↓
SEE AN EXAMPLE
↓
TRY / EXPERIMENT
↓
PRACTICE
↓
WRITE CODE
↓
PREVIEW / TEST
↓
SUBMIT
↓
FAIL → FEEDBACK → LEARN → RETRY
OR
PASS
↓
GAIN ONE-TIME COMPLETION XP
↓
PROGRESS / UNLOCK
↓
BUILD COMPETENCY
↓
COMPLETE REQUIRED CHALLENGES
↓
BOSS CHALLENGE
↓
PASS
↓
COMPLETE COURSE
↓
UNLOCK NEXT COURSE
↓
RECEIVE ACHIEVEMENTS / RECOMMENDATIONS / NOTIFICATIONS
↓
TEACHER MONITORING + REPORTING
↓
CONTINUE LEARNING
```

**Phase 12 closes the initial CodeQuest development lifecycle. Anything beyond Phase 12 should be treated as CodeQuest v2, maintenance, or a separately approved extension.**
