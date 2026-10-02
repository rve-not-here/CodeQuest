<?php

namespace App\Http\Requests;

use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Course catalog update payload (US-705, §15.0–§17.0). Every editable course
 * field is server-validated here: slug is kebab-case and unique among courses
 * (ignoring self), type is a short free string (CompetencyService degrades
 * gracefully with an uppercase fallback for unknown types), status is clamped
 * to CourseService::STATUSES (active|locked|draft), and order_num is a
 * non-negative integer. The guarded CourseService write path re-checks the
 * allow lists and enforces the never-rewrite-progress invariant on top of this
 * shape validation.
 */
class AdminCourseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $orderNum = $this->input('order_num');

        if (is_string($orderNum) && ctype_digit($orderNum)) {
            $parsed = filter_var($orderNum, FILTER_VALIDATE_INT);

            if ($parsed !== false) {
                $this->merge(['order_num' => $parsed]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Course $course */
        $course = $this->route('course');

        return [
            'name' => ['required', 'string', 'max:128'],
            'slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('the404_courses', 'slug')->ignore($course->id),
            ],
            'type' => ['required', 'string', 'max:16'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(CourseService::STATUSES)],
            'order_num' => ['required', 'integer', 'min:0'],
        ];
    }
}
