<div>
    <div class="space-y-8">
        <!-- Promo Upload Section -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <h3 class="text-base font-medium leading-6 text-gray-900">Upload Promo Baru</h3>
            </div>
            <div class="p-4">
                <form wire:submit.prevent="uploadPromo" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div>
                                <div class="space-y-2">
                                    <label for="title" class="inline-flex text-sm font-medium text-gray-700">Judul Promo</label>
                                    <input 
                                        type="text" 
                                        id="title" 
                                        wire:model="title" 
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                    />
                                    @error('title') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            
                            <div>
                                <div class="space-y-2">
                                    <label for="description" class="inline-flex text-sm font-medium text-gray-700">Deskripsi</label>
                                    <textarea 
                                        id="description" 
                                        wire:model="description" 
                                        rows="4"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                    ></textarea>
                                    @error('description') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            
                            <div>
                                <div class="space-y-2">
                                    <label for="image" class="inline-flex text-sm font-medium text-gray-700">Gambar Promo</label>
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
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="space-y-2">
                                        <label for="original_price" class="inline-flex text-sm font-medium text-gray-700">Harga Asli</label>
                                        <input 
                                            type="number" 
                                            id="original_price" 
                                            wire:model="original_price" 
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                        />
                                        @error('original_price') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="space-y-2">
                                        <label for="promo_price" class="inline-flex text-sm font-medium text-gray-700">Harga Promo</label>
                                        <input 
                                            type="number" 
                                            id="promo_price" 
                                            wire:model="promo_price" 
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                        />
                                        @error('promo_price') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div>
                                <div class="space-y-2">
                                    <label for="discount_percentage" class="inline-flex text-sm font-medium text-gray-700">Persentase Diskon</label>
                                    <input 
                                        type="number" 
                                        id="discount_percentage" 
                                        wire:model="discount_percentage" 
                                        min="0"
                                        max="100"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                    />
                                    @error('discount_percentage') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="space-y-2">
                                        <label for="start_date" class="inline-flex text-sm font-medium text-gray-700">Tanggal Mulai</label>
                                        <input 
                                            type="date" 
                                            id="start_date" 
                                            wire:model="start_date" 
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                        />
                                        @error('start_date') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="space-y-2">
                                        <label for="end_date" class="inline-flex text-sm font-medium text-gray-700">Tanggal Berakhir</label>
                                        <input 
                                            type="date" 
                                            id="end_date" 
                                            wire:model="end_date" 
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                        />
                                        @error('end_date') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="space-y-2">
                                    <label for="promo_code" class="inline-flex text-sm font-medium text-gray-700">Kode Promo</label>
                                    <input 
                                        type="text" 
                                        id="promo_code" 
                                        wire:model="promo_code" 
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                    />
                                    @error('promo_code') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            
                            <div>
                                <div class="space-y-2">
                                    <label for="remaining_slots" class="inline-flex text-sm font-medium text-gray-700">Slot Tersedia</label>
                                    <input 
                                        type="number" 
                                        id="remaining_slots" 
                                        wire:model="remaining_slots" 
                                        min="0"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                    />
                                    <p class="text-sm text-gray-500">Kosongkan jika tidak ada batasan slot</p>
                                    @error('remaining_slots') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        wire:model="is_featured" 
                                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" 
                                    />
                                    <span class="ml-2 text-sm font-medium text-gray-700">Tampilkan di Halaman Utama</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        wire:model="is_active" 
                                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" 
                                    />
                                    <span class="ml-2 text-sm font-medium text-gray-700">Aktif</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <button 
                            type="submit" 
                            wire:loading.attr="disabled" 
                            wire:target="uploadPromo"
                            class="inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-primary-600 bg-primary-600 hover:bg-primary-500 hover:border-primary-500 focus:ring-offset-primary-700"
                        >
                            <span wire:loading.remove wire:target="uploadPromo">Simpan Promo</span>
                            <span wire:loading wire:target="uploadPromo">
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
                    <p class="text-sm mt-2">Promo telah disimpan.</p>
                    
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
    </div>
</div>
