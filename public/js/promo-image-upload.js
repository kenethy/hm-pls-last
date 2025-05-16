/**
 * Promo Image Upload Helper
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
            showNotification('Success', 'Image uploaded successfully!', 'success');
            
            // Update hidden input field if it exists
            updateHiddenInput(data.statePath, data.path);
            
            // Refresh Filament form
            refreshFilamentForm();
        });
        
        Livewire.on('promo-image-removed', (data) => {
            console.log('Promo image removed:', data);
            
            // Update hidden input field if it exists
            updateHiddenInput(data.statePath, '');
            
            // Refresh Filament form
            refreshFilamentForm();
        });
        
        Livewire.on('promo-upload-error', (data) => {
            console.error('Upload error:', data);
            showNotification('Error', data.message || 'Upload failed', 'error');
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
        showNotification('Error', 'File upload failed. Please try again.', 'error');
    });

    // Handle file upload completion
    window.addEventListener('livewire-upload-finish', function(event) {
        console.log('Upload finished');
    });
});

/**
 * Update hidden input field
 */
function updateHiddenInput(statePath, value) {
    if (!statePath) return;
    
    const input = document.querySelector(`input[name="${statePath}"]`);
    if (input) {
        input.value = value;
        
        // Trigger change event to notify Filament
        const event = new Event('change', { bubbles: true });
        input.dispatchEvent(event);
    }
}

/**
 * Refresh Filament form
 */
function refreshFilamentForm() {
    // Try to find and refresh the Filament form
    if (window.Livewire) {
        // Find the closest Livewire component (likely the form)
        const formComponent = document.querySelector('[wire\\:id]');
        if (formComponent) {
            const componentId = formComponent.getAttribute('wire:id');
            const component = window.Livewire.find(componentId);
            
            if (component && typeof component.$refresh === 'function') {
                try {
                    component.$refresh();
                } catch (e) {
                    console.error('Failed to refresh Filament form:', e);
                }
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
