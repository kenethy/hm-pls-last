{{-- BACKUP CREATED: 2025-01-25 - Original homepage before spare parts enhancement --}}
@extends('layouts.main')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gray-900 text-white">
    <div class="absolute inset-0 overflow-hidden">
        <picture>
            <source srcset="{{ asset('images/hero-bg.webp') }}" type="image/webp">
            <source srcset="{{ asset('images/hero-bg.png') }}" type="image/png">
            <img src="{{ asset('images/hero-bg.png') }}" alt="Hartono Motor Workshop"
                class="w-full h-full object-cover opacity-40" style="object-position: center 30%;" fetchpriority="high"
                width="1920" height="1080">
        </picture>
    </div>
    <div class="container mx-auto px-4 py-24 relative z-10">
        <div class="max-w-4xl">
            <h1 class="text-4xl md:text-5xl font-bold mb-6 animate-fade-in">Hartono Motor: Bengkel Terpercaya & Toko
                Sparepart Terlengkap di Sidoarjo</h1>
            <p class="text-xl mb-8 animate-slide-up delay-200">Solusi lengkap untuk kendaraan Anda - dari servis
                profesional hingga sparepart berkualitas dengan harga terbaik.</p>

            <!-- Dual Business Highlight -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 animate-slide-up delay-300">
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 border border-white/20">
                    <div class="flex items-center mb-3">
                        <div class="bg-red-600 rounded-full p-2 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Bengkel Profesional</h3>
                    </div>
                    <p class="text-white/90">Servis berkala, tune up, AC, dan perbaikan dengan mekanik berpengalaman</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 border border-white/20">
                    <div class="flex items-center mb-3">
                        <div class="bg-red-600 rounded-full p-2 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Toko Sparepart</h3>
                    </div>
                    <p class="text-white/90">Oli mesin, kampas rem, kopling, busi, dan sparepart lengkap semua merek</p>
                </div>
            </div>

            <!-- Dual CTAs -->
            <div class="flex flex-wrap gap-4 animate-slide-up delay-400">
                <a href="{{ route('booking') }}"
                    class="bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-6 rounded-md transition-all duration-300 btn-animate flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Booking Servis
                </a>
                <a href="{{ route('spare-parts') }}"
                    class="bg-white hover:bg-gray-100 text-gray-900 font-medium py-3 px-6 rounded-md transition-all duration-300 btn-animate flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Beli Sparepart
                </a>
                <a href="{{ route('services') }}"
                    class="bg-transparent border-2 border-white hover:bg-white hover:text-gray-900 text-white font-medium py-3 px-6 rounded-md transition-all duration-300 btn-animate">
                    Lihat Semua Layanan
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Spare Parts Showcase Section -->
<section class="py-16 bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl font-bold mb-4">Sparepart Berkualitas dengan Harga Terbaik</h2>
            <p class="text-gray-600 max-w-3xl mx-auto">Kami menyediakan berbagai sparepart original dan aftermarket
                untuk semua merek mobil. Dapatkan produk berkualitas dengan harga kompetitif langsung dari toko kami.
            </p>
        </div>

        <!-- Featured Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
            <!-- Motor Oil -->
            <div
                class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up">
                <div class="relative">
                    <img src="{{ asset('images/sparepart/sparepart.png') }}" alt="Oli Mesin Berkualitas"
                        class="w-full h-48 object-cover">
                    <div class="absolute top-4 left-4">
                        <span class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full">BEST SELLER</span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-center mb-3">
                        <div class="bg-red-100 rounded-full p-2 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Oli Mesin</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Shell, Castrol, Mobil 1, dan berbagai merek oli berkualitas untuk
                        semua jenis mesin.</p>
                    <div class="flex items-center justify-between">
                        <span class="text-red-600 font-bold">Mulai Rp 85.000</span>
                        <div class="flex space-x-2">
                            <a href="https://www.tokopedia.com/hartono-m" target="_blank"
                                class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors"
                                title="Beli di Tokopedia">
                                <img src="{{ asset('images/marketplace/tokopedia.png') }}" alt="Tokopedia"
                                    class="h-5 w-5">
                            </a>
                            <a href="https://shopee.co.id/hartono_motor" target="_blank"
                                class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors"
                                title="Beli di Shopee">
                                <img src="{{ asset('images/marketplace/shopee.png') }}" alt="Shopee" class="h-5 w-5">
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Brake Pads -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up"
                style="transition-delay: 0.1s">
                <div class="relative">
                    <img src="{{ asset('images/sparepart/sparepart.png') }}" alt="Kampas Rem Berkualitas"
                        class="w-full h-48 object-cover">
                    <div class="absolute top-4 left-4">
                        <span class="bg-green-600 text-white text-xs font-bold px-3 py-1 rounded-full">ORIGINAL</span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-center mb-3">
                        <div class="bg-red-100 rounded-full p-2 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Kampas Rem</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Brembo, Bendix, dan merek terpercaya lainnya untuk keamanan pengereman
                        optimal.</p>
                    <div class="flex items-center justify-between">
                        <span class="text-red-600 font-bold">Mulai Rp 150.000</span>
                        <div class="flex space-x-2">
                            <a href="https://www.tokopedia.com/hartono-m" target="_blank"
                                class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors"
                                title="Beli di Tokopedia">
                                <img src="{{ asset('images/marketplace/tokopedia.png') }}" alt="Tokopedia"
                                    class="h-5 w-5">
                            </a>
                            <a href="https://shopee.co.id/hartono_motor" target="_blank"
                                class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors"
                                title="Beli di Shopee">
                                <img src="{{ asset('images/marketplace/shopee.png') }}" alt="Shopee" class="h-5 w-5">
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clutch Pads -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up"
                style="transition-delay: 0.2s">
                <div class="relative">
                    <img src="{{ asset('images/sparepart/sparepart.png') }}" alt="Kampas Kopling Berkualitas"
                        class="w-full h-48 object-cover">
                    <div class="absolute top-4 left-4">
                        <span class="bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full">PREMIUM</span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-center mb-3">
                        <div class="bg-red-100 rounded-full p-2 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Kampas Kopling</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Valeo, Exedy, dan merek berkualitas untuk transmisi manual yang halus
                        dan awet.</p>
                    <div class="flex items-center justify-between">
                        <span class="text-red-600 font-bold">Mulai Rp 200.000</span>
                        <div class="flex space-x-2">
                            <a href="https://www.tokopedia.com/hartono-m" target="_blank"
                                class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors"
                                title="Beli di Tokopedia">
                                <img src="{{ asset('images/marketplace/tokopedia.png') }}" alt="Tokopedia"
                                    class="h-5 w-5">
                            </a>
                            <a href="https://shopee.co.id/hartono_motor" target="_blank"
                                class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors"
                                title="Beli di Shopee">
                                <img src="{{ asset('images/marketplace/shopee.png') }}" alt="Shopee" class="h-5 w-5">
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Spark Plugs -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up"
                style="transition-delay: 0.3s">
                <div class="relative">
                    <img src="{{ asset('images/sparepart/sparepart.png') }}" alt="Busi Berkualitas"
                        class="w-full h-48 object-cover">
                    <div class="absolute top-4 left-4">
                        <span class="bg-purple-600 text-white text-xs font-bold px-3 py-1 rounded-full">HOT ITEM</span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-center mb-3">
                        <div class="bg-red-100 rounded-full p-2 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Busi</h3>
                    </div>
                    <p class="text-gray-600 mb-4">NGK, Denso, Bosch untuk performa mesin optimal dan konsumsi BBM
                        efisien.</p>
                    <div class="flex items-center justify-between">
                        <span class="text-red-600 font-bold">Mulai Rp 25.000</span>
                        <div class="flex space-x-2">
                            <a href="https://www.tokopedia.com/hartono-m" target="_blank"
                                class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors"
                                title="Beli di Tokopedia">
                                <img src="{{ asset('images/marketplace/tokopedia.png') }}" alt="Tokopedia"
                                    class="h-5 w-5">
                            </a>
                            <a href="https://shopee.co.id/hartono_motor" target="_blank"
                                class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors"
                                title="Beli di Shopee">
                                <img src="{{ asset('images/marketplace/shopee.png') }}" alt="Shopee" class="h-5 w-5">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Product Categories -->
        <div class="bg-white rounded-xl shadow-lg p-8 reveal">
            <h3 class="text-2xl font-bold text-center mb-6">Kategori Sparepart Lainnya</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <div class="text-center group cursor-pointer">
                    <div
                        class="bg-gray-100 group-hover:bg-red-100 rounded-full p-4 mx-auto mb-3 w-16 h-16 flex items-center justify-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-600 group-hover:text-red-600"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-red-600">Filter</span>
                </div>
                <div class="text-center group cursor-pointer">
                    <div
                        class="bg-gray-100 group-hover:bg-red-100 rounded-full p-4 mx-auto mb-3 w-16 h-16 flex items-center justify-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-600 group-hover:text-red-600"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-red-600">Aki</span>
                </div>
                <div class="text-center group cursor-pointer">
                    <div
                        class="bg-gray-100 group-hover:bg-red-100 rounded-full p-4 mx-auto mb-3 w-16 h-16 flex items-center justify-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-600 group-hover:text-red-600"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-red-600">Suspensi</span>
                </div>
                <div class="text-center group cursor-pointer">
                    <div
                        class="bg-gray-100 group-hover:bg-red-100 rounded-full p-4 mx-auto mb-3 w-16 h-16 flex items-center justify-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-600 group-hover:text-red-600"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-red-600">Radiator</span>
                </div>
                <div class="text-center group cursor-pointer">
                    <div
                        class="bg-gray-100 group-hover:bg-red-100 rounded-full p-4 mx-auto mb-3 w-16 h-16 flex items-center justify-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-600 group-hover:text-red-600"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-red-600">Aksesoris</span>
                </div>
                <div class="text-center group cursor-pointer">
                    <div
                        class="bg-gray-100 group-hover:bg-red-100 rounded-full p-4 mx-auto mb-3 w-16 h-16 flex items-center justify-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-600 group-hover:text-red-600"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-red-600">Lainnya</span>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="text-center mt-12 reveal">
            <div class="bg-gradient-to-r from-red-600 to-red-700 rounded-xl p-8 text-white">
                <h3 class="text-2xl font-bold mb-4">Butuh Sparepart Khusus?</h3>
                <p class="mb-6 opacity-90">Tidak menemukan sparepart yang Anda cari? Tim kami siap membantu mencari dan
                    menyediakan sparepart sesuai kebutuhan kendaraan Anda.</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="{{ route('spare-parts') }}"
                        class="bg-white text-red-600 hover:bg-gray-100 font-medium py-3 px-6 rounded-md transition-colors">
                        Lihat Semua Produk
                    </a>
                    <a href="https://wa.me/6282135202581" target="_blank"
                        class="bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-6 rounded-md transition-colors flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                        </svg>
                        Konsultasi WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Layanan Unggulan Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl font-bold mb-4">Layanan Servis Profesional</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Bengkel terpercaya dengan mekanik berpengalaman dan peralatan
                modern untuk menjaga kendaraan Anda tetap prima dan aman di jalan.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Service 1 -->
            <div class="bg-gray-50 p-6 rounded-lg text-center hover-lift reveal-up">
                <div
                    class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse-subtle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Servis Berkala</h3>
                <p class="text-gray-600">Perawatan rutin untuk menjaga performa dan memperpanjang usia kendaraan Anda.
                </p>
            </div>

            <!-- Service 2 -->
            <div class="bg-gray-50 p-6 rounded-lg text-center hover-lift reveal-up" style="transition-delay: 0.1s">
                <div
                    class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse-subtle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Tune Up Mesin</h3>
                <p class="text-gray-600">Optimalkan performa mesin dengan penyetelan dan perawatan komprehensif.</p>
            </div>

            <!-- Service 3 -->
            <div class="bg-gray-50 p-6 rounded-lg text-center hover-lift reveal-up" style="transition-delay: 0.2s">
                <div
                    class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse-subtle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Servis AC</h3>
                <p class="text-gray-600">Perbaikan dan perawatan sistem AC untuk kenyamanan berkendara Anda.</p>
            </div>

            <!-- Service 4 -->
            <div class="bg-gray-50 p-6 rounded-lg text-center hover-lift reveal-up" style="transition-delay: 0.3s">
                <div
                    class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse-subtle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Ganti Oli</h3>
                <p class="text-gray-600">Penggantian oli berkualitas untuk menjaga kesehatan mesin kendaraan Anda.</p>
            </div>
        </div>

        <div class="text-center mt-10 reveal">
            <a href="{{ route('services') }}"
                class="inline-block bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-6 rounded-md transition-colors btn-animate">Lihat
                Semua Layanan</a>
        </div>
    </div>
