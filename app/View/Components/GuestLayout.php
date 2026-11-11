<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * @param  string|null  $title  judul di atas form (juga dipakai di tab browser)
     * @param  string|null  $subtitle  keterangan singkat di bawah judul
     */
    public function __construct(
        public ?string $title = null,
        public ?string $subtitle = null,
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
