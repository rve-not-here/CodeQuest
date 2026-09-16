<?php

namespace App\Http\Requests;

use App\Services\AdminAuditService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validated query parameters for the Administrative Audit Trail
 * (US-709, §27.0). Every filter is server-side and reaches the feed only in
 * validated form, mirroring ActivityFeedRequest's discipline for the /activity
 * page. 'actor' is a legitimate validated filter here — the trail is
 * system-wide and admin-only, and the actor filter only narrows it — while the
 * untrusted user-scoping spellings (user_id/userId/user/owner) are rejected at
 * the controller as probes before any data-layer work (§42/§44).
 *
 * Unlike the activity timeline, the audit trail is read straight from the
 * append-only AdminAudit table, which is indexed on admin_user_id, action,
 * created_at, and (target_type, target_id). There is no computed over-history
 * span to bound, so from/to are optional and only narrow the query; when both
 * are present, to must not precede from (checked in withValidator so a missing
 * bound never trips the comparison).
 */
class AdminActivityFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Only compare when both bounds were supplied and well-formed.
            if (! $this->filled('from') || ! $this->filled('to')) {
                return;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (Carbon::parse($this->input('from'))->isAfter(Carbon::parse($this->input('to')))) {
                $validator->errors()->add('from', 'The from date must be on or before the to date.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'actor' => ['nullable', 'integer', Rule::exists('the404_users', 'id')],
            'action' => ['nullable', Rule::in(AdminAuditService::ACTIONS)],
            'result' => ['nullable', Rule::in(['success', 'failed'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }
}
