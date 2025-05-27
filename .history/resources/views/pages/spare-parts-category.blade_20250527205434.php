@extends('layouts.main')

@section('content')
<!-- Category Hero Section -->
<section class="relative bg-gray-900 text-white py-16">
    <div class="absolute inset-0 overflow-hidden">
        <img src="{{ asset('images/sparepart/sparepart.png') }}" alt="{{ $category->name }}"
            class="w-full h-full object-cover opacity-30" fetchpriority="high">
    </div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl">
            <!-- Breadcrumb -->
            <nav class="mb-6">
                <ol class="flex items-center space-x-2 text-sm">
                    <li><a href="{{ route('home') }}" class="text-gray-300 hover:text-white">Beranda</a></li>
                    <li class="text-gray-400">/</li>
                    <li><a href="{{ route('spare-parts') }}" class="text-gray-300 hover:text-white">Sparepart</a></li>
                    <li class="text-gray-400">/</li>
                    <li class="text-white">{{ $category->name }}</li>
                </ol>
            </nav>

            <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $category->name }}</h1>
            <p class="text-xl mb-6">{{ $category->description }}</p>

            <div class="flex items-center space-x-4">
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm">
                    {{ $products->total() }} produk tersedia
                </span>
            </div>
        </div>
    </div>
</section>

<!-- Products Grid Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4 max-w-7xl">
        @if($products->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            @foreach($products as $product)
            <div
                class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-2">
                <div class="relative">
                    <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                        class="w-full h-48 object-cover">

                    <!-- Product Badges -->
                    <div class="absolute top-4 left-4 flex flex-col gap-2">
                        @if($product->is_best_seller)
                        <span class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full">BEST SELLER</span>
                        @endif
                        @if($product->is_featured)
                        <span class="bg-purple-600 text-white text-xs font-bold px-3 py-1 rounded-full">UNGGULAN</span>
                        @endif
                    </div>

                    <div class="absolute top-4 right-4 flex flex-col gap-2">
                        @if($product->is_original)
                        <span class="bg-green-600 text-white text-xs font-bold px-3 py-1 rounded-full">ORIGINAL</span>
                        @endif
                        @if($product->condition === 'new')
                        <span class="bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full">BARU</span>
                        @endif
                    </div>
                </div>

                <div class="p-6">
                    <h3 class="text-lg font-bold mb-2">
                        <a href="{{ route('spare-parts.show', $product->slug) }}"
                            class="hover:text-red-600 transition-colors">
                            {{ $product->name }}
                        </a>
                    </h3>

                    <p class="text-gray-600 text-sm mb-4">{{ Str::limit($product->short_description, 80) }}</p>

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            @if($product->original_price && $product->original_price > $product->price)
                            <span class="text-gray-400 line-through text-sm">{{ $product->formatted_original_price
                                }}</span>
                            <span class="text-red-600 font-bold text-lg block">{{ $product->formatted_price }}</span>
                            <span class="bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full">
                                Hemat {{ $product->discount_percentage }}%
                            </span>
                            @else
                            <span class="text-red-600 font-bold text-lg">{{ $product->formatted_price }}</span>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="text-xs {{ $product->stock_status_color }} font-medium">
                                {{ $product->stock_status_text }}
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <!-- WhatsApp Inquiry -->
                        <button onclick="askAboutProduct('{{ $product->name }}', '{{ $product->short_description }}')"
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                            </svg>
                            Tanya Produk
                        </button>

                        <!-- Marketplace Links -->
                        @if($product->has_marketplace_links)
                        <div class="flex space-x-2">
                            @foreach(array_slice($product->available_marketplaces, 0, 2) as $marketplace)
                            @php
                            $platforms = \App\Models\SparePart::getMarketplacePlatforms();
                            $platform = $platforms[$marketplace['platform']] ?? null;
                            @endphp
                            @if($platform)
                            <a href="{{ $marketplace['url'] }}" target="_blank"
                                class="flex-1 marketplace-button-small {{ $marketplace['platform'] }}">
                                {{ $platform['name'] }}
                            </a>
                            @endif
                            @endforeach
                        </div>
                        @else
                        <!-- Default marketplace links -->
                        <div class="flex space-x-2">
                            <a href="https://www.tokopedia.com/hartono-m" target="_blank"
                                class="flex-1 marketplace-button-small tokopedia">
                                Tokopedia
                            </a>
                            <a href="https://shopee.co.id/hartono_motor" target="_blank"
                                class="flex-1 marketplace-button-small shopee">
                                Shopee
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $products->links() }}
        </div>
        @else
        <!-- Empty State -->
        <div class="text-center py-16">
            <div class="max-w-md mx-auto">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="text-xl font-medium text-gray-900 mb-2">Belum ada produk</h3>
                <p class="text-gray-500 mb-6">Produk untuk kategori {{ $category->name }} akan segera ditambahkan.</p>
                <a href="{{ route('spare-parts') }}"
                    class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Lihat Semua Kategori
                </a>
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('styles')
<style>
    /* Marketplace Button Small Styles */
    .marketplace-button-small {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        min-height: 2rem;
    }

    .marketplace-button-small.tokopedia {
        background-color: #42b883;
        color: white;
    }

    .marketplace-button-small.tokopedia:hover {
        background-color: #369870;
    }

    .marketplace-button-small.shopee {
        background-color: #ee4d2d;
        color: white;
    }

    .marketplace-button-small.shopee:hover {
        background-color: #d73211;
    }

    .marketplace-button-small.lazada {
        background-color: #0f146d;
        color: white;
    }

    .marketplace-button-small.lazada:hover {
        background-color: #0a0f4a;
    }

    .marketplace-button-small.bukalapak {
        background-color: #e31e24;
        color: white;
    }

    .marketplace-button-small.bukalapak:hover {
        background-color: #c01a1f;
    }
</style>
@endpush

@push('scripts')
<script>
    // WhatsApp Product Inquiry Function
    function askAboutProduct(productName, productDescription) {
        const phoneNumber = '6282135202581'; // Hartono Motor WhatsApp number
        const message = `Halo Hartono Motor! 👋

Saya tertarik dengan produk:
📦 *${productName}*
📝 ${productDescription}

Bisa tolong berikan informasi lebih detail mengenai:
• Harga terbaru
• Ketersediaan stok
• Spesifikasi lengkap
• Cara pemesanan

Terima kasih! 🙏`;

        const encodedMessage = encodeURIComponent(message);
        const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodedMessage}`;

        window.open(whatsappUrl, '_blank');
    }
</script>
@endpush