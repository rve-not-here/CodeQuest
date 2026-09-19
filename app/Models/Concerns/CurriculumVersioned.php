<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Contract for rows whose material edits advance a curriculum version
 * counter. The bumping itself lives in HasCurriculumVersion; this interface
 * is what lets the shared hook — which only sees a base Model — reach the
 * model's versioning configuration with a real type.
 *
 * @see HasCurriculumVersion
 */
interface CurriculumVersioned
{
    /**
     * The row whose version counter this model's material edits advance:
     * the model itself, except Knowledge Check questions and options, which
     * advance their parent check.
     */
    public function curriculumVersionTarget(): Model;

    /**
     * Non-content fields that never bump the version on their own
     * (status-style access gates, which seal use without changing content).
     *
     * @return array<int, string>
     */
    public function curriculumVersionExcludedFields(): array;

    /**
     * The only fields whose change bumps the version. Every versioned model
     * must declare this list explicitly — there is no silent treat-everything-
     * as-material fallback, so a model without a policy fails loudly instead
     * of versioning the wrong things.
     *
     * @return array<int, string>
     */
    public function curriculumVersionMaterialFields(): array;
}
