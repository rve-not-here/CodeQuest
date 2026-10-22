<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\RecommendationService;
use App\Services\ResumeService;
use App\Services\StudentReminderService;
use App\Support\MissionView;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly ResumeService $resume,
        private readonly RecommendationService $recommendations,
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
        $recommendations = $this->recommendations->recommendations($user);

        // Prefer a card the student can act on now; when nothing is actionable,
        // surface the first locked card so the page explains why instead of
        // offering a link that would bounce. Selection never invents a position:
        // the order comes from the canonical recommendation set.
        $focusCard = $recommendations
            ->first(fn (array $card): bool => $card['slot'] !== 1 && ($card['accessible'] ?? true))
            ?? $recommendations->first(fn (array $card): bool => $card['slot'] !== 1);

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
            'recentActivity' => $this->dashboard->recentActivity($user, 4),
            'resume' => $this->resumeViewData($resume),
            'resumeHasDraft' => $this->resumeHasDraft($user, $resume),
            'focusCard' => $focusCard,
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
