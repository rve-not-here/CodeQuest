<?php

namespace App\Http\Controllers;

use App\Exceptions\KnowledgeCheckAccessDeniedException;
use App\Exceptions\KnowledgeCheckStateException;
use App\Exceptions\KnowledgeCheckUnavailableException;
use App\Http\Requests\KnowledgeCheckSubmitRequest;
use App\Models\Activity;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\Mission;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\AssessmentService;
use App\Services\DashboardService;
use App\Services\DraftService;
use App\Services\KnowledgeCheckGate;
use App\Services\KnowledgeCheckService;
use App\Services\MissionService;
use App\Services\XpService;
use App\Support\KnowledgeCheckView;
use App\Support\MissionView;
use App\Support\SourceCodeInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\ValidatedInput;
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
        private readonly KnowledgeCheckGate $checkGate,
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
     * Start the mission's Knowledge Check from the lesson's own panel.
     *
     * The check opens in place: the lesson is re-rendered with the form in
     * the panel, so the student never leaves the page they are reading.
     * Starting twice is harmless — the service returns the open attempt.
     */
    public function startCheck(Mission $mission, KnowledgeCheck $check): View
    {
        /** @var User $user */
        $user = auth()->user();

        $this->ensureCourseActive($mission);
        $this->ensureCourseReached($user, $mission);

        try {
            $this->knowledgeChecks->start($user, $mission, $check);
        } catch (KnowledgeCheckUnavailableException) {
            abort(404);
        }

        return $this->show($mission);
    }

    /**
     * Answer the mission's Knowledge Check from the lesson's own panel.
     *
     * The answers are scored by the same service the standalone attempt
     * screen uses; the lesson is re-rendered with the result, so the panel
     * stays where the student left it.
     */
    public function submitCheck(
        KnowledgeCheckSubmitRequest $request,
        Mission $mission,
        KnowledgeCheck $check,
        KnowledgeCheckAttempt $attempt,
    ): View {
        /** @var User $user */
        $user = auth()->user();

        $this->ensureCourseActive($mission);
        $this->ensureCourseReached($user, $mission);

        /** @var ValidatedInput $payload */
        $payload = $request->safe(['answers']);
        /** @var array<int|string, int|string> $answers */
        $answers = $payload['answers'];

        try {
            $this->knowledgeChecks->submit($user, $mission, $check, $attempt, $answers);
        } catch (KnowledgeCheckAccessDeniedException|KnowledgeCheckUnavailableException) {
            abort(404);
        }

        $data = $this->lessonData($user, $mission);
        $data['openCheckResultId'] = (int) $check->id;

        return view('lesson', $data);
    }

    /**
     * Open a fresh attempt on the mission's Knowledge Check, in place.
     */
    public function retryCheck(Mission $mission, KnowledgeCheck $check): View
    {
        /** @var User $user */
        $user = auth()->user();

        $this->ensureCourseActive($mission);
        $this->ensureCourseReached($user, $mission);

        try {
            $this->knowledgeChecks->retry($user, $mission, $check);
        } catch (KnowledgeCheckUnavailableException) {
            abort(404);
        } catch (KnowledgeCheckStateException $exception) {
            session()->flash('knowledge_check_error', $exception->getMessage());

            return $this->show($mission);
        }

        return $this->show($mission);
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

    /**
     * The ungraded workbench now lives on the mission panel itself, so the old
     * experiment URL folds into the lesson instead of rendering a second page.
     */
    public function experiment(Mission $mission): RedirectResponse
    {
        $this->ensureCourseActive($mission);

        /** @var User $user */
        $user = auth()->user();
        $this->ensureCourseReached($user, $mission);

        return redirect()->route('mission.show', $mission);
    }

    public function submit(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureCourseActive($mission);

        /** @var User $user */
        $user = auth()->user();

        $this->ensureCourseReached($user, $mission);

        if (($redirect = $this->knowledgeCheckGate($user, $mission, preserveCode: true)) !== null) {
            return $redirect;
        }

        $validated = SourceCodeInput::validate($request);

        $course = $mission->course;
        $progressBefore = $course !== null
            ? $this->dashboard->courseProgress($user, $course)['percent']
            : null;
        $achievementsBefore = $this->achievementNames($user);

        $submission = $request->validate(['submission_id' => ['nullable', 'uuid']]);
        $outcome = $this->missions->submit($user, $mission, $validated['code'] ?? '', $submission['submission_id'] ?? null);

        if ($outcome['passed'] && ! $outcome['alreadyCompleted']) {
            $progressAfter = $course !== null
                ? $this->dashboard->courseProgress($user, $course)['percent']
                : null;
            $nextMission = $course !== null
                ? $this->dashboard->nextMission($user, $course)
                : null;

            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('mission_success', [
                    'title' => 'Challenge complete',
                    'message' => '+'.$outcome['xpAwarded'].' XP awarded.',
                    'xp_awarded' => $outcome['xpAwarded'],
                    'xp_balance' => $outcome['xpBalance'],
                    'progress_before' => $progressBefore,
                    'progress_after' => $progressAfter,
                    'achievement' => $this->newAchievementName($user, $achievementsBefore),
                    'next_url' => $nextMission !== null
                        ? route('mission.show', $nextMission)
                        : route('learning-path'),
                    'next_label' => $nextMission !== null
                        ? 'Next challenge'
                        : 'Review learning path',
                ]);
        }

        if ($outcome['alreadyCompleted']) {
            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('mission_info', [
                    'title' => 'Already completed',
                    'message' => 'You have already passed this challenge.',
                ]);
        }

        $message = $this->firstFailure($outcome['failures']);
        if ($outcome['xpPenalty'] > 0) {
            $message .= ' '.$outcome['xpPenalty'].' XP was deducted; the attempt is recorded on your timeline.';
        }

        return back()
            ->withInput(['code' => $request->input('code')])
            ->with('mission_error', [
                'title' => 'Challenge not passed',
                'message' => $message,
            ]);
    }

    public function saveDraft(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureCourseActive($mission);

        /** @var User $user */
        $user = auth()->user();

        $this->ensureCourseReached($user, $mission);

        if (($redirect = $this->knowledgeCheckGate($user, $mission, preserveCode: true)) !== null) {
            return $redirect;
        }

        $validated = SourceCodeInput::validate($request);

        $this->drafts->save($user, $mission, $validated['code'] ?? '');

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

        if (($redirect = $this->knowledgeCheckGate($user, $mission, preserveCode: true)) !== null) {
            return $redirect;
        }

        SourceCodeInput::validate($request, required: false);

        if ($this->missions->isCompleted($user, $mission)) {
            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('hint_flat', true);
        }

        $hintNumber = $this->xp->revealedHintCount($user, $mission) + 1;

        if ($hintNumber > count($this->xp->hintsFor($mission))) {
            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('hint_flat', true);
        }

        $cost = $this->xp->hintCost($hintNumber);
        $balance = $this->xp->balance($user);

        if (! $this->xp->spendHint($user, $mission, $hintNumber)) {
            $mission->refresh();
            $this->ensureCourseActive($mission);
            if ($this->missions->isCompleted($user, $mission) || $hintNumber > count($this->xp->hintsFor($mission))) {
                return back()->withInput(['code' => $request->input('code')])->with('hint_flat', true);
            }

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

        if (($redirect = $this->knowledgeCheckGate($user, $mission, preserveCode: true)) !== null) {
            return $redirect;
        }

        SourceCodeInput::validate($request, required: false);

        if ($this->missions->isCompleted($user, $mission) || ! $this->xp->hasSolution($mission)) {
            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('reveal_flat', true);
        }

        // A student must attempt the challenge before the reference solution
        // unlocks. Hints remain available; the answer is not the first move.
        if (! $this->hasFailedAttempt($user, $mission)) {
            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('reveal_requires_attempt', true);
        }

        $cost = $this->xp->revealCost();
        $balance = $this->xp->balance($user);

        if (! $this->xp->spendSolutionReveal($user, $mission)) {
            $mission->refresh();
            $this->ensureCourseActive($mission);
            if ($this->missions->isCompleted($user, $mission) || ! $this->xp->hasSolution($mission)) {
                return back()->withInput(['code' => $request->input('code')])->with('reveal_flat', true);
            }

            return back()
                ->withInput(['code' => $request->input('code')])
                ->with('reveal_error', compact('cost', 'balance'));
        }

        return back()
            ->withInput(['code' => $request->input('code')])
            ->with('solution_revealed', true);
    }

    private function hasFailedAttempt(User $user, Mission $mission): bool
    {
        return Activity::query()
            ->where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->where('type', 'wrong_submission')
            ->exists();
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
            'mission' => MissionView::from($mission),
            'course' => $mission->course,
            'section' => $mission->section,
            'completed' => $this->missions->isCompleted($user, $mission),
            'totalXp' => $this->xp->balance($user),
            'wrongPenalty' => $this->xp->wrongSubmissionCost(),
            'knowledgeChecks' => $this->knowledgeChecks->missionPanels($user, $mission),
            'passScore' => KnowledgeCheckService::PASS_SCORE,
            'outstandingRequiredCheck' => $this->requiredCheckView($user, $mission),
        ];
    }

    /**
     * The required-check teaser is a KnowledgeCheck model; student views get
     * the whitelisted view-model instead.
     */
    private function requiredCheckView(User $user, Mission $mission): ?KnowledgeCheckView
    {
        $required = $this->checkGate->outstanding($user, $mission);

        return $required === null ? null : KnowledgeCheckView::from($required);
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(User $user, Mission $mission): array
    {
        $completed = $this->missions->isCompleted($user, $mission);
        $draft = $this->drafts->find($user, $mission);

        $hints = $this->xp->hintsFor($mission);
        $revealedCount = $this->xp->revealedHintCount($user, $mission);
        $revealedHints = array_slice($hints, 0, $revealedCount);
        $solutionRevealed = $this->xp->hasRevealedSolution($user, $mission);

        return [
            'user' => $user,
            'role' => $user->role,
            'mission' => MissionView::from($mission),
            'course' => $mission->course,
            'section' => $mission->section,
            'completed' => $completed,
            'totalXp' => $this->xp->balance($user),
            // A challenge with no draft opens on the corrupted source the brief
            // describes. An empty editor made every student retype the starter
            // when they moved from the lesson bench into the graded challenge.
            'code' => $solutionRevealed && $mission->solution_code !== null
                ? $mission->solution_code
                : ($draft->code ?? (string) $mission->broken_code),
            'hasDraft' => $draft !== null,
            'solutionRevealed' => $solutionRevealed,
            'hints' => $revealedHints,
            'revealedHintCount' => $revealedCount,
            'totalHintCount' => count($hints),
            'nextHintCost' => $this->xp->hintCost($revealedCount + 1),
            'firstHintCost' => $this->xp->hintCost(1),
            'revealCost' => $this->xp->revealCost(),
            'hasSolution' => $this->xp->hasSolution($mission),
            'solutionRequirementMet' => $this->hasFailedAttempt($user, $mission),
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

    /**
     * Required-check preflight for the friendly redirect. The authoritative
     * enforcement lives in the domain services (MissionService, DraftService,
     * XpService) via KnowledgeCheckGate; this method only shapes the response.
     */
    private function knowledgeCheckGate(User $user, Mission $mission, bool $preserveCode = false): ?RedirectResponse
    {
        $required = $this->checkGate->outstanding($user, $mission);

        if ($required === null) {
            return null;
        }

        $redirect = redirect()->route('mission.show', $mission)
            ->with('knowledge_check_info', [
                'title' => 'Knowledge Check required',
                'message' => "Complete {$required->title} before opening this Challenge.",
            ]);

        // A blocked mutation must not cost the student their source. Drafts are
        // explicit-only, so the redirect has to carry the submitted code back.
        return $preserveCode
            ? $redirect->withInput(['code' => request()->input('code')])
            : $redirect;
    }
}
