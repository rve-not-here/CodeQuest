<?php

namespace Tests\Concerns;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;

/**
 * Classroom / Enrollment Authorization fixture helper. Teacher-facing tests
 * must mirror production scoping: a teacher may monitor only the students and
 * courses of their own ACTIVE classrooms. Call this helper and pass the
 * teacher, the students, and the courses the test needs visible — the roster,
 * analytics, activity feed, attention signals, and progress pages all expose
 * exactly that overlap.
 *
 * Tests that call the fleet services directly (no args) keep behaving as the
 * fleet-wide "admin" case and need no fixture; tests that assert teacher-page
 * or teacher-scoped-service content must attach a classroom first.
 */
trait WithClassroomScope
{
    /**
     * An ACTIVE classroom carrying the given teacher, students, and courses.
     *
     * @param  array<int, User>  $students
     * @param  array<int, Course>  $courses
     */
    protected function classroomFor(User $teacher, array $students, array $courses = []): Classroom
    {
        $classroom = Classroom::factory()->create();

        $classroom->teachers()->sync($teacher->id);
        $classroom->students()->sync(collect($students)->pluck('id')->all());
        $classroom->courses()->sync(collect($courses)->pluck('id')->all());

        return $classroom;
    }
}
