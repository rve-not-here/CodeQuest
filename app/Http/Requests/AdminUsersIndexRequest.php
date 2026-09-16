<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validated query parameters for the admin user directory (US-703, §9.0/§10.0).
 * Search and the role filter are server-side and reach the data layer only in
 * their validated form — nothing here is concatenated into SQL, and the client
 * is never handed the whole table to filter in JS.
 */
class AdminUsersIndexRequest extends FormRequest
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
            'role' => ['nullable', Rule::in(['student', 'teacher', 'admin', 'operator'])],
        ];
    }
}
