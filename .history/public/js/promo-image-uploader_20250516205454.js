/**
 * Promo Image Uploader Helper
 * 
 * This script provides helper functions for reliable file uploads in Docker environments
 * with Livewire 3 and Laravel 12, integrated with Filament forms.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle file upload progress (Livewire v3 uses a different event name)
    document.addEventListener('livewire:init', () => {
        // For Livewire v3
        Livewire.on('promo-image-uploaded', (data) => {
            console.log('Promo image uploaded:', data);
            showNotification('Success', 'Gambar promo berhasil diupload!', 'success');
        });
        
        Livewire.on('promo-upload-error', (data) => {
            console.error('Upload error:', data);
            showNotification('Error', data.message || 'Upload gagal', 'error');
        });
        
        // Handle setting file upload value in Filament form
        Livewire.on('set-file-upload', (data) => {
            console.log('Setting file upload value:', data);
            
            // Find the Filament file upload input
            const input = document.querySelector(`[name="${data.statePath}"]`);
            if (input) {
                input.value = data.value || '';
                
                // Trigger change event to notify Filament
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
                
                // Try to find and update the Filament file upload preview
                updateFilamentFileUploadPreview(data.statePath, data.value);
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
    document.addEventListener('promo-image-uploaded', function(event) {
        showNotification('Success', 'Gambar promo berhasil diupload!', 'success');
    });

    document.addEventListener('promo-upload-error', function(event) {
        showNotification('Error', event.detail?.message || 'Upload gagal', 'error');
    });
});

/**
 * Update the Filament file upload preview
 */
function updateFilamentFileUploadPreview(statePath, value) {
    // Try to find the Filament file upload component
    const fileUploadComponent = document.querySelector(`[wire\\:key*="${statePath}"]`);
    if (fileUploadComponent) {
        // If we found it, try to refresh it
        const livewireComponent = window.Livewire.find(
            fileUploadComponent.closest('[wire\\:id]')?.getAttribute('wire:id')
        );
        
        if (livewireComponent && typeof livewireComponent.call === 'function') {
            try {
                // This will refresh the entire form, which will update the file preview
                livewireComponent.call('$refresh');
            } catch (e) {
                console.error('Failed to refresh Filament component:', e);
            }
        }
    }
}

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
