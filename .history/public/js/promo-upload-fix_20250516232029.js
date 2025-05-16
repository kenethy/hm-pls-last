/**
 * Promo Upload Fix
 *
 * This script specifically fixes the Promo image upload functionality
 * by ensuring that uploads are properly handled in Docker environments.
 */
document.addEventListener('DOMContentLoaded', function () {
    console.log('Promo upload fix loaded');

    // Wait for Livewire to initialize
    document.addEventListener('livewire:init', () => {
        // Listen for promo image upload events
        Livewire.on('promo-image-uploaded', (data) => {
            console.log('Promo image uploaded event received:', data);
        });

        Livewire.on('promo-image-removed', (data) => {
            console.log('Promo image removed event received:', data);
        });
    });

    // Patch the PromoImageUploader component's upload method
    function patchPromoImageUploader() {
        // Find all promo image uploader components
        const uploaders = document.querySelectorAll('.filament-forms-promo-image-upload-component');

        if (uploaders.length > 0) {
            console.log('Found promo image uploaders:', uploaders.length);

            // Monitor for file input changes
            uploaders.forEach(uploader => {
                const fileInput = uploader.querySelector('input[type="file"]');

                if (fileInput) {
                    // Replace the default change handler with our custom one
                    fileInput.addEventListener('change', function (event) {
                        event.preventDefault();
                        event.stopPropagation();

                        const files = event.target.files;
                        if (!files || files.length === 0) return;

                        console.log('Promo image file selected, handling upload manually');

                        // Get the Livewire component
                        const livewireComponent = uploader.closest('[wire\\:id]');
                        if (!livewireComponent) {
                            console.error('Could not find Livewire component');
                            return;
                        }

                        // Show upload progress
                        const progressContainer = uploader.querySelector('.upload-progress');
                        if (progressContainer) {
                            progressContainer.style.width = '0%';
                            progressContainer.textContent = '0%';
                        }

                        // Create FormData and append the file
                        const formData = new FormData();
                        formData.append('file', files[0]);
                        formData.append('directory', 'promos');

                        // Add CSRF token
                        const token = document.querySelector('meta[name="csrf-token"]');
                        if (token) {
                            formData.append('_token', token.getAttribute('content'));
                        }

                        // Send the upload request to our custom endpoint
                        fetch('/custom-upload', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Upload failed: ' + response.statusText);
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log('Upload successful:', data);

                                // Update the Livewire component
                                const componentId = livewireComponent.getAttribute('wire:id');
                                const component = window.Livewire.find(componentId);

                                if (component) {
                                    // Find the statePath from the hidden input
                                    const hiddenInput = uploader.querySelector('input[type="hidden"]');
                                    const statePath = hiddenInput ? hiddenInput.getAttribute('name') : null;

                                    if (statePath) {
                                        // Dispatch the event to update the component
                                        Livewire.dispatch('promo-image-uploaded', {
                                            path: data.path,
                                            statePath: statePath
                                        });

                                        // Update the UI
                                        const imagePreview = uploader.querySelector('img');
                                        if (imagePreview) {
                                            imagePreview.src = data.url;
                                        } else {
                                            // Create a new image preview
                                            const previewContainer = document.createElement('div');
                                            previewContainer.className = 'relative';
                                            previewContainer.innerHTML = `
                                            <img src="${data.url}" alt="Promo Image Preview" class="max-w-full h-auto max-h-64 rounded-lg border border-gray-200" />
                                            <button
                                                type="button"
                                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                                title="Remove Image"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        `;

                                            // Add the preview before the file input
                                            const inputContainer = fileInput.closest('.space-y-2');
                                            if (inputContainer && inputContainer.parentNode) {
                                                inputContainer.parentNode.insertBefore(previewContainer, inputContainer);
                                            }

                                            // Add event listener to the remove button
                                            const removeButton = previewContainer.querySelector('button');
                                            if (removeButton) {
                                                removeButton.addEventListener('click', function () {
                                                    // Dispatch the event to update the component
                                                    Livewire.dispatch('promo-image-removed', {
                                                        statePath: statePath
                                                    });

                                                    // Remove the preview
                                                    previewContainer.remove();
                                                });
                                            }
                                        }

                                        // Update the hidden input
                                        if (hiddenInput) {
                                            hiddenInput.value = data.path;

                                            // Trigger change event to notify Filament form
                                            const changeEvent = new Event('change', { bubbles: true });
                                            hiddenInput.dispatchEvent(changeEvent);

                                            // Also update the Filament form state directly
                                            const formComponent = document.querySelector('[wire\\:id^="filament.forms"]');
                                            if (formComponent) {
                                                const formComponentId = formComponent.getAttribute('wire:id');
                                                const formLivewire = window.Livewire.find(formComponentId);

                                                if (formLivewire && typeof formLivewire.$wire !== 'undefined') {
                                                    // Set the value in the Filament form state
                                                    formLivewire.$wire.set(statePath, data.path);
                                                    console.log('Updated Filament form state:', statePath, data.path);
                                                }
                                            }
                                        }
                                    }
                                }

                                // Reset the file input
                                fileInput.value = '';
                            })
                            .catch(error => {
                                console.error('Upload error:', error);
                                alert('Upload failed: ' + error.message);

                                // Reset the file input
                                fileInput.value = '';
                            });

                        return false;
                    }, true);
                }
            });
        }
    }

    // Run the patch when the page is loaded and after any AJAX navigation
    patchPromoImageUploader();

    // Also patch after Livewire updates
    document.addEventListener('livewire:navigated', patchPromoImageUploader);
    document.addEventListener('livewire:load', patchPromoImageUploader);
});
