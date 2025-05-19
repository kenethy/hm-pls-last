<x-filament-panels::page>
    <div class="mb-4">
        <h2 class="text-xl font-bold">{{ $record->title }}</h2>
        <p class="text-gray-500">{{ $record->customer_name }} - {{ $record->license_plate }} ({{ $record->car_model }})</p>
    </div>

    <div class="mb-6 p-4 bg-primary-50 rounded-lg border border-primary-200">
        <div class="flex items-center mb-2">
            <x-heroicon-o-information-circle class="w-5 h-5 text-primary-500 mr-2" />
            <h3 class="font-medium text-primary-700">Tips Pengisian Cepat</h3>
        </div>
        <ul class="list-disc list-inside text-sm text-primary-700 space-y-1">
            <li>Gunakan tombol <strong>Tab</strong> untuk berpindah antar kolom</li>
            <li>Gunakan tombol <strong>1</strong>, <strong>2</strong>, <strong>3</strong> untuk memilih status (OK, Waspada, Harus Diperbaiki)</li>
            <li>Gunakan tombol <strong>+</strong> untuk menambahkan titik pemeriksaan baru</li>
            <li>Gunakan <strong>drag and drop</strong> untuk mengubah urutan titik pemeriksaan</li>
        </ul>
    </div>

    <form wire:submit="save">
        {{ $this->form }}

        <div class="flex justify-between mt-6">
            <x-filament::button
                type="button"
                color="gray"
                tag="a"
                :href="$this->getResource()::getUrl('view', ['record' => $record])"
            >
                Kembali
            </x-filament::button>

            <div class="flex space-x-3">
                <x-filament::button
                    type="button"
                    color="success"
                    tag="a"
                    :href="$record->getUrl()"
                    target="_blank"
                >
                    <x-heroicon-m-eye class="w-5 h-5 mr-1" />
                    Pratinjau
                </x-filament::button>

                <x-filament::button type="submit">
                    <x-heroicon-m-check class="w-5 h-5 mr-1" />
                    Simpan Checklist
                </x-filament::button>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Keyboard shortcuts for status selection
                document.addEventListener('keydown', function(e) {
                    if (document.activeElement.name && document.activeElement.name.includes('status')) {
                        if (e.key === '1') {
                            document.activeElement.value = 'ok';
                            const event = new Event('change', { bubbles: true });
                            document.activeElement.dispatchEvent(event);
                            e.preventDefault();
                        } else if (e.key === '2') {
                            document.activeElement.value = 'warning';
                            const event = new Event('change', { bubbles: true });
                            document.activeElement.dispatchEvent(event);
                            e.preventDefault();
                        } else if (e.key === '3') {
                            document.activeElement.value = 'needs_repair';
                            const event = new Event('change', { bubbles: true });
                            document.activeElement.dispatchEvent(event);
                            e.preventDefault();
                        }
                    }
                });

                // Add new item with + key
                document.addEventListener('keydown', function(e) {
                    if (e.key === '+' && e.ctrlKey) {
                        const addButtons = document.querySelectorAll('button[wire\\:click*="repeaterAddItem"]');
                        if (addButtons.length > 0) {
                            addButtons[0].click();
                            e.preventDefault();
                        }
                    }
                });
            });
        </script>
    @endpush
</x-filament-panels::page>
