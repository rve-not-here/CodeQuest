<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminKnowledgeCheckUpdateRequest;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\Mission;
use App\Models\User;
use App\Services\AdminKnowledgeCheckService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Admin Knowledge Check metadata management (P1-1). Nested under
 * Course → Mission → Knowledge Check so the curriculum hierarchy is explicit
 * in the URL. Only metadata is editable: title, instructions, the required
 * gate, and lifecycle status. Question and option content remains on the
 * controlled release path.
 *
 * The required gate is the operationally important field: a published,
 * required check blocks new mission work through KnowledgeCheckGate until a
 * student submits any attempt. This controller is the way that policy is
 * turned on or off for existing content, without a deployment.
 *
 * A refused update is an InvalidArgumentException and is flashed back; the
 * audit trail already carries the failed row by the time it throws (same
 * success/failed discipline as the other admin write paths).
 */
class AdminKnowledgeCheckController extends Controller
{
    public function __construct(
        private readonly AdminKnowledgeCheckService $checks,
    ) {}

    public function index(Course $course, Mission $mission): View
    {
        $this->assertHierarchy($course, $mission);

        /** @var User $user */
        $user = auth()->user();

        return view('admin.knowledge-checks.index', [
            'user' => $user,
            'role' => $user->role,
            'course' => $course,
            'mission' => $mission,
            'checks' => $this->checks->forMission($mission),
        ]);
    }

    public function edit(Course $course, Mission $mission, KnowledgeCheck $check): View
    {
        $this->assertHierarchy($course, $mission);

        if ($check->mission_id !== $mission->id) {
            abort(404, 'This Knowledge Check does not belong to the given mission.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('admin.knowledge-checks.edit', [
            'user' => $user,
            'role' => $user->role,
            'course' => $course,
            'mission' => $mission,
            'check' => $check,
        ]);
    }

    public function update(AdminKnowledgeCheckUpdateRequest $request, Course $course, Mission $mission, KnowledgeCheck $check): RedirectResponse
    {
        $this->assertHierarchy($course, $mission);

        if ($check->mission_id !== $mission->id) {
            abort(404, 'This Knowledge Check does not belong to the given mission.');
        }

        $payload = $request->safe([
            'title', 'instructions', 'is_required', 'status',
        ]);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $this->checks->update($actor, $course, $mission, $check, $payload);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.courses.missions.checks', [$course, $mission])
            ->with('status', 'Knowledge Check updated.');
    }

    private function assertHierarchy(Course $course, Mission $mission): void
    {
        if ($mission->course_id !== $course->id) {
            abort(404, 'This mission does not belong to the given course.');
        }
    }
}
