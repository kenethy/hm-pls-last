<div>
    <div class="space-y-4">
        @if($imagePath && $imageUrl)
        <div class="relative">
            <img src="{{ $imageUrl }}" alt="Preview"
                class="max-w-full h-auto max-h-64 rounded-lg border border-gray-200" />
            <button type="button" wire:click="removeImage"
                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        @else
        <div class="space-y-2">
            <label for="image-{{ $this->getId() }}" class="inline-flex text-sm font-medium text-gray-700">Gambar
                Promo</label>
            <input type="file" id="image-{{ $this->getId() }}" wire:model="image" accept="image/*" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-primary-50 file:text-primary-700
                        hover:file:bg-primary-100" />
            <p class="mt-1 text-sm text-gray-500">Format: JPG, PNG, GIF. Ukuran maksimum: 5MB</p>
            @error('image') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror

            <div>
                <button type="button" wire:click="uploadImage" wire:loading.attr="disabled"
                    wire:target="uploadImage, image"
                    class="inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-primary-600 bg-primary-600 hover:bg-primary-500 hover:border-primary-500 focus:ring-offset-primary-700">
                    <span wire:loading.remove wire:target="uploadImage, image">Upload Gambar</span>
                    <span wire:loading wire:target="uploadImage, image">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Uploading...
                    </span>
                </button>
            </div>
        </div>
        @endif

        <div wire:loading wire:target="image" class="mt-2">
            <div class="flex items-center justify-center">
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="upload-progress bg-blue-600 h-2.5 rounded-full text-xs text-center text-white"
                        style="width: 0%">0%</div>
                </div>
            </div>
        </div>

        <!-- Hidden input to store the image path for Filament -->
        @if($statePathString)
        <input type="hidden" id="{{ $statePathString }}" name="{{ $statePathString }}" value="{{ $imagePath }}" />
        @endif
    </div>
</div>