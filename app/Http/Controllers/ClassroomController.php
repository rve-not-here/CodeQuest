<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\User;
use App\Services\ClassroomAccessService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

/**
 * The teacher-facing classroom surface (Classroom / Enrollment Authorization).
 * Read-only by construction and fully inside the auth+teacher group: a teacher
 * sees exactly their own teaching classrooms while ACTIVE (inactive classrooms
 * vanish from teacher visibility on the spot), and an admin sees every
 * classroom. This surface only lists memberships — the monitoring data for the
 * enrolled students lives on the existing teacher pages, which are scoped in
 * depth by ClassroomAccessService.
 */
class ClassroomController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ClassroomAccessService $access,
    ) {}

    /**
     * My Classrooms: the classrooms the user is authorized for — an admin's
     * every classroom, a teacher's own ACTIVE teaching assignments only.
     */
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $classrooms = $this->access->classroomsFor($user);
        $classrooms->loadCount(['teachers', 'students', 'courses']);

        return view('classrooms', [
            'role' => $user->role,
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * A classroom's membership detail (teachers, enrolled students, assigned
     * courses). The teacher gate lives in ClassroomPolicy::view, which reuses
     * the same access layer: an admin may open any classroom, a teacher only
     * their own and only while ACTIVE.
     */
    public function show(Classroom $classroom): View
    {
        $this->authorize('view', $classroom);

        /** @var User $user */
        $user = auth()->user();

        $classroom->loadMissing(['teachers', 'students', 'courses']);

        return view('classrooms-show', [
            'role' => $user->role,
            'classroom' => $classroom,
        ]);
    }
}
