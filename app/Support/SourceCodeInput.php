<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class SourceCodeInput
{
    /**
     * @return array{code?: string|null}
     */
    public static function validate(Request $request, bool $required = true): array
    {
        $maximumBytes = max(1, (int) config('grader.max_source_bytes', 65536));

        try {
            return $request->validate([
                'code' => [
                    'bail', $required ? 'required' : 'nullable', 'string',
                    static function (string $attribute, mixed $value, Closure $fail) use ($maximumBytes): void {
                        if (is_string($value) && strlen($value) > $maximumBytes) {
                            $fail("Code must not exceed {$maximumBytes} bytes.");
                        }
                    },
                ],
            ]);
        } catch (ValidationException $exception) {
            $request->request->remove('code');
            $request->json()->remove('code');

            throw $exception;
        }
    }
}
