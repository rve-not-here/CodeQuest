<?php

namespace App\Http\Requests;

use App\Services\AnnouncementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Announcement update payload (US-807, §30.0–§33.0). Same whitelist as
 * creation — title, message, audience — with audience validated against the
 * exact AnnouncementService::AUDIENCES set. status is never accepted; the
 * lifecycle moves only through the publish/archive POST routes. Editing a
 * published announcement never re-notifies (the service writes only the
 * announcement row); an archived announcement is refused at the service.
 */
class AdminAnnouncementUpdateRequest extends FormRequest
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
