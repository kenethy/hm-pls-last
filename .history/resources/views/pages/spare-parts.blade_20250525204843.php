{{-- BACKUP CREATED: 2025-01-25 - Enhanced Hybrid Approach Implementation --}}
@extends('layouts.main')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gray-900 text-white py-20">
    <div class="absolute inset-0 overflow-hidden">
        <picture>
            <source srcset="{{ asset('images/sparepart/sparepart.webp') }}" type="image/webp">
            <source srcset="{{ asset('images/sparepart/sparepart.png') }}" type="image/png">
            <img src="{{ asset('images/sparepart/sparepart.png') }}" alt="Sparepart Hartono Motor"
                class="w-full h-full object-cover opacity-40" fetchpriority="high">
        </picture>
    </div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl">
            <h1 class="text-4xl md:text-5xl font-bold mb-6 animate-fade-in">Toko Sparepart Mobil Terlengkap di Sidoarjo
            </h1>
            <p class="text-xl mb-8 animate-slide-up delay-200">Ribuan sparepart original dan aftermarket untuk semua
                merek mobil. Oli mesin, kampas rem, kopling, busi, dan komponen berkualitas dengan harga terbaik.</p>

            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap gap-4 animate-slide-up delay-400">
                <a href="#produk-unggulan"
                    class="bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-6 rounded-md transition-all duration-300 btn-animate flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Lihat Produk Unggulan
                </a>
                <a href="https://wa.me/6282135202581?text=Halo%20Hartono%20Motor,%20saya%20butuh%20bantuan%20mencari%20sparepart%20untuk%20mobil%20saya.%20Mohon%20informasi%20ketersediaan%20dan%20harga.%20Terima%20kasih!"
                    target="_blank"
                    class="bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-6 rounded-md transition-all duration-300 btn-animate flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path
                            d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                    </svg>
                    Konsultasi WhatsApp
                </a>
                <a href="#kategori"
                    class="bg-transparent border-2 border-white hover:bg-white hover:text-gray-900 text-white font-medium py-3 px-6 rounded-md transition-all duration-300 btn-animate">
                    Jelajahi Kategori
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section id="kategori" class="py-16 bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl font-bold mb-4">Kategori Sparepart Lengkap</h2>
            <p class="text-gray-600 max-w-3xl mx-auto">Jelajahi ribuan produk sparepart berkualitas yang tersedia di
                toko kami. Dari komponen mesin hingga aksesoris, semua tersedia dengan harga kompetitif.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            <!-- Category 1: Mesin -->
            <a href="#mesin"
                class="group bg-white rounded-xl p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up">
                <div
                    class="bg-red-100 group-hover:bg-red-200 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="font-bold text-lg group-hover:text-red-600 transition-colors">Mesin</h3>
                <p class="text-sm text-gray-500 mt-2">Filter, Busi, Timing Belt</p>
            </a>

            <!-- Category 2: Rem -->
            <a href="#rem"
                class="group bg-white rounded-xl p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up"
                style="transition-delay: 0.1s">
                <div
                    class="bg-red-100 group-hover:bg-red-200 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="font-bold text-lg group-hover:text-red-600 transition-colors">Sistem Rem</h3>
                <p class="text-sm text-gray-500 mt-2">Kampas Rem, Cakram, Minyak Rem</p>
            </a>

            <!-- Category 3: Suspensi -->
            <a href="#suspensi"
                class="group bg-white rounded-xl p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up"
                style="transition-delay: 0.2s">
                <div
                    class="bg-red-100 group-hover:bg-red-200 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                </div>
                <h3 class="font-bold text-lg group-hover:text-red-600 transition-colors">Suspensi</h3>
                <p class="text-sm text-gray-500 mt-2">Shock Absorber, Per, Bushing</p>
            </a>

            <!-- Category 4: Elektrikal -->
            <a href="#elektrikal"
                class="group bg-white rounded-xl p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up"
                style="transition-delay: 0.3s">
                <div
                    class="bg-red-100 group-hover:bg-red-200 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="font-bold text-lg group-hover:text-red-600 transition-colors">Elektrikal</h3>
                <p class="text-sm text-gray-500 mt-2">Aki, Alternator, Starter</p>
            </a>

            <!-- Category 5: Oli & Cairan -->
            <a href="#oli"
                class="group bg-white rounded-xl p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up"
                style="transition-delay: 0.4s">
                <div
                    class="bg-red-100 group-hover:bg-red-200 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <h3 class="font-bold text-lg group-hover:text-red-600 transition-colors">Oli & Cairan</h3>
                <p class="text-sm text-gray-500 mt-2">Oli Mesin, Coolant, Power Steering</p>
            </a>

            <!-- Category 6: Aksesoris -->
            <a href="#aksesoris"
                class="group bg-white rounded-xl p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up"
                style="transition-delay: 0.5s">
                <div
                    class="bg-red-100 group-hover:bg-red-200 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
                <h3 class="font-bold text-lg group-hover:text-red-600 transition-colors">Aksesoris</h3>
                <p class="text-sm text-gray-500 mt-2">Lampu, Klakson, Kaca Film</p>
            </a>
        </div>

        <!-- Quick Help Section -->
        <div class="mt-12 text-center reveal">
            <div class="bg-white rounded-xl shadow-lg p-8 max-w-2xl mx-auto">
                <h3 class="text-xl font-bold mb-4">Tidak Menemukan Kategori yang Anda Cari?</h3>
                <p class="text-gray-600 mb-6">Tim ahli kami siap membantu mencari sparepart apapun untuk kendaraan Anda.
                    Konsultasi gratis via WhatsApp!</p>
                <a href="https://wa.me/6282135202581?text=Halo%20Hartono%20Motor,%20saya%20butuh%20bantuan%20mencari%20sparepart%20khusus.%20Mohon%20bantuannya.%20Terima%20kasih!"
                    target="_blank"
                    class="inline-flex items-center bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-6 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path
                            d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                    </svg>
                    Konsultasi Sparepart Khusus
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section id="produk-unggulan" class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl font-bold mb-4">Produk Sparepart Unggulan</h2>
            <p class="text-gray-600 max-w-3xl mx-auto">Koleksi sparepart terlaris dengan kualitas terjamin dan harga
                terbaik. Semua produk tersedia di toko fisik dan marketplace online kami.</p>

            <!-- Trust Indicators -->
            <div class="flex flex-wrap justify-center gap-6 mt-8">
                <div class="flex items-center text-sm text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 mr-2" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Produk Original & Bergaransi
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Pengiriman Cepat
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 mr-2" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    Konsultasi Gratis
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Product 1: Oli Mesin -->
            <div
                class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up">
                <div class="relative">
                    <img src="{{ asset('images/sparepart/sparepart.png') }}" alt="Oli Mesin Berkualitas"
                        class="w-full h-48 object-cover">
                    <div class="absolute top-4 left-4">
                        <span class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full">BEST SELLER</span>
                    </div>
                    <div class="absolute top-4 right-4">
                        <span class="bg-green-600 text-white text-xs font-bold px-3 py-1 rounded-full">ORIGINAL</span>
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
                        <div>
                            <span
                                class="inline-block bg-blue-100 text-blue-600 text-xs font-medium px-2 py-1 rounded-full mb-1">Oli
                                & Cairan</span>
                            <h3 class="text-xl font-bold">Oli Mesin Premium</h3>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">Shell, Castrol, Mobil 1, dan berbagai merek oli berkualitas. Cocok
                        untuk semua jenis mesin bensin dan diesel.</p>

                    <!-- Specifications -->
                    <div class="bg-gray-50 rounded-lg p-3 mb-4">
                        <h4 class="font-semibold text-sm mb-2">Spesifikasi:</h4>
                        <ul class="text-xs text-gray-600 space-y-1">
                            <li>• Viskositas: 5W-30, 5W-40, 10W-40</li>
                            <li>• Kemasan: 1L, 4L, 5L</li>
                            <li>• Cocok untuk: Semua merek mobil</li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <span class="text-red-600 font-bold text-lg">Mulai Rp 85.000</span>
                            <p class="text-xs text-gray-500">*Harga bervariasi per merek</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <!-- WhatsApp Inquiry -->
                        <button onclick="askAboutProduct('Oli Mesin Premium', 'Berbagai merek dan viskositas tersedia')"
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                            </svg>
                            Tanya Produk Ini
                        </button>

                        <!-- Marketplace Links -->
                        <div class="flex space-x-2">
                            <a href="https://www.tokopedia.com/hartono-m" target="_blank"
                                class="flex-1 bg-green-100 hover:bg-green-200 text-green-700 font-medium py-2 px-3 rounded-lg transition-colors text-center text-sm">
                                Tokopedia
                            </a>
                            <a href="https://shopee.co.id/hartono_motor" target="_blank"
                                class="flex-1 bg-orange-100 hover:bg-orange-200 text-orange-700 font-medium py-2 px-3 rounded-lg transition-colors text-center text-sm">
                                Shopee
                            </a>
                            <a href="https://www.lazada.co.id/shop/hartono-motor-sidoarjo" target="_blank"
                                class="flex-1 bg-blue-100 hover:bg-blue-200 text-blue-700 font-medium py-2 px-3 rounded-lg transition-colors text-center text-sm">
                                Lazada
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product 2: Kampas Rem -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up"
                style="transition-delay: 0.1s">
                <div class="relative">
                    <img src="{{ asset('images/sparepart/sparepart.png') }}" alt="Kampas Rem Berkualitas"
                        class="w-full h-48 object-cover">
                    <div class="absolute top-4 left-4">
                        <span class="bg-green-600 text-white text-xs font-bold px-3 py-1 rounded-full">ORIGINAL</span>
                    </div>
                    <div class="absolute top-4 right-4">
                        <span class="bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full">PREMIUM</span>
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
                        <div>
                            <span
                                class="inline-block bg-red-100 text-red-600 text-xs font-medium px-2 py-1 rounded-full mb-1">Sistem
                                Rem</span>
                            <h3 class="text-xl font-bold">Kampas Rem</h3>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">Brembo, Bendix, dan merek terpercaya lainnya. Keamanan pengereman
                        optimal untuk semua jenis kendaraan.</p>

                    <!-- Specifications -->
                    <div class="bg-gray-50 rounded-lg p-3 mb-4">
                        <h4 class="font-semibold text-sm mb-2">Spesifikasi:</h4>
                        <ul class="text-xs text-gray-600 space-y-1">
                            <li>• Tipe: Depan & Belakang</li>
                            <li>• Material: Ceramic, Semi-Metallic</li>
                            <li>• Cocok untuk: Semua merek mobil</li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <span class="text-red-600 font-bold text-lg">Mulai Rp 150.000</span>
                            <p class="text-xs text-gray-500">*Harga per set</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <!-- WhatsApp Inquiry -->
                        <button onclick="askAboutProduct('Kampas Rem', 'Tersedia untuk semua merek mobil')"
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                            </svg>
                            Tanya Produk Ini
                        </button>

                        <!-- Marketplace Links -->
                        <div class="flex space-x-2">
                            <a href="https://www.tokopedia.com/hartono-m" target="_blank"
                                class="flex-1 bg-green-100 hover:bg-green-200 text-green-700 font-medium py-2 px-3 rounded-lg transition-colors text-center text-sm">
                                Tokopedia
                            </a>
                            <a href="https://shopee.co.id/hartono_motor" target="_blank"
                                class="flex-1 bg-orange-100 hover:bg-orange-200 text-orange-700 font-medium py-2 px-3 rounded-lg transition-colors text-center text-sm">
                                Shopee
                            </a>
                            <a href="https://www.lazada.co.id/shop/hartono-motor-sidoarjo" target="_blank"
                                class="flex-1 bg-blue-100 hover:bg-blue-200 text-blue-700 font-medium py-2 px-3 rounded-lg transition-colors text-center text-sm">
                                Lazada
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product 3 -->
            <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                <img src="{{ asset('images/product-3.jpg') }}" alt="Kampas Rem" class="w-full h-48 object-cover">
                <div class="p-6">
                    <span
                        class="inline-block bg-red-100 text-red-600 text-xs font-medium px-2 py-1 rounded-full mb-2">Rem</span>
                    <h3 class="text-xl font-bold mb-2">Brembo Brake Pad</h3>
                    <p class="text-gray-600 mb-4">Kampas rem berkualitas tinggi untuk pengereman optimal dan aman.</p>
                    <div class="flex justify-between items-center">
                        <span class="text-red-600 font-bold">Rp 750.000</span>
                        <div class="flex space-x-2">
                            <a href="https://www.tokopedia.com/hartonomotor" target="_blank"
                                class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors">
                                <img src="{{ asset('images/tokopedia-icon.png') }}" alt="Tokopedia" class="h-6 w-6">
                            </a>
                            <a href="https://shopee.co.id/hartonomotor" target="_blank"
                                class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors">
                                <img src="{{ asset('images/shopee-icon.png') }}" alt="Shopee" class="h-6 w-6">
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product 4 -->
            <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                <img src="{{ asset('images/product-4.jpg') }}" alt="Filter Udara" class="w-full h-48 object-cover">
                <div class="p-6">
                    <span
                        class="inline-block bg-red-100 text-red-600 text-xs font-medium px-2 py-1 rounded-full mb-2">Mesin</span>
                    <h3 class="text-xl font-bold mb-2">K&N Air Filter</h3>
                    <p class="text-gray-600 mb-4">Filter udara performa tinggi untuk aliran udara optimal dan performa
                        mesin lebih baik.</p>
                    <div class="flex justify-between items-center">
                        <span class="text-red-600 font-bold">Rp 650.000</span>
                        <div class="flex space-x-2">
                            <a href="https://www.tokopedia.com/hartonomotor" target="_blank"
                                class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors">
                                <img src="{{ asset('images/tokopedia-icon.png') }}" alt="Tokopedia" class="h-6 w-6">
                            </a>
                            <a href="https://shopee.co.id/hartonomotor" target="_blank"
                                class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors">
                                <img src="{{ asset('images/shopee-icon.png') }}" alt="Shopee" class="h-6 w-6">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-10">
            <a href="https://www.tokopedia.com/hartonomotor" target="_blank"
                class="inline-block bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-6 rounded-md transition-colors">Lihat
                Semua Produk</a>
        </div>
    </div>