</section>

<!-- Keunggulan Section -->
<section class="py-16 bg-gray-100">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Mengapa Memilih Hartono Motor?</h2>
            <p class="text-gray-600 max-w-3xl mx-auto">Sebagai bengkel dan toko sparepart terpercaya, kami berkomitmen
                memberikan solusi lengkap untuk kendaraan Anda dengan standar kualitas tinggi dan pelayanan terbaik.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
            <!-- USP 1 -->
            <div class="flex flex-col items-center">
                <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center shadow-md mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2 text-center">Toko Sparepart Terlengkap</h3>
                <p class="text-gray-600 text-center">Oli mesin, kampas rem, kopling, busi, dan ribuan sparepart original
                    & aftermarket untuk semua merek mobil.</p>
            </div>

            <!-- USP 2 -->
            <div class="flex flex-col items-center">
                <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center shadow-md mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2 text-center">Mekanik Berpengalaman</h3>
                <p class="text-gray-600 text-center">Tim teknisi profesional dengan pengalaman dan sertifikasi di
                    bidangnya.</p>
            </div>

            <!-- USP 3 -->
            <div class="flex flex-col items-center">
                <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center shadow-md mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2 text-center">Layanan Cepat & Profesional</h3>
                <p class="text-gray-600 text-center">Penanganan cepat dan efisien dengan hasil yang memuaskan.</p>
            </div>

            <!-- USP 4 -->
            <div class="flex flex-col items-center">
                <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center shadow-md mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2 text-center">Garansi Servis</h3>
                <p class="text-gray-600 text-center">Memberikan jaminan kualitas untuk setiap pekerjaan yang kami
                    lakukan.</p>
            </div>

            <!-- USP 5: Transparansi & Komunikasi -->
            <div class="flex flex-col items-center">
                <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center shadow-md mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2 text-center">Transparansi & Komunikasi</h3>
                <p class="text-gray-600 text-center">Informasi jelas tentang progres servis dan biaya kepada pelanggan.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Transparansi & Komunikasi Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="order-2 lg:order-1">
                <div class="reveal">
                    <h2 class="text-3xl font-bold mb-4">Transparansi & Komunikasi dengan Pelanggan</h2>
                    <p class="text-gray-600 mb-6">Kami percaya bahwa transparansi dan komunikasi yang baik adalah kunci
                        kepuasan pelanggan. Dengan Hartono Motor, Anda akan selalu mendapatkan:</p>

                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="bg-red-100 rounded-full p-2 mr-3 mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">Informasi Progres Servis</h3>
                                <p class="text-gray-600">Kami akan menginformasikan setiap tahapan servis kendaraan Anda
                                    melalui WhatsApp, lengkap dengan foto dan penjelasan.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="bg-red-100 rounded-full p-2 mr-3 mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">Estimasi Biaya yang Jelas</h3>
                                <p class="text-gray-600">Sebelum memulai pekerjaan, kami akan memberikan estimasi biaya
                                    yang transparan dan tidak ada biaya tersembunyi.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="bg-red-100 rounded-full p-2 mr-3 mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">Konsultasi Teknis</h3>
                                <p class="text-gray-600">Mekanik kami akan menjelaskan masalah dan solusi dengan bahasa
                                    yang mudah dipahami, serta memberikan rekomendasi terbaik.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="bg-red-100 rounded-full p-2 mr-3 mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">Persetujuan Sebelum Tindakan</h3>
                                <p class="text-gray-600">Kami tidak akan melakukan pekerjaan tambahan tanpa persetujuan
                                    Anda terlebih dahulu.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <a href="{{ route('booking') }}"
                            class="inline-block bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-6 rounded-md transition-colors btn-animate">Booking
                            Servis Sekarang</a>
                    </div>
                </div>
            </div>

            <div class="order-1 lg:order-2 reveal-up">
                <picture>
                    <source srcset="{{ asset('images/hero-bg.webp') }}" type="image/webp">
                    <source srcset="{{ asset('images/hero-bg.png') }}" type="image/png">
                    <img src="{{ asset('images/kami/kami.jpg') }}" alt="Transparansi & Komunikasi Hartono Motor"
                        class="rounded-lg shadow-lg w-full h-auto object-cover" loading="lazy" width="800" height="600">
                </picture>
            </div>
        </div>
    </div>
