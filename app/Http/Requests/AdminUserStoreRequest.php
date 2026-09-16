<?php

namespace App\Http\Requests;

use App\Services\UserService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Account creation payload (US-703, §11.0/§12.0). Every field is validated
 * server-side before it reaches the service. The password must be a NEW
 * plaintext value: a client never supplies a pre-hashed password, and the
 * model's 'hashed' cast derives the stored hash from whatever the server
 * accepts here.
 */
class AdminUserStoreRequest extends FormRequest
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
            'username' => ['required', 'string', 'max:64', Rule::unique('the404_users', 'username')],
            'name' => ['required', 'string', 'max:128'],
            'role' => ['required', Rule::in(UserService::CREATABLE_ROLES)],
            'password' => ['required', 'string', 'min:8', $this->rejectPreHashedPassword()],
        ];
    }

    /**
     * A bcrypt/argon2 hash prefix is a pre-hashed password, never a new
     * plaintext secret. Rejecting it here keeps the invariant "the server hashes
     * what the client submits" from silently double-hashing an already-hashed
     * value.
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
