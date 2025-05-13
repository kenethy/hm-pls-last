<div>
    <div class="space-y-8">
        <!-- Single Image Upload Section -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <h3 class="text-base font-medium leading-6 text-gray-900">Upload Gambar Tunggal</h3>
            </div>
            <div class="p-4">
                <form wire:submit.prevent="uploadImage" class="space-y-4">
                    <div>
                        <div class="space-y-2">
                            <label for="image" class="inline-flex text-sm font-medium text-gray-700">Pilih Gambar</label>
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
                            @error('image') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    
                    <div>
                        <div class="space-y-2">
                            <label for="category_id" class="inline-flex text-sm font-medium text-gray-700">Kategori</label>
                            <select 
                                id="category_id" 
                                wire:model="category_id"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                            >
                                <option value="">-- Pilih Kategori --</option>
                                @if(isset($categories) && count($categories) > 0)
                                    @foreach($categories as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                @else
                                    <option value="" disabled>Tidak ada kategori tersedia</option>
                                @endif
                            </select>
                            @error('category_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    
                    <div>
                        <div class="space-y-2">
                            <label for="title_prefix" class="inline-flex text-sm font-medium text-gray-700">Awalan Judul</label>
                            <input 
                                type="text" 
                                id="title_prefix" 
                                wire:model="title_prefix" 
                                placeholder="Contoh: Kegiatan Workshop"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                            />
                            <p class="text-sm text-gray-500">Akan ditambahkan nomor urut di belakangnya</p>
                            @error('title_prefix') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                wire:model="is_featured" 
                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" 
                            />
                            <span class="ml-2 text-sm font-medium text-gray-700">Tampilkan di Halaman Utama</span>
                        </label>
                    </div>
                    
                    <div>
                        <button 
                            type="submit" 
                            wire:loading.attr="disabled" 
                            wire:target="uploadImage"
                            class="inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-primary-600 bg-primary-600 hover:bg-primary-500 hover:border-primary-500 focus:ring-offset-primary-700"
                        >
                            <span wire:loading.remove wire:target="uploadImage">Upload Gambar</span>
                            <span wire:loading wire:target="uploadImage">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Uploading...
                            </span>
                        </button>
                    </div>
                </form>
                
                @if($uploadSuccessful && $uploadedImagePath && !$isUploading)
                <div class="mt-4 p-4 bg-green-50 text-green-700 rounded-lg">
                    <p>Upload berhasil!</p>
                    <p class="font-medium">Path gambar: <code class="bg-gray-100 px-2 py-1 rounded">{{ $uploadedImagePath }}</code></p>
                    <p class="text-sm mt-2">Gambar telah disimpan ke galeri.</p>
                    
                    @if($uploadedImageUrl)
                    <div class="mt-3">
                        <p class="font-medium">Preview:</p>
                        <img src="{{ $uploadedImageUrl }}" alt="Preview" class="mt-2 max-w-full h-auto max-h-48 rounded-lg border border-gray-200" />
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <!-- Multiple Images Upload Section -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <h3 class="text-base font-medium leading-6 text-gray-900">Upload Multiple Gambar Galeri</h3>
            </div>
            <div class="p-4">
                <form wire:submit.prevent="uploadMultipleImages" class="space-y-4">
                    <div>
                        <div class="space-y-2">
                            <label for="images" class="inline-flex text-sm font-medium text-gray-700">Pilih Beberapa Gambar</label>
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
                            @error('images') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                            @error('images.*') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    
                    <div>
                        <div class="space-y-2">
                            <label for="category_id" class="inline-flex text-sm font-medium text-gray-700">Kategori</label>
                            <select 
                                id="category_id" 
                                wire:model="category_id"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                            >
                                <option value="">-- Pilih Kategori --</option>
                                @if(isset($categories) && count($categories) > 0)
                                    @foreach($categories as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                @else
                                    <option value="" disabled>Tidak ada kategori tersedia</option>
                                @endif
                            </select>
                            @error('category_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    
                    <div>
                        <div class="space-y-2">
                            <label for="title_prefix" class="inline-flex text-sm font-medium text-gray-700">Awalan Judul</label>
                            <input 
                                type="text" 
                                id="title_prefix" 
                                wire:model="title_prefix" 
                                placeholder="Contoh: Kegiatan Workshop"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                            />
                            <p class="text-sm text-gray-500">Akan ditambahkan nomor urut di belakangnya</p>
                            @error('title_prefix') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                wire:model="is_featured" 
                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" 
                            />
                            <span class="ml-2 text-sm font-medium text-gray-700">Tampilkan di Halaman Utama</span>
                        </label>
                    </div>
                    
                    <div>
                        <button 
                            type="submit" 
                            wire:loading.attr="disabled" 
                            wire:target="uploadMultipleImages"
                            class="inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-primary-600 bg-primary-600 hover:bg-primary-500 hover:border-primary-500 focus:ring-offset-primary-700"
                        >
                            <span wire:loading.remove wire:target="uploadMultipleImages">Upload Gambar</span>
                            <span wire:loading wire:target="uploadMultipleImages">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Uploading...
                            </span>
                        </button>
                    </div>
                </form>
                
                <div wire:loading wire:target="images" class="mt-4">
                    <div class="flex items-center justify-center">
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="upload-progress bg-blue-600 h-2.5 rounded-full text-xs text-center text-white" style="width: 0%">0%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
