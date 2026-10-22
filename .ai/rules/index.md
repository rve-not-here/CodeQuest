# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| resources/views/admin/dashboard.blade.php, resources/views/admin/** | .ai/rules/admin.md |
| app/** | .ai/rules/app.md |
| resources/views/components/theme-toggle.blade.php | .ai/rules/components.md |
| app/Http/{Controllers,Requests}/KnowledgeCheck*.php | .ai/rules/controllers-requests.md |
| app/Http/Controllers/**, app/Http/Controllers/CompetencyController.php, app/Http/Controllers/NotificationController.php, app/Http/Controllers/StudentController.php, app/Http/Controllers/DashboardController.php, app/Http/Controllers/AdminKnowledgeCheckController.php, app/Http/Controllers/TimelineController.php, app/Http/Controllers/KnowledgeCheckController.php | .ai/rules/controllers.md |
| app/Exceptions/** | .ai/rules/exceptions.md |
| tests/Feature/AssessmentIntegrationTest.php, tests/Feature/ProgressIntegrationTest.php, tests/Feature/JourneyIntegrationTest.php, tests/Feature/**, tests/Feature/TeacherDashboardTest.php, tests/Feature/AdminAuthorizationTest.php, tests/Feature/AdminDashboardTest.php, tests/Feature/AdminMissionManagementTest.php, tests/Feature/*Assessment*.php, tests/Feature/AdminSystemTest.php, tests/Feature/AdministrativeIntegrationTest.php, tests/Feature/StudentReminderTest.php, tests/Feature/NotificationSecurityTest.php | .ai/rules/feature.md |
| .env, **, phpstan.neon, .scratch/phase*/** | .ai/rules/general.md |
| resources/js/app.js | .ai/rules/js.md |
| resources/views/layouts/**, resources/views/layouts/app.blade.php | .ai/rules/layouts.md |
| app/Http/Middleware/EnsureUserIsActive.php | .ai/rules/middleware.md |
| database/migrations/the404_*.php, database/migrations/**, database/migrations/*knowledge_check* | .ai/rules/migrations.md |
| app/{Models,Services}/KnowledgeCheck*.php | .ai/rules/models-services.md |
| app/Models/Progress.php, app/Models/** | .ai/rules/models.md |
| app/Http/Requests/AdminUserStoreRequest.php, app/Http/Requests/AdminCourseUpdateRequest.php, app/Http/Requests/AdminMissionUpdateRequest.php, app/Http/Requests/Admin*Request.php, app/Http/Requests/**, app/Http/Requests/{ActivityFeedRequest,StudentOverviewRequest}.php, app/Http/Requests/{AdminAssessmentUpdateRequest,AdminMissionUpdateRequest}.php | .ai/rules/requests.md |
| resources/css/** | .ai/rules/resources-css.md |
| routes/web.php, routes/** | .ai/rules/routes.md |
| database/seeders/CurriculumContentSeeder.php | .ai/rules/seeders.md |
| app/Services/**, app/Services/*.php, app/Services/XpService.php, app/Services/AssessmentService.php, app/Services/CompetencyService.php, app/Services/RecommendationService.php, app/Services/StudentService.php, app/Services/AttentionService.php, app/Services/TeacherDashboardService.php, app/Services/UserService.php, app/Services/CourseService.php, app/Services/Admin*Service.php, app/Services/SystemStatusService.php, app/Services/NotificationService.php, app/Services/AttentionNotificationService.php, app/Services/StudentReminderService.php, app/Services/ReportAuthorizationService.php, app/Services/ClassroomAccessService.php, app/Services/KnowledgeCheckGate.php, app/Services/DashboardService.php, app/Services/PerformanceSummaryService.php, app/Services/TimelineService.php, app/Services/ChallengeAnalyticsService.php, app/Services/AdminKnowledgeCheckService.php, app/Services/KnowledgeCheckService.php | .ai/rules/services.md |
| app/Support/SchemaAvailability.php | .ai/rules/support.md |
| tests/**, grader/test/integration-container.sh | .ai/rules/tests.md |
| resources/views/**, resources/views/dashboard.blade.php, resources/views/students.blade.php, resources/views/learning-path.blade.php, resources/views/lesson.blade.php, resources/views/challenge.blade.php | .ai/rules/views.md |
