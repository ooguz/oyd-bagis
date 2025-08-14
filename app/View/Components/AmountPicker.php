<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AmountPicker extends Component
{
    public function __construct(public array $preset = [])
    {
        $this->preset = $this->preset ?: array_map(
            fn ($m) => (int) $m,
            config('payments.preset_amounts_major', [100, 250, 500, 1000])
        );
    }

    public function render(): View|Closure|string
    {
        return view('components.amount-picker');
    }
}