</section>

<!-- Brands Section -->
<section id="rem" class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Merek Terpercaya</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Kami bekerja sama dengan merek-merek terpercaya untuk menjamin
                kualitas produk.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-8">
            <div class="flex items-center justify-center p-4">
                <img src="{{ asset('images/brand-1.png') }}" alt="Brand 1"
                    class="h-16 opacity-70 hover:opacity-100 transition-opacity">
            </div>
            <div class="flex items-center justify-center p-4">
                <img src="{{ asset('images/brand-2.png') }}" alt="Brand 2"
                    class="h-16 opacity-70 hover:opacity-100 transition-opacity">
            </div>
            <div class="flex items-center justify-center p-4">
                <img src="{{ asset('images/brand-3.png') }}" alt="Brand 3"
                    class="h-16 opacity-70 hover:opacity-100 transition-opacity">
            </div>
            <div class="flex items-center justify-center p-4">
                <img src="{{ asset('images/brand-4.png') }}" alt="Brand 4"
                    class="h-16 opacity-70 hover:opacity-100 transition-opacity">
            </div>
            <div class="flex items-center justify-center p-4">
                <img src="{{ asset('images/brand-5.png') }}" alt="Brand 5"
                    class="h-16 opacity-70 hover:opacity-100 transition-opacity">
            </div>
            <div class="flex items-center justify-center p-4">
                <img src="{{ asset('images/brand-6.png') }}" alt="Brand 6"
                    class="h-16 opacity-70 hover:opacity-100 transition-opacity">
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-gray-100">
    <div class="container mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-8 md:p-12">
            <div class="text-center max-w-3xl mx-auto">
                <h2 class="text-3xl font-bold mb-4">Tidak Menemukan Sparepart yang Anda Cari?</h2>
                <p class="text-gray-600 mb-8">Kami memiliki jaringan supplier yang luas dan dapat membantu Anda
                    menemukan sparepart yang dibutuhkan. Hubungi kami untuk informasi lebih lanjut.</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="{{ route('contact') }}"
                        class="bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-6 rounded-md transition-colors">Hubungi
                        Kami</a>
                    <a href="https://wa.me/6282135202581" target="_blank"
                        class="bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-6 rounded-md transition-colors flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                        </svg>
                        WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // WhatsApp Product Inquiry Function
    function askAboutProduct(productName, productDetails) {
        const message = `Halo Hartono Motor,

Saya tertarik dengan produk:
📦 Produk: ${productName}
📋 Detail: ${productDetails}

Mohon informasi:
- Ketersediaan stok
- Harga terbaru
- Spesifikasi lengkap
- Estimasi pengiriman

Kendaraan saya: [Mohon isi merek/model/tahun]

Terima kasih!`;

        const whatsappUrl = `https://wa.me/6282135202581?text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');
    }

    // Floating WhatsApp Widget
    document.addEventListener('DOMContentLoaded', function () {
        // Create floating WhatsApp button
        const floatingBtn = document.createElement('div');
        floatingBtn.className = 'fixed bottom-6 right-6 z-50';
        floatingBtn.innerHTML = `
        <button onclick="askAboutProduct('Konsultasi Umum', 'Butuh bantuan mencari sparepart')"
                class="bg-green-500 hover:bg-green-600 text-white p-4 rounded-full shadow-lg transition-all duration-300 hover:scale-110 animate-pulse">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
            </svg>
        </button>
        <div class="absolute bottom-16 right-0 bg-white rounded-lg shadow-xl p-4 w-64 hidden" id="whatsapp-tooltip">
            <h4 class="font-bold mb-2">Butuh Bantuan?</h4>
            <p class="text-sm text-gray-600 mb-3">Tim ahli kami siap membantu mencari sparepart yang Anda butuhkan</p>
        </div>
    `;

        document.body.appendChild(floatingBtn);

        // Show tooltip on hover
        const btn = floatingBtn.querySelector('button');
        const tooltip = floatingBtn.querySelector('#whatsapp-tooltip');

        btn.addEventListener('mouseenter', () => {
            tooltip.classList.remove('hidden');
        });

        btn.addEventListener('mouseleave', () => {
            setTimeout(() => {
                if (!tooltip.matches(':hover')) {
                    tooltip.classList.add('hidden');
                }
            }, 300);
        });

        tooltip.addEventListener('mouseleave', () => {
            tooltip.classList.add('hidden');
        });
    });
</script>
@endpush