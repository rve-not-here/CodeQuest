<?php

namespace App\Http\Controllers;

use App\Exceptions\KnowledgeCheckAccessDeniedException;
use App\Exceptions\KnowledgeCheckStateException;
use App\Exceptions\KnowledgeCheckUnavailableException;
use App\Http\Requests\KnowledgeCheckSubmitRequest;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\Mission;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\KnowledgeCheckService;
use App\Services\XpService;
use App\Support\KnowledgeCheckView;
use App\Support\MissionView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\ValidatedInput;
use Illuminate\View\View;

class KnowledgeCheckController extends Controller
{
    public function __construct(
        private readonly KnowledgeCheckService $knowledgeChecks,
        private readonly AssessmentService $assessments,
        private readonly XpService $xp,
    ) {}

    public function start(Request $request, Mission $mission, KnowledgeCheck $knowledgeCheck): RedirectResponse
    {
        $user = $this->student();
        $this->ensureCourseActive($mission);
        $this->ensureCourseReached($user, $mission, $knowledgeCheck);

        try {
            $attempt = $this->knowledgeChecks->start($user, $mission, $knowledgeCheck);
        } catch (KnowledgeCheckUnavailableException) {
            abort(404);
        }

        if ($this->returnsToMission($request)) {
            return redirect(route('mission.challenge', $mission).'#workspace-check');
        }

        return redirect()->route('knowledge-check.show', [$mission, $knowledgeCheck, $attempt]);
    }

    public function show(
        Mission $mission,
        KnowledgeCheck $knowledgeCheck,
        KnowledgeCheckAttempt $attempt,
    ): View {
        $user = $this->student();
        $this->ensureCourseActive($mission);
        $this->ensureCourseReached($user, $mission, $knowledgeCheck);

        try {
            $presentation = $this->knowledgeChecks->presentation($user, $mission, $knowledgeCheck, $attempt);
        } catch (KnowledgeCheckAccessDeniedException|KnowledgeCheckUnavailableException) {
            abort(404);
        }

        // The presentation's next-check teaser is a KnowledgeCheck model;
        // student views get the whitelisted view-model, and the mission is
        // whitelisted too — the contract forbids passing the full models.
        $nextCheck = $presentation['nextCheck'];
        $presentation['nextCheck'] = $nextCheck === null ? null : KnowledgeCheckView::from($nextCheck);

        return view('knowledge-check', [
            'role' => $user->role,
            'totalXp' => $this->xp->balance($user),
            'mission' => MissionView::from($mission),
            'course' => $mission->course,
            'section' => $mission->section,
            'knowledgeCheck' => KnowledgeCheckView::from($knowledgeCheck),
            'attempt' => $attempt,
            ...$presentation,
        ]);
    }

    public function submit(
        KnowledgeCheckSubmitRequest $request,
        Mission $mission,
        KnowledgeCheck $knowledgeCheck,
        KnowledgeCheckAttempt $attempt,
    ): RedirectResponse {
        $user = $this->student();
        $this->ensureCourseActive($mission);
        $this->ensureCourseReached($user, $mission, $knowledgeCheck);

        /** @var ValidatedInput $payload */
        $payload = $request->safe(['answers']);
        /** @var array<int|string, int|string> $answers */
        $answers = $payload['answers'];

        try {
            $this->knowledgeChecks->submit($user, $mission, $knowledgeCheck, $attempt, $answers);
        } catch (KnowledgeCheckAccessDeniedException|KnowledgeCheckUnavailableException) {
            abort(404);
        }

        if ($this->returnsToMission($request)) {
            return redirect(route('mission.challenge', $mission).'#workspace-check');
        }

        return redirect()->route('knowledge-check.show', [$mission, $knowledgeCheck, $attempt]);
    }

    public function retry(Request $request, Mission $mission, KnowledgeCheck $knowledgeCheck): RedirectResponse
    {
        $user = $this->student();
        $this->ensureCourseActive($mission);
        $this->ensureCourseReached($user, $mission, $knowledgeCheck);

        try {
            $attempt = $this->knowledgeChecks->retry($user, $mission, $knowledgeCheck);
        } catch (KnowledgeCheckUnavailableException) {
            abort(404);
        } catch (KnowledgeCheckStateException $exception) {
            return redirect()->route('mission.show', $mission)
                ->with('knowledge_check_error', $exception->getMessage());
        }

        if ($this->returnsToMission($request)) {
            return redirect(route('mission.challenge', $mission).'#workspace-check');
        }

        return redirect()->route('knowledge-check.show', [$mission, $knowledgeCheck, $attempt]);
    }

    /**
     * The mission workspace hosts the check in its own tab, so an action
     * taken there returns to that tab instead of the standalone attempt
     * screen. The destination is a fixed route, never a client-supplied URL.
     */
    private function returnsToMission(Request $request): bool
    {
        return $request->input('return_to') === 'mission';
    }

    private function student(): User
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->role !== 'student') {
            abort(403, 'Knowledge Checks are available to students only.');
        }

        return $user;
    }

    private function ensureCourseActive(Mission $mission): void
    {
        if ($mission->course === null || $mission->course->status !== 'active') {
            abort(403, 'This course is not currently active.');
        }
    }

    private function ensureCourseReached(User $user, Mission $mission, KnowledgeCheck $check): void
    {
        if ($check->mission_id !== $mission->id || $check->status !== KnowledgeCheck::STATUS_PUBLISHED) {
            abort(404);
        }

        $course = $mission->course;

        if ($course === null || ! $this->assessments->isCourseReached($user, $course)) {
            abort(403, 'Complete earlier courses before opening this Knowledge Check.');
        }
    }
}
