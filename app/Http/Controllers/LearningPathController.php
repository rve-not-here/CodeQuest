<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\DashboardService;
use App\Services\LearningPathService;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LearningPathController extends Controller
{
    public function __construct(
        private readonly LearningPathService $path,
        private readonly DashboardService $dashboard,
        private readonly AssessmentService $assessments,
    ) {}

    public function __invoke(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $course = $this->dashboard->currentCourse($user);
        $nextMission = $this->path->nextMission($user, $course);

        $tree = $this->path->build($user)
            ->map(function (array $courseNode) use ($user): array {
                $courseNode['boss'] = $this->bossNode($user, $courseNode);

                return $courseNode;
            });

        return view('learning-path', [
            'user' => $user,
            'role' => $user->role,
            'tree' => $tree,
            'totalXp' => $this->dashboard->totalXp($user),
            'nextMission' => $nextMission,
            'course' => $course,
        ]);
    }

    /**
     * @param  array{course: Course, progress: array{completed: int, total: int, percent: int}}  $courseNode
     * @return array{assessment: ?Assessment, state: 'PASSED'|'AVAILABLE'|'LOCKED', reason: string}
     */
    private function bossNode(User $user, array $courseNode): array
    {
        $course = $courseNode['course'];
        $assessment = $this->assessments->forCourse($course);

        if ($assessment === null) {
            return [
                'assessment' => null,
                'state' => 'LOCKED',
                'reason' => 'No Boss Challenge is configured for this course.',
            ];
        }

        if ($this->assessments->hasPassed($user, $course)) {
            return [
                'assessment' => $assessment,
                'state' => 'PASSED',
                'reason' => 'Course-level competency has been demonstrated.',
            ];
        }

        if ($this->assessments->isUnlocked($user, $course)) {
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
