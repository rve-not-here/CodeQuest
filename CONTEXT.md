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
A summative evaluation that gates course progression. One per course, placed after the required missions. The Boss Challenge is the final assessment for a course. Passing the assessment unlocks the next course.
_Avoid_: Knowledge Check, quiz, exam

**Knowledge Check**:
A formative concept check attached to a mission's Lesson. It contains ordered selectable-answer questions and preserves every submitted attempt and response for review. A required check may gate that mission's Coding Challenge, but it never completes a mission or course, replaces the Boss Challenge, awards XP, or calculates competency.
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
A spendable resource. Earned by completing missions, lost on wrong submissions (-10), spent on hints (progressive: -5, -10, -15) and solution reveals (-30). Every change is logged in the XP transaction table. The balance is the sum of all transactions. Server-authoritative, auditable.
_Avoid_: Points (points is the mission's value; XP is the student's balance)

**XP Transaction**:
A single row in the XP transaction log recording one XP change. Contains the signed amount, a type (mission_completed, wrong_submission, hint_used, solution_revealed), and a human-readable description. The transaction log is the authoritative source for XP balance.
_Avoid_: XpLog, xp_event

**Competency**:
A skill area the student demonstrates, mapped one-to-one to a course (HTML, CSS, JavaScript, keyed by the course's type). Its state (not started, developing, practicing, demonstrated) derives only from real learning and assessment data: completed missions, Boss Challenge attempts, and the Boss Challenge pass. Demonstrated is permanent once earned. XP is progression, never competency.
_Avoid_: Skill score, XP-based rating, per-section competency

**Operator**:
A reserved system role in the users table. No dedicated UI yet.
_Avoid_: Admin (admins have instructor-level access; operators are distinct)
