/**
 * Custom File Upload Handler
 *
 * Script ini menggantikan mekanisme upload file Livewire dengan solusi kustom
 * yang lebih handal dan tidak bergantung pada endpoint Livewire.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Intercept all AJAX requests to Livewire upload endpoint
    const originalXHROpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function (method, url, async, user, password) {
        // Jika ini adalah request ke livewire/upload-file
        if (typeof url === 'string' && url.includes('livewire/upload-file')) {
            console.log('Intercepting Livewire upload request:', url);

            // Redirect ke endpoint kustom kita
            if (method.toUpperCase() === 'POST') {
                url = '/custom-upload';
            } else {
                // Untuk metode GET atau lainnya, kita redirect ke halaman utama
                // untuk menghindari error
                url = '/';
            }
        }

        return originalXHROpen.call(this, method, url, async, user, password);
    };

    // Intercept fetch requests
    const originalFetch = window.fetch;
    window.fetch = function (resource, options) {
        if (typeof resource === 'string' && resource.includes('livewire/upload-file')) {
            console.log('Intercepting Livewire fetch upload request:', resource);

            // Redirect ke endpoint kustom kita
            if (options && options.method && options.method.toUpperCase() === 'POST') {
                resource = '/custom-upload';
            } else {
                // Untuk metode GET atau lainnya, kita redirect ke halaman utama
                resource = '/';
            }
        }

        return originalFetch.call(this, resource, options);
    };

    // Tunggu hingga Alpine.js dan Filament dimuat
    function waitForAlpine(callback) {
        if (window.Alpine) {
            callback();
        } else {
            setTimeout(function () {
                waitForAlpine(callback);
            }, 100);
        }
    }

    waitForAlpine(function () {
        // Tambahkan handler untuk komponen FileUpload
        Alpine.data('customFileUpload', (config) => ({
            name: config.name,
            multiple: config.multiple || false,
            directory: config.directory || 'uploads',
            acceptedFileTypes: config.acceptedFileTypes || [],
            files: [],
            uploadedFiles: [],
            uploading: false,
            progress: 0,
            error: null,

            init() {
                // Inisialisasi state dari input yang sudah ada
                if (this.$wire.get(this.name)) {
                    this.uploadedFiles = Array.isArray(this.$wire.get(this.name))
                        ? this.$wire.get(this.name)
                        : [this.$wire.get(this.name)];
                }

                // Tambahkan listener untuk file input
                this.$watch('files', () => {
                    if (this.files.length > 0) {
                        this.uploadFiles();
                    }
                });
            },

            uploadFiles() {
                if (this.files.length === 0) return;

                this.uploading = true;
                this.progress = 0;
                this.error = null;

                const formData = new FormData();

                // Tambahkan file ke FormData
                if (this.multiple) {
                    Array.from(this.files).forEach(file => {
                        formData.append('files[]', file);
                    });
                    formData.append('directory', this.directory);

                    // Upload multiple files
                    this.uploadMultipleFiles(formData);
                } else {
                    formData.append('file', this.files[0]);
                    formData.append('directory', this.directory);

                    // Upload single file
                    this.uploadSingleFile(formData);
                }
            },

            uploadSingleFile(formData) {
                fetch('/custom-upload', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'same-origin'
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Upload failed: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Update state
                        this.uploadedFiles = [data];
                        this.$wire.set(this.name, data.path);

                        // Reset state
                        this.uploading = false;
                        this.progress = 100;
                        this.files = [];

                        // Trigger event
                        this.$dispatch('file-uploaded', { name: this.name, value: data.path });
                    })
                    .catch(error => {
                        console.error('Upload error:', error);
                        this.error = error.message;
                        this.uploading = false;
                        this.progress = 0;
                    });
            },

            uploadMultipleFiles(formData) {
                fetch('/custom-upload-multiple', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'same-origin'
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Upload failed: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Update state
                        this.uploadedFiles = [...this.uploadedFiles, ...data.files];

                        // Extract paths
                        const paths = data.files.map(file => file.path);
                        this.$wire.set(this.name, [...(this.$wire.get(this.name) || []), ...paths]);

                        // Reset state
                        this.uploading = false;
                        this.progress = 100;
                        this.files = [];

                        // Trigger event
                        this.$dispatch('files-uploaded', { name: this.name, value: paths });
                    })
                    .catch(error => {
                        console.error('Upload error:', error);
                        this.error = error.message;
                        this.uploading = false;
                        this.progress = 0;
                    });
            },

            removeFile(index) {
                // Remove file from uploadedFiles
                const files = [...this.uploadedFiles];
                const removed = files.splice(index, 1)[0];
                this.uploadedFiles = files;

                // Update Livewire state
                if (this.multiple) {
                    const paths = this.uploadedFiles.map(file => file.path);
                    this.$wire.set(this.name, paths);
                } else {
                    this.$wire.set(this.name, null);
                }

                // Trigger event
                this.$dispatch('file-removed', { name: this.name, value: removed.path });
            }
        }));

        // Patch Filament's FileUpload component
        patchFilamentFileUpload();

        console.log('Custom file upload handler installed');
    });

    // Function to patch Filament's FileUpload component
    function patchFilamentFileUpload() {
        // Override Livewire's upload method to use our custom upload
        if (window.Livewire) {
            // Intercept file upload events
            document.addEventListener('livewire-upload-start', function (event) {
                // Prevent default Livewire upload behavior
                event.stopPropagation();
                event.preventDefault();
                console.log('Livewire upload intercepted');
            }, true);

            // Intercept Livewire file input initialization
            if (window.Alpine && window.Alpine.directive) {
                const originalFileUploadDirective = window.Alpine.directive('bind:livewire-upload');
                if (originalFileUploadDirective) {
                    window.Alpine.directive('bind:livewire-upload', (el, { value, modifiers, expression }, { evaluate }) => {
                        // Replace with our custom upload handler
                        el.setAttribute('x-data', 'customFileUpload(' + expression + ')');
                        el.setAttribute('@change', 'files = $event.target.files');
                    });
                }
            }

            // Disable Livewire's upload functionality
            if (window.Livewire._instance && window.Livewire._instance.upload) {
                const originalUpload = window.Livewire._instance.upload;
                window.Livewire._instance.upload = function () {
                    console.log('Livewire upload method intercepted');
                    // Do nothing, effectively disabling Livewire's upload
                    return;
                };
            }
        }
    }

    // Tambahkan route fallback untuk livewire/upload-file
    // Ini akan menangani kasus di mana interception tidak berhasil
    document.addEventListener('click', function (event) {
        const target = event.target;
        if (target.tagName === 'A' && target.href && target.href.includes('livewire/upload-file')) {
            event.preventDefault();
            console.log('Prevented navigation to Livewire upload URL');
        }
    }, true);
});
