<div>
    <div class="space-y-4">
        <!-- Filters -->
        <div class="flex flex-col md:flex-row gap-4">
            <div class="w-full md:w-1/3">
                <x-filament::input.wrapper>
                    <x-filament::input.text 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Cari judul atau deskripsi..." 
                    />
                </x-filament::input.wrapper>
            </div>
            
            <div class="w-full md:w-1/4">
                <x-filament::input.wrapper>
                    <select 
                        wire:model.live="category" 
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    >
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>
            
            <div class="w-full md:w-1/4">
                <x-filament::input.wrapper>
                    <select 
                        wire:model.live="featured" 
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    >
                        <option value="">Semua Status</option>
                        <option value="yes">Unggulan</option>
                        <option value="no">Bukan Unggulan</option>
                    </select>
                </x-filament::input.wrapper>
            </div>
            
            <div class="w-full md:w-auto flex items-end">
                <x-filament::button color="secondary" wire:click="resetFilters">
                    Reset Filter
                </x-filament::button>
            </div>
        </div>
        
        <!-- Batch Actions -->
        @if(count($selected) > 0)
            <div class="bg-primary-50 p-4 rounded-lg flex flex-wrap items-center gap-4">
                <span class="text-primary-700 font-medium">{{ count($selected) }} item dipilih</span>
                
                <x-filament::button color="danger" size="sm" wire:click="deleteSelected" wire:confirm="Yakin ingin menghapus {{ count($selected) }} foto?">
                    <x-heroicon-m-trash class="w-4 h-4 mr-1" />
                    Hapus Terpilih
                </x-filament::button>
                
                <x-filament::button color="success" size="sm" wire:click="setFeaturedForSelected(true)">
                    <x-heroicon-m-star class="w-4 h-4 mr-1" />
                    Jadikan Unggulan
                </x-filament::button>
                
                <x-filament::button color="warning" size="sm" wire:click="setFeaturedForSelected(false)">
                    <x-heroicon-m-x-mark class="w-4 h-4 mr-1" />
                    Hapus Status Unggulan
                </x-filament::button>
            </div>
        @endif
        
        <!-- Gallery Grid -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left rtl:text-right divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 w-10">
                                <x-filament::input.checkbox wire:model.live="selectAll" wire:click="toggleSelectAll" />
                            </th>
                            <th class="px-4 py-3 w-24">Gambar</th>
                            <th class="px-4 py-3">
                                <button wire:click="sortBy('title')" class="flex items-center gap-1">
                                    Judul
                                    @if($sortField === 'title')
                                        @if($sortDirection === 'asc')
                                            <x-heroicon-m-chevron-up class="w-4 h-4" />
                                        @else
                                            <x-heroicon-m-chevron-down class="w-4 h-4" />
                                        @endif
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3">
                                <button wire:click="sortBy('category_id')" class="flex items-center gap-1">
                                    Kategori
                                    @if($sortField === 'category_id')
                                        @if($sortDirection === 'asc')
                                            <x-heroicon-m-chevron-up class="w-4 h-4" />
                                        @else
                                            <x-heroicon-m-chevron-down class="w-4 h-4" />
                                        @endif
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 w-24">
                                <button wire:click="sortBy('is_featured')" class="flex items-center gap-1">
                                    Unggulan
                                    @if($sortField === 'is_featured')
                                        @if($sortDirection === 'asc')
                                            <x-heroicon-m-chevron-up class="w-4 h-4" />
                                        @else
                                            <x-heroicon-m-chevron-down class="w-4 h-4" />
                                        @endif
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 w-24">
                                <button wire:click="sortBy('order')" class="flex items-center gap-1">
                                    Urutan
                                    @if($sortField === 'order')
                                        @if($sortDirection === 'asc')
                                            <x-heroicon-m-chevron-up class="w-4 h-4" />
                                        @else
                                            <x-heroicon-m-chevron-down class="w-4 h-4" />
                                        @endif
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($galleries as $gallery)
                            <tr wire:key="gallery-{{ $gallery->id }}">
                                <td class="px-4 py-3">
                                    <x-filament::input.checkbox wire:model.live="selected" value="{{ $gallery->id }}" />
                                </td>
                                <td class="px-4 py-3">
                                    <img 
                                        src="{{ Storage::url($gallery->image_path) }}" 
                                        alt="{{ $gallery->title }}" 
                                        class="w-16 h-16 object-cover rounded"
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $gallery->title }}</div>
                                    <div class="text-sm text-gray-500 truncate max-w-xs">{{ $gallery->description }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    {{ $gallery->category->name ?? 'Tidak ada kategori' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button wire:click="toggleFeatured({{ $gallery->id }})" class="focus:outline-none">
                                        @if($gallery->is_featured)
                                            <x-heroicon-s-star class="w-5 h-5 text-amber-500" />
                                        @else
                                            <x-heroicon-o-star class="w-5 h-5 text-gray-400" />
                                        @endif
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ $gallery->order }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('filament.admin.resources.galleries.edit', $gallery) }}" class="text-primary-600 hover:text-primary-900">
                                            <x-heroicon-m-pencil-square class="w-5 h-5" />
                                        </a>
                                        <button wire:click="delete({{ $gallery->id }})" wire:confirm="Yakin ingin menghapus foto ini?" class="text-danger-600 hover:text-danger-900">
                                            <x-heroicon-m-trash class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    Tidak ada foto yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-4 py-3 bg-gray-50">
                {{ $galleries->links() }}
            </div>
        </div>
    </div>
    
    <!-- Notification -->
    <div
        x-data="{ 
            show: false,
            message: '',
            type: 'success'
        }"
        @notify.window="
            show = true;
            message = $event.detail.message;
            type = $event.detail.type;
            setTimeout(() => { show = false }, 3000);
        "
        x-show="show"
        x-transition
        class="fixed bottom-4 right-4 z-50"
    >
        <div 
            :class="{
                'bg-green-50 text-green-800 border-green-200': type === 'success',
                'bg-red-50 text-red-800 border-red-200': type === 'error'
            }"
            class="px-4 py-3 rounded-lg shadow-lg border"
        >
            <div class="flex items-center gap-2">
                <template x-if="type === 'success'">
                    <x-heroicon-m-check-circle class="w-5 h-5 text-green-500" />
                </template>
                <template x-if="type === 'error'">
                    <x-heroicon-m-x-circle class="w-5 h-5 text-red-500" />
                </template>
                <span x-text="message"></span>
            </div>
        </div>
    </div>
</div>
