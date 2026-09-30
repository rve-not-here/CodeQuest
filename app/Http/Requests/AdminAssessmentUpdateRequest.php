<?php

namespace App\Http\Requests;

use App\Services\AdminAssessmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Assessment update payload (US-708, §24.0). One course has exactly one
 * assessment (the the404_assessments unique course_id constraint), so all five
 * editable fields mirror the AdminAssessmentService allow list: title,
 * description, instructions, passing_score (non-negative integer), and status
 * (from the active|locked|draft enum). grading_rule is deliberately NOT
 * present: it is view-only this story — grading rules are authored through the
 * controlled AssessmentSeeder, the same path as solution_code/validate_rule on
 * missions — and can never be written even by a crafted payload. The guarded
 * AdminAssessmentService write path re-checks the allow list on top of this
 * shape validation.
 */
class AdminAssessmentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the passing_score input before validation. HTML forms submit
     * the number field as a numeric string; the guarded service requires a
     * real int, so coerce here rather than forcing the service to widen its
     * type (same pattern as section_id on missions).
     */
    protected function prepareForValidation(): void
    {
        $passingScore = $this->input('passing_score');

        if (is_string($passingScore) && ctype_digit($passingScore)) {
            $parsed = filter_var($passingScore, FILTER_VALIDATE_INT);

            if ($parsed !== false) {
                $this->merge(['passing_score' => $parsed]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'passing_score' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(AdminAssessmentService::STATUSES)],
        ];
    }
}
