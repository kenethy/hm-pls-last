/**
 * Livewire Upload Fix for Docker Environments
 *
 * This script intercepts Livewire file upload requests and redirects them to our custom route.
 * This fixes the 404 errors that occur in Docker environments.
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log('Livewire Docker fix initialized');

    // Store the original fetch function
    const originalFetch = window.fetch;

    // Override the fetch function to intercept ONLY Livewire file upload requests
    window.fetch = function (url, options) {
        // Check if this is a Livewire file upload request (be very specific)
        if (typeof url === 'string' &&
            url.includes('livewire/upload') &&
            options &&
            options.body instanceof FormData &&
            options.body.has('files')) {

            console.log('Intercepting Livewire file upload request:', url);

            // Replace the URL with our custom route
            const newUrl = '/livewire/upload-file';

            // Keep any query parameters from the original URL
            const queryString = url.includes('?') ? url.substring(url.indexOf('?')) : '';
            const finalUrl = newUrl + queryString;

            console.log('Redirecting to:', finalUrl);

            // Call the original fetch with the new URL
            return originalFetch(finalUrl, options)
                .then(response => {
                    if (!response.ok) {
                        console.error('Livewire upload response not OK:', response.status);
                    }
                    return response;
                })
                .catch(error => {
                    console.error('Livewire upload error:', error);
                    throw error;
                });
        }

        // For all other requests, use the original fetch without modification
        return originalFetch(url, options);
    };

    // We'll skip the XMLHttpRequest patch as it might interfere with login
    // If we need it later, we can add it back with more specific conditions

    // Handle Livewire upload errors
    window.addEventListener('livewire-upload-error', function (event) {
        console.error('Livewire upload error:', event.detail);
    });

    // Handle Livewire upload progress
    window.addEventListener('livewire-upload-progress', function (event) {
        console.log('Livewire upload progress:', event.detail.progress);
    });

    // Handle Livewire upload finish
    window.addEventListener('livewire-upload-finish', function (event) {
        console.log('Livewire upload finished');
    });
});
