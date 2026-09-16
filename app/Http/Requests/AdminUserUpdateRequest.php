<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\UserService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Account update payload (US-703, §12.0; role/status US-704, §13). Username
 * and name are always writable; password is optional (blank keeps the current
 * hash). Role and status are OPTIONAL too but server-determined: role changes
 * validate against the exact UserService::ROLES set (operator included on
 * existing accounts), status against active|inactive. The guarded service
 * write path enforces self-protection and the ≥2-active-admin floor on top of
 * this shape validation.
 */
class AdminUserUpdateRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->route('user');

        return [
            'username' => [
                'required',
                'string',
                'max:64',
                Rule::unique('the404_users', 'username')->ignore($user->id),
            ],
            'name' => ['required', 'string', 'max:128'],
            'password' => ['nullable', 'string', 'min:8', $this->rejectPreHashedPassword()],
            'role' => ['nullable', Rule::in(UserService::ROLES)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * Same pre-hashed-password guard as AdminUserStoreRequest.
     */
    private function rejectPreHashedPassword(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && preg_match('/^\$(2[aby]\d\$|argon2|\w+\$)/i', $value) === 1) {
                $fail('Passwords must be submitted as plaintext, never pre-hashed.');
            }
        };
    }
}
