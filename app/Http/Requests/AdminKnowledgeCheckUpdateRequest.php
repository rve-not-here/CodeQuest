<?php

namespace App\Http\Requests;

use App\Services\AdminKnowledgeCheckService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Knowledge Check metadata payload (P1-1). Four editable fields: title,
 * instructions, the required gate, and lifecycle status. Question/option
 * content stays on the curriculum release path and can never be written
 * through this request.
 */
class AdminKnowledgeCheckUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * HTML checkboxes submit "1"/"0" (and the hidden fallback submits "0"),
     * so coerce the flag to a real boolean before validation. The guarded
     * service requires a bool and would refuse a string.
     */
    protected function prepareForValidation(): void
    {
        $required = $this->input('is_required');

        if (is_string($required)) {
            $this->merge(['is_required' => in_array($required, ['1', 'on', 'true'], true)]);
        } elseif (is_int($required)) {
            $this->merge(['is_required' => $required === 1]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:128'],
            'instructions' => ['nullable', 'string'],
            'is_required' => ['required', 'boolean'],
            'status' => ['required', Rule::in(AdminKnowledgeCheckService::STATUSES)],
        ];
    }
}
