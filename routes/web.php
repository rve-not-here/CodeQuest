<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AdminActivityController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AdminAnnouncementController;
use App\Http\Controllers\AdminAssessmentController;
use App\Http\Controllers\AdminClassroomController;
use App\Http\Controllers\AdminCourseController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminMissionController;
use App\Http\Controllers\AdminSectionController;
use App\Http\Controllers\AdminSystemController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AttentionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CompetencyController;
use App\Http\Controllers\CourseAnalyticsController;
use App\Http\Controllers\CourseProgressController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KnowledgeCheckController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\MissionIndexController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RecommendationsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SectionProgressController;
use App\Http\Controllers\ShellController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentProgressController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\XpLedgerController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/shell', ShellController::class)->name('shell');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->middleware('student')->name('dashboard');

    // Learning path (courses -> sections -> missions)
    Route::get('/learning-path', LearningPathController::class)->middleware('student')->name('learning-path');

    // Course progress overview (every course's mission state + status label)
    Route::get('/progress', CourseProgressController::class)->middleware('student')->name('progress');

    // Section progress overview (per-section mission state, grouped by course)
    Route::get('/section-progress', SectionProgressController::class)->middleware('student')->name('section-progress');

    // Report exports (US-1010, CSV only): the student's own progress as a
    // download. Authorization and filters match the on-screen report.
    Route::get('/export/progress', [ReportExportController::class, 'studentProgress'])->middleware('student')->middleware('throttle:report-export')->name('export.progress');
    Route::get('/reports/progress', [ReportController::class, 'student'])->middleware('student')->name('reports.progress');

    // Mission / challenge
    Route::get('/missions', MissionIndexController::class)->middleware('student')->name('missions');
    Route::get('/missions/{mission}', [MissionController::class, 'show'])->middleware('student')->name('mission.show');
    Route::get('/missions/{mission}/challenge', [MissionController::class, 'challenge'])->middleware('student')->name('mission.challenge');
    Route::get('/missions/{mission}/experiment', [MissionController::class, 'experiment'])->middleware('student')->name('mission.experiment');
    Route::post('/missions/{mission}/submit', [MissionController::class, 'submit'])->middleware(['student', 'throttle:academic-submit'])->name('mission.submit');
    Route::post('/missions/{mission}/draft', [MissionController::class, 'saveDraft'])->middleware(['student', 'throttle:academic-draft'])->name('mission.draft');
    Route::post('/missions/{mission}/hints', [MissionController::class, 'hint'])->middleware(['student', 'throttle:academic-assistance'])->name('mission.hint');
    Route::post('/missions/{mission}/reveal', [MissionController::class, 'reveal'])->middleware(['student', 'throttle:academic-assistance'])->name('mission.reveal');

    Route::scopeBindings()->group(function (): void {
        Route::post('/missions/{mission}/knowledge-checks/{knowledgeCheck}/start', [KnowledgeCheckController::class, 'start'])
            ->middleware(['student', 'throttle:academic-submit'])->name('knowledge-check.start');
        Route::get('/missions/{mission}/knowledge-checks/{knowledgeCheck}/attempts/{attempt}', [KnowledgeCheckController::class, 'show'])
            ->middleware('student')->name('knowledge-check.show');
        Route::post('/missions/{mission}/knowledge-checks/{knowledgeCheck}/attempts/{attempt}/submit', [KnowledgeCheckController::class, 'submit'])
            ->middleware(['student', 'throttle:academic-submit'])->name('knowledge-check.submit');
        Route::post('/missions/{mission}/knowledge-checks/{knowledgeCheck}/retry', [KnowledgeCheckController::class, 'retry'])
            ->middleware(['student', 'throttle:academic-submit'])->name('knowledge-check.retry');
    });

    // Boss Challenge (assessments)
    Route::get('/assessments', [AssessmentController::class, 'index'])->middleware('student')->name('assessments');
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])->middleware('student')->name('assessment.show');
    Route::post('/assessments/{assessment}/start', [AssessmentController::class, 'start'])->middleware(['student', 'throttle:academic-submit'])->name('assessment.start');
    Route::post('/assessments/{assessment}/submit', [AssessmentController::class, 'submit'])->middleware(['student', 'throttle:academic-submit'])->name('assessment.submit');
    Route::post('/assessments/{assessment}/retry', [AssessmentController::class, 'retry'])->middleware(['student', 'throttle:academic-submit'])->name('assessment.retry');

    // Unified learning timeline (US-505)
    Route::get('/timeline', TimelineController::class)->middleware('student')->name('timeline');

    // XP ledger history (US-506)
    Route::get('/xp-ledger', XpLedgerController::class)->middleware('student')->name('xp-ledger');

    // Competency dashboard (US-507)
    Route::get('/competency', CompetencyController::class)->middleware('student')->name('competency');

    // Personalized recommendations (US-509)
    Route::get('/recommendations', RecommendationsController::class)->middleware('student')->name('recommendations');

    // Notification center (US-801, US-802, US-803): only the authenticated
    // user's own rows. Mark-read POST routes (US-803) resolve via
    // findForUser; {notification} is whereNumber so a numeric foreign or
    // nonexistent id is a safe no-op, not a 404 that distinguishes existence.
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->whereNumber('notification')->name('notifications.read');
    Route::get('/notifications', NotificationController::class)->name('notifications');

    // Achievements registry (US-508 display): the authenticated user's own
    // catalog. Awards remain server-only through AchievementService::award().
    Route::get('/achievements', AchievementController::class)->middleware('student')->name('achievements');
});

