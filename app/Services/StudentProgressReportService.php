<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Support\ReportFilters;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Student progress report (US-1002). The student-facing descriptive report
 * over the student's own authoritative records. Nothing here grades,
 * progresses, unlocks, scores, or recommends: every figure is either a
 * verbatim subsection of an accepted service or a simple COUNT of stored
 * rows. No ranking, no classmate comparison, no teacher or admin data, no
 * prediction.
 *
 * Reporting contract:
 * - authorization: none lives here. The caller must already have established
 *   student-self access (ReportAuthorizationService::canViewStudentReport
 *   with the viewer as the student) before invoking the report. A null
 *   course scope is the student's whole catalog because the caller said so.
 * - progress: per-course state from CompetencyService::overview() (the
 *   single per-course calculation path), plus the current course and the
 *   next mission from DashboardService (the authoritative progression
 *   owner). Current/cumulative state, never backdated.
 * - challenges: ChallengeAnalyticsService::summarize() verbatim (US-1006).
 * - assessments: AssessmentAnalyticsService::summarize() verbatim (US-1005).
 * - competency: skill rows and the weak list projected field-for-field from
 *   CompetencyService::skills() and ::weakSkills(). No percentage or
 *   threshold lives here; weak stays defined only by the competency domain.
 * - xp: the current balance from XpService::balance(). Current state; the
 *   balance is never reconstructed from transaction sums.
 * - achievements: AchievementService::catalog() verbatim plus
 *   ::currentStreak(). Current state.
 * - timeline: the owner's recent beats (latest 20, course scope aware).
 *   Neither the owner nor this report accepts a selected date range for it,
 *   so it is recent activity by definition — a selected period never
 *   filters it, and the regression suite pins that distinction.
 * - recommendations: the owner's current advisory cards. Neither the owner
 *   nor this report accepts a selected date range for them, so they are
 *   current state by definition — a selected period never filters them,
 *   and the regression suite pins that distinction.
 * - period: descriptive movement inside the filter range (or all time when
 *   unfiltered): mission completions, wrong submissions, assessment
 *   attempts and passes. Each count reads its own authoritative table on
 *   that table's event timestamp and never feeds back into state.
 * - filter applicability: date narrows challenges/assessments/period only;
 *   the requested student must equal the report student or the entire
 *   report fails closed to its neutral/empty shape — a mismatched student
 *   filter exposes no subject facts at all, neither the subject's nor the
 *   requested student's. Course narrows progress/competency/challenges/
 *   assessments/timeline/recommendations/period, while xp, achievements,
 *   and streak are per-student current-state facts the domain does not
 *   attribute to courses. No accepted dimension is silently ignored, and
 *   every scope mismatch fails closed. No status vocabulary is registered,
 *   so validated filters can never carry one.
 * - null/zero: empty scopes yield empty lists and zero counts; owned-service
 *   rates read null exactly when the owner says so.
 */
class StudentProgressReportService
{
    public function __construct(
        private readonly AssessmentAnalyticsService $assessments,
        private readonly ChallengeAnalyticsService $challenges,
        private readonly CompetencyService $competencies,
        private readonly DashboardService $dashboard,
        private readonly XpService $xp,
        private readonly AchievementService $achievements,
        private readonly TimelineService $timeline,
        private readonly RecommendationService $recommendations,
    ) {}

