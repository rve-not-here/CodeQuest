<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Section update payload (US-706, §18.0). Sections have no status field
 * (the access gate for everything beneath them lives on course.status,
 * US-705), so the only editable fields are title, description, and order_num
 * (non-negative integer, the same single ordering key the learning path
 * uses). The guarded SectionService write path re-checks the allow list and
 * the course-membership guard on top of this shape validation.
 */
class AdminSectionUpdateRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string'],
            'order_num' => ['required', 'integer', 'min:0'],
        ];
    }
}
