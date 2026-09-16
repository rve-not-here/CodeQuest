# CodeQuest — Master Project Specification

> **Project:** CodeQuest: An Integrated Gamified Learning and Assessment System  
> **Current Phase:** Phase 8  
> **Status:** Phases 1–4 accepted/completed; Phase 5–8 specifications established  
> **Primary Stack:** Laravel 13, PHP 8.5, MariaDB 12.x, Blade, JavaScript, CodeMirror 6  
> **Legacy Reference:** `~/system404`  
> **Laravel Project:** `~/codequest`

---

# Table of Contents

1. [Project Overview](#1-project-overview)
2. [Core Product Direction](#2-core-product-direction)
3. [Authoritative Learning Loop](#3-authoritative-learning-loop)
4. [Curriculum Architecture](#4-curriculum-architecture)
5. [Roles and Responsibilities](#5-roles-and-responsibilities)
6. [Technology and Architecture](#6-technology-and-architecture)
7. [Database and Legacy Integration Rules](#7-database-and-legacy-integration-rules)
8. [Security Principles](#8-security-principles)
9. [Phase 1 — Design System & Application Shell](#9-phase-1--design-system--application-shell)
10. [Phase 2 — Foundation / Backend Architecture](#10-phase-2--foundation--backend-architecture)
11. [Phase 3 — Student Learning & Mission System](#11-phase-3--student-learning--mission-system)
12. [Phase 4 — Assessment / Boss Challenge](#12-phase-4--assessment--boss-challenge)
13. [Phase 5 — Quality, Progress & Gamification Foundation](#13-phase-5--quality-progress--gamification-foundation)
14. [Phase 6 — Teacher / Instructor Monitoring](#14-phase-6--teacher--instructor-monitoring)
15. [Phase 7 — Admin & System Management](#15-phase-7--admin--system-management)
16. [Phase 8 — Notifications & Learning Engagement](#16-phase-8--notifications--learning-engagement)
17. [Feature Mapping](#17-feature-mapping)
18. [UI / UX Requirements](#18-ui--ux-requirements)
19. [XP and Gamification Rules](#19-xp-and-gamification-rules)
20. [Assessment Rules](#20-assessment-rules)
21. [Progress, Drafts and Attempts](#21-progress-drafts-and-attempts)
22. [Competency Rules](#22-competency-rules)
23. [Teacher Monitoring Rules](#23-teacher-monitoring-rules)
24. [Admin Rules](#24-admin-rules)
25. [Notification Rules](#25-notification-rules)
26. [Testing Strategy](#26-testing-strategy)
27. [Migration Safety](#27-migration-safety)
28. [Legacy Migration Strategy](#28-legacy-migration-strategy)
29. [Definition of Done](#29-definition-of-done)
30. [Final Project Quality Checklist](#30-final-project-quality-checklist)

---

# 1. Project Overview

## 1.1 Project Name

**CodeQuest: An Integrated Gamified Learning and Assessment System**

CodeQuest combines:

- a structured coding-learning platform
- interactive coding challenges
- server-side assessment
- gamification
- progress tracking
- competency monitoring
- teacher monitoring
- administrative management

The system integrates the concepts of:

```text
THE 404: SYSTEM RESTORE
        +
Online Quiz / Examination System
        ↓
     CODEQUEST
```

CodeQuest is intended to provide a structured learning and assessment environment for programming education.

---

# 2. Core Product Direction

CodeQuest should work like **freeCodeCamp at the system-flow level**, not merely look like freeCodeCamp.

The intended product combination is:

```text
FREECODECAMP-STYLE LEARNING
+
BOOT.DEV-STYLE PROGRESSION / GAMIFICATION
+
SYSTEM 404 CRT / EMERGENCY THEME
=
CODEQUEST
```

## 2.1 Visual / Product Ratio

```text
40% freeCodeCamp
- interactive challenge flow
- structured lessons
- clear next task

40% Boot.dev
- learning paths
- sequential progression
- XP/game mechanics
- continue-learning flow

20% System 404
- CRT
- emergency terminal
- phosphor green
- missions
- system-recovery narrative
```

## 2.2 Important Product Rule

CodeQuest is **not a broken-code repair system**.

Students:

- read the lesson
- understand the concept
- receive a task
- write code from scratch
- run/preview it
- submit it
- receive feedback
- retry if necessary
- earn XP when successful

---

# 3. Authoritative Learning Loop

The primary student workflow is:

```text
Dashboard
  ↓
Learning Path
  ↓
Course
  ↓
Section / Chapter
  ↓
Lesson / Short Discussion
  ↓
Explanation + Examples
  ↓
Coding Challenge
  ↓
Student writes code FROM SCRATCH
  ↓
Run / Preview
  ↓
Server-side validation/tests
  ↓
FAIL
  ↓
Feedback / Hint
  ↓
Retry
  ↓
PASS
  ↓
Complete + XP + Save Progress
  ↓
Next Challenge
  ↓
Required Challenges Completed
  ↓
Boss Challenge Assessment
  ↓
PASS
  ↓
Course Complete
  ↓
Next Course Unlocked
```

Overall philosophy:

```text
LEARN
→ UNDERSTAND
→ PRACTICE
→ WRITE CODE
→ TEST
→ FIX
→ MASTER
→ ASSESS
→ IMPROVE
```

---

# 4. Curriculum Architecture

The curriculum hierarchy is:

```text
Learning Path
  ↓
Course
  ↓
Section
  ↓
Lesson
  ↓
Mission / Challenge
  ↓
Progress / Attempts / Draft
```

## 4.1 Learning Path

For the initial implementation there is one sequential learning path.

```text
HTML Fundamentals
      ↓
CSS Styling
      ↓
JavaScript Scripting
```

Course ordering comes from the database.

Do not invent an enrollment system merely to support the learning path.

## 4.2 Course

A course is a substantial learning unit.

Current courses:

```text
HTML Fundamentals
CSS Styling
JavaScript Scripting
```

## 4.3 Section

Sections are real conceptual entities.

They are not merely a string stored on a mission.

Hierarchy:

```text
Course
  ↓
Section
  ↓
Mission / Challenge
```

Sections have:

- course relationship
- ordering
- conceptual learning purpose
- progress visibility

## 4.4 Lesson

A lesson is primarily instructional content:

- explanation
- examples
- task/instructions

Initially, existing mission descriptions may contain instructional content.

Do not add video, Q&A, or media CMS unless explicitly approved.

## 4.5 Mission / Challenge

Internal domain term:

```text
Mission
```

Student-facing term:

```text
Challenge
```

A challenge should contain:

- concise concept explanation
- example
- task
- code editor
- live preview where applicable
- Run/Test
- feedback
- retry
- server validation
- XP reward/penalty
- progress update

---

# 5. Roles and Responsibilities

Existing roles:

```text
student
teacher
admin
operator
```

## 5.1 Student

Student can:

- follow the learning path
- read lessons
- write code
- run previews
- submit challenges
- save drafts
- use XP for learning assistance
- complete Boss Challenges
- view progress
- view competency
- view achievements
- view notifications

Student cannot:

- change their role
- alter XP directly
- mark challenges complete
- pass assessments directly
- unlock courses directly
- alter competency directly

## 5.2 Teacher

Teacher primarily monitors students.

Teacher can view:

- student overview
- progress
- section progress
- challenge completion
- assessment performance
- competency
- learning activity
- students needing attention

Teacher cannot:

- change XP
- mark challenges complete
- mark assessments passed
- unlock courses
- modify assessment results
- modify competency
- award achievements
- edit curriculum content

## 5.3 Admin

Admin manages the platform.

Admin may manage:

- users
- courses
- sections
- mission/challenge content where approved
- assessment configuration where approved
- system activity
- system announcements
- system status
- administrative audit information

Admin must not automatically gain authority to modify learning outcomes.

## 5.4 Operator

The database role `operator` remains reserved.

Do not invent a complete operator UI unless explicitly required.

Operator must not silently fall through to student behavior when authorization can distinguish the role.

---

# 6. Technology and Architecture

## 6.1 Stack

```text
Laravel 13
PHP 8.5
MariaDB 12.x
Blade
JavaScript
CodeMirror 6
Vite
```

## 6.2 Request Architecture

Use:

```text
HTTP Request
    ↓
Route
    ↓
Middleware / Authorization
    ↓
Controller
    ↓
Service
    ↓
Repository / Eloquent
    ↓
Model
    ↓
MariaDB
```

Simple read-only cases may skip unnecessary layers.

## 6.3 Controllers

Controllers should be thin.

Controllers handle:

- HTTP input
- authorization
- request validation
- service invocation
- response/view selection

Controllers must not contain:

- complex SQL
- business rules
- scoring
- progression logic
- XP logic
- assessment eligibility logic

## 6.4 Services

Services contain behavior:

- workflows
- business rules
- calculations
- eligibility
- transactions
- cross-module operations

Do not create services merely to wrap trivial operations.

## 6.5 Repositories

Repositories contain data access:

- queries
- filtering
- sorting
- pagination
- eager loading
- aggregates
- persistence where useful

Repositories must not contain business decisions.

Boundary:

```text
Repository → DATA
Service → BEHAVIOR / BUSINESS RULES
```

## 6.6 Dependency Injection

Prefer:

- constructor injection
- Laravel container resolution
- method injection for one-off dependencies

Avoid:

- manual `new Service()`
- service locator
- unnecessary interfaces
- interface-per-class architecture

## 6.7 Transactions

Services coordinate transactions when multiple records must change together.

Example:

```text
Assessment pass
    ↓
Assessment result
    ↓
Course completion
    ↓
Next-course unlock
    ↓
XP transaction
    ↓
Activity / notification
```

These state changes should remain transactionally consistent where appropriate.

---

# 7. Database and Legacy Integration Rules

## 7.1 Existing Database

Laravel currently connects to the existing MariaDB database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=system404
DB_USERNAME=sys404
DB_PASSWORD=sys404_dev_pass
```

Connection was verified through Laravel/Tinker.

## 7.2 Existing Tables

Important existing tables:

```text
the404_users
the404_courses
the404_sections
the404_missions
the404_progress
the404_activity
the404_mission_drafts
the404_xp_transactions
```

Assessment and achievement tables were introduced in later phases.

## 7.3 Existing Users

```text
the404_users
```

Fields include:

```text
id
username
password
name
role
created_at
updated_at
```

Role enum:

```text
student
teacher
admin
operator
```

## 7.4 Existing Courses

```text
the404_courses
```

Important fields:

```text
id
slug
name
type
description
status
order_num
created_at
updated_at
```

Status:

```text
active
locked
draft
```

## 7.5 Existing Missions

```text
the404_missions
```

Important fields include:

```text
id
course_id
section_id
order_num
title
difficulty
description
broken_code
solution_code
target_html
validate_rule
hints
points
created_at
updated_at
```

Important:

`broken_code` exists in the legacy schema, but CodeQuest's student learning model is **not** based on broken-code repair.

## 7.6 Existing Progress

```text
the404_progress
```

Fields:

```text
id
user_id
mission_id
pts_earned
completed_at
created_at
```

Unique:

```text
(user_id, mission_id)
```

Progress means learning completion.

It is not a draft store.

## 7.7 Existing Activity

```text
the404_activity
```

Fields:

```text
id
user_id
type
message
pts
created_at
```

Important:

**There is no `updated_at` column.**

Do not configure Eloquent in a way that attempts to update a nonexistent `updated_at`.

## 7.8 Existing XP Ledger

```text
the404_xp_transactions
```

XP changes must be auditable.

Do not use `the404_progress.pts_earned` as a complete XP ledger.

## 7.9 Migration Rule

Do not run:

```bash
php artisan migrate
```

against the real `system404` database merely to see whether a migration works.

Use an isolated test database first.

---

# 8. Security Principles

The server is authoritative.

Never trust client-provided:

```text
points
user_id
role
unlock
completion
assessment eligibility
assessment result
competency
XP
```

## 8.1 Authentication

Use Laravel authentication/session mechanisms.

Never expose:

- password hashes
- session secrets
- authentication tokens
- application keys

## 8.2 Authorization

Authorization must be server-side.

Hiding a navigation link is not authorization.

## 8.3 IDOR

Every resource access must verify authorization.

Changing:

```text
/users/1
```

to:

```text
/users/2
```

must not bypass authorization.

## 8.4 CSRF

State-changing web requests must use appropriate CSRF protection.

## 8.5 Validation

Client-side validation is UX only.

Server-side validation is authoritative.

## 8.6 Student Code

Student code is untrusted input.

Never execute arbitrary student code on the main application page.

---

# 9. Phase 1 — Design System & Application Shell

## Objective

Establish the CodeQuest visual system and reusable application shell.

## Scope

- global layout
- navigation
- responsive structure
- typography
- color system
- CRT effects
- buttons
- status indicators
- progress bars
- panels
- alerts/status messages
- loading/focus states
- reusable Blade components

## Visual Identity

Preserve:

```text
--phosphor: #33ff00
```

and:

- monospace typography
- scanlines
- glow
- terminal interface
- System 404 identity
- light mode

## Constraints

Phase 1 must not implement later-phase business functionality.

## Definition of Done

- reusable shell exists
- components are reusable
- mobile works at 360/390/430px
- no unintended horizontal scroll
- CRT identity is preserved
- tests pass
- formatting/quality checks are reported honestly

---

# 10. Phase 2 — Foundation / Backend Architecture

Phase 2 establishes the Laravel foundation and integration strategy.

## Objectives

- connect Laravel to MariaDB
- establish models
- establish authentication
- establish role handling
- establish application architecture
- map Laravel models to the existing database
- preserve the existing system

## Existing Model Mapping

```text
User       → the404_users
Course     → the404_courses
Section    → the404_sections
Mission    → the404_missions
Progress   → the404_progress
Activity   → the404_activity
MissionDraft → the404_mission_drafts
XpTransaction → the404_xp_transactions
```

## Architecture

```text
Route
 ↓
Middleware / Authorization
 ↓
Controller
 ↓
Service
 ↓
Repository / Eloquent
 ↓
MariaDB
```

## Legacy Rule

Do not delete or rewrite `~/system404`.

It remains a reference and behavior source.

---

# 11. Phase 3 — Student Learning & Mission System

## Epic

**Student Learning & Mission System**

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
```

## Phase 3 Result

Accepted implementation report:

```text
128 tests passing
269 assertions
```

All prior behavior was preserved.

## 11.1 CodeMirror

Primary editor:

**CodeMirror 6**

Requirements:

- syntax highlighting
- line numbers
- indentation
- bracket matching
- keyboard support
- CRT/System 404 theme
- Vite integration
- responsive behavior

Monaco is not required.

## 11.2 Live Preview

Use:

```text
CodeMirror
   ↓
Sandboxed iframe
   ↓
srcdoc
   ↓
Instant preview
```

Preview is never proof of completion.

## 11.3 Server Validation

Submit:

```text
CodeMirror
   ↓
Laravel endpoint
   ↓
ValidationService
   ↓
authoritative result
```

No client-side completion authority.

## 11.4 Validation Rules

Existing allow-list:

```text
contains
contains_all
contains_any
count_tag
count
regex
exact_normalized
```

Do not use:

```text
eval
exec
shell_exec
proc_open
```

or equivalent arbitrary execution.

JavaScript challenge submissions are pattern-matched rather than executed by the validation service.

## 11.5 Drafts

Drafts represent unfinished code.

```text
Draft
→ current unfinished code

Progress
→ completed learning requirement

Submission / Attempt
→ explicit validation attempt
```

Do not use progress as draft storage.

## 11.6 Progress States

Conceptually:

```text
NOT STARTED
IN PROGRESS
COMPLETED
```

Saving code does not mean completion.

## 11.7 Hints

Existing `hints` data can be progressive.

Example:

```json
[
  "Think about which HTML element creates a heading.",
  "The largest heading uses the h1 element.",
  "Your answer should contain an h1 element."
]
```

Hints cost XP.

## 11.8 Solution Reveal

Solution/reference code is protected.

Solution reveal:

- requires server authorization
- costs XP
- must not expose solution code in normal challenge payloads

---

# 12. Phase 4 — Assessment / Boss Challenge

## Status

**COMPLETE AND ACCEPTED**

Phase 4 established the assessment architecture.

The user explicitly accepted Phase 4.

Do not reopen or redesign Phase 4 unless specifically requested.

## Core Rule

There is exactly **one assessment per course**.

The assessment is the:

**Boss Challenge**

It is a separate domain concept from normal missions.

## Progression

```text
Required Missions Complete
        ↓
Boss Challenge Unlocked
        ↓
Practical Coding Assessment
        ↓
PASS
        ↓
Course Complete
        ↓
Next Course Unlocked
```

Failure:

```text
FAIL
 ↓
Feedback
 ↓
Retry
```

## Assessment Characteristics

The Boss Challenge is:

- practical
- real-world/problem-solving oriented
- coding-focused
- server-evaluated
- separate from ordinary missions

Do not model it as:

```text
Mission 31
```

## Hard Gate

Assessment eligibility is server-side.

Frontend cannot unlock an assessment by modifying JavaScript state.

## Phase 4 Acceptance

Phase 4 included:

- 14 stories
- approved schema before migration
- cross-cutting integration testing
- security testing
- XP accounting reconciliation

The accepted accounting trail ended at:

```text
248 / 639
```

Do not reinterpret or change this accepted Phase 4 result.

---

# 13. Phase 5 — Quality, Progress & Gamification Foundation

Phase 5 begins with the PHPStan/Larastan baseline established after Phase 4.

## Primary Direction

Phase 5 focuses on:

- static analysis
- progress/gamification quality
- XP accounting
- achievements
- timeline
- competency foundations
- reuse of domain services

## PHPStan / Larastan

The baseline must be established honestly.

Do not claim:

```text
0 errors
```

unless PHPStan/Larastan was actually run and verified.

## Core Rule

Phase 5 must not duplicate business rules.

For example:

```text
ProgressService
TimelineService
CompetencyService
AchievementService
XpService
AssessmentService
```

should be reused where those services already own the relevant behavior.

## XP

XP remains a learning mechanic.

It is not a grade.

## Competency

Competency must eventually derive from real:

- coding performance
- assessment performance
- progress data

Do not hard-code competency percentages.

---

# 14. Phase 6 — Teacher / Instructor Monitoring

## Epic

**Teacher / Instructor Monitoring System**

## Objective

Give teachers a dedicated read-oriented monitoring interface.

Teachers monitor learning.

They do not become curriculum administrators.

## Routes

```text
/students
/student-progress
/activity
```

## Dashboard

Teacher dashboard should show:

- total students
- active students
- courses in progress
- assessments passed
- students needing attention
- meaningful recent activity

## Student Overview

Show:

- username/name
- current course
- course progress
- current section
- assessment status
- competency summary
- last activity
- attention indicator

## Student Search

Search:

- username
- name

Use server-side search where practical.

## Student Filtering

Possible filters:

- course
- progress
- assessment status
- activity status
- attention status

Avoid excessive filtering.

## Student Detail

Show:

- current course
- course progress
- current section
- current challenge
- Boss Challenge status
- competency
- challenge completion
- assessment results
- meaningful activity

Do not expose source code by default.

## Assessment Monitoring

Teachers can view:

- status
- attempts
- score
- pass/fail
- completion date

Teachers cannot modify results.

## Competency

Teacher competency view must reuse the same competency logic as students.

Never duplicate the formula.

## Activity

Meaningful activity includes:

- challenge completion
- challenge failure
- assessment attempt
- assessment pass/fail
- course completion
- section completion
- significant milestones

Avoid meaningless events such as:

- page view
- dashboard open
- button click

## Students Needing Attention

Possible signals:

- repeated failed challenges
- failed Boss Challenge
- long inactivity
- stalled progress
- low assessment performance
- incomplete required learning

Rules must be:

- deterministic
- documented
- testable

Do not invent arbitrary thresholds without approval.

## Phase 6 Stories

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

## Cross-Cutting Flow

```text
Student completes challenge
        ↓
Progress / XP update
        ↓
Teacher sees progress
        ↓
Student passes Boss Challenge
        ↓
Assessment result
        ↓
Course completion
        ↓
Teacher sees result
        ↓
Competency reflects result
```

---

# 15. Phase 7 — Admin & System Management

## Epic

**Admin & System Management**

## Objective

Provide administrators with tools to manage the CodeQuest platform.

Admin management must remain distinct from teacher monitoring.

```text
Student
→ learns

Teacher
→ monitors learners

Admin
→ manages platform
```

## Admin Routes

Recommended:

```text
/admin
/admin/users
/admin/courses
/admin/courses/{course}/sections
/admin/missions
/admin/assessments
/admin/activity
/admin/system
```

## Admin Dashboard

System-level statistics:

### Users

- total
- students
- teachers
- administrators
- active/inactive if supported

### Curriculum

- courses
- sections
- missions
- assessments

### Learning

- active students
- completed challenges
- course completions
- assessment attempts
- pass rates

### XP

- awarded
- spent
- deducted

All values must come from real data.

## User Management

Admin may:

- view users
- search users
- paginate users
- create users where appropriate
- update appropriate user fields
- manage roles according to authorization
- deactivate/reactivate where supported
- reset passwords through secure workflows

Never expose password hashes.

## Role Management

Allowed roles:

```text
student
teacher
admin
operator
```

Prevent unauthorized role escalation.

Consider self-lockout protection.

## Course Management

Admin may manage:

- name
- slug
- type
- description
- status
- order

Do not confuse:

```text
course.status
```

with:

```text
student progression / eligibility
```

## Section Management

Admin may manage:

- section information
- ordering
- course relationship

Preserve:

```text
Course
 ↓
Section
 ↓
Mission
```

## Mission Management

If approved, admin may manage:

- title
- description
- difficulty
- points
- section
- ordering
- challenge instructions
- hints
- target content
- solution/reference code
- validation configuration

Sensitive fields must remain protected.

## Assessment Administration

Admin may manage assessment configuration where explicitly approved.

Do not turn the Boss Challenge into an ordinary mission.

Do not casually edit historical student results.

## Audit Trail

Administrative actions should answer:

```text
WHO
WHAT
WHEN
TARGET
RESULT
```

Never log secrets.

## System Status

May show:

- application status
- database connectivity
- safe application information
- migration status where safely readable
- storage/log health where appropriate

Never expose:

- `APP_KEY`
- database password
- API keys
- authentication secrets

## Phase 7 Stories

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

## Phase 7 Integration Principle

Administrative changes must not bypass learning rules.

```text
Admin modifies curriculum
        ↓
Student learning remains valid
        ↓
Student completes challenge
        ↓
Progress updates
        ↓
XP updates
        ↓
Assessment eligibility remains correct
        ↓
Boss Challenge remains valid
        ↓
Teacher monitoring remains accurate
```

---

# 16. Phase 8 — Notifications & Learning Engagement

## Epic

**System Integration, Notifications & Learning Engagement**

## Objective

Connect the major CodeQuest modules using useful, educational notifications.

Phase 8 is not a social messaging platform.

## Notification Flow

```text
Meaningful Event
    ↓
Notification Rule
    ↓
Authorized Recipient
    ↓
Notification Record
    ↓
Notification UI
    ↓
Read / Unread
```

## Notification Types

Possible types:

```text
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

Only implement types with a real use case.

## Student Notifications

Examples:

- challenge completed
- Boss Challenge unlocked
- Boss Challenge passed
- Boss Challenge failed
- course completed
- next course unlocked
- achievement earned
- unfinished draft reminder
- system announcement

## Teacher Notifications

Possible:

- student passes Boss Challenge
- student completes course
- student needs attention
- repeated challenge failures
- meaningful inactivity

Reuse Phase 6 attention rules.

## Admin Notifications

Possible:

- important system events
- security events
- administrative events
- system announcements
- failed background processes if introduced

Do not expose secrets.

## Notification Center

Route:

```text
/notifications
```

Should show:

- unread count
- notification history
- title
- message
- type
- timestamp
- read/unread state
- valid internal destination

## Read / Unread

Users can:

- mark one notification read
- mark visible notifications read
- view unread notifications
- view history

Ownership must be checked server-side.

## Notifications vs Timeline

These are different:

```text
Timeline
→ historical learning activity

Notifications
→ messages requiring awareness or action
```

Do not convert every timeline event into a notification.

## Learning Reminders

Possible reminder conditions:

- unfinished draft
- unlocked assessment not attempted
- in-progress course with no recent activity
- remaining required challenge

Rules must be:

- deterministic
- documented
- testable
- non-spammy

Do not invent arbitrary thresholds without approval.

## Duplicate Prevention

Repeated event processing must not create duplicate notifications.

Use an appropriate idempotency strategy.

## Transaction Consistency

For example:

```text
Boss Challenge passed
    ↓
Assessment result
    ↓
Course completion
    ↓
Next-course unlock
    ↓
Relevant notifications
```

A failed transaction must not leave misleading success notifications.

## System Announcements

Admin may publish:

- maintenance notices
- new course availability
- important learning instructions
- system changes
- service limitations

Possible status:

```text
draft
published
archived
```

Drafts must not be visible.

## Announcement Auditing

Record:

```text
actor
action
announcement
timestamp
result
```

## Suggested Notification Schema

Only if no suitable table exists:

```text
the404_notifications
```

Possible fields:

```text
id
user_id
type
title
message
data
read_at
created_at
updated_at
```

This is a proposal and requires schema review before migration.

## Suggested Announcement Schema

Only if required:

```text
the404_announcements
```

Possible fields:

```text
id
created_by
title
message
audience
status
published_at
created_at
updated_at
```

## Phase 8 Stories

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

## Phase 8 Integration Flow

```text
Student completes required challenges
        ↓
Boss Challenge becomes eligible
        ↓
Assessment-unlocked notification
        ↓
Student passes Boss Challenge
        ↓
Course completion
        ↓
Next-course unlock
        ↓
Course completion notification
        ↓
Teacher monitoring remains accurate
        ↓
Admin statistics remain accurate
```

---

# 17. Feature Mapping

CodeQuest's major thesis/product features:

## 1. Mission-Based Assessment Unlocking

```text
Required missions
    ↓
Assessment eligibility
    ↓
Boss Challenge
```

## 2. Unified Learning Timeline

Connects:

- lessons
- challenges
- quizzes
- assessments
- milestones

## 3. Personalized Learning Path

Uses:

- progress
- assessment results
- competency
- recommended next activity

The learning path remains sequential for the initial system.

## 4. Competency Dashboard

Competency is based on real:

- coding performance
- assessment performance
- learning data

## 5. Boss Challenge Assessment

Each course ends with one practical Boss Challenge.

---

# 18. UI / UX Requirements

## 18.1 Dashboard

Dashboard is a resume/continue-learning entry point.

Example:

```text
CODEQUEST                                      OPERATOR_02
SYSTEM 404 // LEARNING TERMINAL                    [PROFILE]

CONTINUE LEARNING
HTML FUNDAMENTALS
Mission 04 — Image Recovery
████████████████░░░░ 70%
[ CONTINUE MISSION → ]

LEARNING PATH                 ASSESSMENT STATUS
✓ HTML Fundamentals 7/10     QUIZ 01  ✓ UNLOCKED
🔒 CSS Styling                EXAMINATION 🔒 LOCKED
🔒 JavaScript Scripting      Complete required missions
```

Continue Learning should resume the student's actual current lesson/challenge position.

It must not randomly jump into an editor.

## 18.2 Responsive

Must work at:

```text
360px
390px
430px
desktop
```

No unintended horizontal scroll.

## 18.3 Visual Identity

Preserve:

```text
--phosphor: #33ff00
```

Use:

- monospace
- CRT glow
- scanlines
- terminal panels
- emergency-system messaging
- light mode

Avoid:

- generic SaaS
- purple/blue AI dashboards
- excessive glassmorphism
- excessive rounded cards
- meaningless icons
- cosmetic redesigns

## 18.4 Editor

CodeMirror 6.

## 18.5 Preview

Sandboxed iframe.

Do not execute arbitrary student code in the main application context.

---

# 19. XP and Gamification Rules

## 19.1 XP Reward

Correct challenge submission:

```text
mission.points
```

is awarded as XP.

## 19.2 XP Penalty

Incorrect challenge submission:

```text
-10 XP
```

per submission.

XP cannot go below zero.

## 19.3 XP Uses

XP may be spent on:

- hints
- solution/reference code
- future approved learning assistance

There is no general item/shop economy.

## 19.4 XP vs Grade

These are different:

```text
XP
→ gamification / learning assistance

Assessment Score
→ assessment performance

Competency
→ skill representation

Course Completion
→ progression state
```

Do not merge them.

## 19.5 XP Audit

XP changes must be represented in an auditable transaction/ledger.

---

# 20. Assessment Rules

## 20.1 One Assessment Per Course

Exactly one Boss Challenge exists per course.

## 20.2 Eligibility

Assessment eligibility requires completion of required challenges.

## 20.3 Passing

Pass:

```text
Assessment
 ↓
Course complete
 ↓
Next course unlock
```

## 20.4 Failure

Fail:

```text
Assessment
 ↓
Feedback
 ↓
Retry
```

No next-course unlock.

## 20.5 Authority

The backend determines:

- eligibility
- scoring
- pass/fail
- course completion
- next-course unlock

Frontend state is never authoritative.

---

# 21. Progress, Drafts and Attempts

These concepts remain separate.

```text
Draft
→ unfinished code

Progress
→ completed learning requirement

Submission / Attempt
→ explicit validation attempt
```

Do not collapse them into one table.

## Progress

States:

```text
NOT STARTED
IN PROGRESS
COMPLETED
```

Completion only occurs after successful authoritative validation.

## Draft

A student may save unfinished code.

Saving a draft does not:

- award XP
- complete a mission
- unlock an assessment
- unlock a course

## Attempts

Attempts represent explicit validation/assessment actions.

Attempt history may be used for:

- teacher monitoring
- analytics
- attention rules
- assessment history

---

# 22. Competency Rules

Competency must use actual learning data.

Potential inputs:

- coding performance
- challenge completion
- assessment performance
- Boss Challenge performance

Never hard-code example values.

Never let:

```text
admin UI
teacher UI
student UI
```

directly set competency.

Use a single authoritative competency calculation/service.

---

# 23. Teacher Monitoring Rules

Teacher monitoring is read-oriented.

Teacher should be able to understand:

```text
Who is learning?
What are they learning?
How far are they?
Where are they struggling?
What assessments have they passed?
Which students may need support?
```

Teacher must not become an unrestricted administrator.

Do not introduce:

- mission CMS
- course authoring
- assessment editing
- manual XP manipulation
- manual progression

unless separately approved.

---

# 24. Admin Rules

Admin manages platform configuration.

Admin must not automatically receive authority over learning outcomes.

Administrative controls should be:

- explicit
- authorized
- auditable
- secure

Avoid unrestricted CRUD over sensitive learning records.

Historical records should generally be preserved.

Prefer deactivation over destructive user deletion when educational history exists.

---

# 25. Notification Rules

Notifications communicate state.

They do not create state.

For example:

```text
Correct:
Assessment passes
    ↓
Notification says assessment passed

Incorrect:
Notification created
    ↓
System assumes assessment passed
```

The event/state must always exist first.

## Notification Privacy

Students cannot see:

- teacher-only attention alerts
- other students
- admin activity
- validation rules
- solution code
- security information

Teachers cannot see unnecessary secrets.

Admins still should not see credentials/secrets through normal UI.

---

# 26. Testing Strategy

Testing must exist at several levels.

## 26.1 Unit Tests

Test:

- services
- validation
- XP
- progress
- competency
- attention rules
- notification rules
- duplicate prevention

## 26.2 Feature Tests

Test:

- routes
- authentication
- authorization
- forms
- responses
- role restrictions

## 26.3 Security Tests

Test:

- IDOR
- role escalation
- mass assignment
- CSRF
- sensitive data exposure
- unauthorized state mutation
- recipient tampering
- arbitrary redirects

## 26.4 Integration Tests

Test complete workflows.

### Student Flow

```text
Course
 ↓
Section
 ↓
Lesson
 ↓
Challenge
 ↓
Draft
 ↓
Submission
 ↓
Validation
 ↓
XP
 ↓
Progress
 ↓
Boss Challenge
 ↓
Course Completion
 ↓
Next Course
```

### Teacher Flow

```text
Student activity
 ↓
Progress
 ↓
Assessment
 ↓
Competency
 ↓
Teacher monitoring
```

### Admin Flow

```text
Admin configuration
 ↓
Student learning
 ↓
Progress
 ↓
Assessment
 ↓
Teacher monitoring
 ↓
Admin statistics
```

### Notification Flow

```text
Domain event
 ↓
Notification
 ↓
Correct recipient
 ↓
Read/unread
 ↓
No duplicate
```

---

# 27. Migration Safety

## Absolute Rule

Never migrate the production/authoritative `system404` database just to test whether a migration works.

Process:

```text
Inspect schema
    ↓
Design migration
    ↓
Review/approve migration
    ↓
Test isolated database
    ↓
Verify MariaDB compatibility
    ↓
Only then consider applying
```

## Existing Data

Protect:

```text
users
courses
sections
missions
progress
activity
XP transactions
assessment results
achievement history
```

Do not accidentally delete or rewrite historical records.

## Schema Changes

Prefer reuse over duplication.

Do not create:

```text
new_progress_table
new_xp_table
new_competency_table
```

when existing authoritative systems already serve those purposes.

---

# 28. Legacy Migration Strategy

Legacy project:

```text
~/system404
```

Laravel project:

```text
~/codequest
```

Do not modify the legacy project unless explicitly instructed.

Use:

```text
Understand
  ↓
Rebuild UI in Blade
  ↓
Reuse/replicate verified backend behavior
  ↓
Integrate gradually
  ↓
Verify
```

## Legacy Frontend

Do not delete or rename:

```text
legacy/INDEX.html
```

until migration is explicitly complete and approved.

## Legacy Backend

Use it as a behavior/reference source.

Do not blindly copy insecure legacy patterns.

---

# 29. Definition of Done

The overall CodeQuest project should satisfy:

## Product

- structured learning path
- courses
- sections
- lessons
- coding challenges
- live preview
- server validation
- drafts
- XP
- assessments
- Boss Challenges
- course gating
- competency
- achievements
- timeline
- teacher monitoring
- admin management
- notifications

## Security

- authentication
- RBAC
- server-side authorization
- IDOR protection
- CSRF
- secure password hashing
- no sensitive data leakage
- no arbitrary student-code execution
- no client-side learning authority

## Architecture

- thin controllers
- behavior-oriented services
- data-oriented repositories
- pragmatic SOLID
- DRY where useful
- no unnecessary abstractions
- dependency injection

## Database

- MariaDB compatible
- existing authoritative data preserved
- no accidental migrations
- no duplicate domain systems
- auditable XP
- protected assessment records

## UX

- freeCodeCamp-like learning flow
- Boot.dev-like progression
- System 404 visual identity
- CRT theme
- phosphor green
- responsive layout
- mobile no-scroll
- CodeMirror editor
- sandboxed preview

## Testing

- unit tests
- feature tests
- security tests
- integration tests
- regression tests
- static analysis

---

# 30. Final Project Quality Checklist

Before declaring a phase complete, verify:

## Requirements

- [ ] All stories implemented
- [ ] All acceptance criteria tested
- [ ] Out-of-scope requirements were not accidentally added

## Architecture

- [ ] Controllers are thin
- [ ] Services own business behavior
- [ ] Repositories own data access
- [ ] No unnecessary interfaces
- [ ] No manual service construction
- [ ] Existing services are reused

## Security

- [ ] Authentication works
- [ ] Authorization works
- [ ] IDOR tested
- [ ] Role escalation tested
- [ ] Mass assignment tested
- [ ] CSRF tested
- [ ] Sensitive data protected
- [ ] Client cannot control authoritative state

## Database

- [ ] Existing schema inspected
- [ ] No unnecessary duplicate tables
- [ ] Migration reviewed
- [ ] Migration tested separately
- [ ] Real `system404` database not modified without approval
- [ ] Existing data preserved

## Testing

- [ ] Unit tests pass
- [ ] Feature tests pass
- [ ] Integration tests pass
- [ ] Security tests pass
- [ ] Regression tests pass
- [ ] PHPStan/Larastan status reported honestly

## UI

- [ ] 360px tested
- [ ] 390px tested
- [ ] 430px tested
- [ ] Desktop tested
- [ ] No horizontal overflow
- [ ] CRT identity preserved
- [ ] Light mode preserved
- [ ] Keyboard accessibility considered

## Final Reporting

Every completed phase should report:

```text
PHASE IMPLEMENTATION REPORT

1. Summary
2. PHPStan/Larastan Status
3. Agile Stories
4. Architecture
5. Database Changes
6. Security Review
7. Cross-Cutting Integration Test
8. Test Results
9. Static Analysis
10. Responsive/UI Review
11. Code Quality
12. Migration Status
13. Known Issues / Risks
14. Files Changed
15. Acceptance Criteria
16. Phase Status
17. Recommended Next Phase
```

---

# Project Status Summary

```text
Phase 1
Design System & Application Shell
STATUS: Completed

Phase 2
Foundation / Backend Architecture
STATUS: Established

Phase 3
Student Learning & Mission System
STATUS: Completed
Tests: 128 passing / 269 assertions

Phase 4
Assessment / Boss Challenge
STATUS: Completed and Accepted

Phase 5
Quality, Progress & Gamification Foundation
STATUS: Established / ongoing specification

Phase 6
Teacher / Instructor Monitoring
STATUS: Specification established

Phase 7
Admin & System Management
STATUS: Specification established

Phase 8
Notifications & Learning Engagement
STATUS: Specification established
```

---

# Core CodeQuest Invariants

These rules must remain true across all future phases:

```text
1. Students write code from scratch.
2. Server-side validation is authoritative.
3. Preview is never proof of completion.
4. Progress is not the same as drafts.
5. Attempts are separate from progress.
6. XP is not a grade.
7. XP cannot go below zero.
8. XP changes are auditable.
9. There is exactly one Boss Challenge per course.
10. Boss Challenge is separate from ordinary missions.
11. Required challenges gate assessment eligibility.
12. Passing the Boss Challenge gates course completion.
13. Course completion unlocks the next course.
14. Teachers monitor; they do not become CMS administrators.
15. Admins manage the platform; they do not automatically modify learning outcomes.
16. Competency comes from real learning/assessment data.
17. Notifications communicate state; they do not create state.
18. Client-side JavaScript is never the security boundary.
19. Existing authoritative database data must be protected.
20. `~/system404` must not be modified unless explicitly instructed.
21. No arbitrary student-code execution.
22. No unnecessary domain duplication.
23. No migration against the real database without explicit approval.
24. Preserve the CodeQuest CRT/System 404 identity.
25. Prefer incremental, testable implementation over big-bang rewrites.
```

---

# CodeQuest Product Identity

```text
                 CODEQUEST

       FREECODECAMP-STYLE LEARNING
                    +
        BOOT.DEV-STYLE PROGRESSION
                    +
          SYSTEM 404 CRT IDENTITY

                    ↓

        LEARN → PRACTICE → CODE
                    ↓
                 TEST
                    ↓
                 RETRY
                    ↓
                COMPLETE
                    ↓
                ASSESS
                    ↓
                IMPROVE
```

**CodeQuest is an integrated gamified learning and assessment system, not merely a coding editor, quiz application, dashboard, or CRUD administration panel.**

Its central purpose is to guide a learner from:

```text
Learning
→ Practice
→ Coding
→ Validation
→ Mastery
→ Assessment
→ Course Completion
```

while giving teachers the ability to understand learner progress and administrators the ability to maintain the platform securely.