    /**
     * @param  Collection<int, int>|null  $courseIds  pre-authorized scope; null is the whole catalog
     * @return array{
     *     student_id: int,
     *     progress: array{
     *         current_course_id: int|null,
     *         next_mission: array{course_id: int, mission_id: int, title: string}|null,
     *         courses: list<array{
     *             course_id: int,
     *             name: string,
     *             state: string,
     *             percent: int,
     *             completed_missions: int,
     *             total_missions: int,
     *             challenge_passed: bool,
     *         }>,
     *     },
     *     challenges: array<string, mixed>,
     *     assessments: array<string, mixed>,
     *     competency: array{
     *         skills: list<array{
     *             key: string,
     *             label: string,
     *             percentage: float|null,
     *             state: string,
     *             weak: bool,
     *             kc_correct: int,
     *             kc_total: int,
     *             challenges_completed: int,
     *             challenges_applicable: int,
     *         }>,
     *         weak_skills: list<string>,
     *     },
     *     xp: array{balance: int},
     *     achievements: array{
     *         earned: int,
     *         total: int,
     *         streak: int,
     *         items: list<array{slug: string, name: string, description: string|null, awarded: bool, unlocked_at: mixed}>,
     *     },
     *     timeline: list<array{at: mixed, label: string, type: string, pts: int|null, seq: int}>,
     *     recommendations: list<array{slot: int, title: string, subtitle: string, href: string, cta: string}>,
     *     period: array{
     *         from: string|null,
     *         to_exclusive: string|null,
     *         completions: int,
     *         wrong_submissions: int,
     *         assessment_attempts: int,
     *         assessment_passes: int,
     *     },
     * }
     */
    public function forStudent(
        User $student,
        ?Collection $courseIds = null,
        ?ReportFilters $filters = null,
    ): array {
        if ($filters !== null && $filters->studentId !== null && $filters->studentId !== $student->id) {
            return $this->emptyReport($student, $filters);
        }

        $scope = $courseIds;

        if ($filters !== null && $filters->courseId !== null) {
            if ($scope !== null && ! $scope->contains($filters->courseId)) {
                return $this->emptyReport($student, $filters);
            }

            $scope = collect([$filters->courseId]);
        }

        $studentIds = collect([$student->id]);

        $courses = $this->competencies->overview($student, $scope);
        $skills = $this->competencies->skills($student, $scope);
        $current = $this->dashboard->currentCourse($student, $scope);
        $catalog = $this->achievements->catalog($student);

        $courseRows = [];

        foreach ($courses as $row) {
            $courseRows[] = [
                'course_id' => $row['course']->id,
                'name' => $row['name'],
                'state' => $row['state'],
                'percent' => $row['percent'],
                'completed_missions' => $row['completedMissions'],
                'total_missions' => $row['totalMissions'],
                'challenge_passed' => $row['challengePassed'],
            ];
        }

        $skillRows = [];

        foreach ($skills as $row) {
            $skillRows[] = [
                'key' => $row['key'],
                'label' => $row['label'],
                'percentage' => $row['percentage'],
                'state' => $row['state'],
                'weak' => $row['weak'],
                'kc_correct' => $row['kcCorrect'],
                'kc_total' => $row['kcTotal'],
                'challenges_completed' => $row['challengesCompleted'],
                'challenges_applicable' => $row['challengesApplicable'],
            ];
        }

        $weakSkills = [];

        foreach ($skills as $row) {
            if ($row['weak']) {
                $weakSkills[] = $row['key'];
            }
        }

        return [
            'student_id' => $student->id,
            'progress' => [
                'current_course_id' => $current?->id,
                'next_mission' => $current === null ? null : $this->nextMissionRow($student, $current),
                'courses' => $courseRows,
            ],
            'challenges' => $this->challenges->summarize($studentIds, $scope, $filters),
            'assessments' => $this->assessments->summarize($studentIds, $scope, $filters),
            'competency' => [
                'skills' => $skillRows,
                'weak_skills' => $weakSkills,
            ],
            'xp' => [
                'balance' => $this->xp->balance($student),
            ],
            'achievements' => [
                'earned' => $catalog->where('awarded', true)->count(),
                'total' => $catalog->count(),
                'streak' => $this->achievements->currentStreak($student),
                'items' => array_values($catalog->all()),
            ],
            'timeline' => array_values($this->timeline->events($student, 20, $scope)->all()),
            'recommendations' => array_values($this->recommendations->recommendations($student, $scope)->all()),
            'period' => $this->periodActivity($student, $scope, $filters),
        ];
    }

    /**
     * The next incomplete mission in the current course, if any. Progression
     * stays owned by DashboardService; this only reshapes its answer.
     *
     * @return array{course_id: int, mission_id: int, title: string}|null
     */
    private function nextMissionRow(User $student, Course $course): ?array
    {
        $next = $this->dashboard->nextMission($student, $course);

        if ($next === null) {
            return null;
        }

        return [
            'course_id' => $course->id,
            'mission_id' => $next->id,
            'title' => $next->title,
        ];
    }

