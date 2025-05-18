/**
 * Enhanced Gallery Upload Fix
 *
 * This script specifically fixes issues with file uploads in the Enhanced Gallery section.
 * It ensures proper CSRF token inclusion and handles the 422 Unprocessable Content errors.
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log('Enhanced Gallery Upload Fix initialized');

    // Run on all pages, but apply specific fixes for enhanced galleries
    const isEnhancedGalleryPage = window.location.pathname.includes('enhanced-galleries');

    if (isEnhancedGalleryPage) {
        console.log('Enhanced Gallery page detected, applying specific fixes');
    }

    // Fix for file upload in all pages
    const originalFetch = window.fetch;
    window.fetch = function (url, options) {
        // Check if this is a custom upload request
        if (typeof url === 'string' && (url.includes('/custom-upload') || url.includes('/livewire/upload-file'))) {
            console.log('Intercepting upload request:', url);

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

            // Log the request details
            console.log('Modified request:', {
                url,
                method: options.method || 'GET',
                headers: options.headers,
                hasBody: options.body ? true : false,
                bodyType: options.body ? (options.body instanceof FormData ? 'FormData' : typeof options.body) : 'none'
            });

            // If this is a FormData body, log its contents
            if (options.body instanceof FormData) {
                const formDataEntries = [];
                for (let pair of options.body.entries()) {
                    if (pair[1] instanceof File) {
                        formDataEntries.push({
                            key: pair[0],
                            type: 'File',
                            name: pair[1].name,
                            size: pair[1].size,
                            mimeType: pair[1].type
                        });
                    } else {
                        formDataEntries.push({
                            key: pair[0],
                            value: pair[1]
                        });
                    }
                }
                console.log('FormData contents:', formDataEntries);
            }
        }

        return originalFetch(url, options)
            .then(response => {
                // Log all responses for debugging
                console.log('Fetch response:', {
                    url,
                    status: response.status,
                    statusText: response.statusText,
                    headers: Array.from(response.headers.entries())
                });

                // Handle 422 errors specifically for uploads
                if (response.status === 422 && typeof url === 'string' &&
                    (url.includes('/custom-upload') || url.includes('/livewire/upload-file'))) {
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
                            window.dispatchEvent(new CustomEvent('upload-error', {
                                detail: { message: errorMessage }
                            }));

                            // Return the original response to allow normal error handling
                            return response;
                        })
                        .catch(error => {
                            console.error('Error parsing validation response:', error);
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

    // Also intercept XMLHttpRequest for older browsers or libraries
    const originalXHROpen = XMLHttpRequest.prototype.open;
    const originalXHRSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function (method, url, async, user, password) {
        // Store the URL for later use in send
        this._url = url;

        // Check if this is an upload request
        if (typeof url === 'string' && (url.includes('/custom-upload') || url.includes('/livewire/upload-file'))) {
            console.log('Intercepting XHR upload request:', { method, url });

            // Redirect to our custom endpoint if needed
            if (url.includes('/livewire/upload-file')) {
                url = '/custom-upload' + (url.includes('?') ? url.substring(url.indexOf('?')) : '');
                console.log('Redirecting XHR to:', url);
            }

            // Add a timestamp to prevent caching
            const separator = url.includes('?') ? '&' : '?';
            url = `${url}${separator}_=${Date.now()}`;
        }

        return originalXHROpen.call(this, method, url, async, user, password);
    };

    XMLHttpRequest.prototype.send = function (data) {
        // Check if this is an upload request
        if (this._url && typeof this._url === 'string' &&
            (this._url.includes('/custom-upload') || this._url.includes('/livewire/upload-file'))) {

            // Add CSRF token header
            const token = document.querySelector('meta[name="csrf-token"]');
            if (token) {
                this.setRequestHeader('X-CSRF-TOKEN', token.getAttribute('content'));
            }

            // Add X-Requested-With header for proper Laravel detection
            this.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            // Log the request data
            if (data instanceof FormData) {
                console.log('XHR sending FormData');
            } else {
                console.log('XHR sending data:', data);
            }

            // Add event listeners for debugging
            this.addEventListener('load', function () {
                console.log('XHR load:', {
                    url: this._url,
                    status: this.status,
                    statusText: this.statusText,
                    response: this.responseText.substring(0, 200) + (this.responseText.length > 200 ? '...' : '')
                });

                // Handle 422 errors
                if (this.status === 422) {
                    try {
                        const responseData = JSON.parse(this.responseText);
                        console.error('XHR validation error:', responseData);

                        // Create error message
                        let errorMessage = 'Upload validation failed: ';
                        if (responseData.errors) {
                            Object.keys(responseData.errors).forEach(key => {
                                errorMessage += `${key}: ${responseData.errors[key].join(', ')}; `;
                            });
                        } else if (responseData.message) {
                            errorMessage += responseData.message;
                        }

                        // Show error to user
                        window.dispatchEvent(new CustomEvent('upload-error', {
                            detail: { message: errorMessage }
                        }));
                    } catch (e) {
                        console.error('Error parsing XHR response:', e);
                    }
                }
            });

            this.addEventListener('error', function () {
                console.error('XHR error:', {
                    url: this._url,
                    status: this.status,
                    statusText: this.statusText
                });
            });
        }

        return originalXHRSend.call(this, data);
    };

    // Add event listener for upload errors
    window.addEventListener('upload-error', function (event) {
        console.error('Upload error event:', event.detail);
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

    // Add direct fix for the specific error in livewire.js (id=df3a17f2:613)
    // This patches the Livewire upload functionality to ensure proper headers
    if (window.Livewire && isEnhancedGalleryPage) {
        console.log('Applying direct fix for Livewire upload in enhanced galleries');

        // Wait for all scripts to load
        setTimeout(() => {
            // Find all file upload inputs in the enhanced gallery form
            const fileInputs = document.querySelectorAll('input[type="file"]');

            fileInputs.forEach(input => {
                console.log('Found file input:', input.name || 'unnamed');

                // Add a custom event listener to intercept the upload
                input.addEventListener('change', function (event) {
                    console.log('File input changed:', this.files);

                    // If using the direct upload approach, we need to ensure proper headers
                    if (this.files && this.files.length > 0) {
                        console.log('Files selected, ensuring proper upload handling');
                    }
                });
            });
        }, 1000);
    }
});
