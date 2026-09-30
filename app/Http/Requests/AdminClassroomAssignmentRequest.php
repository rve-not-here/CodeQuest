<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validated assignment payload for the three classroom membership operations
 * (Classroom / Enrollment Authorization): teacher assignment, student
 * enrollment, and course assignment. Each set is shape-validated here against
 * the real account/course tables; ClassroomService re-checks and refuses at
 * the write boundary so a bad id can never silently attach to a pivot.
 *
 * Assignments are deliberately separate from classroom base fields: membership
 * changes never rewrite academic history, and deactivating a classroom never
 * rewrites (or clears) the pivots.
 */
class AdminClassroomAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'teacher_ids' => ['nullable', 'array', 'max:200'],
            'teacher_ids.*' => [
                'integer',
                'min:1',
                Rule::exists('the404_users', 'id')->where('role', 'teacher'),
            ],
            'student_ids' => ['nullable', 'array', 'max:200'],
            'student_ids.*' => [
                'integer',
                'min:1',
                Rule::exists('the404_users', 'id')->where('role', 'student'),
            ],
            'course_ids' => ['nullable', 'array', 'max:200'],
            'course_ids.*' => [
                'integer',
                'min:1',
                Rule::exists('the404_courses', 'id'),
            ],
        ];
    }
}
