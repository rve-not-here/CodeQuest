<?php

namespace App\Http\Requests;

use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\Mission;
use Illuminate\Foundation\Http\FormRequest;

class KnowledgeCheckSubmitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user?->role !== 'student') {
            return false;
        }

        $attempt = $this->route('attempt');
        // The lesson's own panel posts under a `check` parameter; the
        // standalone attempt screen posts under `knowledgeCheck`. One
        // request, one authorization contract, two hosts.
        $check = $this->route('knowledgeCheck') ?? $this->route('check');
        $mission = $this->route('mission');

        if (! $attempt instanceof KnowledgeCheckAttempt || $attempt->user_id !== $user->id) {
            abort(404);
        }

        if (! $check instanceof KnowledgeCheck || ! $mission instanceof Mission
            || $check->mission_id !== $mission->id
            || $attempt->knowledge_check_id !== $check->id
            || $check->status !== KnowledgeCheck::STATUS_PUBLISHED) {
            abort(404);
        }

        if ($mission->course === null || $mission->course->status !== 'active') {
            abort(403, 'This course is not currently active.');
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1', 'max:50'],
            'answers.*' => ['required', 'integer'],
            // The mission workspace hosts the check in its own tab; the
            // student returns there instead of the standalone attempt screen.
            'return_to' => ['nullable', 'in:mission'],
        ];
    }
}
