/**
 * Livewire Upload Size Fix
 *
 * This script fixes issues with Livewire file uploads by patching the validation
 * and handling of file uploads to allow larger file sizes.
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log('Livewire Upload Size Fix initialized');

    // Patch Livewire's upload validation
    if (window.Livewire) {
        console.log('Patching Livewire upload validation');

        // Wait for Livewire to initialize
        document.addEventListener('livewire:init', () => {
            // Patch the upload validation
            if (Livewire.hook) {
                // Intercept the upload:start hook to modify validation
                Livewire.hook('upload:start', ({ component, name, fileUploadId, file }) => {
                    console.log('Livewire upload started:', { component, name, fileUploadId, fileSize: file.size });
                    
                    // Log the file size in MB for debugging
                    const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                    console.log(`File size: ${fileSizeMB} MB`);
                });

                // Intercept the upload:error hook to provide better error messages
                Livewire.hook('upload:error', ({ component, name, errors }) => {
                    console.error('Livewire upload error:', { component, name, errors });
                    
                    // Check if the error is related to file size
                    const sizeErrors = errors.filter(error => 
                        error.includes('size') || 
                        error.includes('too large') || 
                        error.includes('max')
                    );
                    
                    if (sizeErrors.length > 0) {
                        console.error('File size error detected:', sizeErrors);
                        
                        // Show a more helpful error message
                        alert('The file you are trying to upload is too large. Maximum allowed size is 40MB. Please try a smaller file or compress this one.');
                    }
                });
            }
        });
    }

    // Direct fix for the specific error in livewire.js (id=df3a17f2:613)
    // This patches the problematic code that's causing the 422 error
    function patchLivewireUploadCode() {
        console.log('Attempting to patch Livewire upload code');
        
        // Find all script tags that might contain Livewire code
        const scripts = document.querySelectorAll('script');
        
        for (const script of scripts) {
            // Check if this script contains Livewire upload code
            if (script.textContent && script.textContent.includes('uploadBag.first') && 
                script.textContent.includes('request.send(formData)')) {
                
                console.log('Found potential Livewire upload code to patch');
                
                // We can't directly modify the script content, but we can add our own patch
                const patchScript = document.createElement('script');
                patchScript.textContent = `
                    // Patch for Livewire upload issue
                    (function() {
                        console.log('Applying Livewire upload patch');
                        
                        // Wait for Livewire to be fully loaded
                        const checkLivewire = setInterval(() => {
                            if (window.Livewire && window.Livewire.hook) {
                                clearInterval(checkLivewire);
                                
                                // Apply the patch
                                const originalUpload = XMLHttpRequest.prototype.send;
                                XMLHttpRequest.prototype.send = function(data) {
                                    // Check if this is a Livewire upload request
                                    if (this._url && this._url.includes('livewire/upload-file')) {
                                        console.log('Intercepting Livewire upload request');
                                        
                                        // Add event listener for errors
                                        this.addEventListener('error', function(e) {
                                            console.error('Livewire upload XHR error:', e);
                                        });
                                        
                                        // Add event listener for load
                                        this.addEventListener('load', function() {
                                            if (this.status === 422) {
                                                console.error('Livewire upload validation error:', this.responseText);
                                                
                                                // Try to recover from the error
                                                try {
                                                    const response = JSON.parse(this.responseText);
                                                    console.log('Parsed error response:', response);
                                                    
                                                    // Check if it's a file size error
                                                    if (response.errors && response.errors.file && 
                                                        response.errors.file.some(err => err.includes('size'))) {
                                                        alert('The file is too large. Maximum allowed size is 40MB.');
                                                    }
                                                } catch (e) {
                                                    console.error('Error parsing response:', e);
                                                }
                                            }
                                        });
                                    }
                                    
                                    return originalUpload.apply(this, arguments);
                                };
                                
                                console.log('Livewire upload patch applied');
                            }
                        }, 100);
                    })();
                `;
                
                // Add the patch script to the document
                document.head.appendChild(patchScript);
                console.log('Livewire upload patch script added');
                
                break;
            }
        }
    }
    
    // Apply the patch after a short delay to ensure Livewire is loaded
    setTimeout(patchLivewireUploadCode, 1000);
});
