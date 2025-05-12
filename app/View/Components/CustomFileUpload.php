<?php

namespace App\View\Components;

use Illuminate\View\Component;

class CustomFileUpload extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $name,
        public ?string $label = null,
        public bool $multiple = false,
        public ?string $accept = null,
        public string $directory = 'uploads',
        public ?string $helperText = null
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.custom-file-upload');
    }
}
