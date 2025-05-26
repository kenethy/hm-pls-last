{{-- BACKUP CREATED: 2025-01-25 - Enhanced Hybrid Approach Implementation --}}
@extends('layouts.main')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gray-900 text-white py-20">
    <div class="absolute inset-0 overflow-hidden">
        <img src="{{ asset('images/sparepart/sparepart.png') }}" alt="Sparepart Hartono Motor"
            class="w-full h-full object-cover opacity-40" fetchpriority="high">
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

@if(isset($pricingNotification) && $pricingNotification['enabled'])
@if($pricingNotification['display_type'] === 'banner')
<!-- Pricing Notification Banner -->
<div class="pricing-notification-banner">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-center md:text-left">
                <h3 class="text-lg font-bold mb-2">{{ $pricingNotification['title'] }}</h3>
                <p class="text-sm opacity-90">{{ $pricingNotification['message'] }}</p>
            </div>
            <div class="flex-shrink-0">
                <a href="https://wa.me/{{ $pricingNotification['whatsapp_number'] }}?text=Halo, saya ingin menanyakan harga sparepart terbaik di toko Hartono Motor"
                    target="_blank" class="whatsapp-button">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488" />
                    </svg>
                    {{ $pricingNotification['cta_text'] }}
                </a>
            </div>
        </div>
    </div>
