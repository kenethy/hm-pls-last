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

            // Ensure the image path is properly set in the form
            setTimeout(() => {
                ensureImagePathInForm(data.statePath, data.path);
            }, 100);
        });

        Livewire.on('promo-image-removed', (data) => {
            console.log('Promo image removed event received:', data);
        });

        // Listen for form submission events
        Livewire.on('form-submitted', () => {
            console.log('Form submission detected');
            ensureAllImagePathsInForm();
        });
    });

    // Function to ensure image path is in the form
    function ensureImagePathInForm(statePath, value) {
        if (!statePath || !value) return;

        console.log('Ensuring image path is in form:', statePath, value);

        // Find the Filament form component
        const formComponent = document.querySelector('[wire\\:id^="filament.forms"]');
        if (formComponent) {
            const formComponentId = formComponent.getAttribute('wire:id');
            const formLivewire = window.Livewire.find(formComponentId);

            if (formLivewire && typeof formLivewire.$wire !== 'undefined') {
                // Set the value in the Filament form state
                formLivewire.$wire.set(statePath, value);
                console.log('Updated form state for', statePath);
            }
        }
    }

    // Function to ensure all image paths are in the form
    function ensureAllImagePathsInForm() {
        // Find all hidden inputs for uploaded images
        const hiddenInputs = document.querySelectorAll('input[type="hidden"][name*="image_path"]');

        // Make sure they're properly included in the form data
        hiddenInputs.forEach(input => {
            if (input.value) {
                ensureImagePathInForm(input.name, input.value);
            }
        });
    }

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

    // Function to ensure form submission works correctly
    function patchFormSubmission() {
        // Find all potential submit buttons (Filament uses different selectors)
        const submitButtons = document.querySelectorAll('button[type="submit"], button.filament-button');

        submitButtons.forEach(submitButton => {
            console.log('Found form submit button, ensuring it works correctly');

            // Make sure the button is not disabled
            if (submitButton.hasAttribute('disabled')) {
                submitButton.removeAttribute('disabled');
            }

            // Add a click handler to ensure form submission works
            submitButton.addEventListener('click', function (event) {
                console.log('Submit button clicked');

                // Ensure all image paths are in the form
                ensureAllImagePathsInForm();

                // Find the Filament form component
                const formComponent = document.querySelector('[wire\\:id^="filament.forms"]');
                if (formComponent) {
                    const formComponentId = formComponent.getAttribute('wire:id');
                    const formLivewire = window.Livewire.find(formComponentId);

                    if (formLivewire && typeof formLivewire.$wire !== 'undefined') {
                        // Dispatch a custom event to notify our code that form is being submitted
                        Livewire.dispatch('form-submitted');

                        // Force a form validation and submission
                        setTimeout(() => {
                            if (typeof formLivewire.$wire.submit === 'function') {
                                console.log('Forcing form submission');
                                formLivewire.$wire.submit();
                            }
                        }, 100);
                    }
                }
            });
        });

        // Also monitor for form elements being added to the DOM
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                    for (let i = 0; i < mutation.addedNodes.length; i++) {
                        const node = mutation.addedNodes[i];
                        if (node.nodeType === 1) { // Element node
                            const newButtons = node.querySelectorAll('button[type="submit"], button.filament-button');
                            if (newButtons.length > 0) {
                                console.log('New submit buttons detected, patching them');
                                patchFormSubmission();
                                break;
                            }
                        }
                    }
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Run the patches when the page is loaded and after any AJAX navigation
    patchPromoImageUploader();
    patchFormSubmission();

    // Also patch after Livewire updates
    document.addEventListener('livewire:navigated', () => {
        patchPromoImageUploader();
        patchFormSubmission();
    });

    document.addEventListener('livewire:load', () => {
        patchPromoImageUploader();
        patchFormSubmission();
    });
});
