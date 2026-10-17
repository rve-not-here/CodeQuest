<?php

namespace App\Support;

use App\Models\Mission;
use Illuminate\Contracts\Routing\UrlRoutable;
use LogicException;

/**
 * Explicit whitelist of Mission fields a student-facing view may receive.
 *
 * Passing the Eloquent model would carry solution_code, hints, and
 * validate_rule into the view layer even when Blade does not print them.
 * This view-model exposes only the fields the student templates render, and
 * implements UrlRoutable so their route('mission.show', $mission) calls keep
 * working unchanged. Display-only: it is never route-bound.
 */
final readonly class MissionView implements UrlRoutable
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public ?string $broken_code,
        public ?string $target_html,
        public ?string $difficulty,
        public int $points,
        public int $order_num,
    ) {}

    public static function from(Mission $mission): self
    {
        return new self(
            id: (int) $mission->id,
            title: (string) $mission->title,
            description: $mission->description,
            broken_code: $mission->broken_code,
            target_html: $mission->target_html,
            difficulty: $mission->difficulty,
            points: (int) $mission->points,
            order_num: (int) $mission->order_num,
        );
    }

    public function getRouteKey(): mixed
    {
        return $this->id;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function resolveRouteBinding(mixed $value, mixed $field = null): never
    {
        throw new LogicException('MissionView is a display-only view-model and is never route-bound.');
    }

    public function resolveChildRouteBinding(mixed $childType, mixed $value, mixed $field = null): never
    {
        throw new LogicException('MissionView is a display-only view-model and is never route-bound.');
    }
}
