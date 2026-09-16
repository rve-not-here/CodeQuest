<?php

namespace App\Http\Requests;

use App\Services\AnnouncementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Announcement creation payload (US-807, §30.0–§33.0). Only title, message,
 * and audience are accepted; status is always server-set to 'draft' on
 * creation (a crafted payload can never skip straight to published/archived),
 * and audience is validated against the exact AnnouncementService::AUDIENCES
 * set — the reach of an announcement is server-determined, never free-form.
 */
class AdminAnnouncementStoreRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string'],
            'audience' => ['required', Rule::in(AnnouncementService::AUDIENCES)],
        ];
    }
}
