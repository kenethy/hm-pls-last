// Script untuk memperbaiki masalah upload file Livewire
document.addEventListener('DOMContentLoaded', function () {
    // Patch XMLHttpRequest untuk mengganti URL upload
    const originalOpen = XMLHttpRequest.prototype.open;

    XMLHttpRequest.prototype.open = function (method, url, async, user, password) {
        // Jika ini adalah request ke /livewire/upload-file
        if (typeof url === 'string' && url.includes('/livewire/upload-file')) {
            // Ganti dengan URL proxy kita
            url = '/admin/upload-file-proxy';
        }

        return originalOpen.call(this, method, url, async, user, password);
    };

    // Tambahkan listener untuk event upload
    document.addEventListener('livewire-upload-start', function (event) {
        console.log('Upload started, using proxy URL');
    }, true);

    console.log('Livewire upload fix loaded');
});
