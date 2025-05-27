@extends('layouts.main')

@section('content')
<!-- Product Detail Hero Section -->
<section class="relative bg-gray-900 text-white py-16">
    <div class="absolute inset-0 overflow-hidden">
        <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
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
                    <li><a href="{{ route('spare-parts.category', $product->category->slug) }}"
                            class="text-gray-300 hover:text-white">{{ $product->category->name }}</a></li>
                    <li class="text-gray-400">/</li>
                    <li class="text-white">{{ $product->name }}</li>
                </ol>
            </nav>

            <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $product->name }}</h1>
            <p class="text-xl mb-6">{{ $product->short_description }}</p>

            <!-- Product Badges -->
            <div class="flex flex-wrap gap-2 mb-6">
                @if($product->is_best_seller)
                <span class="bg-red-600 text-white text-sm font-bold px-4 py-2 rounded-full">BEST SELLER</span>
                @endif
                @if($product->is_featured)
                <span class="bg-purple-600 text-white text-sm font-bold px-4 py-2 rounded-full">UNGGULAN</span>
                @endif
                @if($product->is_original)
                <span class="bg-green-600 text-white text-sm font-bold px-4 py-2 rounded-full">ORIGINAL</span>
                @endif
                @if($product->condition === 'new')
                <span class="bg-blue-600 text-white text-sm font-bold px-4 py-2 rounded-full">BARU</span>
                @endif
            </div>
        </div>
    </div>
</section>

