<?php

namespace App\Http\Requests;

use App\Services\ClassroomService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Classroom update payload (Classroom / Enrollment Authorization). Updates the
 * base fields only — assignments stay separate membership operations. Status
 * is an authorization visibility boundary: deactivating a classroom removes
 * teacher visibility immediately without touching the pivot assignments or any
 * academic history.
 */
class AdminClassroomUpdateRequest extends FormRequest
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
