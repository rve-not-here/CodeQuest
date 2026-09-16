<?php

namespace App\Http\Requests;

use App\Services\AdminMissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mission update payload (US-707, §19.0). Missions carry no status field (the
 * access gate sits on course.status, US-705), so there is no status input.
 * The nine editable fields mirror the AdminMissionService allow list: title,
 * description, difficulty (from the EASY/MEDIUM/HARD enum), points and
 * order_num (non-negative integers), section_id (nullable — a cross-course
 * section is checked at the service layer, the schema FK alone does not stop
 * it), hints, broken_code, and target_html. solution_code and validate_rule
 * are deliberately NOT present: they are view-only this story and can never
 * be written even by a crafted payload. The guarded AdminMissionService write
 * path re-checks the allow list on top of this shape validation.
 */
class AdminMissionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the section select before validation. HTML forms submit the
     * option value as a numeric string (and "NO SECTION" as an empty string,
     * already nulled by the ConvertEmptyStringsToNull middleware); the guarded
     * service requires a real int or null, so coerce here rather than forcing
     * the service to widen its type.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('section_id') && $this->input('section_id') !== null) {
            $this->merge(['section_id' => (int) $this->input('section_id')]);
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
            'difficulty' => ['required', Rule::in(AdminMissionService::DIFFICULTIES)],
            'points' => ['required', 'integer', 'min:0'],
            'order_num' => ['required', 'integer', 'min:0'],
            'section_id' => ['nullable', 'integer'],
            'hints' => ['nullable', 'string'],
            'broken_code' => ['nullable', 'string'],
            'target_html' => ['nullable', 'string'],
        ];
    }
}
