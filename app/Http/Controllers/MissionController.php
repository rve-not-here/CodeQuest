<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\User;
use App\Services\DraftService;
use App\Services\LearningPathService;
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
        private readonly LearningPathService $path,
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

        return view('lesson', $this->lessonData($user, $mission));
    }

    /**
     * Coding Challenge Workspace: the focused editor for this mission. Only
     * lightweight context (breadcrumb, title, objective, XP reward, back
     * navigation) accompanies the CodeMirror / RUN / SUBMIT surfaces.
     */
    public function challenge(Mission $mission): View
    {
        $this->ensureCourseActive($mission);

        /** @var User $user */
        $user = auth()->user();

        return view('challenge', $this->viewData($user, $mission));
    }

    public function submit(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureCourseActive($mission);

        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $outcome = $this->missions->submit($user, $mission, $validated['code']);

        if ($outcome['passed'] && ! $outcome['alreadyCompleted']) {
            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('mission_success', [
                    'title' => 'Mission complete',
                    'message' => '+'.$outcome['xpAwarded'].' XP awarded.',
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
            'wrongPenalty' => $this->xp->wrongSubmissionCost(),
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
            'code' => $solutionRevealed && $mission->solution_code !== null ? $mission->solution_code : ($draft?->code ?? ''),
            'solutionRevealed' => $solutionRevealed,
            'hints' => $revealedHints,
            'revealedHintCount' => $revealedCount,
            'totalHintCount' => count($hints),
            'nextHintCost' => $this->xp->hintCost($revealedCount + 1),
            'revealCost' => $this->xp->revealCost(),
            'wrongPenalty' => $this->xp->wrongSubmissionCost(),
            'xpBalance' => $this->xp->balance($user),
            'nextMissionId' => $this->nextMissionId($user, $mission),
        ];
    }

    private function nextMissionId(User $user, Mission $mission): ?int
    {
        return $this->path->nextMission($user, $mission->course)?->id;
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
}