// Teacher area (US-601): authenticated, role-gated to teacher or admin.
// Guests are redirected to login by the auth middleware (the 'teacher'
// middleware runs only once authenticated); any other authenticated role is
// rejected with 403. 'students' is realized by US-602 (StudentController);
// 'student-progress' is realized by US-603 (StudentProgressController) with an
// optional path-bound student — a bare visit bounces to the roster, because
// the detail page is reached via the roster's deep-links, not the sidebar
// (US-604 removed the standalone nav item). US-604 expands that same page with
// a read-only Assessment Performance section; US-605 adds a read-only Competency
// section — no separate route was needed for either.
// 'activity' is realized by US-606 (ActivityController): the Learning Activity
// feed across students, on the same teacher-gated route the sidebar already
// links. 'student' is a legit validated filter key on this page (system-wide
// gate, narrowing only); the other user-scoping spellings are still rejected.
// Apply this same 'teacher' middleware to every route added in US-602..US-610.
Route::middleware(['auth', 'teacher'])->group(function (): void {
    Route::get('/reports/teacher', [ReportController::class, 'teacherIndex'])->name('reports.teacher');
    Route::get('/reports/teacher/students/{student}', [ReportController::class, 'teacherStudent'])->name('reports.teacher-student');
    Route::get('/reports/teacher/courses/{course}', [ReportController::class, 'teacherCourse'])->name('reports.teacher-course');
    Route::get('/students', StudentController::class)->name('students');
    Route::get('/student-progress/{student?}', StudentProgressController::class)->name('student-progress');
    Route::get('/activity', ActivityController::class)->name('activity');
    Route::get('/course-analytics', CourseAnalyticsController::class)->name('course-analytics');
    Route::get('/needs-attention', AttentionController::class)->name('needs-attention');

    // Classroom / Enrollment Authorization (teacher read side): a teacher sees
    // exactly their own teaching classrooms while ACTIVE, and the monitoring
    // data on every teacher page above is scoped in depth by
    // ClassroomAccessService. Read-only GETs, like the rest of the teacher area.
    Route::get('/classrooms', [ClassroomController::class, 'index'])->name('classrooms');
    Route::get('/classrooms/{classroom}', [ClassroomController::class, 'show'])->name('classrooms.show');

    // Report exports (US-1010, CSV only): teacher-scoped student and course
    // downloads. Same authorization, scope, and filters as the reports.
    Route::get('/export/teacher/students/{student}', [ReportExportController::class, 'teacherStudent'])->middleware('throttle:report-export')->name('export.teacher-student');
    Route::get('/export/teacher/courses/{course}', [ReportExportController::class, 'teacherCourse'])->middleware('throttle:report-export')->name('export.teacher-course');
});