<!-- Product Details Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Product Images -->
            <div class="space-y-4">
                <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden">
                    <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                        class="w-full h-full object-cover">
                </div>

                @if($product->images && count($product->images) > 1)
                <div class="grid grid-cols-4 gap-2">
                    @foreach(array_slice($product->images, 1, 4) as $image)
                    <div
                        class="aspect-square bg-gray-100 rounded-lg overflow-hidden cursor-pointer hover:opacity-75 transition-opacity">
                        <img src="{{ $image }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Product Info -->
            <div class="space-y-6">
                <!-- Category -->
                <div class="flex items-center">
                    <span class="inline-block text-sm font-medium px-3 py-1 rounded-full"
                        style="background-color: {{ $product->category->color ?? '#dc2626' }}20; color: {{ $product->category->color ?? '#dc2626' }};">
                        {{ $product->category->name }}
                    </span>
                </div>

                <!-- Price -->
                <div class="space-y-2">
                    @if($product->original_price && $product->original_price > $product->price)
                    <div class="flex items-center space-x-3">
                        <span class="text-gray-400 line-through text-lg">{{ $product->formatted_original_price }}</span>
                        <span class="bg-red-100 text-red-600 text-sm px-2 py-1 rounded-full">
                            Hemat {{ $product->discount_percentage }}%
                        </span>
                    </div>
                    <div class="text-3xl font-bold text-red-600">{{ $product->formatted_price }}</div>
                    @else
                    <div class="text-3xl font-bold text-red-600">{{ $product->formatted_price }}</div>
                    @endif

                    <!-- Stock Status -->
                    <div class="flex items-center space-x-2">
                        <span class="text-sm {{ $product->stock_status_color }} font-medium">
                            {{ $product->stock_status_text }}
                        </span>
                    </div>
                </div>

                <!-- Description -->
                <div class="prose prose-gray max-w-none">
                    <h3 class="text-lg font-semibold mb-3">Deskripsi Produk</h3>
                    <p class="text-gray-600 leading-relaxed">{{ $product->description }}</p>
                </div>

                <!-- Specifications -->
                @if($product->specifications && count($product->specifications) > 0)
                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="text-lg font-semibold mb-4">Spesifikasi</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($product->specifications as $spec)
                        <div class="flex justify-between py-2 border-b border-gray-200 last:border-b-0">
                            <span class="font-medium text-gray-700">{{ $spec['name'] }}</span>
                            <span class="text-gray-600">{{ $spec['value'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Action Buttons -->
                <div class="space-y-4">
                    <!-- WhatsApp Inquiry -->
                    <button onclick="askAboutProduct('{{ $product->name }}', '{{ $product->short_description }}')"
                        class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-4 px-6 rounded-xl transition-colors flex items-center justify-center text-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                        </svg>
                        Tanya Produk Ini via WhatsApp
                    </button>

                    <!-- Marketplace Links -->
                    @if($product->has_marketplace_links)
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($product->available_marketplaces as $marketplace)
                        @php
                        $platforms = \App\Models\SparePart::getMarketplacePlatforms();
                        $platform = $platforms[$marketplace['platform']] ?? null;
                        @endphp
                        @if($platform)
                        <a href="{{ $marketplace['url'] }}" target="_blank"
                            class="marketplace-button-large {{ $marketplace['platform'] }}">
                            <span class="marketplace-icon-text">
                                {{ strtoupper(substr($platform['name'], 0, 1)) }}
                            </span>
                            Beli di {{ $platform['name'] }}
                        </a>
                        @endif
                        @endforeach
                    </div>
                    @else
                    <!-- Default marketplace links -->
                    <div class="grid grid-cols-2 gap-3">
                        <a href="https://www.tokopedia.com/hartono-m" target="_blank"
                            class="marketplace-button-large tokopedia">
                            <span class="marketplace-icon-text">T</span>
                            Beli di Tokopedia
                        </a>
                        <a href="https://shopee.co.id/hartono_motor" target="_blank"
                            class="marketplace-button-large shopee">
                            <span class="marketplace-icon-text">S</span>
                            Beli di Shopee
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Products Section -->
@if($relatedProducts->count() > 0)
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Produk Terkait</h2>
            <p class="text-gray-600">Produk lain dari kategori {{ $product->category->name }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach($relatedProducts as $relatedProduct)
            <a href="{{ route('spare-parts.show', $relatedProduct->slug) }}"
                class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-2">
                <div class="relative">
                    <img src="{{ $relatedProduct->main_image_url }}" alt="{{ $relatedProduct->name }}"
                        class="w-full h-48 object-cover">

                    @if($relatedProduct->is_best_seller)
                    <div class="absolute top-4 left-4">
                        <span class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full">BEST SELLER</span>
                    </div>
                    @endif
                </div>

                <div class="p-6">
                    <h3 class="text-lg font-bold mb-2">{{ $relatedProduct->name }}</h3>
                    <p class="text-gray-600 text-sm mb-4">{{ Str::limit($relatedProduct->short_description, 60) }}</p>

                    <div class="flex items-center justify-between">
                        @if($relatedProduct->original_price && $relatedProduct->original_price > $relatedProduct->price)
                        <div>
                            <span class="text-gray-400 line-through text-sm">{{
                                $relatedProduct->formatted_original_price }}</span>
                            <span class="text-red-600 font-bold block">{{ $relatedProduct->formatted_price }}</span>
                        </div>
                        @else
                        <span class="text-red-600 font-bold">{{ $relatedProduct->formatted_price }}</span>
                        @endif

                        <span class="text-xs {{ $relatedProduct->stock_status_color }} font-medium">
                            {{ $relatedProduct->stock_status_text }}
                        </span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection

@push('styles')
<style>
    /* Marketplace Button Large Styles */
    .marketplace-button-large {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        min-height: 3rem;
    }

    .marketplace-button-large.tokopedia {
        background-color: #42b883;
        color: white;
    }

    .marketplace-button-large.tokopedia:hover {
        background-color: #369870;
        transform: translateY(-2px);
    }

    .marketplace-button-large.shopee {
        background-color: #ee4d2d;
        color: white;
    }

    .marketplace-button-large.shopee:hover {
        background-color: #d73211;
        transform: translateY(-2px);
    }

    .marketplace-button-large.lazada {
        background-color: #0f146d;
        color: white;
    }

    .marketplace-button-large.lazada:hover {
        background-color: #0a0f4a;
        transform: translateY(-2px);
    }

    .marketplace-button-large.bukalapak {
        background-color: #e31e24;
        color: white;
    }

    .marketplace-button-large.bukalapak:hover {
        background-color: #c01a1f;
        transform: translateY(-2px);
    }

    .marketplace-icon-text {
        font-weight: bold;
        margin-right: 0.5rem;
        font-size: 1rem;
    }

    /* Image Gallery */
    .product-image-gallery img {
        cursor: pointer;
        transition: opacity 0.2s ease;
    }

    .product-image-gallery img:hover {
        opacity: 0.75;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .marketplace-button-large {
            padding: 0.75rem;
            font-size: 0.75rem;
        }

        .marketplace-icon-text {
            font-size: 0.875rem;
            margin-right: 0.25rem;
        }
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