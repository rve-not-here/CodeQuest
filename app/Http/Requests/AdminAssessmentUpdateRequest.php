<?php

namespace App\Http\Requests;

use App\Services\AdminAssessmentService;
use App\Services\ValidationService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Assessment update payload (US-708, §24.0). One course has exactly one
 * assessment (the the404_assessments unique course_id constraint), so the
 * editable fields mirror the AdminAssessmentService allow list: title,
 * description, instructions, passing_score (integer 1..100), status (from the
 * active|locked|draft enum) and grading_rule.
 *
 * grading_rule is now an audited admin write (P2-7): criteria must stay
 * editable after seeding instead of being frozen. The value must be a
 * structurally valid JSON rule array — the same contract used for academic
 * evaluation — and the guarded service re-checks it on top of this shape
 * validation, so a crafted payload cannot store an unusable rule set.
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
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', Rule::in(AdminAssessmentService::STATUSES)],
            'grading_rule' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! app(ValidationService::class)->isValidDefinition($value)) {
                        $fail('Grading rules must be a structurally valid JSON array of supported rules.');
                    }
                },
            ],
        ];
    }
}