</div>
@endif
@endif

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

            <!-- Product 3: Kampas Kopling -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up"
                style="transition-delay: 0.2s">
                <div class="relative">
                    <img src="{{ asset('images/sparepart/sparepart.png') }}" alt="Kampas Kopling Berkualitas"
                        class="w-full h-48 object-cover">
                    <div class="absolute top-4 left-4">
                        <span class="bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full">PREMIUM</span>
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
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <div>
                            <span
                                class="inline-block bg-purple-100 text-purple-600 text-xs font-medium px-2 py-1 rounded-full mb-1">Transmisi</span>
                            <h3 class="text-xl font-bold">Kampas Kopling</h3>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">Valeo, Exedy, dan merek berkualitas untuk transmisi manual yang halus
                        dan awet. Daya cengkeram optimal.</p>

                    <!-- Specifications -->
                    <div class="bg-gray-50 rounded-lg p-3 mb-4">
                        <h4 class="font-semibold text-sm mb-2">Spesifikasi:</h4>
                        <ul class="text-xs text-gray-600 space-y-1">
                            <li>• Material: Organic, Ceramic</li>
                            <li>• Tipe: Set Lengkap (Plat + Cover)</li>
                            <li>• Cocok untuk: Manual Transmission</li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <span class="text-red-600 font-bold text-lg">Mulai Rp 200.000</span>
                            <p class="text-xs text-gray-500">*Harga per set lengkap</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <!-- WhatsApp Inquiry -->
                        <button onclick="askAboutProduct('Kampas Kopling', 'Set lengkap untuk transmisi manual')"
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

            <!-- Product 4: Busi -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-2 reveal-up"
                style="transition-delay: 0.3s">
                <div class="relative">
                    <img src="{{ asset('images/sparepart/sparepart.png') }}" alt="Busi Berkualitas"
                        class="w-full h-48 object-cover">
                    <div class="absolute top-4 left-4">
                        <span class="bg-purple-600 text-white text-xs font-bold px-3 py-1 rounded-full">HOT ITEM</span>
                    </div>
                    <div class="absolute top-4 right-4">
                        <span class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full">BEST SELLER</span>
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
                        <div>
                            <span
                                class="inline-block bg-yellow-100 text-yellow-600 text-xs font-medium px-2 py-1 rounded-full mb-1">Sistem
                                Pengapian</span>
                            <h3 class="text-xl font-bold">Busi</h3>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">NGK, Denso, Bosch untuk performa mesin optimal dan konsumsi BBM
                        efisien. Heat range sesuai spesifikasi.</p>

                    <!-- Specifications -->
                    <div class="bg-gray-50 rounded-lg p-3 mb-4">
                        <h4 class="font-semibold text-sm mb-2">Spesifikasi:</h4>
                        <ul class="text-xs text-gray-600 space-y-1">
                            <li>• Tipe: Standard, Iridium, Platinum</li>
                            <li>• Heat Range: Dingin, Normal, Panas</li>
                            <li>• Cocok untuk: Semua merek mobil</li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <span class="text-red-600 font-bold text-lg">Mulai Rp 25.000</span>
                            <p class="text-xs text-gray-500">*Harga per pcs</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <!-- WhatsApp Inquiry -->
                        <button onclick="askAboutProduct('Busi', 'Tersedia berbagai merek dan heat range')"
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
        </div>

        <!-- Enhanced CTA Section -->
        <div class="text-center mt-12 reveal">
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-xl p-8 text-white max-w-4xl mx-auto shadow-lg">
                <h3 class="text-2xl font-bold mb-4">Jelajahi Ribuan Produk Sparepart Lainnya</h3>
                <p class="mb-6 opacity-90 max-w-2xl mx-auto">Tidak menemukan produk yang Anda cari di atas? Kami
                    memiliki ribuan sparepart lainnya tersedia di marketplace online kami dengan harga kompetitif.</p>

                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="https://www.tokopedia.com/hartono-m" target="_blank"
                        class="bg-white text-red-600 hover:bg-gray-100 font-medium py-3 px-6 rounded-lg transition-colors flex items-center justify-center">
                        <img src="{{ asset('images/marketplace/tokopedia.png') }}" alt="Tokopedia" class="h-5 w-5 mr-2">
                        Belanja di Tokopedia
                    </a>
                    <a href="https://shopee.co.id/hartono_motor" target="_blank"
                        class="bg-white text-red-600 hover:bg-gray-100 font-medium py-3 px-6 rounded-lg transition-colors flex items-center justify-center">
                        <img src="{{ asset('images/marketplace/shopee.png') }}" alt="Shopee" class="h-5 w-5 mr-2">
                        Belanja di Shopee
                    </a>
                    <a href="https://www.lazada.co.id/shop/hartono-motor-sidoarjo" target="_blank"
                        class="bg-white text-red-600 hover:bg-gray-100 font-medium py-3 px-6 rounded-lg transition-colors flex items-center justify-center">
                        <img src="{{ asset('images/marketplace/lazada.png') }}" alt="Lazada" class="h-5 w-5 mr-2">
                        Belanja di Lazada
                    </a>
                </div>

                <div class="mt-6 pt-6 border-t border-white/20">
                    <p class="text-sm opacity-80 mb-3">Atau konsultasi langsung untuk sparepart khusus:</p>
                    <button
                        onclick="askAboutProduct('Konsultasi Sparepart Khusus', 'Butuh bantuan mencari sparepart tertentu')"
                        class="bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-6 rounded-lg transition-colors inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                        </svg>
                        Konsultasi WhatsApp Gratis
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Partner Brands Carousel Section -->
<section id="partner-brands" class="py-20 bg-gradient-to-b from-white to-gray-50">
    <div class="container mx-auto px-4 max-w-7xl">
        <!-- Section Header -->
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">Partner & Merek Terpercaya</h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto leading-relaxed">
                Kami bekerja sama dengan merek-merek terpercaya dunia untuk menjamin kualitas dan keaslian
                setiap produk sparepart yang kami jual.
            </p>
        </div>

        <!-- Minimalist Logo Carousel -->
        <div class="logo-carousel-container" id="logoCarousel">
            <div class="logo-carousel-track">
                <!-- First set of logos -->
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/Shell_logo.svg.png') }}" alt="Shell" class="partner-logo"
                        loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/Castrol_logo_2023.svg.png') }}" alt="Castrol"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/pertamina_lubricants-logo_brandlogos.net_02sbt.png') }}"
                        alt="Pertamina Lubricants" class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/Akebono_Brake_company_logo.svg.png') }}" alt="Akebono"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/exedy-logo-png_seeklogo-611832.png') }}" alt="Exedy"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/Denso-Logo.wine.png') }}" alt="Denso" class="partner-logo"
                        loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/kyb-logo-png_seeklogo-502885.png') }}" alt="KYB"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/NTN_Corporation_Logo.svg.png') }}" alt="NTN Corporation"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/Stanley_Electric_logo.svg.png') }}" alt="Stanley Electric"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/yuasa-logo-png_seeklogo-257397.png') }}" alt="Yuasa"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/STP_(motor_oil_company)_(logo).png') }}" alt="STP"
                        class="partner-logo" loading="lazy">
                </div>

                <!-- Duplicate set for seamless loop -->
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/Shell_logo.svg.png') }}" alt="Shell" class="partner-logo"
                        loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/Castrol_logo_2023.svg.png') }}" alt="Castrol"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/pertamina_lubricants-logo_brandlogos.net_02sbt.png') }}"
                        alt="Pertamina Lubricants" class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/Akebono_Brake_company_logo.svg.png') }}" alt="Akebono"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/exedy-logo-png_seeklogo-611832.png') }}" alt="Exedy"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/Denso-Logo.wine.png') }}" alt="Denso" class="partner-logo"
                        loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/kyb-logo-png_seeklogo-502885.png') }}" alt="KYB"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/NTN_Corporation_Logo.svg.png') }}" alt="NTN Corporation"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/Stanley_Electric_logo.svg.png') }}" alt="Stanley Electric"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/yuasa-logo-png_seeklogo-257397.png') }}" alt="Yuasa"
                        class="partner-logo" loading="lazy">
                </div>
                <div class="logo-item">
                    <img src="{{ asset('images/logo partner/STP_(motor_oil_company)_(logo).png') }}" alt="STP"
                        class="partner-logo" loading="lazy">
                </div>
            </div>
        </div>

        <!-- Partner Trust Statement -->
        <div class="text-center mt-12">
            <p class="text-base text-gray-500 font-medium">
                Dipercaya oleh merek-merek terkemuka dunia untuk kualitas dan keaslian produk sparepart
            </p>
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

