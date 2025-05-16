@php
// Get the state path from the component
$statePath = $getStatePath();

// Get the current state (image path)
$state = $getState();

// Generate a unique key for the component
$componentKey = 'promo-image-' . md5($statePath);
@endphp

<div class="filament-forms-promo-image-upload-component">
    <livewire:promo.promo-image-uploader :statePath="$statePath" :imagePath="$state" wire:key="{{ $componentKey }}" />
</div>