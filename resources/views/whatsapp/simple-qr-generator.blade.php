<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'WhatsApp QR Generator - Hartono Motor' }}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen py-8 px-4">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-green-600 to-blue-600 px-8 py-6">
                <div class="flex items-center justify-center">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mr-4">
                        <span class="text-2xl font-bold text-green-600">HM</span>
                    </div>
                    <div class="text-center">
                        <h1 class="text-3xl font-bold text-white">Hartono Motor</h1>
                        <p class="text-green-100 text-lg">WhatsApp QR Generator (Simple Version)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="px-8 py-6">
                <!-- Action Buttons -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <button 
                        id="generateBtn" 
                        onclick="generateQR()" 
                        class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-4 px-6 rounded-xl transition duration-300 transform hover:scale-105"
                    >
                        <i class="fas fa-qrcode mr-3 text-xl"></i>
                        <span class="text-lg">Generate QR Code</span>
                    </button>
                    
                    <button 
                        id="statusBtn" 
                        onclick="checkStatus()" 
                        class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-4 px-6 rounded-xl transition duration-300 transform hover:scale-105"
                    >
                        <i class="fas fa-wifi mr-3 text-xl"></i>
                        <span class="text-lg">Check Status</span>
                    </button>
                </div>

                <!-- Status Display -->
                <div id="status" class="mb-6"></div>

                <!-- QR Code Container -->
                <div id="qr-container" class="hidden bg-gray-50 rounded-xl p-8 text-center">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-mobile-alt mr-2 text-green-500"></i>
                        Scan QR Code dengan WhatsApp
                    </h3>
                    <div id="qr-display" class="mb-4"></div>
                </div>

                <!-- Instructions -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-yellow-800 mb-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        Cara Menggunakan:
                    </h3>
                    <ol class="list-decimal list-inside text-yellow-700 space-y-2">
                        <li>Klik "Generate QR Code"</li>
                        <li>Buka WhatsApp di ponsel → Settings → Linked Devices</li>
                        <li>Tap "Link a Device" dan scan QR code</li>
                        <li>Tunggu hingga terhubung</li>
                    </ol>
                </div>

                <!-- Test Message -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-blue-800 mb-3">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Test Kirim Pesan:
                    </h3>
                    <div class="space-y-3">
                        <input 
                            type="text" 
                            id="testPhone" 
                            placeholder="Nomor HP (contoh: 628123456789)" 
                            class="w-full px-4 py-2 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <textarea 
                            id="testMessage" 
                            placeholder="Pesan test..." 
                            rows="3"
                            class="w-full px-4 py-2 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                        <button 
                            onclick="sendTestMessage()" 
                            class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-lg transition duration-300"
                        >
                            <i class="fas fa-send mr-2"></i>
                            Kirim Test
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Setup CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function showStatus(message, type = 'info') {
            const statusDiv = document.getElementById('status');
            const typeClasses = {
                info: 'bg-blue-50 border-blue-200 text-blue-800',
                success: 'bg-green-50 border-green-200 text-green-800',
                error: 'bg-red-50 border-red-200 text-red-800',
                warning: 'bg-yellow-50 border-yellow-200 text-yellow-800'
            };
            
            const icons = {
                info: 'fa-info-circle',
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle'
            };
            
            statusDiv.innerHTML = `
                <div class="border rounded-xl p-4 ${typeClasses[type]}">
                    <div class="flex items-center">
                        <i class="fas ${icons[type]} mr-3 text-xl"></i>
                        <span class="font-semibold">${message}</span>
                    </div>
                </div>
            `;
        }

        async function generateQR() {
            const button = document.getElementById('generateBtn');
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i><span>Generating...</span>';
            
            showStatus('🔄 Membuat QR Code...', 'info');
            
            try {
                const response = await fetch('{{ route("simple-whatsapp.generate-qr") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showStatus('✅ QR Code berhasil dibuat!', 'success');
                    
                    // Display QR code
                    const qrContainer = document.getElementById('qr-container');
                    const qrDisplay = document.getElementById('qr-display');
                    
                    qrDisplay.innerHTML = `<img src="${result.qr_code}" class="mx-auto max-w-xs rounded-lg shadow-lg">`;
                    qrContainer.classList.remove('hidden');
                } else {
                    showStatus(`❌ Error: ${result.message}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Network Error: ${error.message}`, 'error');
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }

        async function checkStatus() {
            const button = document.getElementById('statusBtn');
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i><span>Checking...</span>';
            
            showStatus('🔍 Mengecek status koneksi...', 'info');
            
            try {
                const response = await fetch('{{ route("simple-whatsapp.check-status") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (result.connected) {
                        showStatus('✅ WhatsApp terhubung!', 'success');
                    } else {
                        showStatus('⚠️ WhatsApp belum terhubung. Silakan scan QR code.', 'warning');
                    }
                } else {
                    showStatus(`❌ Error: ${result.message}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Network Error: ${error.message}`, 'error');
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }

        async function sendTestMessage() {
            const phone = document.getElementById('testPhone').value;
            const message = document.getElementById('testMessage').value;
            
            if (!phone || !message) {
                showStatus('⚠️ Mohon isi nomor HP dan pesan', 'warning');
                return;
            }
            
            showStatus('📤 Mengirim pesan test...', 'info');
            
            try {
                const response = await fetch('{{ route("simple-whatsapp.send-message") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        phone: phone,
                        message: message
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showStatus('✅ Pesan berhasil dikirim!', 'success');
                    document.getElementById('testPhone').value = '';
                    document.getElementById('testMessage').value = '';
                } else {
                    showStatus(`❌ Error: ${result.message}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Network Error: ${error.message}`, 'error');
            }
        }

        // Auto-check status on page load
        window.addEventListener('load', function() {
            showStatus('🌐 Siap untuk generate QR code WhatsApp', 'info');
            setTimeout(checkStatus, 1000);
        });
    </script>
</body>
</html>
