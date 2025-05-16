@props([
'statePath',
'imagePath' => null,
])

@php
// Convert statePath to string if it's a Closure
$statePathValue = $statePath;
if ($statePathValue instanceof Closure) {
$statePathValue = $statePathValue();
}
@endphp

<div class="filament-promo-image-uploader">
    <livewire:promo.promo-image-uploader :statePath="$statePath" :imagePath="$imagePath" />

    <!-- Include the promo image uploader helper script -->
    <script src="{{ asset('js/promo-image-uploader.js') }}"></script>
</div>