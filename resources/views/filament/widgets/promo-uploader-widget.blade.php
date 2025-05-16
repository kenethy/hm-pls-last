<x-filament::section>
    <x-slot name="heading">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">Upload Promo Baru</h2>
            <div>
                <a href="{{ route('filament.admin.resources.promos.index') }}" class="text-primary-600 hover:text-primary-900">
                    Kembali ke Daftar Promo
                </a>
            </div>
        </div>
    </x-slot>
    
    <livewire:promo.promo-uploader />
</x-filament::section>

<!-- Include the promo upload helper script -->
<script src="{{ asset('js/promo-upload-helper.js') }}"></script>
