<?php

namespace App\View\Components;

use App\Support\IconMap;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Icon extends Component
{
    /**
     * @var list<int>
     */
    public const SIZES = [12, 14, 16, 18, 20];

    public int $size;

    public function __construct(
        public string $name,
        int $size = 16,
        public ?string $label = null,
        public float $stroke = 2,
    ) {
        $this->size = in_array($size, self::SIZES, true) ? $size : 16;
    }

    public function render(): View|Closure|string
    {
        return view('components.icon', [
            'body' => IconMap::body($this->name),
        ]);
    }
}
