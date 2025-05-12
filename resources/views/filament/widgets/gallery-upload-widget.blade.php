<x-filament::section>
    <h2 class="text-xl font-bold mb-4">Upload Gambar Galeri</h2>
    
    <form action="{{ route('admin.gallery.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <x-filament::input.wrapper>
                <x-filament::input.label for="gallery_image">Pilih Gambar</x-filament::input.label>
                <input type="file" id="gallery_image" name="image" accept="image/*" class="block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-md file:border-0
                    file:text-sm file:font-semibold
                    file:bg-primary-50 file:text-primary-700
                    hover:file:bg-primary-100" />
                <p class="mt-1 text-sm text-gray-500">Format: JPG, PNG, GIF. Ukuran maksimum: 5MB</p>
            </x-filament::input.wrapper>
        </div>
        
        <div>
            <x-filament::button type="submit">
                Upload Gambar
            </x-filament::button>
        </div>
    </form>
    
    @if(session('upload_success'))
        <div class="mt-4 p-4 bg-green-50 text-green-700 rounded-lg">
            <p>Upload berhasil!</p>
            <p class="font-medium">Path gambar: <code class="bg-gray-100 px-2 py-1 rounded">{{ session('image_path') }}</code></p>
            <p class="text-sm mt-2">Salin path di atas ke field Gambar pada form.</p>
            
            @if(session('image_url'))
                <div class="mt-3">
                    <p class="font-medium">Preview:</p>
                    <img src="{{ session('image_url') }}" alt="Preview" class="mt-2 max-w-full h-auto max-h-48 rounded-lg border border-gray-200" />
                </div>
            @endif
        </div>
    @endif
</x-filament::section>

<x-filament::section class="mt-6">
    <h2 class="text-xl font-bold mb-4">Upload Multiple Gambar Galeri</h2>
    
    <form action="{{ route('admin.gallery.upload.multiple') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <x-filament::input.wrapper>
                <x-filament::input.label for="gallery_images">Pilih Beberapa Gambar</x-filament::input.label>
                <input type="file" id="gallery_images" name="images[]" accept="image/*" multiple class="block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-md file:border-0
                    file:text-sm file:font-semibold
                    file:bg-primary-50 file:text-primary-700
                    hover:file:bg-primary-100" />
                <p class="mt-1 text-sm text-gray-500">Format: JPG, PNG, GIF. Ukuran maksimum: 5MB per file</p>
            </x-filament::input.wrapper>
        </div>
        
        <div>
            <x-filament::button type="submit">
                Upload Gambar
            </x-filament::button>
        </div>
    </form>
    
    @if(session('image_paths'))
        <div class="mt-4 p-4 bg-green-50 text-green-700 rounded-lg">
            <p>Upload multiple berhasil!</p>
            <div class="mt-2">
                <p class="font-medium">Path gambar:</p>
                <ul class="list-disc list-inside mt-1 space-y-1">
                    @foreach(session('image_paths') as $index => $path)
                        <li>
                            <code class="bg-gray-100 px-2 py-1 rounded">{{ $path }}</code>
                        </li>
                    @endforeach
                </ul>
                <p class="text-sm mt-2">Salin path di atas ke field Gambar pada form.</p>
            </div>
            
            @if(session('image_urls'))
                <div class="mt-3">
                    <p class="font-medium">Preview:</p>
                    <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach(session('image_urls') as $url)
                            <img src="{{ $url }}" alt="Preview" class="max-w-full h-auto max-h-32 rounded-lg border border-gray-200" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-filament::section>
