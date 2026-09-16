<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validated query parameters for the student overview (US-602). Search and every
 * filter are server-side and reach the data layer only in their validated form —
 * nothing here is concatenated into SQL (§47.0/§48.0).
 */
class StudentOverviewRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:64'],
            'course' => ['nullable', 'integer', Rule::exists('the404_courses', 'id')],
            'status' => ['nullable', Rule::in(['in_progress', 'ready', 'completed'])],
        ];
    }
}
