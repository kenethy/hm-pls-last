/**
 * Enhanced Gallery Upload Fix
 *
 * This script specifically fixes issues with file uploads in the Enhanced Gallery section.
 * It ensures proper CSRF token inclusion and handles the 422 Unprocessable Content errors.
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log('Enhanced Gallery Upload Fix initialized');

    // Only run on enhanced galleries pages
    if (!window.location.pathname.includes('enhanced-galleries')) {
        return;
    }

    console.log('Enhanced Gallery page detected, applying fixes');

    // Fix for file upload in enhanced galleries
    const originalFetch = window.fetch;
    window.fetch = function (url, options) {
        // Check if this is a custom upload request
        if (typeof url === 'string' && url.includes('/custom-upload')) {
            console.log('Intercepting custom upload request:', url);

            // Ensure options is initialized
            options = options || {};
            options.headers = options.headers || {};

            // Add CSRF token
            const token = document.querySelector('meta[name="csrf-token"]');
            if (token) {
                options.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
            }

            // Add X-Requested-With header for proper Laravel detection
            options.headers['X-Requested-With'] = 'XMLHttpRequest';

            // Add a timestamp to prevent caching
            const separator = url.includes('?') ? '&' : '?';
            url = `${url}${separator}_=${Date.now()}`;

            console.log('Modified request:', { url, headers: options.headers });
        }

        return originalFetch(url, options)
            .then(response => {
                // Handle 422 errors specifically for uploads
                if (response.status === 422 && typeof url === 'string' && url.includes('/custom-upload')) {
                    console.error('Validation error in upload request');
                    
                    // Clone the response to read its body
                    return response.clone().json()
                        .then(data => {
                            console.error('Validation error details:', data);
                            
                            // Create a more informative error message
                            let errorMessage = 'Upload validation failed: ';
                            if (data.errors) {
                                Object.keys(data.errors).forEach(key => {
                                    errorMessage += `${key}: ${data.errors[key].join(', ')}; `;
                                });
                            } else if (data.message) {
                                errorMessage += data.message;
                            }
                            
                            // Show error to user
                            if (window.Livewire) {
                                window.dispatchEvent(new CustomEvent('upload-error', { 
                                    detail: { message: errorMessage } 
                                }));
                            }
                            
                            // Return the original response to allow normal error handling
                            return response;
                        })
                        .catch(() => {
                            // If we can't parse the JSON, just return the original response
                            return response;
                        });
                }
                
                return response;
            })
            .catch(error => {
                console.error('Fetch error:', error);
                throw error;
            });
    };

    // Add event listener for upload errors
    window.addEventListener('upload-error', function(event) {
        alert('Upload Error: ' + event.detail.message);
    });

    // Add event listener for Livewire file upload
    document.addEventListener('livewire-upload-start', function (event) {
        console.log('Livewire upload started', event);
    });

    document.addEventListener('livewire-upload-finish', function (event) {
        console.log('Livewire upload finished', event);
    });

    document.addEventListener('livewire-upload-error', function (event) {
        console.error('Livewire upload error', event);
    });

    // Fix for Livewire 3 file uploads
    if (window.Livewire) {
        console.log('Livewire detected, adding upload hooks');
        
        document.addEventListener('livewire:init', () => {
            Livewire.hook('upload:start', ({ component, name, fileUploadId, file }) => {
                console.log('Livewire hook: upload started', { component, name, fileUploadId });
            });
            
            Livewire.hook('upload:finish', ({ component, name, tmpFilenames }) => {
                console.log('Livewire hook: upload finished', { component, name, tmpFilenames });
            });
            
            Livewire.hook('upload:error', ({ component, name, errors }) => {
                console.error('Livewire hook: upload error', { component, name, errors });
                
                // Show a more user-friendly error message
                let errorMessage = 'Upload failed: ';
                if (Array.isArray(errors) && errors.length > 0) {
                    errorMessage += errors.join(', ');
                } else {
                    errorMessage += 'Unknown error';
                }
                
                alert(errorMessage);
            });
        });
    }
});
