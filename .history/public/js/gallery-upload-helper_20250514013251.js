/**
 * Gallery Upload Helper
 * 
 * This script provides helper functions for reliable file uploads in Docker environments
 * with Livewire 3 and Laravel 12.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle file upload progress
    window.addEventListener('livewire-upload-progress', function(event) {
        // Update progress bars if they exist
        const progressBars = document.querySelectorAll('.upload-progress');
        if (progressBars.length) {
            progressBars.forEach(progressBar => {
                progressBar.style.width = event.detail.progress + '%';
                progressBar.textContent = event.detail.progress + '%';
            });
        }
    });

    // Handle file upload errors
    window.addEventListener('livewire-upload-error', function(event) {
        console.error('Upload error:', event.detail);
        
        // Show error notification
        showNotification('Error', 'File upload failed. Please try again.', 'error');
    });

    // Handle file upload completion
    window.addEventListener('livewire-upload-finish', function(event) {
        console.log('Upload finished');
    });

    // Listen for custom events from our Livewire components
    document.addEventListener('gallery-image-uploaded', function(event) {
        showNotification('Success', 'Image uploaded successfully!', 'success');
    });

    document.addEventListener('gallery-images-uploaded', function(event) {
        const count = event.detail.count || 'Multiple';
        showNotification('Success', `${count} images uploaded successfully!`, 'success');
    });

    document.addEventListener('gallery-upload-error', function(event) {
        showNotification('Error', event.detail.message || 'Upload failed', 'error');
    });
});

/**
 * Show a notification using Filament's notification system if available,
 * or fallback to a simple alert
 */
function showNotification(title, message, type = 'success') {
    if (window.Filament && window.Filament.notify) {
        window.Filament.notify({
            title: title,
            body: message,
            icon: type,
            iconColor: type === 'success' ? 'success' : 'danger',
            timeout: 3000,
        });
    } else {
        // Fallback to alert if Filament notification is not available
        alert(`${title}: ${message}`);
    }
}
