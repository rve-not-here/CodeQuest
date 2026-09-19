<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\DashboardService;
use App\Services\LearningPathService;
use App\Services\RecommendationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LearningPathController extends Controller
{
    public function __construct(
        private readonly LearningPathService $path,
        private readonly DashboardService $dashboard,
        private readonly AssessmentService $assessments,
        private readonly RecommendationService $recommendations,
    ) {}

    /**
     * Learning Path with integrated recommendations (US-908). Thin
     * composition only: the path service still owns all path state and the
     * recommendation service still owns all recommendation content. Cards
     * are display-only — every href targets an existing route that enforces
     * its own authorization, so rendering a link can never unlock, bypass,
     * or complete anything. Scoped strictly to the authenticated user; the
     * route takes no identifier of any spelling.
     */
    public function __invoke(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $course = $this->dashboard->currentCourse($user);
        $nextMission = $this->path->nextMission($user, $course);

        $tree = $this->path->build($user);

        // US-911: one batched assessment-state read for every boss node
        // instead of forCourse/hasPassed/isUnlocked per course. The node
        // verdicts below read off this map; no rule changes.
        $states = $this->assessments->assessmentStatesForCourses(
            $user,
            $tree->map(fn (array $courseNode): Course => $courseNode['course'])
        );

        $tree = $tree->map(function (array $courseNode) use ($states): array {
            $courseNode['boss'] = $this->bossNode($courseNode, $states[$courseNode['course']->id]);

            return $courseNode;
        });

        return view('learning-path', [
            'user' => $user,
            'role' => $user->role,
            'tree' => $tree,
            'totalXp' => $this->dashboard->totalXp($user),
            'nextMission' => $nextMission,
            'course' => $course,
            'recommendations' => $this->accessibleRecommendations($user, $tree),
        ]);
    }

    /**
     * Recommendation cards annotated with target accessibility (US-908
     * audit). No progression, unlock, eligibility, or status formula lives
     * here: every verdict is read off state this request already computed.
     * Mission targets reuse the tree's own sealed-course flag (the same
     * predicate the path nodes render with — MissionController answers a
     * sealed course with 403). Assessment targets reuse the boss node state
     * (built from AssessmentService::isUnlocked/hasPassed — the same rule
     * assessment.show enforces). Learning-path targets are always
     * accessible. An href with no known target defaults to inaccessible
     * rather than rendering a hopeful link.
     *
     * @param  Collection<int, array{course: Course, progress: array{completed: int, total: int, percent: int}, sections: Collection<int, array{section: Section, progress: array{completed: int, total: int, percent: int}, missions: Collection<int, array{mission: Mission, state: string, xp: int}>}>, boss: array{assessment: ?Assessment, state: string, reason: string}}>  $tree
     * @return Collection<int, array{slot: int, title: string, subtitle: string, href: string, cta: string, accessible: bool, locked_reason: ?string}>
     */
    private function accessibleRecommendations(User $user, Collection $tree): Collection
    {
        $access = [route('learning-path') => ['accessible' => true, 'locked_reason' => null]];

        foreach ($tree as $courseNode) {
            $locked = $courseNode['course']->status !== 'active';

            foreach ($courseNode['sections'] as $sectionNode) {
                foreach ($sectionNode['missions'] as $row) {
                    $access[route('mission.show', $row['mission'])] = [
                        'accessible' => ! $locked,
                        'locked_reason' => $locked
                            ? 'Course access is sealed. Return when Command restores this course.'
                            : null,
                    ];
                }
            }

            $boss = $courseNode['boss'];

            if ($boss['assessment'] !== null) {
                $available = $boss['state'] !== 'LOCKED';
                $access[route('assessment.show', $boss['assessment'])] = [
                    'accessible' => $available,
                    'locked_reason' => $available ? null : $boss['reason'],
                ];
            }
        }

        return $this->recommendations->recommendations($user)
            ->map(fn (array $card): array => array_merge($card, $access[$card['href']] ?? [
                'accessible' => false,
                'locked_reason' => 'Currently unavailable.',
            ]));
    }

    /**
     * @param  array{course: Course, progress: array{completed: int, total: int, percent: int}}  $courseNode
     * @param  array{assessment: ?Assessment, passed: bool, eligible: bool}  $state
     * @return array{assessment: ?Assessment, state: 'PASSED'|'AVAILABLE'|'LOCKED', reason: string}
     */
    private function bossNode(array $courseNode, array $state): array
    {
        $course = $courseNode['course'];
        $assessment = $state['assessment'];

        if ($assessment === null) {
            return [
                'assessment' => null,
                'state' => 'LOCKED',
                'reason' => 'No Boss Challenge is configured for this course.',
            ];
        }

        if ($state['passed']) {
            return [
                'assessment' => $assessment,
                'state' => 'PASSED',
                'reason' => 'Course-level competency has been demonstrated.',
            ];
        }

        if ($course->status === 'active' && $assessment->status === 'active' && $state['eligible']) {
            return [
                'assessment' => $assessment,
                'state' => 'AVAILABLE',
                'reason' => 'All required challenges are complete.',
            ];
        }

        $remaining = max(0, $courseNode['progress']['total'] - $courseNode['progress']['completed']);

        return [
            'assessment' => $assessment,
            'state' => 'LOCKED',
            'reason' => $course->status !== 'active' || $assessment->status !== 'active'
                ? 'This assessment is not currently active.'
                : 'Complete '.$remaining.' remaining '.Str::plural('challenge', $remaining).' to unlock it.',
        ];
    }
}
