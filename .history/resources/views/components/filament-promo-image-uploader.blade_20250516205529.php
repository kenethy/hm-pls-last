@props([
    'statePath',
    'imagePath' => null,
])

<div class="filament-promo-image-uploader">
    <livewire:promo.promo-image-uploader :statePath="$statePath" :imagePath="$imagePath" />
    
    <!-- Include the promo image uploader helper script -->
    <script src="{{ asset('js/promo-image-uploader.js') }}"></script>
</div>