@push('styles')
<link rel="stylesheet" href="{{ asset('css/marketplace-icons.css') }}">
<style>
    /* Partner Logo Carousel Styles - Minimalist Design */
    .logo-carousel-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        overflow: hidden;
        position: relative;
        background: linear-gradient(135deg, #fafafa 0%, #f8f9fa 100%);
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 20px;
        padding: 40px 0;
        box-shadow:
            0 4px 6px -1px rgba(0, 0, 0, 0.1),
            0 2px 4px -1px rgba(0, 0, 0, 0.06);
        backdrop-filter: blur(10px);
    }

    .logo-carousel-track {
        display: flex;
        width: 200%;
        animation: scroll-infinite 30s linear infinite;
        will-change: transform;
    }

    .logo-carousel-track:hover {
        animation-play-state: paused;
    }

    .logo-item {
        flex: 0 0 auto;
        width: 160px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 15px;
    }

    .logo-placeholder {
        width: 120px;
        height: 60px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 2px solid #dee2e6;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .logo-placeholder:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-color: #dc3545;
    }

    .brand-text {
        font-weight: bold;
        font-size: 12px;
        color: #495057;
        letter-spacing: 1px;
    }

    /* Partner Logo Styles - Natural Colors */
    .partner-logo {
        max-width: 130px;
        max-height: 65px;
        width: auto;
        height: auto;
        object-fit: contain;
        filter: brightness(0.95) saturate(0.9);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0.9;
        border-radius: 8px;
        padding: 12px;
        background: rgba(255, 255, 255, 0.7);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .partner-logo:hover {
        filter: brightness(1.05) saturate(1.1);
        opacity: 1;
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        background: rgba(255, 255, 255, 0.95);
    }

    /* Simplified Animation - More Reliable */
    @keyframes scroll-infinite {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(-50%);
        }
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .logo-carousel-container {
            border-radius: 16px;
            padding: 32px 0;
            margin: 0 16px;
        }

        .logo-carousel-track {
            animation-duration: 25s;
        }

        .logo-item {
            width: 140px;
            height: 70px;
            padding: 0 12px;
        }

        .partner-logo {
            max-width: 110px;
            max-height: 55px;
            padding: 8px;
        }

        .logo-placeholder {
            width: 100px;
            height: 50px;
        }

        .brand-text {
            font-size: 10px;
        }
    }

    @media (max-width: 480px) {
        .logo-carousel-container {
            border-radius: 12px;
            padding: 24px 0;
            margin: 0 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
        }

        .logo-carousel-track {
            animation-duration: 20s;
        }

        .logo-item {
            width: 120px;
            height: 60px;
            padding: 0 10px;
        }

        .partner-logo {
            max-width: 90px;
            max-height: 45px;
            padding: 6px;
        }

        .logo-placeholder {
            width: 80px;
            height: 40px;
        }

        .brand-text {
            font-size: 9px;
        }
    }

    /* Minimalist fade edges */
    .logo-carousel-container::before,
    .logo-carousel-container::after {
        content: '';
        position: absolute;
        top: 0;
        width: 60px;
        height: 100%;
        z-index: 2;
        pointer-events: none;
    }

    .logo-carousel-container::before {
        left: 0;
        background: linear-gradient(to right,
                rgba(250, 250, 250, 1) 0%,
                rgba(250, 250, 250, 0.8) 50%,
                transparent 100%);
    }

    .logo-carousel-container::after {
        right: 0;
        background: linear-gradient(to left,
                rgba(250, 250, 250, 1) 0%,
                rgba(250, 250, 250, 0.8) 50%,
                transparent 100%);
    }

    /* Pricing Notification Styles */
    .pricing-notification-banner {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: white;
        padding: 16px 0;
        position: relative;
        overflow: hidden;
    }

    .pricing-notification-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
        0% {
            left: -100%;
        }

        100% {
            left: 100%;
        }
    }

    .pricing-notification-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .pricing-notification-modal.show {
        opacity: 1;
        visibility: visible;
    }

    .pricing-notification-content {
        background: white;
        border-radius: 16px;
        padding: 32px;
        max-width: 500px;
        margin: 20px;
        position: relative;
        transform: scale(0.8);
        transition: transform 0.3s ease;
    }

    .pricing-notification-modal.show .pricing-notification-content {
        transform: scale(1);
    }

    .pricing-notification-close {
        position: absolute;
        top: 16px;
        right: 16px;
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6b7280;
        transition: color 0.3s ease;
    }

    .pricing-notification-close:hover {
        color: #dc2626;
    }

    .pricing-notification-sticky {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: white;
        padding: 16px;
        transform: translateY(100%);
        transition: transform 0.3s ease;
        z-index: 1000;
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.15);
    }

    .pricing-notification-sticky.show {
        transform: translateY(0);
    }

    .whatsapp-button {
        background: #25d366;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .whatsapp-button:hover {
        background: #20ba5a;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        color: white;
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .pricing-notification-content {
            padding: 24px;
            margin: 16px;
        }

        .pricing-notification-banner {
            padding: 12px 0;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Pricing Notification Functions
    function showPricingModal() {
        const modal = document.getElementById('pricingModal');
        if (modal) {
            modal.classList.add('show');
        }
    }

    function closePricingModal() {
        const modal = document.getElementById('pricingModal');
        if (modal) {
            modal.classList.remove('show');
            // Set session storage to prevent showing again
            sessionStorage.setItem('pricingNotificationShown', 'true');
        }
    }

    function showPricingSticky() {
        const sticky = document.getElementById('pricingSticky');
        if (sticky) {
            setTimeout(() => {
                sticky.classList.add('show');
            }, 3000); // Show after 3 seconds
        }
    }

    function closePricingSticky() {
        const sticky = document.getElementById('pricingSticky');
        if (sticky) {
            sticky.classList.remove('show');
            // Set session storage to prevent showing again
            sessionStorage.setItem('pricingNotificationShown', 'true');
        }
    }

    // Initialize pricing notifications
    document.addEventListener('DOMContentLoaded', function () {
        @if (isset($pricingNotification) && $pricingNotification['enabled'])
            const notificationShown = sessionStorage.getItem('pricingNotificationShown');

        @if ($pricingNotification['display_type'] === 'modal')
            if (!notificationShown) {
                setTimeout(showPricingModal, 2000); // Show modal after 2 seconds
            }
        @elseif($pricingNotification['display_type'] === 'sticky')
        if (!notificationShown) {
            showPricingSticky();
        }
        @endif
        @endif
    });

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

    // Partner Logo Carousel Enhancement - Simplified
    document.addEventListener('DOMContentLoaded', function () {
        console.log('Initializing partner logo carousel...');

        const carousel = document.getElementById('logoCarousel');
        if (!carousel) {
            console.error('Logo carousel container not found!');
            return;
        }

        const track = carousel.querySelector('.logo-carousel-track');
        if (!track) {
            console.error('Logo carousel track not found!');
            return;
        }

        console.log('Carousel elements found successfully');

        // Simple hover pause/resume
        carousel.addEventListener('mouseenter', function () {
            track.style.animationPlayState = 'paused';
            console.log('Animation paused');
        });

        carousel.addEventListener('mouseleave', function () {
            track.style.animationPlayState = 'running';
            console.log('Animation resumed');
        });

        // Touch support for mobile
        carousel.addEventListener('touchstart', function () {
            track.style.animationPlayState = 'paused';
        });

        carousel.addEventListener('touchend', function () {
            setTimeout(() => {
                track.style.animationPlayState = 'running';
            }, 300);
        });

        console.log('Partner logo carousel initialized successfully!');
        console.log('Track computed style:', window.getComputedStyle(track).animation);
    });

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