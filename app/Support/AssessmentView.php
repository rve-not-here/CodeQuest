<?php

namespace App\Support;

use App\Models\Assessment;
use Illuminate\Contracts\Routing\UrlRoutable;
use LogicException;

/**
 * Explicit whitelist of Assessment fields a student-facing view may receive.
 *
 * Passing the Eloquent model would carry grading_rule into the view layer
 * even when Blade does not print it. This view-model exposes only the fields
 * the student templates render, and implements UrlRoutable so their
 * route('assessment.show', $assessment) calls keep working unchanged.
 * Display-only: it is never route-bound.
 */
final readonly class AssessmentView implements UrlRoutable
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public ?string $instructions,
        public int $passing_score,
    ) {}

    public static function from(Assessment $assessment): self
    {
        return new self(
            id: (int) $assessment->id,
            title: (string) $assessment->title,
            description: $assessment->description,
            instructions: $assessment->instructions,
            passing_score: (int) $assessment->passing_score,
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
        throw new LogicException('AssessmentView is a display-only view-model and is never route-bound.');
    }

    public function resolveChildRouteBinding(mixed $childType, mixed $value, mixed $field = null): never
    {
        throw new LogicException('AssessmentView is a display-only view-model and is never route-bound.');
    }
}
