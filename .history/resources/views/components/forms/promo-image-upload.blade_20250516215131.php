@props([
'statePath',
'imagePath' => null,
])

<div class="filament-forms-promo-image-upload-component">
    <livewire:promo.promo-image-uploader :statePath="$statePath" :imagePath="$imagePath" wire:key="{{ $statePath }}" />
</div>