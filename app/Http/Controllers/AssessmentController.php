<?php

namespace App\Http\Controllers;

use App\Exceptions\AssessmentAttemptAccessDeniedException;
use App\Exceptions\AssessmentAttemptStateException;
use App\Exceptions\AssessmentNotUnlockedException;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\XpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function __construct(
        private readonly AssessmentService $assessments,
        private readonly XpService $xp,
    ) {}

    /**
     * The Boss Challenge hub: one row per active course, tagged with the
     * student's derived challenge state.
     */
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('assessments.index', [
            'user' => $user,
            'role' => $user->role,
            'rows' => $this->indexRows($user),
            'totalXp' => $this->xp->balance($user),
        ]);
    }

    public function show(Assessment $assessment): View|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $course = $assessment->course;

        if (! $this->assessments->isUnlocked($user, $course)) {
            return redirect()->route('assessments')
                ->with('assessment_locked', [
                    'title' => 'Challenge sealed',
                    'message' => 'Complete every mission in '.$course->name.' to unlock its Boss Challenge.',
                ]);
        }

        return view('assessments.show', $this->showData($user, $assessment, $course));
    }

    public function start(Request $request, Assessment $assessment): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $course = $assessment->course;

        try {
            $this->assessments->beginAttempt($user, $course);
        } catch (AssessmentNotUnlockedException) {
            return redirect()->route('assessments')
                ->with('assessment_error', [
                    'title' => 'Challenge sealed',
                    'message' => 'Complete every mission in '.$course->name.' before beginning its Boss Challenge.',
                ]);
        } catch (AssessmentAttemptStateException) {
            return redirect()->route('assessment.show', $assessment)
                ->with('assessment_error', [
                    'title' => 'Attempt already in progress',
                    'message' => 'An attempt is already active for this challenge.',
                ]);
        }

        return redirect()->route('assessment.show', $assessment);
    }

    /**
     * Client-supplied input is exactly one field: the submission code. No
     * attempt identifiers, scores, verdicts, or XP values may enter the
     * request (§43/§45, US-408 mass-assignment discipline). The attempt the
     * code lands on is resolved server-side as the authenticated user's own
     * latest attempt, so a client can never steer a submission onto another
     * student's row.
     */
    public function submit(Request $request, Assessment $assessment): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $course = $assessment->course;

        if ($course === null || ! $this->assessments->isUnlocked($user, $course)) {
            return redirect()->route('assessments')
                ->with('assessment_error', [
                    'title' => 'Challenge sealed',
                    'message' => 'Complete every mission in '.($course === null ? 'this course' : $course->name).' before submitting its Boss Challenge.',
                ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $attempt = $this->assessments->latestAttemptFor($user, $course);

        if ($attempt === null) {
            return redirect()->route('assessment.show', $assessment)
                ->with('assessment_error', [
                    'title' => 'No attempt in progress',
                    'message' => 'Initiate the challenge before submitting your code.',
                ]);
        }

        // Whether the student had already cleared the course before this
        // submission. Drives the honest success copy: +100 XP only on the
        // first pass (the service already guards the ledger; this is
        // presentational, never authoritative).
        $hadPassedBefore = $this->assessments->hasPassed($user, $course);

        try {
            $this->assessments->submitAttempt($user, $attempt, $validated['code']);
            $attempt = $this->assessments->evaluateAttempt($user, $attempt);
        } catch (AssessmentAttemptAccessDeniedException) {
            abort(403);
        } catch (AssessmentAttemptStateException) {
            return redirect()->route('assessment.show', $assessment)
                ->with('assessment_error', [
                    'title' => 'Attempt not submittable',
                    'message' => 'This attempt cannot be submitted in its current state.',
                ]);
        }

        if ($attempt->status === 'passed') {
            $message = $hadPassedBefore
                ? 'Course already cleared on a prior attempt — no additional XP.'
                : '+'.$this->xp->assessmentPassedAmount().' XP awarded. Course cleared.';

            return redirect()->route('assessment.show', $assessment)
                ->with('assessment_success', [
                    'title' => 'Challenge passed',
                    'message' => $message,
                ]);
        }

        return redirect()->route('assessment.show', $assessment)
            ->with('assessment_error', [
                'title' => 'Challenge failed',
                'message' => 'Scored '.$attempt->score.'% — need '.$assessment->passing_score.'% to clear. Review and retry.',
            ]);
    }

    public function retry(Request $request, Assessment $assessment): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $course = $assessment->course;

        try {
            $this->assessments->retryAttempt($user, $course);
        } catch (AssessmentNotUnlockedException) {
            return redirect()->route('assessments')
                ->with('assessment_error', [
                    'title' => 'Challenge sealed',
                    'message' => 'Complete every mission in '.$course->name.' before retrying its Boss Challenge.',
                ]);
        } catch (AssessmentAttemptStateException) {
            return redirect()->route('assessment.show', $assessment)
                ->with('assessment_error', [
                    'title' => 'Retry not available',
                    'message' => 'A retry opens only after a challenge has been passed or failed.',
                ]);
        }

        return redirect()->route('assessment.show', $assessment)
            ->with('assessment_info', [
                'title' => 'Retry opened',
                'message' => 'A fresh attempt is ready. Previous attempts stay on record.',
            ]);
    }

    /**
     * Build the index view model: every active course in order, each with its
     * assessment and the student's derived challenge state.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function indexRows(User $user): Collection
    {
        return Course::query()
            ->where('status', 'active')
            ->orderBy('order_num')
            ->with('assessment')
            ->get()
            ->map(fn (Course $course): array => $this->indexRow($user, $course));
    }

    /**
     * @return array<string, mixed>
     */
    private function indexRow(User $user, Course $course): array
    {
        $assessment = $course->assessment;
        $eligible = $assessment !== null && $this->assessments->isEligible($user, $course);
        $passed = $assessment !== null && $this->assessments->hasPassed($user, $course);
        $latest = $assessment !== null ? $this->assessments->latestAttemptFor($user, $course) : null;

        return [
            'course' => $course,
            'assessment' => $assessment,
            'hasPassed' => $passed,
            'latest' => $latest,
            'unlocked' => $eligible,
            'missionProgress' => $this->missionProgress($user, $course),
            'state' => $this->challengeState($assessment, $eligible, $passed, $latest),
        ];
    }

    private function challengeState(?Assessment $assessment, bool $eligible, bool $passed, ?object $latest): string
    {
        if ($assessment === null) {
            return 'none';
        }

        if ($passed) {
            return $latest !== null && $latest->status === 'failed' ? 'failed-retry' : 'passed';
        }

        if (! $eligible) {
            return 'sealed';
        }

        return match ($latest?->status) {
            null => 'ready',
            'available', 'started' => 'in-progress',
            'submitted' => 'submitted',
            'failed' => 'failed',
            'passed' => 'passed',
            default => 'ready',
        };
    }

    /**
     * @return array{completed: int, total: int}
     */
    private function missionProgress(User $user, Course $course): array
    {
        $missionIds = $course->missions()->pluck('id');

        $completed = $user->progress()
            ->whereIn('mission_id', $missionIds)
            ->count();

        return [
            'completed' => $completed,
            'total' => $missionIds->count(),
        ];
    }

    /**
     * View data for the challenge screen. The latest attempt is resolved as
     * the authenticated user's own (latestAttemptFor is user-scoped), so
     * nothing here can surface another student's rows.
     *
     * @return array<string, mixed>
     */
    private function showData(User $user, Assessment $assessment, Course $course): array
    {
        $attempt = $this->assessments->latestAttemptFor($user, $course);

        return [
            'user' => $user,
            'role' => $user->role,
            'assessment' => $assessment,
            'course' => $course,
            'attempt' => $attempt,
            'code' => $attempt?->code ?? '',
            'hasPassed' => $this->assessments->hasPassed($user, $course),
            'canBegin' => $attempt === null,
            'canEdit' => in_array($attempt?->status, ['available', 'started'], true),
            'canRetry' => in_array($attempt?->status, ['passed', 'failed'], true),
            'xpBalance' => $this->xp->balance($user),
            'reward' => $this->xp->assessmentPassedAmount(),
        ];
    }
}
