<?php

namespace App\Http\Requests;

use App\Services\ClassroomService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Classroom creation payload (Classroom / Enrollment Authorization). Only the
 * base fields are created here — teacher/student/course assignments are
 * separate, explicit membership operations, never part of this payload.
 */
class AdminClassroomStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:128'],
            'code' => ['nullable', 'string', 'max:32'],
            'status' => ['required', Rule::in(ClassroomService::STATUSES)],
        ];
    }
}
