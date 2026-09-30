<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminClassroomAssignmentRequest;
use App\Http\Requests\AdminClassroomsIndexRequest;
use App\Http\Requests\AdminClassroomStoreRequest;
use App\Http\Requests\AdminClassroomUpdateRequest;
use App\Models\Classroom;
use App\Models\User;
use App\Services\ClassroomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * The admin classroom surface (Classroom / Enrollment Authorization). Admins
 * create and edit classroom rows (name/code/status) and manage the three
 * membership sets — teachers, enrolled students, assigned courses — each
 * through its own explicit operation. Business logic stays in ClassroomService;
 * this controller only plugs validated payloads into it and renders views.
 *
 * Status is an authorization visibility boundary: deactivating a classroom
 * immediately removes teacher visibility (ClassroomAccessService only ever
 * surfaces ACTIVE classrooms to teachers), but the pivots and all academic
 * history are deliberately untouched — activating later restores the scope.
 *
 * A refused write is an InvalidArgumentException and is flashed back; the audit
 * trail already carries the failed row by the time it throws (same success/
 * failed discipline as CourseService/UserService).
 */
class AdminClassroomController extends Controller
{
    public function __construct(private readonly ClassroomService $classrooms) {}

    public function index(AdminClassroomsIndexRequest $request): View
    {
        $filters = $request->safe(['q', 'status']);

        $paginatorQuery = array_filter($filters, fn (mixed $value): bool => $value !== null);

        /** @var User $user */
        $user = auth()->user();

        return view('admin.classrooms.index', [
            'role' => $user->role,
            'classrooms' => $this->classrooms->index(
                $filters['q'] ?? null,
                $filters['status'] ?? null,
            ),
            'filters' => $filters,
            'paginatorQuery' => $paginatorQuery,
        ]);
    }

    public function create(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('admin.classrooms.create', ['role' => $user->role]);
    }

    public function store(AdminClassroomStoreRequest $request): RedirectResponse
    {
        $payload = $request->safe(['name', 'code', 'status']);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $classroom = $this->classrooms->store($actor, $payload);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.classrooms.edit', $classroom)
            ->with('status', 'Classroom created.');
    }

    public function edit(Classroom $classroom): View
    {
        /** @var User $user */
        $user = auth()->user();

        $classroom->loadMissing(['teachers', 'students', 'courses']);

        return view('admin.classrooms.edit', [
            'role' => $user->role,
            'classroom' => $classroom,
            'teacherOptions' => $this->classrooms->teacherOptions(),
            'studentOptions' => $this->classrooms->studentOptions(),
            'courseOptions' => $this->classrooms->courseOptions(),
        ]);
    }

    public function update(AdminClassroomUpdateRequest $request, Classroom $classroom): RedirectResponse
    {
        $payload = $request->safe(['name', 'code', 'status']);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $this->classrooms->update($actor, $classroom, $payload);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.classrooms.edit', $classroom)
            ->with('status', 'Classroom updated.');
    }

    public function assignTeachers(AdminClassroomAssignmentRequest $request, Classroom $classroom): RedirectResponse
    {
        $payload = $request->safe(['teacher_ids']);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $this->classrooms->assignTeachers($actor, $classroom, $payload['teacher_ids'] ?? []);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.classrooms.edit', $classroom)
            ->with('status', 'Teachers assigned.');
    }

    public function enrollStudents(AdminClassroomAssignmentRequest $request, Classroom $classroom): RedirectResponse
    {
        $payload = $request->safe(['student_ids']);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $this->classrooms->enrollStudents($actor, $classroom, $payload['student_ids'] ?? []);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.classrooms.edit', $classroom)
            ->with('status', 'Students enrolled.');
    }

    public function assignCourses(AdminClassroomAssignmentRequest $request, Classroom $classroom): RedirectResponse
    {
        $payload = $request->safe(['course_ids']);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $this->classrooms->assignCourses($actor, $classroom, $payload['course_ids'] ?? []);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.classrooms.edit', $classroom)
            ->with('status', 'Courses assigned.');
    }
}
