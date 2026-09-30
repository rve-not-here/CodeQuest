<?php

namespace App\Http\Requests;

use App\Services\ClassroomAccessService;
use App\Services\TimelineService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validated query parameters for the Learning Activity feed (US-606). Every
 * filter is server-side and reaches the feed only in validated form. The date
 * window is anchored in prepareForValidation: a missing bound defaults to
 * today, and a missing lower bound falls back to a trailing
 * DEFAULT_WINDOW_DAYS span, so the unfiltered view is always bounded rather
 * than computed over the whole history. The span itself is capped at
 * MAX_WINDOW_DAYS in withValidator, so a client cannot widen the window into
 * an unbounded computation (§44/§45).
 */
class ActivityFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $to = $this->input('to') ?: now()->toDateString();
        $from = $this->input('from') ?: Carbon::parse($to)->subDays(TimelineService::DEFAULT_WINDOW_DAYS)->toDateString();

        $this->merge([
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Only run the span check when the base date rules passed, so a
            // malformed 'from'/'to' is reported once by the 'date' rule
            // instead of tripping a Carbon::parse here.
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $from = Carbon::parse($this->input('from'));
            $to = Carbon::parse($this->input('to'));

            if ($from->diffInDays($to) > TimelineService::MAX_WINDOW_DAYS) {
                $validator->errors()->add(
                    'from',
                    'The date range cannot exceed '.TimelineService::MAX_WINDOW_DAYS.' days.',
                );
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $access = app(ClassroomAccessService::class);
        $user = $this->user();
        $studentRule = $user !== null && $user->role === 'teacher'
            ? Rule::in($access->studentIdsFor($user)?->all() ?? [])
            : Rule::exists('the404_users', 'id');
        $courseRule = $user !== null && $user->role === 'teacher'
            ? Rule::in($access->courseIdsFor($user)?->all() ?? [])
            : Rule::exists('the404_courses', 'id');

        return [
            'student' => ['nullable', 'integer', $studentRule],
            'course' => ['nullable', 'integer', $courseRule],
            'type' => ['nullable', Rule::in(TimelineService::FILTERABLE_TYPES)],
            'from' => ['required', 'date', 'before_or_equal:to'],
            'to' => ['required', 'date'],
        ];
    }
}
