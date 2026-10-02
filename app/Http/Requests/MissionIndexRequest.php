<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MissionIndexRequest extends FormRequest
{
    protected $redirectRoute = 'missions';

    public function authorize(): bool
    {
        return $this->user()?->role === 'student'
            && ! $this->hasAny(['user_id', 'userId', 'user', 'student', 'owner']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string', Rule::in(['COMPLETED', 'IN PROGRESS', 'NOT STARTED'])],
            'course' => ['nullable', 'integer', Rule::exists('the404_courses', 'id')],
        ];
    }
}
