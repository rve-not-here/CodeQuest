<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\LearningPathService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The flat challenge registry (/missions). Courses, sections and missions are
 * rendered from the same LearningPathService tree the learning path shows, so
 * there is exactly one per-mission state derivation. This page is a compact,
 * status-tagged index of every challenge in course order — the "Missions" nav
 * target — while the learning path remains the navigational course view.
 *
 * Strictly the authenticated user's own state: no user-suppliable identifier
 * is accepted. A client cannot pivot the registry onto another user.
 */
class MissionIndexController extends Controller
{
    public function __construct(
        private readonly LearningPathService $paths,
        private readonly AssessmentService $assessments,
    ) {}

    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'The challenge index is scoped to your own progress.');
        }

        /** @var User $user */
        $user = auth()->user();

        /** @var Collection<int, array{course: Course, progress: array{completed: int, total: int, percent: int}, sections: Collection<int, array{section: Section, progress: array{completed: int, total: int, percent: int}, missions: Collection<int, array{mission: Mission, state: string}>}>}> $tree */
        $tree = $this->paths->build($user);

        // Use the same course-reach verdict as MissionController::show.
        $reachedCourses = $tree->mapWithKeys(fn (array $node): array => [
            $node['course']->id => $node['course']->status === 'active'
                && $this->assessments->isCourseReached($user, $node['course']),
        ]);

        /** @var Collection<int, array{course: Course, section: Section, mission: Mission, state: string, accessible: bool}> $rows */
        $rows = collect();

        foreach ($tree as $courseNode) {
            foreach ($courseNode['sections'] as $sectionNode) {
                foreach ($sectionNode['missions'] as $row) {
                    $rows->push([
                        'course' => $courseNode['course'],
                        'section' => $sectionNode['section'],
                        'mission' => $row['mission'],
                        'state' => $row['state'],
                        'accessible' => $reachedCourses->get($courseNode['course']->id, false),
                    ]);
                }
            }
        }

        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $courseId = $request->query('course');

        if ($search !== '') {
            $rows = $rows->filter(
                fn (array $row): bool => str_contains(strtolower($row['mission']->title), strtolower($search))
                    || str_contains(strtolower($row['course']->name), strtolower($search))
            );
        }

        if (in_array($status, ['COMPLETED', 'IN PROGRESS', 'NOT STARTED'], true)) {
            $rows = $rows->filter(fn (array $row): bool => $row['state'] === $status);
        }

        if ($request->filled('course') && ctype_digit((string) $courseId)) {
            $rows = $rows->filter(fn (array $row): bool => $row['course']->id === (int) $courseId);
        }

        return view('missions-index', [
            'role' => $user->role,
            'rows' => $rows->values(),
            'courses' => Course::query()->orderBy('order_num')->get(),
            'filters' => [
                'q' => $search,
                'status' => in_array($status, ['COMPLETED', 'IN PROGRESS', 'NOT STARTED'], true) ? $status : null,
                'course' => $request->filled('course') && ctype_digit((string) $courseId) ? (int) $courseId : null,
            ],
            'totalXp' => (int) $user->xpTransactions()->sum('amount'),
            'currentMissionId' => $this->paths->nextMission($user)?->id,
        ]);
    }
}
