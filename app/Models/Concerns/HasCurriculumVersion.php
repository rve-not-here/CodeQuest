<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Bumps a curriculum version exactly once per material edit.
 *
 * A material edit is a change to a field listed in the model's
 * curriculumVersionMaterialFields(). Fields outside that list — identity
 * labels such as titles and names, and status-style access gates — never
 * bump the version no matter how often they are rewritten. A no-op save has
 * no qualifying dirty field and bumps nothing. A refused edit never reaches
 * save() and bumps nothing. A rolled-back transaction takes the bump with
 * it. Manually assigned versions are respected, never overridden.
 *
 * Most models version themselves. Knowledge Check questions and options
 * carry no version of their own: their material edits bump the parent
 * check's version instead, via curriculumVersionTarget() and an atomic
 * query increment (never a stale-model save).
 *
 * The material-field list has no default: each versioned model declares its
 * own via the CurriculumVersioned contract, so a model without an explicit
 * policy cannot boot.
 *
 * Version counters are evidence, not audit rows. Edits through the admin
 * services carry AdminAudit rows with actor and summary; seeder- or
 * console-authored content (including Knowledge Check definitions, which
 * have no admin write path) is governed by code review and the §29/Phase-12
 * release process instead — the hook never writes audit rows itself, so one
 * logical change can never fan out into duplicate rows.
 */
trait HasCurriculumVersion
{
    public static function bootHasCurriculumVersion(): void
    {
        static::updating(function (Model $model): void {
            if (! $model instanceof CurriculumVersioned) {
                return;
            }

            $target = $model->curriculumVersionTarget();
            $versionsSelf = $target->is($model);

            if ($versionsSelf && $model->isDirty('version')) {
                return;
            }

            $materialFields = $model->curriculumVersionMaterialFields();

            $excluded = array_merge(
                ['version', 'created_at', 'updated_at'],
                $model->curriculumVersionExcludedFields()
            );

            $material = [];

            foreach ($model->getDirty() as $key => $value) {
                if (in_array($key, $excluded, true)) {
                    continue;
                }

                if (! in_array($key, $materialFields, true)) {
                    continue;
                }

                if (! self::versionValueChanged($value, $model->getOriginal($key))) {
                    continue;
                }

                $material[] = $key;
            }

            if ($material === []) {
                return;
            }

            if ($versionsSelf) {
                $model->setAttribute('version', max(1, (int) $model->getAttribute('version')) + 1);
            } else {
                $target->newQuery()->whereKey($target->getKey())->increment('version');
            }
        });
    }

    /**
     * The row whose version counter this model's material edits advance.
     * Defaults to the model itself; Knowledge Check questions and options
     * return their parent check.
     */
    public function curriculumVersionTarget(): Model
    {
        return $this;
    }

    /**
     * Non-content fields that never bump the version on their own
     * (status-style access gates, which seal use without changing content).
     *
     * @return array<int, string>
     */
    public function curriculumVersionExcludedFields(): array
    {
        return [];
    }

    /**
     * Whether an assigned value really differs from the stored original.
     * Eloquent reports assigning null to a never-set attribute as dirty, and
     * database round-trips juggle int/string types, so equivalence is
     * semantic: null and empty string are interchangeable, and numeric
     * strings match their numbers. Anything else must be identical.
     */
    public static function versionValueChanged(mixed $value, mixed $original): bool
    {
        if ($value === $original) {
            return false;
        }

        if (($value === null || $value === '') && ($original === null || $original === '')) {
            return false;
        }

        if (is_numeric($value) && is_numeric($original) && (float) $value === (float) $original) {
            return false;
        }

        return true;
    }
}
