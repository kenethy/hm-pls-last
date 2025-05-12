<div>
    <div class="space-y-8">
        <!-- Single Image Upload Section -->
        <x-filament::section>
            <x-slot name="heading">Upload Gambar Tunggal</x-slot>
            
            <form wire:submit="uploadImage" class="space-y-4">
                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input.label for="image">Pilih Gambar</x-filament::input.label>
                        <input 
                            type="file" 
                            id="image" 
                            wire:model="image" 
                            accept="image/*" 
                            class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-primary-50 file:text-primary-700
                                hover:file:bg-primary-100"
                        />
                        <p class="mt-1 text-sm text-gray-500">Format: JPG, PNG, GIF. Ukuran maksimum: 5MB</p>
                        @error('image') <p class="mt-1 text-sm text-danger-500">{{ $message }}</p> @enderror
                    </x-filament::input.wrapper>
                </div>
                
                <div>
                    <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="uploadImage">
                        <span wire:loading.remove wire:target="uploadImage">Upload Gambar</span>
                        <span wire:loading wire:target="uploadImage">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Uploading...
                        </span>
                    </x-filament::button>
                </div>
            </form>
            
            @if($uploadSuccessful && $uploadedImagePath && !$isUploading)
                <div class="mt-4 p-4 bg-green-50 text-green-700 rounded-lg">
                    <p>Upload berhasil!</p>
                    <p class="font-medium">Path gambar: <code class="bg-gray-100 px-2 py-1 rounded">{{ $uploadedImagePath }}</code></p>
                    <p class="text-sm mt-2">Salin path di atas ke field Gambar pada form.</p>
                    
                    @if($uploadedImageUrl)
                        <div class="mt-3">
                            <p class="font-medium">Preview:</p>
                            <img src="{{ $uploadedImageUrl }}" alt="Preview" class="mt-2 max-w-full h-auto max-h-48 rounded-lg border border-gray-200" />
                        </div>
                    @endif
                </div>
            @endif
        </x-filament::section>

        <!-- Multiple Images Upload Section -->
        <x-filament::section>
            <x-slot name="heading">Upload Multiple Gambar Galeri</x-slot>
            
            <form wire:submit="uploadMultipleImages" class="space-y-4">
                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input.label for="images">Pilih Beberapa Gambar</x-filament::input.label>
                        <input 
                            type="file" 
                            id="images" 
                            wire:model="images" 
                            accept="image/*" 
                            multiple 
                            class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-primary-50 file:text-primary-700
                                hover:file:bg-primary-100"
                        />
                        <p class="mt-1 text-sm text-gray-500">Format: JPG, PNG, GIF. Ukuran maksimum: 5MB per file</p>
                        @error('images') <p class="mt-1 text-sm text-danger-500">{{ $message }}</p> @enderror
                        @error('images.*') <p class="mt-1 text-sm text-danger-500">{{ $message }}</p> @enderror
                    </x-filament::input.wrapper>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-filament::input.wrapper>
                            <x-filament::input.label for="category_id">Kategori</x-filament::input.label>
                            <select 
                                id="category_id" 
                                wire:model="category_id" 
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                            >
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="mt-1 text-sm text-danger-500">{{ $message }}</p> @enderror
                        </x-filament::input.wrapper>
                    </div>
                    
                    <div>
                        <x-filament::input.wrapper>
                            <x-filament::input.label for="title_prefix">Awalan Judul</x-filament::input.label>
                            <x-filament::input.text id="title_prefix" wire:model="title_prefix" placeholder="Contoh: Kegiatan Workshop" />
                            <p class="mt-1 text-sm text-gray-500">Akan ditambahkan nomor urut di belakangnya</p>
                            @error('title_prefix') <p class="mt-1 text-sm text-danger-500">{{ $message }}</p> @enderror
                        </x-filament::input.wrapper>
                    </div>
                </div>
                
                <div>
                    <label class="flex items-center">
                        <x-filament::input.checkbox wire:model="is_featured" />
                        <span class="ml-2 text-sm font-medium text-gray-700">Tampilkan di Halaman Utama</span>
                    </label>
                </div>
                
                <div>
                    <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="uploadMultipleImages">
                        <span wire:loading.remove wire:target="uploadMultipleImages">Upload Gambar</span>
                        <span wire:loading wire:target="uploadMultipleImages">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Uploading...
                        </span>
                    </x-filament::button>
                </div>
                
                @if($isUploading && $uploadProgress > 0)
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-primary-600 h-2.5 rounded-full" style="width: {{ $uploadProgress }}%"></div>
                    </div>
                    <p class="text-sm text-gray-500">Upload progress: {{ round($uploadProgress) }}%</p>
                @endif
            </form>
            
            @if($uploadSuccessful && count($uploadedImagePaths) > 0 && !$isUploading)
                <div class="mt-4 p-4 bg-green-50 text-green-700 rounded-lg">
                    <p>Upload multiple berhasil!</p>
                    <div class="mt-2">
                        <p class="font-medium">Path gambar:</p>
                        <ul class="list-disc list-inside mt-1 space-y-1">
                            @foreach($uploadedImagePaths as $index => $path)
                                <li>
                                    <code class="bg-gray-100 px-2 py-1 rounded">{{ $path }}</code>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    
                    @if(count($uploadedImageUrls) > 0)
                        <div class="mt-3">
                            <p class="font-medium">Preview:</p>
                            <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-3">
                                @foreach($uploadedImageUrls as $url)
                                    <img src="{{ $url }}" alt="Preview" class="max-w-full h-auto max-h-32 rounded-lg border border-gray-200" />
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </x-filament::section>
    </div>
</div>
