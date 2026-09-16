<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminSectionUpdateRequest;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Services\SectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * The admin section listing (US-706, §18.0). Nested under a course so the
 * Course → Section → Mission hierarchy is explicit in the URL; lists that
 * course's sections in order_num order with their mission counts. Sections
 * carry no status of their own — the access gate sits on course.status
 * (US-705) — so there is no status field to manage here. Business logic
 * stays in SectionService; this controller only plugs the validated,
 * whitelisted payload into it and renders views. Section creation and
 * deletion are out of scope for this story (§14.0 no-destructive-operation
 * posture): only existing sections are editable.
 *
 * A refused update is an InvalidArgumentException and is flashed back; the
 * audit trail already carries the failed row by the time it throws (same
 * success/failed discipline as US-704/705).
 */
class AdminSectionController extends Controller
{
    public function __construct(private readonly SectionService $sections) {}

    public function index(Course $course): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('admin.sections.index', [
            'user' => $user,
            'role' => $user->role,
            'course' => $course,
            'sections' => $this->sections->forCourse($course),
        ]);
    }

    public function edit(Course $course, Section $section): View
    {
        if ($section->course_id !== $course->id) {
            abort(404, 'This section does not belong to the given course.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('admin.sections.edit', [
            'user' => $user,
            'role' => $user->role,
            'course' => $course,
            'section' => $section,
        ]);
    }

    public function update(AdminSectionUpdateRequest $request, Course $course, Section $section): RedirectResponse
    {
        $payload = $request->safe(['title', 'description', 'order_num']);

        /** @var User $actor */
        $actor = auth()->user();

        if ($section->course_id !== $course->id) {
            abort(404, 'This section does not belong to the given course.');
        }

        try {
            $this->sections->update($actor, $course, $section, $payload);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.courses.sections', $course)
            ->with('status', 'Section updated.');
    }
}
