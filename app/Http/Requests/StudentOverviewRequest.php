<?php

namespace App\Http\Requests;

use App\Services\ClassroomAccessService;
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
        $user = $this->user();
        $courseRule = $user !== null && $user->role === 'teacher'
            ? Rule::in(app(ClassroomAccessService::class)->courseIdsFor($user)?->all() ?? [])
            : Rule::exists('the404_courses', 'id');

        return [
            'q' => ['nullable', 'string', 'max:64'],
            'course' => ['nullable', 'integer', $courseRule],
            'status' => ['nullable', Rule::in(['in_progress', 'ready', 'completed'])],
        ];
    }
}
