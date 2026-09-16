<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminMissionUpdateRequest;
use App\Models\Course;
use App\Models\Mission;
use App\Models\User;
use App\Services\AdminMissionService;
use App\Services\SectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * The admin mission listing (US-707, §19.0). Nested under a course so the
 * Course → Section → Mission hierarchy is explicit in the URL; lists that
 * course's missions in order_num order with their section, difficulty, and
 * points. Missions carry no status of their own — the access gate sits on
 * course.status (US-705) — so there is no status field to manage here.
 * Business logic stays in AdminMissionService; this controller only plugs the
 * validated, whitelisted payload into it and renders views. Mission creation
 * and deletion are out of scope for this story (§14.0 no-destructive-operation
 * posture): only existing missions are editable, and only their nine editable
 * fields (title, description, difficulty, points, order_num, section_id,
 * hints, broken_code, target_html). solution_code and validate_rule are
 * view-only and never writable here.
 *
 * A refused update is an InvalidArgumentException and is flashed back; the
 * audit trail already carries the failed row by the time it throws (same
 * success/failed discipline as US-704/705/706).
 */
class AdminMissionController extends Controller
{
    public function __construct(
        private readonly AdminMissionService $missions,
        private readonly SectionService $sections,
    ) {}

    public function index(Course $course): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('admin.missions.index', [
            'user' => $user,
            'role' => $user->role,
            'course' => $course,
            'missions' => $this->missions->forCourse($course),
        ]);
    }

    public function edit(Course $course, Mission $mission): View
    {
        if ($mission->course_id !== $course->id) {
            abort(404, 'This mission does not belong to the given course.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('admin.missions.edit', [
            'user' => $user,
            'role' => $user->role,
            'course' => $course,
            'mission' => $mission,
            'sections' => $this->sections->forCourse($course),
        ]);
    }

    public function update(AdminMissionUpdateRequest $request, Course $course, Mission $mission): RedirectResponse
    {
        $payload = $request->safe([
            'title', 'description', 'difficulty', 'points', 'order_num',
            'section_id', 'hints', 'broken_code', 'target_html',
        ]);

        /** @var User $actor */
        $actor = auth()->user();

        if ($mission->course_id !== $course->id) {
            abort(404, 'This mission does not belong to the given course.');
        }

        try {
            $this->missions->update($actor, $course, $mission, $payload);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.courses.missions', $course)
            ->with('status', 'Mission updated.');
    }
}
