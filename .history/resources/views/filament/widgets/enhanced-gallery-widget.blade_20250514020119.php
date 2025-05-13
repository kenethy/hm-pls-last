<x-filament::section>
    <div class="space-y-6">
        @php
            $stats = $this->getGalleryStats();
        @endphp
        
        <!-- Gallery Stats -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Foto</p>
                        <p class="text-3xl font-semibold text-gray-900">{{ $stats['total'] }}</p>
                    </div>
                    <div class="rounded-full bg-primary-50 p-3 text-primary-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('filament.admin.resources.galleries.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                        Lihat semua foto
                    </a>
                </div>
            </div>
            
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Foto Unggulan</p>
                        <p class="text-3xl font-semibold text-gray-900">{{ $stats['featured'] }}</p>
                    </div>
                    <div class="rounded-full bg-amber-50 p-3 text-amber-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('filament.admin.resources.galleries.index') }}?tableFilters[is_featured][value]=true" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                        Lihat foto unggulan
                    </a>
                </div>
            </div>
            
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Kategori</p>
                        <p class="text-3xl font-semibold text-gray-900">{{ $stats['categories'] }}</p>
                    </div>
                    <div class="rounded-full bg-green-50 p-3 text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('filament.admin.resources.gallery-categories.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                        Kelola kategori
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Photos and Popular Categories -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Recent Photos -->
            <div class="col-span-2 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-medium text-gray-900">Foto Terbaru</h3>
                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    @forelse($stats['recent'] as $photo)
                        <div class="group relative overflow-hidden rounded-lg">
                            <img 
                                src="{{ \Illuminate\Support\Facades\Storage::url($photo->image_path) }}" 
                                alt="{{ $photo->title }}" 
                                class="h-24 w-full object-cover transition-all duration-300 group-hover:scale-110"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                            <div class="absolute bottom-0 left-0 right-0 p-2 text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                <p class="truncate text-xs font-medium">{{ $photo->title }}</p>
                            </div>
                            <a href="{{ route('filament.admin.resources.galleries.edit', $photo) }}" class="absolute inset-0" aria-label="Edit {{ $photo->title }}"></a>
                        </div>
                    @empty
                        <div class="col-span-full py-4 text-center text-gray-500">
                            Belum ada foto yang diupload.
                        </div>
                    @endforelse
                </div>
                <div class="mt-4 text-right">
                    <a href="{{ route('filament.admin.resources.galleries.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                        Lihat semua foto &rarr;
                    </a>
                </div>
            </div>
            
            <!-- Popular Categories -->
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-medium text-gray-900">Kategori Populer</h3>
                <div class="mt-4 space-y-4">
                    @forelse($stats['popular_categories'] as $category)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-50 text-primary-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $category->name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <span class="rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">
                                    {{ $category->galleries_count }} foto
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-4 text-center text-gray-500">
                            Belum ada kategori yang dibuat.
                        </div>
                    @endforelse
                </div>
                <div class="mt-4 text-right">
                    <a href="{{ route('filament.admin.resources.gallery-categories.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                        Kelola kategori &rarr;
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-medium text-gray-900">Aksi Cepat</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                <a href="{{ route('filament.admin.resources.galleries.create') }}" class="flex items-center rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all hover:bg-gray-50 hover:shadow">
                    <div class="mr-4 flex h-10 w-10 items-center justify-center rounded-full bg-primary-50 text-primary-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Tambah Foto Baru</p>
                        <p class="text-xs text-gray-500">Upload foto tunggal</p>
                    </div>
                </a>
                
                <a href="{{ route('admin.simple-gallery') }}" class="flex items-center rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all hover:bg-gray-50 hover:shadow">
                    <div class="mr-4 flex h-10 w-10 items-center justify-center rounded-full bg-amber-50 text-amber-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Gallery Manager</p>
                        <p class="text-xs text-gray-500">Upload multiple & kelola</p>
                    </div>
                </a>
                
                <a href="{{ route('filament.admin.resources.gallery-categories.index') }}" class="flex items-center rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all hover:bg-gray-50 hover:shadow">
                    <div class="mr-4 flex h-10 w-10 items-center justify-center rounded-full bg-green-50 text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Kelola Kategori</p>
                        <p class="text-xs text-gray-500">Tambah & edit kategori</p>
                    </div>
                </a>
                
                <a href="{{ route('gallery.index') }}" target="_blank" class="flex items-center rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all hover:bg-gray-50 hover:shadow">
                    <div class="mr-4 flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Lihat di Website</p>
                        <p class="text-xs text-gray-500">Buka halaman galeri</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-filament::section>
