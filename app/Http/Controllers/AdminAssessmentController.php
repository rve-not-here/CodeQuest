<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAssessmentUpdateRequest;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use App\Services\AdminAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * The admin assessment management (US-708, §24.0). Nested under a course,
 * mirroring sections and missions. Unlike those, a course has exactly ONE
 * assessment (if any), so the index shows a single Boss Challenge or an empty
 * state, and the routes are singular. Edit writes title/description/
 * instructions/passing_score/status. grading_rule is view-only — authored via
 * the controlled AssessmentSeeder, exactly like solution_code/validate_rule on
 * missions. assessment.status is an access gate (AssessmentService::isUnlocked
 * requires 'active'), so locking or drafting seals the challenge the same way
 * course.status seals a course (US-705) without rewriting recorded attempts.
 * passing_score edits affect only future evaluations — verdicts already
 * persisted on attempt rows are never rewritten. Creation and deletion are out
 * of scope for this story (§24: the one-per-course invariant is the seeder's
 * job); only an existing assessment is editable.
 *
 * A refused update is an InvalidArgumentException and is flashed back; the
 * audit trail already carries the failed row by the time it throws (same
 * success/failed discipline as US-704/705/706/707).
 */
class AdminAssessmentController extends Controller
{
    public function __construct(
        private readonly AdminAssessmentService $assessments,
    ) {}

    public function index(Course $course): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('admin.assessments.index', [
            'user' => $user,
            'role' => $user->role,
            'course' => $course,
            'assessment' => $this->assessments->forCourse($course),
        ]);
    }

    public function edit(Course $course, Assessment $assessment): View
    {
        if ($assessment->course_id !== $course->id) {
            abort(404, 'This assessment does not belong to the given course.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('admin.assessments.edit', [
            'user' => $user,
            'role' => $user->role,
            'course' => $course,
            'assessment' => $assessment,
        ]);
    }

    public function update(AdminAssessmentUpdateRequest $request, Course $course, Assessment $assessment): RedirectResponse
    {
        $payload = $request->safe([
            'title', 'description', 'instructions', 'passing_score', 'status',
        ]);

        /** @var User $actor */
        $actor = auth()->user();

        if ($assessment->course_id !== $course->id) {
            abort(404, 'This assessment does not belong to the given course.');
        }

        try {
            $this->assessments->update($actor, $course, $assessment, $payload);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.courses.assessment', $course)
            ->with('status', 'Assessment updated.');
    }
}
