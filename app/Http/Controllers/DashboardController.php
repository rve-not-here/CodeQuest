<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\CompetencyService;
use App\Services\DashboardService;
use App\Services\ResumeService;
use App\Services\StudentReminderService;
use App\Services\TimelineService;
use App\Support\MissionView;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly CompetencyService $competencies,
        private readonly ResumeService $resume,
        private readonly StudentReminderService $reminders,
    ) {}

    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'The dashboard is scoped to your own account.');
        }

        /** @var User $user */
        $user = auth()->user();

        $course = $this->dashboard->currentCourse($user);
        $hasLearnableCourses = $course !== null || Course::query()
            ->where('status', 'active')
            ->whereHas('missions')
            ->exists();
        $progress = $course !== null ? $this->dashboard->courseProgress($user, $course) : null;
        $resume = $this->resume->resolve($user);
        $competencies = $this->competencies->overview($user);
        $competencySummary = $course === null
            ? $competencies->last()
            : $competencies->first(fn (array $row): bool => $row['course']->is($course));

        // US-809: the student's reminders are synced lazily from the dashboard
        // — the same deliberate write side-effect on an otherwise read-only
        // GET that US-806 established for the teacher area (see
        // .ai/rules/controllers.md). It runs after the page data is composed,
        // and syncFor() is a no-op for non-students.
        $this->reminders->syncFor($user);

        return view('dashboard', [
            'user' => $user,
            'role' => $user->role,
            'course' => $course,
            'hasLearnableCourses' => $hasLearnableCourses,
            'courseProgress' => $progress,
            'totalXp' => $this->dashboard->totalXp($user),
            'learnerStats' => $this->dashboard->learnerStats($user),
            'recentActivity' => $this->dashboard->recentActivity($user)
                ->reject(fn (Activity $activity): bool => in_array($activity->type, TimelineService::NON_LEARNING_ACTIVITY_TYPES, true))
                ->take(4)
                ->values(),
            'resume' => $this->resumeViewData($resume),
            'resumeHasDraft' => $this->resumeHasDraft($user, $resume),
            'competencySummary' => $competencySummary,
        ]);
    }

    /**
     * @param  null|array{type: 'mission'|'course', mission?: Mission}  $resume
     */
    private function resumeHasDraft(User $user, ?array $resume): bool
    {
        if ($resume === null || $resume['type'] !== 'mission' || ! isset($resume['mission'])) {
            return false;
        }

        return $user->missionDrafts()
            ->where('mission_id', $resume['mission']->id)
            ->exists();
    }

    /**
     * Student views receive the whitelisted mission view-model, never the
     * Eloquent model carrying solution_code/hints/validate_rule.
     *
     * @param  null|array{type: 'mission'|'course', course: Course, section?: Section|null, mission?: Mission}  $resume
     * @return null|array{type: 'mission'|'course', course: Course, section?: Section|null, mission?: MissionView}
     */
    private function resumeViewData(?array $resume): ?array
    {
        if ($resume === null) {
            return null;
        }

        $view = [
            'type' => $resume['type'],
            'course' => $resume['course'],
            'section' => $resume['section'] ?? null,
        ];

        if (isset($resume['mission'])) {
            $view['mission'] = MissionView::from($resume['mission']);
        }

        return $view;
    }
}
