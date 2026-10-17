<?php

namespace App\Support;

use App\Models\KnowledgeCheck;
use Illuminate\Contracts\Routing\UrlRoutable;
use LogicException;

/**
 * Explicit whitelist of KnowledgeCheck fields a student-facing view may
 * receive.
 *
 * Passing the Eloquent model (with its questions and options loaded) would
 * carry is_correct and explanation into the view layer even when Blade does
 * not print them. This view-model exposes only display metadata, and
 * implements UrlRoutable so route(...) calls using the check keep working
 * unchanged. Display-only: it is never route-bound.
 */
final readonly class KnowledgeCheckView implements UrlRoutable
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $instructions,
        public bool $is_required,
    ) {}

    public static function from(KnowledgeCheck $check): self
    {
        return new self(
            id: (int) $check->id,
            title: (string) $check->title,
            instructions: $check->instructions,
            is_required: (bool) $check->is_required,
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
        throw new LogicException('KnowledgeCheckView is a display-only view-model and is never route-bound.');
    }

    public function resolveChildRouteBinding(mixed $childType, mixed $value, mixed $field = null): never
    {
        throw new LogicException('KnowledgeCheckView is a display-only view-model and is never route-bound.');
    }
}
