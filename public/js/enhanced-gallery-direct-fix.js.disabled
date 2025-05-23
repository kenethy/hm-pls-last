/**
 * Enhanced Gallery Direct Fix
 *
 * This script provides a direct fix for the file upload issue in the enhanced galleries section.
 * It completely bypasses the Livewire upload mechanism and uses a direct AJAX upload instead.
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log('Enhanced Gallery Direct Fix initialized');

    // Only run on enhanced galleries pages
    if (!window.location.pathname.includes('enhanced-galleries')) {
        return;
    }

    console.log('Enhanced Gallery page detected, applying direct fix');

    // Wait for the DOM to be fully loaded
    setTimeout(function() {
        // Find all file upload inputs in the enhanced gallery form
        const fileInputs = document.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach(input => {
            const inputName = input.name || 'unnamed';
            console.log('Found file input:', inputName);
            
            // Get the Livewire component ID
            const form = input.closest('form');
            const componentId = form ? form.getAttribute('wire:id') : null;
            
            if (!componentId) {
                console.log('No Livewire component found for input:', inputName);
                return;
            }
            
            console.log('Found Livewire component:', componentId, 'for input:', inputName);
            
            // Create a wrapper div to replace the original input
            const wrapper = document.createElement('div');
            wrapper.className = 'custom-file-upload-wrapper';
            wrapper.style.position = 'relative';
            
            // Clone the original input
            const clonedInput = input.cloneNode(true);
            clonedInput.style.opacity = '0';
            clonedInput.style.position = 'absolute';
            clonedInput.style.top = '0';
            clonedInput.style.left = '0';
            clonedInput.style.width = '100%';
            clonedInput.style.height = '100%';
            clonedInput.style.cursor = 'pointer';
            
            // Create a visual replacement
            const visualElement = document.createElement('div');
            visualElement.className = 'custom-file-upload-visual';
            visualElement.innerHTML = `
                <div class="flex items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <p class="mt-1 text-sm text-gray-500">
                            Click to upload or drag and drop
                        </p>
                        <p class="text-xs text-gray-500">
                            PNG, JPG, GIF up to 10MB
                        </p>
                    </div>
                </div>
            `;
            
            // Create a preview element
            const previewElement = document.createElement('div');
            previewElement.className = 'custom-file-upload-preview mt-2 hidden';
            
            // Add elements to the wrapper
            wrapper.appendChild(visualElement);
            wrapper.appendChild(clonedInput);
            wrapper.appendChild(previewElement);
            
            // Replace the original input with our wrapper
            input.parentNode.replaceChild(wrapper, input);
            
            // Add event listener to the cloned input
            clonedInput.addEventListener('change', function(event) {
                if (!this.files || !this.files.length) {
                    return;
                }
                
                console.log('File selected:', this.files[0].name);
                
                // Show loading state
                visualElement.innerHTML = `
                    <div class="flex items-center justify-center w-full h-32">
                        <div class="text-center">
                            <svg class="animate-spin h-8 w-8 text-primary-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Uploading...</p>
                        </div>
                    </div>
                `;
                
                // Create FormData
                const formData = new FormData();
                formData.append('file', this.files[0]);
                formData.append('directory', 'galleries');
                
                // Get CSRF token
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // Upload the file directly
                fetch('/enhanced-gallery/upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Upload failed: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Upload successful:', data);
                    
                    // Show success state with preview
                    visualElement.innerHTML = `
                        <div class="flex items-center justify-center w-full h-32 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="text-center">
                                <svg class="mx-auto h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <p class="mt-2 text-sm text-gray-700">Upload successful</p>
                                <p class="text-xs text-gray-500">${this.files[0].name}</p>
                            </div>
                        </div>
                    `;
                    
                    // Show preview if it's an image
                    if (this.files[0].type.startsWith('image/')) {
                        previewElement.innerHTML = `
                            <img src="${data.url}" alt="Preview" class="mt-2 rounded-lg max-h-48 mx-auto">
                        `;
                        previewElement.classList.remove('hidden');
                    }
                    
                    // Update the Livewire component with the file path
                    if (window.Livewire) {
                        // Get the field name from the input
                        const fieldName = inputName.replace(/\[\d*\]$/, '');
                        
                        // Set the value in the Livewire component
                        window.Livewire.find(componentId).set(fieldName, data.path);
                        
                        console.log('Updated Livewire component:', componentId, 'field:', fieldName, 'value:', data.path);
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    
                    // Show error state
                    visualElement.innerHTML = `
                        <div class="flex items-center justify-center w-full h-32 bg-red-50 rounded-lg border border-red-200">
                            <div class="text-center">
                                <svg class="mx-auto h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="mt-2 text-sm text-red-700">Upload failed</p>
                                <p class="text-xs text-red-500">${error.message}</p>
                            </div>
                        </div>
                    `;
                    
                    // Reset the input
                    this.value = '';
                });
            });
        });
    }, 1000);
});