    /**
     * Fail-closed report: the neutral/empty shape. A mismatched student
     * filter lands here, so no branch may carry a subject fact — neither
     * the subject's nor the requested student's. Only the invoked subject
     * id echoes back as the call identity.
     *
     * @return array{
     *     student_id: int,
     *     progress: array{
     *         current_course_id: int|null,
     *         next_mission: array{course_id: int, mission_id: int, title: string}|null,
     *         courses: list<array{
     *             course_id: int,
     *             name: string,
     *             state: string,
     *             percent: int,
     *             completed_missions: int,
     *             total_missions: int,
     *             challenge_passed: bool,
     *         }>,
     *     },
     *     challenges: array<string, mixed>,
     *     assessments: array<string, mixed>,
     *     competency: array{
     *         skills: list<array{
     *             key: string,
     *             label: string,
     *             percentage: float|null,
     *             state: string,
     *             weak: bool,
     *             kc_correct: int,
     *             kc_total: int,
     *             challenges_completed: int,
     *             challenges_applicable: int,
     *         }>,
     *         weak_skills: list<string>,
     *     },
     *     xp: array{balance: int},
     *     achievements: array{
     *         earned: int,
     *         total: int,
     *         streak: int,
     *         items: list<array{slug: string, name: string, description: string|null, awarded: bool, unlocked_at: mixed}>,
     *     },
     *     timeline: list<array{at: mixed, label: string, type: string, pts: int|null, seq: int}>,
     *     recommendations: list<array{slot: int, title: string, subtitle: string, href: string, cta: string}>,
     *     period: array{
     *         from: string|null,
     *         to_exclusive: string|null,
     *         completions: int,
     *         wrong_submissions: int,
     *         assessment_attempts: int,
     *         assessment_passes: int,
     *     },
     * }
     */
    private function emptyReport(User $student, ?ReportFilters $filters): array
    {
        return [
            'student_id' => $student->id,
            'progress' => [
                'current_course_id' => null,
                'next_mission' => null,
                'courses' => [],
            ],
            'challenges' => $this->challenges->summarize(collect([$student->id]), collect(), $filters),
            'assessments' => $this->assessments->summarize(collect([$student->id]), collect(), $filters),
            'competency' => [
                'skills' => [],
                'weak_skills' => [],
            ],
            'xp' => [
                'balance' => 0,
            ],
            'achievements' => [
                'earned' => 0,
                'total' => 0,
                'streak' => 0,
                'items' => [],
            ],
            'timeline' => [],
            'recommendations' => [],
            'period' => [
                'from' => $filters?->from?->toDateTimeString(),
                'to_exclusive' => $filters?->toExclusive?->toDateTimeString(),
                'completions' => 0,
                'wrong_submissions' => 0,
                'assessment_attempts' => 0,
                'assessment_passes' => 0,
            ],
        ];
    }

    /**
     * Descriptive movement inside the filter range. Each count reads its own
     * authoritative table; none feeds back into state computation.
     *
     * @param  Collection<int, int>|null  $scope
     * @return array{
     *     from: string|null,
     *     to_exclusive: string|null,
     *     completions: int,
     *     wrong_submissions: int,
     *     assessment_attempts: int,
     *     assessment_passes: int,
     * }
     */
    private function periodActivity(User $student, ?Collection $scope, ?ReportFilters $filters): array
    {
        $missionIds = $scope === null
            ? null
            : DB::table('the404_missions')->whereIn('course_id', $scope)->pluck('id');

        $completions = DB::table('the404_progress')->where('user_id', $student->id);

        if ($missionIds !== null) {
            $completions->whereIn('mission_id', $missionIds);
        }

        if ($filters !== null) {
            $filters->applyDateRange($completions, 'completed_at');
        }

        $wrong = DB::table('the404_xp_transactions')
            ->where('user_id', $student->id)
            ->where('type', XpService::TYPE_WRONG_SUBMISSION);

        if ($missionIds !== null) {
            $wrong->whereIn('mission_id', $missionIds);
        }

        if ($filters !== null) {
            $filters->applyDateRange($wrong, 'created_at');
        }

        $assessments = DB::table('the404_assessment_attempts as a')
            ->join('the404_assessments as s', 's.id', '=', 'a.assessment_id')
            ->where('a.user_id', $student->id)
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw("SUM(CASE WHEN a.status = 'passed' THEN 1 ELSE 0 END) as passes");

        if ($scope !== null) {
            $assessments->whereIn('s.course_id', $scope);
        }

        if ($filters !== null) {
            $filters->applyDateRange($assessments, 'a.created_at');
        }

        $assessmentRow = $assessments->first();

        return [
            'from' => $filters?->from?->toDateTimeString(),
            'to_exclusive' => $filters?->toExclusive?->toDateTimeString(),
            'completions' => (int) $completions->count(),
            'wrong_submissions' => (int) $wrong->count(),
            'assessment_attempts' => (int) ($assessmentRow->attempts ?? 0),
            'assessment_passes' => (int) ($assessmentRow->passes ?? 0),
        ];
    }
}
