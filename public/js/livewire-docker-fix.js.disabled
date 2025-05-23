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
            return originalFetch(finalUrl, options);
        }

        // For all other requests, use the original fetch
        return originalFetch(url, options);
    };

    // Also intercept XMLHttpRequest for older browsers or libraries
    const originalXHROpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function (method, url, async, user, password) {
        // Check if this is a Livewire file upload request
        if (typeof url === 'string' && url.includes('livewire/upload')) {
            console.log('Intercepting XHR Livewire upload request:', url);
            
            // Replace the URL with our custom route
            const newUrl = '/livewire/upload-file';
            
            // Keep any query parameters from the original URL
            const queryString = url.includes('?') ? url.substring(url.indexOf('?')) : '';
            const finalUrl = newUrl + queryString;
            
            console.log('Redirecting XHR to:', finalUrl);
            
            // Call the original open with the new URL
            return originalXHROpen.call(this, method, finalUrl, async, user, password);
        }
        
        // For all other requests, use the original open
        return originalXHROpen.call(this, method, url, async, user, password);
    };

    // Add event listeners for Livewire upload events
    document.addEventListener('livewire:init', () => {
        console.log('Livewire initialized, setting up upload event listeners');
        
        // Listen for upload start events
        Livewire.hook('upload:start', ({ component, name, fileUploadId, file }) => {
            console.log('Livewire upload started:', { component, name, fileUploadId });
        });
        
        // Listen for upload finish events
        Livewire.hook('upload:finish', ({ component, name, tmpFilenames }) => {
            console.log('Livewire upload finished:', { component, name, tmpFilenames });
        });
        
        // Listen for upload error events
        Livewire.hook('upload:error', ({ component, name, errors }) => {
            console.error('Livewire upload error:', { component, name, errors });
        });
    });
});
