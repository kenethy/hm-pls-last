@props([
    'name',
    'label' => null,
    'multiple' => false,
    'accept' => null,
    'directory' => 'uploads',
    'helperText' => null,
])

<div
    x-data="customFileUpload({
        name: '{{ $name }}',
        multiple: {{ $multiple ? 'true' : 'false' }},
        directory: '{{ $directory }}',
        acceptedFileTypes: {{ json_encode(explode(',', $accept ?? '')) }}
    })"
    class="filament-forms-file-upload-component"
>
    <div class="flex items-center justify-between space-x-2 rtl:space-x-reverse">
        @if ($label)
            <label for="{{ $name }}" class="inline-flex items-center space-x-3 rtl:space-x-reverse">
                <span class="text-sm font-medium leading-4 text-gray-700 dark:text-gray-300">
                    {{ $label }}
                </span>
            </label>
        @endif
        
        <div class="flex items-center space-x-2 rtl:space-x-reverse">
            <input
                type="file"
                id="{{ $name }}"
                name="{{ $name }}"
                @if ($multiple) multiple @endif
                @if ($accept) accept="{{ $accept }}" @endif
                class="hidden"
                x-ref="input"
                @change="files = $event.target.files"
            />
            
            <button
                type="button"
                class="filament-forms-file-upload-component-upload-button inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset dark:focus:ring-offset-0 min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700 filament-page-button-action"
                x-on:click="$refs.input.click()"
                :disabled="uploading"
            >
                <span class="flex items-center gap-1">
                    <svg class="w-5 h-5 -ml-1 rtl:ml-0 rtl:-mr-1 filament-button-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    <span>
                        {{ $multiple ? 'Upload files' : 'Upload file' }}
                    </span>
                </span>
            </button>
        </div>
    </div>
    
    <!-- Helper text -->
    @if ($helperText)
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ $helperText }}
        </div>
    @endif
    
    <!-- Progress bar -->
    <div x-show="uploading" class="mt-2">
        <div class="bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 w-full">
            <div class="bg-primary-600 h-2.5 rounded-full" :style="{ width: progress + '%' }"></div>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Uploading... <span x-text="progress"></span>%
        </div>
    </div>
    
    <!-- Error message -->
    <div x-show="error" class="text-sm text-danger-500 mt-2" x-text="error"></div>
    
    <!-- Preview uploaded files -->
    <div class="mt-2 space-y-2" x-show="uploadedFiles.length > 0">
        <template x-for="(file, index) in uploadedFiles" :key="index">
            <div class="flex items-center justify-between p-2 border border-gray-300 rounded-lg dark:border-gray-700">
                <div class="flex items-center space-x-2 rtl:space-x-reverse overflow-hidden">
                    <!-- Preview for images -->
                    <div class="flex-shrink-0 w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded overflow-hidden">
                        <img x-show="file.mime && file.mime.startsWith('image/')" :src="file.url" class="w-full h-full object-cover" />
                        <div x-show="!file.mime || !file.mime.startsWith('image/')" class="w-full h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                    </div>
                    
                    <!-- File info -->
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="file.name || file.path"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="formatFileSize(file.size)"></p>
                    </div>
                </div>
                
                <!-- Remove button -->
                <button
                    type="button"
                    class="text-danger-600 hover:text-danger-500 focus:outline-none"
                    x-on:click="removeFile(index)"
                >
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </button>
            </div>
        </template>
    </div>
    
    <script>
        function formatFileSize(bytes) {
            if (!bytes) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</div>
