<?php

namespace App\Http\Requests;

use App\Services\ClassroomService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validated query parameters for the admin classroom directory (Classroom /
 * Enrollment Authorization). Search and the status filter reach the data layer
 * only in their validated form; nothing is concatenated into SQL.
 */
class AdminClassroomsIndexRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(ClassroomService::STATUSES)],
        ];
    }
}
