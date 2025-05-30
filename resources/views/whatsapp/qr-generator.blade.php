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
    
    <!-- Custom Styles -->
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-shadow {
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .countdown-text {
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header Card -->
        <div class="bg-white rounded-2xl card-shadow overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-6">
                <div class="flex items-center justify-center">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mr-4">
                        <span class="text-2xl font-bold text-blue-600">HM</span>
                    </div>
                    <div class="text-center">
                        <h1 class="text-3xl font-bold text-white">Hartono Motor</h1>
                        <p class="text-blue-100 text-lg">WhatsApp API QR Code Generator</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-2xl card-shadow overflow-hidden">
            <div class="px-8 py-6">
                <!-- Action Buttons -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <button 
                        id="generateBtn" 
                        onclick="generateFreshQR()" 
                        class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-4 px-6 rounded-xl transition duration-300 btn-hover focus:outline-none focus:ring-4 focus:ring-green-300"
                    >
                        <i class="fas fa-qrcode mr-3 text-xl"></i>
                        <span class="text-lg">Generate Fresh QR Code</span>
                    </button>
                    
                    <button 
                        id="statusBtn" 
                        onclick="checkStatus()" 
                        class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-4 px-6 rounded-xl transition duration-300 btn-hover focus:outline-none focus:ring-4 focus:ring-blue-300"
                    >
                        <i class="fas fa-mobile-alt mr-3 text-xl"></i>
                        <span class="text-lg">Check Connection Status</span>
                    </button>
                </div>

                <!-- Status Display -->
                <div id="status" class="mb-6"></div>

                <!-- QR Code Container -->
                <div id="qr-container" class="hidden">
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-8 text-center border-2 border-dashed border-gray-300">
                        <h3 class="text-2xl font-bold text-gray-800 mb-6">
                            <i class="fas fa-mobile-alt mr-3 text-green-500"></i>
                            Scan QR Code dengan WhatsApp Anda
                        </h3>
                        
                        <div class="flex justify-center mb-6">
                            <div class="relative">
                                <img id="qr-image" class="max-w-sm w-full h-auto rounded-2xl shadow-2xl border-4 border-white" src="" alt="QR Code">
                                <div class="absolute inset-0 rounded-2xl border-4 border-green-400 pulse-animation"></div>
                            </div>
                        </div>
                        
                        <div id="countdown" class="text-2xl font-bold text-red-500 mb-6 countdown-text"></div>
                        
                        <div id="qr-details" class="bg-white rounded-xl p-6 text-left border shadow-lg">
                            <h4 class="font-bold text-gray-800 mb-3 text-lg">
                                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                                Detail QR Code
                            </h4>
                            <div id="qr-details-content" class="text-sm text-gray-600"></div>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-2xl p-8 mt-8">
                    <h3 class="text-2xl font-bold text-yellow-800 mb-6">
                        <i class="fas fa-info-circle mr-3 text-yellow-600"></i>
                        Cara Menghubungkan WhatsApp
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <ol class="list-decimal list-inside text-yellow-700 space-y-3 text-lg">
                            <li class="flex items-start">
                                <span class="mr-3">1.</span>
                                <span>Klik tombol <strong>"Generate Fresh QR Code"</strong></span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-3">2.</span>
                                <span>Buka aplikasi <strong>WhatsApp</strong> di ponsel</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-3">3.</span>
                                <span>Masuk ke <strong>Settings → Linked Devices</strong></span>
                            </li>
                        </ol>
                        <ol class="list-decimal list-inside text-yellow-700 space-y-3 text-lg" start="4">
                            <li class="flex items-start">
                                <span class="mr-3">4.</span>
                                <span>Tap <strong>"Link a Device"</strong></span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-3">5.</span>
                                <span>Scan QR code yang ditampilkan</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-3">6.</span>
                                <span>Tunggu hingga koneksi berhasil</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-white text-lg opacity-80">
                <i class="fas fa-tools mr-2"></i>
                Hartono Motor - Solusi Terpercaya untuk Kendaraan Anda
            </p>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 flex items-center shadow-2xl">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mr-4"></div>
            <span class="text-gray-700 text-xl font-semibold">Memproses...</span>
        </div>
    </div>

    <script>
        let countdownTimer;
        
        // Setup CSRF token for all AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function showStatus(message, type = 'info') {
            const statusDiv = document.getElementById('status');
            const typeClasses = {
                info: 'bg-blue-50 border-blue-200 text-blue-800 border-l-4 border-l-blue-500',
                success: 'bg-green-50 border-green-200 text-green-800 border-l-4 border-l-green-500',
                error: 'bg-red-50 border-red-200 text-red-800 border-l-4 border-l-red-500',
                warning: 'bg-yellow-50 border-yellow-200 text-yellow-800 border-l-4 border-l-yellow-500'
            };
            
            const icons = {
                info: 'fa-info-circle',
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle'
            };
            
            statusDiv.innerHTML = `
                <div class="border rounded-xl p-6 ${typeClasses[type]}">
                    <div class="flex items-center">
                        <i class="fas ${icons[type]} mr-3 text-xl"></i>
                        <span class="text-lg font-semibold">${message}</span>
                    </div>
                </div>
            `;
        }

        function showLoading(show = true) {
            const overlay = document.getElementById('loading-overlay');
            overlay.classList.toggle('hidden', !show);
        }

        function showQR(qrUrl, duration, debugData = null) {
            const qrContainer = document.getElementById('qr-container');
            const qrImage = document.getElementById('qr-image');
            const qrDetailsContent = document.getElementById('qr-details-content');
            
            qrImage.src = qrUrl;
            qrContainer.classList.remove('hidden');
            
            showStatus(`✅ Fresh QR Code berhasil dibuat! Berlaku selama ${duration} detik.`, 'success');
            
            // Show QR details
            if (debugData) {
                const qrFileName = qrUrl.split('/').pop();
                qrDetailsContent.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <div><strong class="text-gray-800">📁 File:</strong> <span class="font-mono text-sm">${qrFileName}</span></div>
                            <div><strong class="text-gray-800">🔄 Fresh:</strong> <span class="text-green-600 font-semibold">${debugData.fresh ? 'Ya' : 'Tidak'}</span></div>
                            <div><strong class="text-gray-800">⏱️ Durasi:</strong> <span class="text-blue-600 font-semibold">${duration} detik</span></div>
                        </div>
                        <div class="space-y-2">
                            <div><strong class="text-gray-800">📅 Dibuat:</strong> <span class="text-sm">${debugData.generated_at || 'N/A'}</span></div>
                            <div><strong class="text-gray-800">⏰ Kadaluarsa:</strong> <span class="text-sm">${debugData.expires_at || 'N/A'}</span></div>
                            <div><strong class="text-gray-800">🚀 Waktu Proses:</strong> <span class="text-purple-600 font-semibold">${debugData.total_time_ms || 'N/A'}ms</span></div>
                        </div>
                    </div>
                `;
            }
            
            startCountdown(duration);
        }

        function startCountdown(duration) {
            clearInterval(countdownTimer);
            let timeLeft = duration;
            
            const countdownDiv = document.getElementById('countdown');
            
            countdownTimer = setInterval(() => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                countdownDiv.innerHTML = `
                    <i class="fas fa-clock mr-2"></i>
                    Kadaluarsa dalam: ${minutes}:${seconds.toString().padStart(2, '0')}
                `;
                
                if (timeLeft <= 10) {
                    countdownDiv.classList.add('pulse-animation');
                }
                
                if (timeLeft <= 0) {
                    clearInterval(countdownTimer);
                    countdownDiv.innerHTML = '<i class="fas fa-times-circle mr-2"></i>QR Code Kadaluarsa';
                    countdownDiv.classList.remove('pulse-animation');
                    showStatus('⚠️ QR Code sudah kadaluarsa. Silakan buat yang baru.', 'warning');
                }
                
                timeLeft--;
            }, 1000);
        }

        async function generateFreshQR() {
            const button = document.getElementById('generateBtn');
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i><span>Generating...</span>';
            showLoading(true);
            
            showStatus('🔄 Membuat Fresh QR Code...', 'info');
            
            try {
                const response = await fetch('{{ route("whatsapp.generate-qr") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                
                const result = await response.json();
                
                if (result.success && result.data.code === 'SUCCESS') {
                    showQR(result.data.results.qr_link, result.data.results.qr_duration, result.data.results);
                } else {
                    showStatus(`❌ Error: ${result.message || result.data?.message || 'Unknown error'}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Network Error: ${error.message}`, 'error');
                console.error('Error:', error);
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
                showLoading(false);
            }
        }

        async function checkStatus() {
            const button = document.getElementById('statusBtn');
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i><span>Checking...</span>';
            
            showStatus('🔍 Mengecek status koneksi WhatsApp...', 'info');
            
            try {
                const response = await fetch('{{ route("whatsapp.check-status") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                
                const result = await response.json();
                
                if (result.success && result.data.code === 'SUCCESS') {
                    const devices = result.data.results || [];
                    if (devices.length > 0) {
                        const deviceNames = devices.map(d => d.name || 'Unknown Device').join(', ');
                        showStatus(`✅ Terhubung! Perangkat: ${deviceNames}`, 'success');
                    } else {
                        showStatus('⚠️ Tidak ada perangkat yang terhubung. Silakan scan QR code untuk menghubungkan perangkat.', 'warning');
                    }
                } else {
                    showStatus(`❌ Status Error: ${result.message || result.data?.message || 'Unknown error'}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Network Error: ${error.message}`, 'error');
                console.error('Error:', error);
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }

        // Auto-check status on page load
        window.addEventListener('load', function() {
            showStatus('🌐 Siap untuk membuat QR code WhatsApp', 'info');
            setTimeout(checkStatus, 1000); // Delay 1 second for better UX
        });
    </script>
</body>
</html>
