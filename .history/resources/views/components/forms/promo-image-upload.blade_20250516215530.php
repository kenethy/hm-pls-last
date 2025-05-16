@props([
'statePath',
'imagePath' => null,
])

@php
// Convert statePath to string if it's a Closure
$statePathString = $statePath;
if ($statePathString instanceof \Closure) {
$statePathString = $statePathString();
}

// Generate a unique key for the component
$componentKey = 'promo-image-' . md5($statePathString);
@endphp

<div class="filament-forms-promo-image-upload-component">
    <livewire:promo.promo-image-uploader :statePath="$statePath" :imagePath="$imagePath"
        wire:key="{{ $componentKey }}" />
</div>