// Admin area (US-701): authenticated, admin-only. The 'admin' middleware is
// stricter than the teacher 'teacher' middleware — teacher, student, and
// operator are all rejected with 403. Fully separate from the teacher group:
// every route added by US-702..US-713 must land inside the admin group, never
// in the teacher group and never without the 'admin' middleware.
Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/admin', AdminDashboardController::class)->name('admin.dashboard');

    // Report exports (US-1010, CSV only): fleet system download under the
    // same admin authorization, scope, and filters as the system report.
    Route::get('/export/admin/system', [ReportExportController::class, 'adminSystem'])->middleware('throttle:report-export')->name('export.admin-system');
    Route::get('/reports/admin/system', [ReportController::class, 'system'])->name('reports.admin-system');

    // User management (US-703, §9.0–§12.0): directory + create/edit. Since
    // US-704 update also accepts optional role (UserService::ROLES, operator
    // included) and status (active|inactive) changes, guarded in UserService:
    // no self role-away-from-admin, no self-deactivation, active-admin fleet
    // stays ≥ 2. Create still takes no status input and only CREATABLE_ROLES.
    // System announcements (US-807, §30.0–§33.0): admin-authored broadcast
    // messages on a draft → published → archived lifecycle. Audience
    // (all|students|teachers|admins) is server-determined; publish fans out
    // one SYSTEM_ANNOUNCEMENT notification per matching ACTIVE user exactly
    // once (first publish only, dedupe_key announcement:{id}); editing a
    // published announcement never re-notifies; archiving is silent;
    // re-announcing means a new announcement. Create is in scope (the admin
    // owns the announcement surface) and archiving is the terminal state —
    // there is no delete/destroy route (§14 no-destructive posture applies to
    // educational records, not the announcement surface, but archive keeps
    // the trail append-only either way).
    Route::get('/admin/announcements', [AdminAnnouncementController::class, 'index'])->name('admin.announcements');
    Route::get('/admin/announcements/create', [AdminAnnouncementController::class, 'create'])->name('admin.announcements.create');
    Route::post('/admin/announcements', [AdminAnnouncementController::class, 'store'])->name('admin.announcements.store');
    Route::get('/admin/announcements/{announcement}/edit', [AdminAnnouncementController::class, 'edit'])->name('admin.announcements.edit');
    Route::put('/admin/announcements/{announcement}', [AdminAnnouncementController::class, 'update'])->name('admin.announcements.update');
    Route::post('/admin/announcements/{announcement}/publish', [AdminAnnouncementController::class, 'publish'])->name('admin.announcements.publish');
    Route::post('/admin/announcements/{announcement}/archive', [AdminAnnouncementController::class, 'archive'])->name('admin.announcements.archive');

    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::get('/admin/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');

    // Course catalog (US-705, §15.0–§17.0): ordered listing + edit existing
    // courses (name/slug/type/description/status/order_num). order_num is the
    // single ordering mechanism (preserved, no second sort). Status changes
    // are an access gate and never rewrite student progress (CourseService
    // writes only the course row). Create/delete are out of scope for this
    // story — only existing courses are editable.
    Route::get('/admin/courses', [AdminCourseController::class, 'index'])->name('admin.courses');
    Route::get('/admin/courses/{course}/edit', [AdminCourseController::class, 'edit'])->name('admin.courses.edit');
    Route::put('/admin/courses/{course}', [AdminCourseController::class, 'update'])->name('admin.courses.update');

    // Section management (US-706, §18.0): nested under the course so the
    // Course → Section → Mission hierarchy is explicit in the URL. Lists the
    // course's sections with mission counts and edits title/description/
    // order_num. Sections carry no status (course.status is the gate for
    // everything beneath them). Create/delete are out of scope for this
    // story — only existing sections are editable.
    Route::get('/admin/courses/{course}/sections', [AdminSectionController::class, 'index'])->name('admin.courses.sections');
    Route::get('/admin/courses/{course}/sections/{section}/edit', [AdminSectionController::class, 'edit'])->name('admin.courses.sections.edit');
    Route::put('/admin/courses/{course}/sections/{section}', [AdminSectionController::class, 'update'])->name('admin.courses.sections.update');

    // Mission management (US-707, §19.0): nested under the course so the
    // Course → Section → Mission hierarchy is explicit in the URL. Lists the
    // course's missions and edits title/description/difficulty/points/
    // order_num/section_id/hints/broken_code/target_html. Missions carry no
    // status (course.status is the gate for everything beneath them);
    // solution_code and validate_rule are view-only and never writable here.
    // Create/delete are out of scope for this story — only existing missions
    // are editable.
    Route::get('/admin/courses/{course}/missions', [AdminMissionController::class, 'index'])->name('admin.courses.missions');
    Route::get('/admin/courses/{course}/missions/{mission}/edit', [AdminMissionController::class, 'edit'])->name('admin.courses.missions.edit');
    Route::put('/admin/courses/{course}/missions/{mission}', [AdminMissionController::class, 'update'])->name('admin.courses.missions.update');

    // Assessment management (US-708, §24.0): nested under the course, but
    // singular — a course has exactly one Boss Challenge (the the404_assessments
    // unique course_id constraint), so there is one index/edit/update, not a
    // collection. Edits title/description/instructions/passing_score/status.
    // grading_rule is view-only (Authored via the AssessmentSeeder, the same
    // controlled path as solution_code/validate_rule). assessment.status is an
    // access gate: locking/drafting seals the challenge but never rewrites
    // recorded attempts. Create/delete are out of scope for this story.
    Route::get('/admin/courses/{course}/assessment', [AdminAssessmentController::class, 'index'])->name('admin.courses.assessment');
    Route::get('/admin/courses/{course}/assessment/{assessment}/edit', [AdminAssessmentController::class, 'edit'])->name('admin.courses.assessment.edit');
    Route::put('/admin/courses/{course}/assessment/{assessment}', [AdminAssessmentController::class, 'update'])->name('admin.courses.assessment.update');

    // Administrative Audit Trail (US-709, §27.0): the system-wide append-only
    // ledger (AdminAuditService::record, the only writer). Filters are
    // server-side and validated (AdminActivityFeedRequest); 'actor' narrows
    // the admin-gated view, and the untrusted user-scoping spellings are
    // rejected at the controller. Route is not nested — the trail spans every
    // admin mutation, not a single course's.
    Route::get('/admin/activity', AdminActivityController::class)->name('admin.activity');

    // System Analytics (US-710, §29.0/§30.0): a fleet-wide drill-down page.
    // Reuses the admin dashboard's metrics and the teacher analytics course
    // table, and is the only place that surfaces fleet-level XP administration
    // statistics. Strictly a read — no CRUD on users, courses, or the XP
    // ledger (§30.0). Route is not nested: it spans the whole fleet.
    Route::get('/admin/analytics', AdminAnalyticsController::class)->name('admin.analytics');

    // System Status (US-711, §33.0): operational/infrastructure visibility —
    // application version + environment, real database connectivity, migration
    // status, and storage/log health. Unlike the rest of Phase 7 this is a
    // NEW kind of check (infrastructure, not data aggregation), so it is
    // read-only by construction and the secret boundary is enforced
    // structurally: SystemStatusService only surfaces config keys on an
    // explicit SAFE_CONFIG_KEYS allowlist, never credentials, keys, or
    // connection internals. Route is not nested: it describes the deployment.
    Route::get('/admin/system', AdminSystemController::class)->name('admin.system');

    // Classroom management (Classroom / Enrollment Authorization): admin-owned
    // CRUD for the three base fields and the three membership assignment sets.
    // Create + assignments + edit run through ClassroomService and write a
    // persist audit trail row for every applied change and every refusal. The
    // assignment POSTs are separate membership operations — they never touch
    // academic history, and a status change never rewrites (or clears) the
    // pivots. No delete/destroy route (§14 no-destructive posture).
    Route::get('/admin/classrooms', [AdminClassroomController::class, 'index'])->name('admin.classrooms');
    Route::get('/admin/classrooms/create', [AdminClassroomController::class, 'create'])->name('admin.classrooms.create');
    Route::post('/admin/classrooms', [AdminClassroomController::class, 'store'])->name('admin.classrooms.store');
    Route::get('/admin/classrooms/{classroom}/edit', [AdminClassroomController::class, 'edit'])->name('admin.classrooms.edit');
    Route::put('/admin/classrooms/{classroom}', [AdminClassroomController::class, 'update'])->name('admin.classrooms.update');
    Route::post('/admin/classrooms/{classroom}/teachers', [AdminClassroomController::class, 'assignTeachers'])->name('admin.classrooms.teachers');
    Route::post('/admin/classrooms/{classroom}/students', [AdminClassroomController::class, 'enrollStudents'])->name('admin.classrooms.students');
    Route::post('/admin/classrooms/{classroom}/courses', [AdminClassroomController::class, 'assignCourses'])->name('admin.classrooms.courses');
});
