<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\AssessmentService;
use App\Services\DashboardService;
use App\Services\DraftService;
use App\Services\KnowledgeCheckService;
use App\Services\MissionService;
use App\Services\XpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MissionController extends Controller
{
    public function __construct(
        private readonly MissionService $missions,
        private readonly DraftService $drafts,
        private readonly XpService $xp,
        private readonly DashboardService $dashboard,
        private readonly AchievementService $achievements,
        private readonly KnowledgeCheckService $knowledgeChecks,
        private readonly AssessmentService $assessments,
    ) {}

    /**
     * Lesson screen: teaches the concept behind the mission without exposing
     * the authoritative coding workspace. The Challenge workspace lives at
     * GET /missions/{mission}/challenge (mission.challenge).
     */
    public function show(Mission $mission): View
    {
        $this->ensureCourseActive($mission);

        /** @var User $user */
        $user = auth()->user();

        $this->ensureCourseReached($user, $mission);

        return view('lesson', $this->lessonData($user, $mission));
    }

    /**
     * Coding Challenge Workspace: the focused editor for this mission. Only
     * lightweight context (breadcrumb, title, objective, XP reward, back
     * navigation) accompanies the CodeMirror / RUN / SUBMIT surfaces.
     */
    public function challenge(Mission $mission): RedirectResponse|View
    {
        $this->ensureCourseActive($mission);

        /** @var User $user */
        $user = auth()->user();

        $this->ensureCourseReached($user, $mission);

        if (($redirect = $this->knowledgeCheckGate($user, $mission)) !== null) {
            return $redirect;
        }

        return view('challenge', $this->viewData($user, $mission));
    }

    public function submit(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureCourseActive($mission);

        /** @var User $user */
        $user = auth()->user();

        $this->ensureCourseReached($user, $mission);

        if (($redirect = $this->knowledgeCheckGate($user, $mission)) !== null) {
            return $redirect;
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $course = $mission->course;
        $progressBefore = $course !== null
            ? $this->dashboard->courseProgress($user, $course)['percent']
            : null;
        $achievementsBefore = $this->achievementNames($user);

        $outcome = $this->missions->submit($user, $mission, $validated['code']);

        if ($outcome['passed'] && ! $outcome['alreadyCompleted']) {
            $progressAfter = $course !== null
                ? $this->dashboard->courseProgress($user, $course)['percent']
                : null;

            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('mission_success', [
                    'title' => 'Mission complete',
                    'message' => '+'.$outcome['xpAwarded'].' XP awarded.',
                    'xp_awarded' => $outcome['xpAwarded'],
                    'xp_balance' => $outcome['xpBalance'],
                    'progress_before' => $progressBefore,
                    'progress_after' => $progressAfter,
                    'achievement' => $this->newAchievementName($user, $achievementsBefore),
                ]);
        }

        if ($outcome['alreadyCompleted']) {
            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('mission_info', [
                    'title' => 'Already completed',
                    'message' => 'You have already passed this mission.',
                ]);
        }

        return back()
            ->withInput(['code' => $request->input('code')])
            ->with('mission_error', [
                'title' => 'Mission not restored',
                'message' => $this->firstFailure($outcome['failures']),
            ]);
    }

    public function saveDraft(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureCourseActive($mission);

        /** @var User $user */
        $user = auth()->user();

        $this->ensureCourseReached($user, $mission);

        if (($redirect = $this->knowledgeCheckGate($user, $mission)) !== null) {
            return $redirect;
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $this->drafts->save($user, $mission, $validated['code']);

        return back()->with('draft_saved', true);
    }

    /**
     * Reveals the next hint in sequence, charging its XP cost.
     */
    public function hint(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureCourseActive($mission);

        /** @var User $user */
        $user = auth()->user();

        $this->ensureCourseReached($user, $mission);

        if (($redirect = $this->knowledgeCheckGate($user, $mission)) !== null) {
            return $redirect;
        }

        if ($this->missions->isCompleted($user, $mission)) {
            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('hint_flat', true);
        }

        $hintNumber = $this->xp->revealedHintCount($user, $mission) + 1;

        if ($hintNumber > 3) {
            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('hint_flat', true);
        }

        $cost = $this->xp->hintCost($hintNumber);
        $balance = $this->xp->balance($user);

        if (! $this->xp->spendHint($user, $mission, $hintNumber)) {
            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('hint_error', compact('cost', 'balance'));
        }

        return back()
            ->withInput(['code' => $request->input('code')])
            ->with('hint_revealed', $hintNumber);
    }

    public function reveal(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureCourseActive($mission);

        /** @var User $user */
        $user = auth()->user();

        $this->ensureCourseReached($user, $mission);

        if (($redirect = $this->knowledgeCheckGate($user, $mission)) !== null) {
            return $redirect;
        }

        if ($this->missions->isCompleted($user, $mission)) {
            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('reveal_flat', true);
        }

        $cost = $this->xp->revealCost();
        $balance = $this->xp->balance($user);

        if (! $this->xp->spendSolutionReveal($user, $mission)) {
            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('reveal_error', compact('cost', 'balance'));
        }

        return back()
            ->withInput(['code' => $request->input('code')])
            ->with('solution_revealed', true);
    }

    /**
     * Lesson screen data. A lean authoring subset of viewData(): the lesson
     * teaches the mission (title, difficulty, concept, starter scaffold) and
     * routes the student to mission.challenge for the authoritative editor.
     * No draft, solution, hint, or grading state reaches the lesson view.
     *
     * @return array<string, mixed>
     */
    private function lessonData(User $user, Mission $mission): array
    {
        return [
            'user' => $user,
            'role' => $user->role,
            'mission' => $mission,
            'course' => $mission->course,
            'section' => $mission->section,
            'completed' => $this->missions->isCompleted($user, $mission),
            'totalXp' => $this->xp->balance($user),
            'wrongPenalty' => $this->xp->wrongSubmissionCost(),
            'knowledgeChecks' => $this->knowledgeChecks->lessonSummaries($user, $mission),
            'outstandingRequiredCheck' => $this->knowledgeChecks->firstOutstandingRequired($user, $mission),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(User $user, Mission $mission): array
    {
        $completed = $this->missions->isCompleted($user, $mission);
        $draft = $this->drafts->find($user, $mission);

        $hints = $this->parseHints($mission);
        $revealedCount = $this->xp->revealedHintCount($user, $mission);
        $revealedHints = array_slice($hints, 0, $revealedCount);
        $solutionRevealed = $this->xp->hasRevealedSolution($user, $mission);

        return [
            'user' => $user,
            'role' => $user->role,
            'mission' => $mission,
            'course' => $mission->course,
            'section' => $mission->section,
            'completed' => $completed,
            'code' => $solutionRevealed && $mission->solution_code !== null ? $mission->solution_code : ($draft->code ?? ''),
            'hasDraft' => $draft !== null,
            'solutionRevealed' => $solutionRevealed,
            'hints' => $revealedHints,
            'revealedHintCount' => $revealedCount,
            'totalHintCount' => count($hints),
            'nextHintCost' => $this->xp->hintCost($revealedCount + 1),
            'revealCost' => $this->xp->revealCost(),
            'wrongPenalty' => $this->xp->wrongSubmissionCost(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function achievementNames(User $user): array
    {
        return $this->achievements->catalog($user)
            ->filter(fn (array $achievement): bool => $achievement['awarded'])
            ->mapWithKeys(fn (array $achievement): array => [
                $achievement['slug'] => $achievement['name'],
            ])
            ->all();
    }

    /**
     * @param  array<string, string>  $achievementsBefore
     */
    private function newAchievementName(User $user, array $achievementsBefore): ?string
    {
        foreach ($this->achievementNames($user) as $slug => $name) {
            if (! array_key_exists($slug, $achievementsBefore)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function parseHints(Mission $mission): array
    {
        if ($mission->hints === null || trim($mission->hints) === '') {
            return [];
        }

        $decoded = json_decode($mission->hints, true);

        if (! is_array($decoded)) {
            return [trim($mission->hints)];
        }

        return array_values(array_filter(array_map('strval', $decoded), fn (string $h): bool => $h !== ''));
    }

    /**
     * @param  list<string>  $failures
     */
    private function firstFailure(array $failures): string
    {
        if ($failures === []) {
            return 'The submitted code did not satisfy the challenge.';
        }

        return $failures[0];
    }

    /**
     * Gate every mission interaction on the course being available. course.status
     * is an access gate (US-705, confirmed): 'locked' and 'draft' seal the
     * course's missions, so a direct URL cannot keep earning progress, XP, or
     * achievements under a course the admin took out of active service. Runs
     * before any user resolution or write, like the IDOR guards. Progress the
     * student recorded before the course was sealed is untouched.
     */
    private function ensureCourseActive(Mission $mission): void
    {
        if ($mission->course === null || $mission->course->status !== 'active') {
            abort(403, 'This course is not currently active.');
        }
    }

    private function ensureCourseReached(User $user, Mission $mission): void
    {
        $course = $mission->course;

        if ($course === null || ! $this->assessments->isCourseReached($user, $course)) {
            abort(403, 'Complete earlier courses before opening this Challenge.');
        }
    }

    private function knowledgeCheckGate(User $user, Mission $mission): ?RedirectResponse
    {
        $required = $this->knowledgeChecks->firstOutstandingRequired($user, $mission);

        if ($required === null) {
            return null;
        }

        return redirect()->route('mission.show', $mission)
            ->with('knowledge_check_info', [
                'title' => 'Knowledge Check required',
                'message' => "Complete {$required->title} before opening this Challenge.",
            ]);
    }
}
