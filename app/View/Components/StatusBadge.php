<?php

namespace App\View\Components;

use App\Helpers\Helper;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    /**
     * Badge CSS classes
     */
    public string $badgeClasses;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $value
    ) {
        $this->badgeClasses = Helper::pillClasses($value);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.status-badge');
    }
}
