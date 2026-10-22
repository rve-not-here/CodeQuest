# CodeQuest

A web-based learning platform where students learn HTML, CSS, and JavaScript by writing code from scratch. Built on Laravel with a retro-terminal "System 404" theme.

## Language

**Mission**:
A database record representing a single coding exercise within a course section. Contains the description, reference solution, validation rules, and point value.
_Avoid_: Challenge (use only as a student-facing label in views)

**Challenge**:
The student-facing term for a mission. What the UI calls it, not what the code calls it.

**Section**:
A group of missions within a course, ordered and titled. Sections divide a course into chapters.
_Avoid_: Chapter, module

**Course**:
A substantial learning unit (e.g. HTML Fundamentals). Contains sections, which contain missions. Courses are ordered in the learning path.
_Avoid_: Track, path, unit

**Learning Path**:
The ordered sequence of all courses. Students progress through it linearly.
_Avoid_: Curriculum, syllabus

**Assessment**:
A summative evaluation that gates course progression. One per course, placed after the required missions. The Boss Challenge is the final assessment for a course. Passing requires a valid threshold, every structural grading rule, and every configured hidden behavioural test to pass; the score is the combined percentage of those checks. Passing the assessment unlocks the next course.
_Avoid_: Knowledge Check, quiz, exam

**Assessment Attempt**:
One student's try at a Boss Challenge. It records the submitted source, status, score, the curriculum revision and rule/threshold snapshot used for the verdict, hidden-test counts, and sanitized failure feedback. A grader outage leaves the attempt submitted (no verdict, no XP) so it can be re-evaluated rather than being recorded as a student failure.
_Avoid_: Mission attempt, Knowledge Check Attempt

**Knowledge Check**:
A formative concept check attached to a mission's Lesson. Every mission owns one: it contains ordered selectable-answer questions and preserves every submitted attempt and response for review. A check is required: it gates that mission's Coding Challenge until any attempt is submitted; a submitted attempt clears the gate whether it passes or fails, and already-completed missions are exempt. The check lives in the mission's own panel at the foot of the lesson, where it opens in place — started, answered and reviewed without leaving the lesson. The student meets every check in the mission it belongs to; there is no separate check list. Instructors manage the title, instructions, required flag, and lifecycle status through the admin Knowledge Check screen. It never completes a mission or course, replaces the Boss Challenge, awards XP, or calculates competency.
_Avoid_: Quiz, exam, test, assessment

**Knowledge Check Attempt**:
One student's immutable try at a Knowledge Check. It starts in progress, becomes submitted after atomic server scoring, and retains question-level answer snapshots. Retrying creates a new numbered attempt instead of overwriting history.
_Avoid_: Mission attempt, Assessment Attempt

**Boss Challenge**:
The final assessment for a course. An integrated, real-world coding task that tests everything learned in that course.

**Mission Draft**:
A student's in-progress code for a mission. Saved automatically. Separate from submissions and progress.
_Avoid_: Submission (submissions are explicit sends for validation)

**Progress**:
A record that a mission was completed successfully. Tied to XP earned and completion timestamp. The `pts_earned` column is a cache; the XP transaction log is the source of truth.
_Avoid_: Completion, done

**XP**:
A spendable resource. Earned by completing missions (+mission points) and passing a Boss Challenge the first time (+100), lost on wrong submissions (-10), spent on hints (progressive: -10, -20, -30) and solution reveals (-75, only after a failed attempt). A failed Boss attempt carries no XP penalty. Every change is logged in the XP transaction table. The balance is the sum of all transactions. Server-authoritative, auditable.
_Avoid_: Points (points is the mission's value; XP is the student's balance)

**XP Transaction**:
A single row in the XP transaction log recording one XP change. Contains the signed amount, a type (mission_completed, assessment_completed, wrong_submission, hint_used, solution_revealed), and a human-readable description. Exactly one of `mission_id` and `assessment_id` attributes the row, with the curriculum revision stamped alongside it. The transaction log is the authoritative source for XP balance.
_Avoid_: XpLog, xp_event

**Competency**:
A skill area the student demonstrates, mapped one-to-one to a course (HTML, CSS, JavaScript, keyed by the course's type). Its state (not started, developing, practicing, demonstrated) derives only from real learning and assessment data: mapped Knowledge Check correctness, mapped mission completions, and mapped Boss Challenge evidence (ever passed = full credit; attempted without a pass = zero; no attempt = no Boss component). Demonstrated is permanent once earned. XP is progression, never competency.
_Avoid_: Skill score, XP-based rating, per-section competency

**Operator**:
A reserved system role in the users table. No dedicated UI yet.
_Avoid_: Admin (admins have instructor-level access; operators are distinct)
