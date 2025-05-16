<div class="w-full">
    <div class="space-y-4">
        @if($imagePath && $imageUrl)
            <div class="relative">
                <img src="{{ $imageUrl }}" alt="Promo Image Preview" class="max-w-full h-auto max-h-64 rounded-lg border border-gray-200" />
                <button 
                    type="button" 
                    wire:click="removeImage" 
                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    title="Remove Image"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
        
        <div class="space-y-2">
            <label for="promo-image-{{ rand() }}" class="inline-flex text-sm font-medium text-gray-700">
                {{ $imagePath ? 'Change Image' : 'Upload Image' }}
            </label>
            <input 
                type="file" 
                id="promo-image-{{ rand() }}" 
                wire:model="image" 
                accept="image/*" 
                class="block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-md file:border-0
                    file:text-sm file:font-semibold
                    file:bg-primary-50 file:text-primary-700
                    hover:file:bg-primary-100"
            />
            <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, GIF. Max size: 5MB</p>
            @error('image') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        
        @if($isUploading)
            <div class="mt-2">
                <div class="flex items-center justify-center">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="upload-progress bg-primary-600 h-2.5 rounded-full text-xs text-center text-white" style="width: 0%">0%</div>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Hidden input to store the image path for Filament -->
        @if($statePath)
            <input type="hidden" id="{{ $statePath }}" name="{{ $statePath }}" value="{{ $imagePath }}" />
        @endif
    </div>
</div>
