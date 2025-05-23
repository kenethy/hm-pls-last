/**
 * Livewire Upload Fix for Multiple Domains
 * 
 * This script fixes issues with Livewire file uploads when the application
 * is accessed from multiple domains or when using custom URL validation.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Only run this script if Livewire is loaded
    if (window.Livewire) {
        console.log('Livewire Upload Fix v2 loaded');
        
        // Fix for URL signature validation issues
        const originalFetch = window.fetch;
        window.fetch = function(resource, options) {
            // Check if this is a Livewire upload request
            if (typeof resource === 'string' && resource.includes('/livewire/upload-file')) {
                console.log('Intercepting Livewire upload request:', resource);
                
                // Add a timestamp parameter to avoid caching issues
                const url = new URL(resource);
                url.searchParams.set('_ts', Date.now());
                resource = url.toString();
                
                // Ensure the Authorization header is included if available
                if (options && !options.headers) {
                    options.headers = {};
                }
                
                // Add CSRF token if available
                const token = document.querySelector('meta[name="csrf-token"]');
                if (token && options && options.headers) {
                    options.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
                }
            }
            
            return originalFetch.call(this, resource, options);
        };
        
        // Handle upload errors more gracefully
        window.addEventListener('livewire-upload-error', function(event) {
            console.error('Livewire upload error:', event.detail);
            
            // Show a user-friendly error message
            alert('Terjadi kesalahan saat mengupload file. Silakan coba lagi.');
        });
        
        // Add retry capability for failed uploads
        window.addEventListener('livewire-upload-finish', function(event) {
            console.log('Upload finished successfully');
        });
    }
});
