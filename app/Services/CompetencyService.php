<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Skill;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the competency overview (US-507): one competency per active course
 * with missions, all derived server-side from real learning and assessment
 * data. XP figures play no part in any competency value (§23.0); the only
 * wrong-submission signal reads the transaction log's penalty rows as a
 * per-mission engagement journal, and their amounts are never consulted.
 *
 * The small state model (§26.0), evaluated in order per course:
 *   NOT STARTED   -> no completed mission, no wrong submission, no attempt
 *   DEVELOPING    -> first mission completed or first wrong submission, with
 *                    fewer than half the course's missions done
 *   PRACTICING    -> at least half the missions done, or the Boss Challenge
 *                    attempted without a pass yet
 *   DEMONSTRATED  -> every mission completed and the Boss Challenge passed
 *
 * Monotonicity mirrors US-409/410: DEMONSTRATED reads hasPassed(), which is
 * attempt-history based, and Progress rows are never deleted, so a failed
 * retry after the first pass can never downgrade a competency.
 */
class CompetencyService
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly AssessmentService $assessments,
    ) {}

    /**
     * An optional $courseIds scope restricts the overview to a monitorable
     * course set (a teacher's shared classrooms). null keeps every active
     * course with missions; an empty Collection yields no rows.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, array{
     *     course: Course,
     *     name: string,
     *     state: 'not_started'|'developing'|'practicing'|'demonstrated',
     *     completedMissions: int,
     *     totalMissions: int,
     *     percent: int,
     *     wrongSubmissions: int,
     *     attempts: int,
     *     challengePassed: bool,
     * }>
     */
    public function overview(User $user, ?Collection $courseIds = null): Collection
    {
        $courses = Course::query()
            ->where('status', 'active')
            ->orderBy('order_num')
            ->orderBy('id')
            ->with('missions')
            ->when($courseIds !== null, fn ($query) => $query->whereIn('id', $courseIds))
            ->get()
            ->filter(fn (Course $course): bool => $course->missions->isNotEmpty())
            ->values();

        if ($courses->isEmpty()) {
            return collect();
        }

        // US-911: all per-course evidence below is fetched in grouped reads.
        // Every predicate matches the single-course methods verbatim; only
        // the access pattern changes from one-query-per-course to constant.
        $progress = $this->dashboard->courseProgressMap($user, $courses);
        $states = $this->assessments->assessmentStatesForCourses($user, $courses);

        $missionIds = $courses->flatMap(fn (Course $course) => $course->missions->pluck('id'));

        $wrongByMission = XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_WRONG_SUBMISSION)
            ->whereIn('mission_id', $missionIds)
            ->selectRaw('mission_id, count(*) as wrong_count')
            ->groupBy('mission_id')
            ->pluck('wrong_count', 'mission_id');

        $assessmentIds = collect($states)
            ->map(fn (array $state) => $state['assessment'])
            ->filter()
            ->map(fn (Assessment $assessment): int => $assessment->id);

        $attemptsByAssessment = AssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('assessment_id', $assessmentIds)
            ->selectRaw('assessment_id, count(*) as attempt_count')
            ->groupBy('assessment_id')
            ->pluck('attempt_count', 'assessment_id');

        return $this->buildCourseRows($courses, $progress, $states, $wrongByMission, $attemptsByAssessment);
    }

    /**
     * Course overview rows for many students in bounded queries (US-1004).
     * Same evidence predicates as overview(), grouped by student instead of
     * filtered to one: progress per student/course, passed verdicts per
     * student, wrong submissions per student/mission, attempts per
     * student/assessment. Row construction is the shared buildCourseRows()
     * path, so batch and single-student results cannot drift apart.
     *
     * @param  Collection<int, User>  $users
     * @param  Collection<int, int>|null  $courseIds
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     * @return Collection<int, Collection<int, array{
     *     course: Course,
     *     name: string,
     *     state: 'not_started'|'developing'|'practicing'|'demonstrated',
     *     completedMissions: int,
     *     totalMissions: int,
     *     percent: int,
     *     wrongSubmissions: int,
     *     attempts: int,
     *     challengePassed: bool,
     * }>> keyed by user id
     */
    public function overviewForStudents(Collection $users, ?Collection $courseIds = null, ?array $studentCourseScopes = null): Collection
    {
        if ($users->isEmpty()) {
            return collect();
        }

        $courses = Course::query()
            ->where('status', 'active')
            ->orderBy('order_num')
            ->orderBy('id')
            ->with('missions')
            ->when($courseIds !== null, fn ($query) => $query->whereIn('id', $courseIds))
            ->get()
            ->filter(fn (Course $course): bool => $course->missions->isNotEmpty())
            ->values();

        $userIds = $users->pluck('id')->map(fn (mixed $id): int => (int) $id)->values();

        $courseIdList = $courses->pluck('id');

        $missionIdsByCourse = Mission::query()
            ->whereIn('course_id', $courseIdList)
            ->get(['id', 'course_id'])
            ->groupBy('course_id');

        $allMissionIds = $missionIdsByCourse->flatten()->pluck('id');

        $totalByCourse = [];

        foreach ($missionIdsByCourse as $courseId => $missions) {
            $totalByCourse[(int) $courseId] = $missions->count();
        }

        $progressRows = DB::table('the404_progress as p')
            ->join('the404_missions as m', 'm.id', '=', 'p.mission_id')
            ->whereIn('p.user_id', $userIds)
            ->whereIn('p.mission_id', $allMissionIds)
            ->when($studentCourseScopes !== null, fn ($query) => $query->where(function ($query) use ($studentCourseScopes): void {
                foreach ($studentCourseScopes ?? [] as $userId => $allowedCourses) {
                    $query->orWhere(fn ($query) => $query->where('p.user_id', $userId)->whereIn('m.course_id', $allowedCourses));
                }
            }))
            ->selectRaw('p.user_id as user_id, m.course_id as course_id, COUNT(DISTINCT p.mission_id) as done')
            ->groupBy('p.user_id', 'm.course_id')
            ->get();

        $progressByUserCourse = [];

        foreach ($progressRows as $row) {
            $progressByUserCourse[(int) $row->user_id][(int) $row->course_id] = (int) $row->done;
        }

        $passedRows = AssessmentAttempt::query()
            ->whereIn('user_id', $userIds)
            ->where('status', 'passed')
            ->when($studentCourseScopes !== null, fn ($query) => $query->where(function ($query) use ($studentCourseScopes): void {
                foreach ($studentCourseScopes ?? [] as $userId => $allowedCourses) {
                    $query->orWhere(fn ($query) => $query->where('user_id', $userId)
                        ->whereHas('assessment', fn ($query) => $query->whereIn('course_id', $allowedCourses)));
                }
            }))
            ->distinct()
            ->get(['user_id', 'assessment_id']);

        $passedByUserAssessment = [];

        foreach ($passedRows as $row) {
            $passedByUserAssessment[(int) $row->user_id][(int) $row->assessment_id] = true;
        }

        $assessmentsByCourse = Assessment::query()
            ->whereIn('course_id', $courseIdList)
            ->get()
            ->keyBy('course_id');

        $assessmentIds = $assessmentsByCourse->pluck('id');

        $wrongRows = XpTransaction::query()
            ->toBase()
            ->whereIn('user_id', $userIds)
            ->where('type', XpService::TYPE_WRONG_SUBMISSION)
            ->whereIn('mission_id', $allMissionIds)
            ->when($studentCourseScopes !== null, fn ($query) => $query->where(function ($query) use ($studentCourseScopes, $missionIdsByCourse): void {
                foreach ($studentCourseScopes ?? [] as $userId => $allowedCourses) {
                    $missionIds = collect($missionIdsByCourse->all())->only($allowedCourses)->flatten()->pluck('id');
                    $query->orWhere(fn ($query) => $query->where('user_id', $userId)->whereIn('mission_id', $missionIds));
                }
            }))
            ->selectRaw('user_id, mission_id, count(*) as wrong_count')
            ->groupBy('user_id', 'mission_id')
            ->get();

        $wrongByUserMission = [];

        foreach ($wrongRows as $row) {
            $wrongByUserMission[(int) $row->user_id][(int) $row->mission_id] = (int) $row->wrong_count;
        }

        $attemptRows = AssessmentAttempt::query()
            ->toBase()
            ->whereIn('user_id', $userIds)
            ->whereIn('assessment_id', $assessmentIds)
            ->when($studentCourseScopes !== null, fn ($query) => $query->where(function ($query) use ($studentCourseScopes, $assessmentsByCourse): void {
                foreach ($studentCourseScopes ?? [] as $userId => $allowedCourses) {
                    $allowedAssessmentIds = collect($assessmentsByCourse->all())->only($allowedCourses)->pluck('id');
                    $query->orWhere(fn ($query) => $query->where('user_id', $userId)->whereIn('assessment_id', $allowedAssessmentIds));
                }
            }))
            ->selectRaw('user_id, assessment_id, count(*) as attempt_count')
            ->groupBy('user_id', 'assessment_id')
            ->get();

        $attemptsByUserAssessment = [];

        foreach ($attemptRows as $row) {
            $attemptsByUserAssessment[(int) $row->user_id][(int) $row->assessment_id] = (int) $row->attempt_count;
        }

        $byUser = [];

        foreach ($userIds as $userId) {
            $userCourses = $studentCourseScopes === null
                ? $courses
                : $courses->filter(fn (Course $course): bool => ($studentCourseScopes[$userId] ?? collect())->contains($course->id));
            $userTotals = array_intersect_key($totalByCourse, array_fill_keys($userCourses->pluck('id')->all(), true));
            $byUser[$userId] = $this->buildUserRows(
                $userId,
                $userCourses,
                $userTotals,
                $progressByUserCourse,
                $passedByUserAssessment,
                $assessmentsByCourse,
                $wrongByUserMission,
                $attemptsByUserAssessment,
            );
        }

        return collect($byUser);
    }

    /**
     * One student's course rows from the batch evidence maps. Thin
     * per-student projection over grouped reads — no queries here.
     *
     * @param  EloquentCollection<int, Course>  $courses
     * @param  array<int, int>  $totalByCourse
     * @param  array<int, array<int, int>>  $progressByUserCourse
     * @param  array<int, array<int, true>>  $passedByUserAssessment
     * @param  Collection<int, Assessment>  $assessmentsByCourse
     * @param  array<int, array<int, int>>  $wrongByUserMission
     * @param  array<int, array<int, int>>  $attemptsByUserAssessment
     * @return Collection<int, array{
     *     course: Course,
     *     name: string,
     *     state: 'not_started'|'developing'|'practicing'|'demonstrated',
     *     completedMissions: int,
     *     totalMissions: int,
     *     percent: int,
     *     wrongSubmissions: int,
     *     attempts: int,
     *     challengePassed: bool,
     * }>
     */
    private function buildUserRows(
        int $userId,
        EloquentCollection $courses,
        array $totalByCourse,
        array $progressByUserCourse,
        array $passedByUserAssessment,
        Collection $assessmentsByCourse,
        array $wrongByUserMission,
        array $attemptsByUserAssessment,
    ): Collection {
        // Percent math matches DashboardService::courseProgressMap(),
        // the canonical definition the single-student path reuses.
        $progressRows = [];

        foreach ($totalByCourse as $courseId => $total) {
            $done = $progressByUserCourse[$userId][$courseId] ?? 0;

            $progressRows[$courseId] = [
                'completed' => $done,
                'total' => $total,
                'percent' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
            ];
        }

        $progress = collect($progressRows);

        $states = [];

        foreach ($courses as $course) {
            $assessment = $assessmentsByCourse->get($course->id);

            $states[$course->id] = [
                'assessment' => $assessment,
                'passed' => $assessment !== null && isset($passedByUserAssessment[$userId][$assessment->id]),
            ];
        }

        $wrongByMission = collect($wrongByUserMission[$userId] ?? []);
        $attemptsByAssessment = collect($attemptsByUserAssessment[$userId] ?? []);

        return $this->buildCourseRows($courses, $progress, $states, $wrongByMission, $attemptsByAssessment);
    }

    /**
     * One overview row per course from pre-fetched evidence maps. Shared by
     * the single-student and batch paths so both construct rows — state,
     * counts, and pass verdicts — from identical logic.
     *
     * @param  EloquentCollection<int, Course>  $courses
     * @param  Collection<int, array{completed: int, total: int, percent: int}>  $progress
     * @param  array<int, array{assessment: ?Assessment, passed: bool}>  $states
     * @param  Collection<int, int>  $wrongByMission
     * @param  Collection<int, int>  $attemptsByAssessment
     * @return Collection<int, array{
     *     course: Course,
     *     name: string,
     *     state: 'not_started'|'developing'|'practicing'|'demonstrated',
     *     completedMissions: int,
     *     totalMissions: int,
     *     percent: int,
     *     wrongSubmissions: int,
     *     attempts: int,
     *     challengePassed: bool,
     * }>
     */
    private function buildCourseRows(
        EloquentCollection $courses,
        Collection $progress,
        array $states,
        Collection $wrongByMission,
        Collection $attemptsByAssessment,
    ): Collection {
        return $courses
            ->map(function (Course $course) use ($progress, $states, $wrongByMission, $attemptsByAssessment): array {
                // The map covers every input course; the default matches
                // zero-mission math (0/0 → 0%) and only satisfies the type.
                $courseProgress = $progress->get($course->id) ?? ['completed' => 0, 'total' => 0, 'percent' => 0];
                $state = $states[$course->id];

                $wrongSubmissions = $course->missions
                    ->pluck('id')
                    ->sum(fn (int $missionId): int => (int) $wrongByMission->get($missionId, 0));

                $attempts = $state['assessment'] !== null
                    ? (int) $attemptsByAssessment->get($state['assessment']->id, 0)
                    : 0;

                $rowState = $this->stateFor(
                    $courseProgress['completed'],
                    $courseProgress['total'],
                    $attempts,
                    $wrongSubmissions,
                    $state['passed'],
                );

                return [
                    'course' => $course,
                    'name' => $this->nameFor($course->type),
                    'state' => $rowState,
                    'completedMissions' => $courseProgress['completed'],
                    'totalMissions' => $courseProgress['total'],
                    'percent' => $courseProgress['percent'],
                    'wrongSubmissions' => $wrongSubmissions,
                    'attempts' => $attempts,
                    'challengePassed' => $state['passed'],
                ];
            })
            ->values();
    }

    /**
     * Skill-level competency (US-905): one row per skill mapped in the
     * applicable course scope, derived only from recorded evidence.
     *
     * Approved v1 formula, per skill:
     *   KC component         = correct mapped KC responses / mapped KC responses
     *   Challenge component  = completed mapped challenges / applicable mapped challenges
     *   both present         = average of the two (50/50)
     *   one present          = that source at 100% (no penalty for a missing category)
     *   neither present      = null (NOT ASSESSED, never weak)
     *
     * Evidence attribution uses the skill-key snapshots stored on each
     * evidence row at creation time, never the live pivot mappings: a later
     * remapping cannot reinterpret history. Rows predating snapshots carry
     * NULL and are excluded from skill attribution. Denominators use
     * required mapped curriculum in scope, so uncompleted challenges stay
     * incomplete evidence. Unattempted Knowledge Checks contribute no KC
     * evidence; checks gate nothing and complete nothing, so this preserves
     * existing progression semantics. Wrong submissions never enter the
     * percentage; Boss evidence is excluded from v1 skill scoring entirely.
     *
     * All reads are grouped; nothing here queries per skill, mission,
     * question, or course (US-911).
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, array{
     *     key: string,
     *     label: string,
     *     percentage: float|null,
     *     kcCorrect: int,
     *     kcTotal: int,
     *     challengesCompleted: int,
     *     challengesApplicable: int,
     *     state: 'not_assessed'|'weak'|'proficient',
     *     weak: bool,
     * }>
     */
    public function skills(User $user, ?Collection $courseIds = null): Collection
    {
        $courses = Course::query()
            ->where('status', 'active')
            ->when($courseIds !== null, fn ($query) => $query->whereIn('id', $courseIds))
            ->pluck('id');

        if ($courses->isEmpty()) {
            return collect();
        }

        $missionIds = DB::table('the404_missions')->whereIn('course_id', $courses)->pluck('id');

        $missionSkillIds = DB::table('the404_mission_skill')
            ->whereIn('mission_id', $missionIds)
            ->select(['mission_id', 'skill_id'])
            ->get();

        $questionSkillIds = DB::table('the404_knowledge_check_question_skill as qsk')
            ->join('the404_knowledge_check_questions as q', 'q.id', '=', 'qsk.question_id')
            ->join('the404_knowledge_checks as kc', 'kc.id', '=', 'q.knowledge_check_id')
            ->whereIn('kc.mission_id', $missionIds)
            ->select(['qsk.question_id', 'qsk.skill_id'])
            ->get();

        $skillIds = $missionSkillIds->pluck('skill_id')
            ->merge($questionSkillIds->pluck('skill_id'))
            ->unique()
            ->values();

        if ($skillIds->isEmpty()) {
            return collect();
        }

        $skills = Skill::query()->whereIn('id', $skillIds)->get()->keyBy('id');
        $keyOf = fn (int $id): ?string => $skills->get($id)?->key;

        $missionKeys = [];
        foreach ($missionSkillIds as $row) {
            $key = $keyOf((int) $row->skill_id);
            if ($key !== null) {
                $missionKeys[(int) $row->mission_id][] = $key;
            }
        }

        $responses = DB::table('the404_knowledge_check_responses as r')
            ->join('the404_knowledge_check_attempts as a', 'a.id', '=', 'r.knowledge_check_attempt_id')
            ->where('a.user_id', $user->id)
            ->whereIn('r.knowledge_check_question_id', $questionSkillIds->pluck('question_id')->unique()->values())
            ->select(['r.is_correct', 'r.skill_keys'])
            ->get();

        $kcCorrect = [];
        $kcTotal = [];
        $seenKeys = [];
        $hasEvidence = [];
        foreach ($responses as $response) {
            foreach (self::snapshotKeys($response->skill_keys) as $key) {
                $seenKeys[$key] = true;
                $hasEvidence[$key] = true;
                $kcTotal[$key] = ($kcTotal[$key] ?? 0) + 1;
                if ((bool) $response->is_correct) {
                    $kcCorrect[$key] = ($kcCorrect[$key] ?? 0) + 1;
                }
            }
        }

        $completions = DB::table('the404_progress')
            ->where('user_id', $user->id)
            ->whereIn('mission_id', $missionIds)
            ->select(['mission_id', 'skill_keys'])
            ->get();

        $completedWithKey = [];
        foreach ($completions as $progress) {
            foreach (self::snapshotKeys($progress->skill_keys) as $key) {
                $seenKeys[$key] = true;
                $hasEvidence[$key] = true;
                $completedWithKey[$key][(int) $progress->mission_id] = true;
            }
        }

        $applicableKeys = [];
        foreach ($missionKeys as $missionId => $keys) {
            foreach (array_unique($keys) as $key) {
                $applicableKeys[$key][$missionId] = true;
            }
        }
        // Historical evidence keeps its missions applicable: a remapped
        // completion still counts for the skill it was recorded under.
        foreach ($completedWithKey as $key => $missionSet) {
            foreach ($missionSet as $missionId => $true) {
                $applicableKeys[$key][$missionId] = true;
            }
        }

        $labels = $skills->mapWithKeys(fn (Skill $skill): array => [$skill->key => $skill->label]);
        $missingLabels = array_diff(array_keys($seenKeys), $labels->keys()->all());
        if ($missingLabels !== []) {
            foreach (Skill::query()->whereIn('key', $missingLabels)->pluck('label', 'key') as $key => $label) {
                $labels->put($key, $label);
            }
        }
        foreach (array_keys($seenKeys) as $key) {
            $labels->put($key, $labels->get($key, $key));
        }

        $rows = collect();

        foreach ($labels->sortKeys() as $key => $label) {
            // No evidence of any kind: NOT ASSESSED, never weak — even
            // when required curriculum is mapped. Denominators only drag
            // down a percentage once evidence exists.
            if (! isset($hasEvidence[$key])) {
                $rows->push(self::skillRow($key, $label, null, 0, 0, 0, count($applicableKeys[$key] ?? []), false));

                continue;
            }

            $kcDenominator = $kcTotal[$key] ?? 0;
            $kcPercentage = $kcDenominator > 0 ? ($kcCorrect[$key] ?? 0) / $kcDenominator * 100 : null;

            $applicable = array_keys($applicableKeys[$key] ?? []);
            $challengePercentage = null;
            $completed = 0;
            if ($applicable !== []) {
                foreach ($applicable as $missionId) {
                    if (isset($completedWithKey[$key][$missionId])) {
                        $completed++;
                    }
                }
                $challengePercentage = $completed / count($applicable) * 100;
            }

            $percentage = match (true) {
                $kcPercentage !== null && $challengePercentage !== null => ($kcPercentage + $challengePercentage) / 2,
                $kcPercentage !== null => $kcPercentage,
                $challengePercentage !== null => $challengePercentage,
                default => null,
            };

            $weak = $percentage !== null && $percentage < 70;

            $rows->push(self::skillRow($key, $label, $percentage, $kcCorrect[$key] ?? 0, $kcDenominator, $completed, count($applicable), $weak));
        }

        return $rows;
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     percentage: float|null,
     *     kcCorrect: int,
     *     kcTotal: int,
     *     challengesCompleted: int,
     *     challengesApplicable: int,
     *     state: 'not_assessed'|'weak'|'proficient',
     *     weak: bool,
     * }
     */
    private static function skillRow(
        string $key,
        string $label,
        ?float $percentage,
        int $kcCorrect,
        int $kcTotal,
        int $completed,
        int $applicable,
        bool $weak,
    ): array {
        if ($percentage === null) {
            $state = 'not_assessed';
        } elseif ($weak) {
            $state = 'weak';
        } else {
            $state = 'proficient';
        }

        return [
            'key' => $key,
            'label' => $label,
            'percentage' => $percentage === null ? null : round($percentage, 2),
            'kcCorrect' => $kcCorrect,
            'kcTotal' => $kcTotal,
            'challengesCompleted' => $completed,
            'challengesApplicable' => $applicable,
            'state' => $state,
            'weak' => $weak,
        ];
    }

    /**
     * Weak skills (US-906): the skill competency rows with actual evidence
     * below 70%. Derived by filtering skills() output — there is exactly
     * one calculation path, no second formula.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, array{
     *     key: string,
     *     label: string,
     *     percentage: float|null,
     *     kcCorrect: int,
     *     kcTotal: int,
     *     challengesCompleted: int,
     *     challengesApplicable: int,
     *     state: 'not_assessed'|'weak'|'proficient',
     *     weak: bool,
     * }>
     */
    public function weakSkills(User $user, ?Collection $courseIds = null): Collection
    {
        $weak = collect();

        foreach ($this->skills($user, $courseIds) as $row) {
            if ($row['weak']) {
                $weak->push($row);
            }
        }

        return $weak;
    }

    /**
     * @return list<string>
     */
    private static function snapshotKeys(mixed $value): array
    {
        if (! is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn (mixed $key): bool => is_string($key) && $key !== ''));
    }

    /**
     * @return 'not_started'|'developing'|'practicing'|'demonstrated'
     */
    private function stateFor(int $done, int $total, int $attempts, int $wrongSubmissions, bool $passed): string
    {
        if ($done >= $total && $passed) {
            return 'demonstrated';
        }

        if ($done * 2 >= $total || $attempts > 0) {
            return 'practicing';
        }

        if ($done > 0 || $wrongSubmissions > 0) {
            return 'developing';
        }

        return 'not_started';
    }

    private function nameFor(string $type): string
    {
        return match (strtolower($type)) {
            'html' => 'HTML',
            'css' => 'CSS',
            'js' => 'JavaScript',
            default => strtoupper($type),
        };
    }
}
