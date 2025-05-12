// Script untuk memperbaiki masalah upload file Livewire
document.addEventListener('DOMContentLoaded', function() {
    // Override URL untuk upload file Livewire
    if (window.Livewire) {
        // Simpan referensi ke fungsi asli
        const originalGenerateSignedUrl = window.Livewire.hook('message.sent', (message, component) => {
            // Jika ini adalah permintaan untuk mendapatkan signed URL
            if (message.updateQueue && message.updateQueue.some(item => item.type === 'callMethod' && item.payload.method === '_startUpload')) {
                // Ganti URL upload dengan URL proxy kita
                window.livewireUploadFixUrl = '/admin/upload-file-proxy';
                
                // Tambahkan listener untuk mengganti URL
                window.addEventListener('livewire-upload-start', function() {
                    // Cari semua elemen dengan atribut data-url yang berisi /livewire/upload-file
                    const uploadElements = document.querySelectorAll('[data-url*="/livewire/upload-file"]');
                    uploadElements.forEach(el => {
                        // Ganti URL dengan URL proxy kita
                        el.dataset.url = window.livewireUploadFixUrl;
                    });
                }, true);
            }
        });
    }
});
