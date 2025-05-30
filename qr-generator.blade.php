@extends('layouts.app')

@section('title', 'WhatsApp QR Code Generator')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-500 to-purple-600 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-6">
                <div class="flex items-center justify-center">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mr-4">
                        <span class="text-2xl font-bold text-blue-600">HM</span>
                    </div>
                    <div class="text-center">
                        <h1 class="text-2xl font-bold text-white">Hartono Motor</h1>
                        <p class="text-blue-100">WhatsApp API QR Code Generator</p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="px-8 py-6">
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <button 
                        id="generateBtn" 
                        onclick="generateFreshQR()" 
                        class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50"
                    >
                        <i class="fas fa-qrcode mr-2"></i>
                        Generate Fresh QR Code
                    </button>
                    
                    <button 
                        id="statusBtn" 
                        onclick="checkStatus()" 
                        class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
                    >
                        <i class="fas fa-mobile-alt mr-2"></i>
                        Check Connection Status
                    </button>
                </div>

                <!-- Status Display -->
                <div id="status" class="mb-6"></div>

                <!-- QR Code Container -->
                <div id="qr-container" class="hidden bg-gray-50 rounded-xl p-8 text-center">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">
                        <i class="fas fa-mobile-alt mr-2 text-green-500"></i>
                        Scan this QR Code with WhatsApp
                    </h3>
                    <div class="flex justify-center mb-4">
                        <img id="qr-image" class="max-w-xs w-full h-auto rounded-lg shadow-lg" src="" alt="QR Code">
                    </div>
                    <div id="countdown" class="text-lg font-bold text-red-500 mb-4"></div>
                    <div id="qr-details" class="bg-white rounded-lg p-4 text-left text-sm text-gray-600 border"></div>
                </div>

                <!-- Instructions -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-yellow-800 mb-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        Cara Menghubungkan WhatsApp:
                    </h3>
                    <ol class="list-decimal list-inside text-yellow-700 space-y-2">
                        <li>Klik tombol "Generate Fresh QR Code"</li>
                        <li>Buka WhatsApp di ponsel Anda</li>
                        <li>Masuk ke Settings → Linked Devices</li>
                        <li>Tap "Link a Device"</li>
                        <li>Scan QR code yang ditampilkan di atas</li>
                        <li>Tunggu hingga koneksi berhasil</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 flex items-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mr-3"></div>
        <span class="text-gray-700">Loading...</span>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endpush

@push('scripts')
<script>
    let countdownTimer;
    const API_BASE = '{{ $api_url }}';

    function showStatus(message, type = 'info') {
        const statusDiv = document.getElementById('status');
        const typeClasses = {
            info: 'bg-blue-50 border-blue-200 text-blue-800',
            success: 'bg-green-50 border-green-200 text-green-800',
            error: 'bg-red-50 border-red-200 text-red-800',
            warning: 'bg-yellow-50 border-yellow-200 text-yellow-800'
        };
        
        statusDiv.innerHTML = `
            <div class="border rounded-lg p-4 ${typeClasses[type]}">
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                    ${message}
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
        const qrDetails = document.getElementById('qr-details');
        
        qrImage.src = qrUrl;
        qrContainer.classList.remove('hidden');
        
        showStatus(`✅ Fresh QR Code berhasil dibuat! Berlaku selama ${duration} detik.`, 'success');
        
        // Show QR details
        if (debugData) {
            const qrFileName = qrUrl.split('/').pop();
            qrDetails.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div><strong>📁 File:</strong> ${qrFileName}</div>
                    <div><strong>🔄 Fresh:</strong> ${debugData.fresh ? 'Ya' : 'Tidak'}</div>
                    <div><strong>📅 Dibuat:</strong> ${debugData.generated_at || 'N/A'}</div>
                    <div><strong>⏰ Kadaluarsa:</strong> ${debugData.expires_at || 'N/A'}</div>
                    <div><strong>🚀 Waktu Proses:</strong> ${debugData.total_time_ms || 'N/A'}ms</div>
                    <div><strong>⏱️ Durasi:</strong> ${duration} detik</div>
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
            
            if (timeLeft <= 0) {
                clearInterval(countdownTimer);
                countdownDiv.innerHTML = '<i class="fas fa-times-circle mr-2"></i>QR Code Kadaluarsa';
                showStatus('⚠️ QR Code sudah kadaluarsa. Silakan buat yang baru.', 'warning');
            }
            
            timeLeft--;
        }, 1000);
    }

    async function generateFreshQR() {
        const button = document.getElementById('generateBtn');
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
        showLoading(true);
        
        showStatus('🔄 Membuat Fresh QR Code...', 'info');
        
        try {
            const response = await fetch('{{ route("whatsapp.generate-qr") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Checking...';
        
        showStatus('🔍 Mengecek status koneksi WhatsApp...', 'info');
        
        try {
            const response = await fetch('{{ route("whatsapp.check-status") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            const result = await response.json();
            
            if (result.success && result.data.code === 'SUCCESS') {
                const devices = result.data.results || [];
                if (devices.length > 0) {
                    showStatus(`✅ Terhubung! Perangkat: ${devices.map(d => d.name || 'Unknown Device').join(', ')}`, 'success');
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
        checkStatus();
    });
</script>
@endpush
