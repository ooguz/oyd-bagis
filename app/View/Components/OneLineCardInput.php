<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OneLineCardInput extends Component
{
    public function __construct(public ?string $checkoutFormContent = null)
    {
    }

    public function render(): View|Closure|string
    {
        return view('components.one-line-card-input');
    }
}



