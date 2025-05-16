/**
 * Promo Upload Helper
 * 
 * This script provides helper functions for reliable file uploads in Docker environments
 * with Livewire 3 and Laravel 12.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle file upload progress (Livewire v3 uses a different event name)
    document.addEventListener('livewire:init', () => {
        // For Livewire v3
        Livewire.on('promo-uploaded', (data) => {
            console.log('Promo uploaded:', data);
            showNotification('Success', 'Promo berhasil diupload!', 'success');
        });
        
        Livewire.on('promo-upload-error', (data) => {
            console.error('Upload error:', data);
            showNotification('Error', data.message || 'Upload gagal', 'error');
        });
        
        Livewire.on('refreshPromos', () => {
            console.log('Refreshing promos list');
            // If we're in Filament, we can try to refresh the table
            if (window.Livewire) {
                // Try to find and refresh the Filament table
                const tableComponent = window.Livewire.find(
                    document.querySelector('[wire\\:id]')?.getAttribute('wire:id')
                );
                
                if (tableComponent && typeof tableComponent.call === 'function') {
                    try {
                        tableComponent.call('$refresh');
                    } catch (e) {
                        console.error('Failed to refresh table:', e);
                    }
                }
            }
        });
    });
    
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
    
    // Fallback for direct DOM events (for backward compatibility)
    document.addEventListener('promo-uploaded', function(event) {
        showNotification('Success', 'Promo berhasil diupload!', 'success');
    });

    document.addEventListener('promo-upload-error', function(event) {
        showNotification('Error', event.detail?.message || 'Upload gagal', 'error');
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