</section>

<!-- Promo Section -->
<x-promos.home-section :featuredPromos="$featuredPromos" :endingSoonPromos="$endingSoonPromos" />

<!-- Testimonials Section -->
<section class="py-16 bg-gray-100">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4 reveal">Testimoni Pelanggan</h2>
            <p class="text-gray-600 max-w-2xl mx-auto reveal-up">Apa kata pelanggan tentang layanan kami.</p>
        </div>

        <!-- Include the modern testimonial carousel component -->
        @include('components.testimonial-carousel-modern')
    </div>
</section>

<!-- Marketplace & Social Media Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Belanja Sparepart Online & Ikuti Media Sosial Kami</h2>
            <p class="text-gray-600 max-w-3xl mx-auto">Dapatkan sparepart berkualitas dengan harga terbaik di
                marketplace terpercaya. Ikuti juga media sosial kami untuk tips perawatan mobil dan promo menarik.</p>
        </div>

        <!-- Marketplace Section -->
        <div class="mb-12">
            <h3 class="text-xl font-bold text-center mb-6 text-red-600">Belanja Sparepart Online</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <a href="https://www.tokopedia.com/hartono-m" target="_blank"
                    class="flex flex-col items-center p-6 rounded-xl bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <img src="{{ asset('images/marketplace/tokopedia.png') }}" alt="Tokopedia"
                        class="h-16 w-16 object-contain mb-3" loading="lazy">
                    <span class="font-bold text-gray-900 mb-1">Tokopedia</span>
                    <span class="text-sm text-gray-600 text-center">Sparepart lengkap dengan pengiriman cepat</span>
                </a>

                <a href="https://shopee.co.id/hartono_motor" target="_blank"
                    class="flex flex-col items-center p-6 rounded-xl bg-gradient-to-br from-orange-50 to-orange-100 hover:from-orange-100 hover:to-orange-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <img src="{{ asset('images/marketplace/shopee.png') }}" alt="Shopee"
                        class="h-16 w-16 object-contain mb-3" loading="lazy">
                    <span class="font-bold text-gray-900 mb-1">Shopee</span>
                    <span class="text-sm text-gray-600 text-center">Promo menarik dan cashback</span>
                </a>

                <a href="https://www.lazada.co.id/shop/hartono-motor-sidoarjo/?spm=a2o4j.pdp_revamp.seller.1.3efb7b46zHzsFf&itemId=8407228578&channelSource=pdp"
                    target="_blank"
                    class="flex flex-col items-center p-6 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <img src="{{ asset('images/marketplace/lazada.png') }}" alt="Lazada"
                        class="h-16 w-16 object-contain mb-3" loading="lazy">
                    <span class="font-bold text-gray-900 mb-1">Lazada</span>
                    <span class="text-sm text-gray-600 text-center">Kualitas terjamin dan bergaransi</span>
                </a>
            </div>
        </div>

        <!-- Social Media Section -->
        <div>
            <h3 class="text-xl font-bold text-center mb-6 text-red-600">Ikuti Media Sosial Kami</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <a href="https://instagram.com/hartonomotorsidoarjo" target="_blank"
                    class="flex flex-col items-center p-6 rounded-xl bg-gradient-to-br from-pink-50 to-purple-100 hover:from-pink-100 hover:to-purple-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <img src="{{ asset('images/marketplace/instagram.png') }}" alt="Instagram"
                        class="h-16 w-16 object-contain mb-3" loading="lazy">
                    <span class="font-bold text-gray-900 mb-1">Instagram</span>
                    <span class="text-sm text-gray-600 text-center">Tips perawatan & foto hasil servis</span>
                </a>

                <a href="https://www.facebook.com/hartonomotorsidoarjo" target="_blank"
                    class="flex flex-col items-center p-6 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-100 hover:from-blue-100 hover:to-indigo-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <img src="{{ asset('images/marketplace/facebook.png') }}" alt="Facebook"
                        class="h-16 w-16 object-contain mb-3" loading="lazy">
                    <span class="font-bold text-gray-900 mb-1">Facebook</span>
                    <span class="text-sm text-gray-600 text-center">Komunitas & diskusi otomotif</span>
                </a>

                <a href="https://www.tiktok.com/@hartonomotorsidoarjo" target="_blank"
                    class="flex flex-col items-center p-6 rounded-xl bg-gradient-to-br from-gray-50 to-gray-100 hover:from-gray-100 hover:to-gray-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <img src="{{ asset('images/marketplace/tiktok.png') }}" alt="TikTok"
                        class="h-16 w-16 object-contain mb-3" loading="lazy">
                    <span class="font-bold text-gray-900 mb-1">TikTok</span>
                    <span class="text-sm text-gray-600 text-center">Video tutorial & tips singkat</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Map & Contact Section -->
