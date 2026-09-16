<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminCourseUpdateRequest;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * The admin course catalog (US-705, §15.0–§17.0). Lists every course in
 * order_num order (the single ordering mechanism, preserved) and edits the
 * catalog fields plus status. Business logic stays in CourseService — this
 * controller only plugs the validated, whitelisted payload into it and renders
 * views. Course creation and deletion are out of scope for this story (§14.0
 * no-destructive-operation posture): only existing courses are editable.
 *
 * Course status changes are access gates (US-705, confirmed): locking or
 * drafting a course seals its missions and Boss Challenge, blocks all new
 * progress/XP/achievement accrual by URL, and never rewrites recorded
 * progress (CourseService writes only the course row). A refused update is
 * an InvalidArgumentException and is flashed back; the audit trail already
 * carries the failed row by the time it throws (same success/failed
 * discipline as US-704).
 */
class AdminCourseController extends Controller
{
    public function __construct(private readonly CourseService $courses) {}

    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('admin.courses.index', [
            'user' => $user,
            'role' => $user->role,
            'courses' => $this->courses->ordered(),
        ]);
    }

    public function edit(Course $course): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('admin.courses.edit', [
            'user' => $user,
            'role' => $user->role,
            'course' => $course,
        ]);
    }

    public function update(AdminCourseUpdateRequest $request, Course $course): RedirectResponse
    {
        $payload = $request->safe(['name', 'slug', 'type', 'description', 'status', 'order_num']);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $this->courses->update($actor, $course, $payload);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.courses')
            ->with('status', 'Course updated.');
    }
}