<section class="py-16 bg-gray-100">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Lokasi & Kontak</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Kunjungi bengkel kami atau hubungi kami untuk informasi lebih
                lanjut.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Map -->
            <div class="rounded-lg overflow-hidden shadow-md h-96">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d43371.58621648754!2d112.68514490118963!3d-7.4377883769144715!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7e6d2846c1cd3%3A0x15b5e7e7d101e4c3!2sHARTONO%20MOTOR%20Bengkel%20Mobil%2FSparepart%20Onderdil!5e0!3m2!1sid!2sid!4v1746187797124!5m2!1sid!2sid"
                    width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>

            <!-- Contact Info -->
            <div class="bg-white p-8 rounded-lg shadow-md">
                <h3 class="text-2xl font-bold mb-6">Informasi Kontak</h3>

                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="bg-red-100 rounded-full p-3 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg mb-1">Alamat</h4>
                            <p class="text-gray-600">Jl. Samanhudi No 2, Kebonsari, Sidoarjo
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="bg-red-100 rounded-full p-3 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg mb-1">Telepon</h4>
                            <p class="text-gray-600">+62 821 3520 2581</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="bg-red-100 rounded-full p-3 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg mb-1">Email</h4>
                            <p class="text-gray-600">hartonomotor1979@gmail.com
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="bg-red-100 rounded-full p-3 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg mb-1">Jam Operasional</h4>
                            <p class="text-gray-600">Senin - Sabtu: 08.00 - 16.00</p>
                            <p class="text-gray-600">Minggu: 08.00 - 14.00</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <a href="{{ route('contact') }}"
                        class="inline-block bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-6 rounded-md transition-colors">Hubungi
                        Kami</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection