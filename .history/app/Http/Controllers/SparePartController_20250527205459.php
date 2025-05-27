<?php

namespace App\Http\Controllers;

use App\Models\SparePart;
use App\Models\SparePartCategory;
use App\Models\SparePartSetting;

class SparePartController extends Controller
{
    public function index()
    {
        // Get active categories with their spare parts count
        $categories = SparePartCategory::active()
            ->ordered()
            ->withCount(['spareParts' => function ($query) {
                $query->active();
            }])
            ->get();

        // Get featured products
        $featuredProducts = SparePart::active()
            ->featured()
            ->with('category')
            ->ordered()
            ->limit(8)
            ->get();

        // Get best seller products
        $bestSellerProducts = SparePart::active()
            ->bestSeller()
            ->with('category')
            ->ordered()
            ->limit(4)
            ->get();

        // Get pricing notification settings
        $pricingNotification = SparePartSetting::getPricingNotificationSettings();

        return view('pages.spare-parts', [
            'title' => 'Toko Sparepart Mobil Terlengkap di Sidoarjo - Hartono Motor',
            'metaDescription' => 'Toko sparepart mobil terlengkap di Sidoarjo. Oli mesin, kampas rem, kopling, busi, dan ribuan sparepart original & aftermarket. Belanja online di Tokopedia, Shopee, Lazada. Konsultasi gratis via WhatsApp!',
            'metaKeywords' => 'toko sparepart mobil sidoarjo, oli mesin, kampas rem, kampas kopling, busi mobil, sparepart original, sparepart aftermarket, hartono motor, tokopedia, shopee, lazada, sparepart murah, sparepart berkualitas',
            'ogImage' => asset('images/sparepart/sparepart.png'),
            'canonicalUrl' => route('spare-parts'),
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'bestSellerProducts' => $bestSellerProducts,
            'pricingNotification' => $pricingNotification,
        ]);
    }

    public function category($slug)
    {
        $category = SparePartCategory::where('slug', $slug)
            ->active()
            ->firstOrFail();

        $products = SparePart::active()
            ->where('category_id', $category->id)
            ->with('category')
            ->ordered()
            ->paginate(12);

        return view('pages.spare-parts-category', [
            'title' => $category->name . ' - Sparepart',
            'category' => $category,
            'products' => $products,
        ]);
    }

    public function show($slug)
    {
        $product = SparePart::where('slug', $slug)
            ->active()
            ->with('category')
            ->firstOrFail();

        // Get related products from same category
        $relatedProducts = SparePart::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('category')
            ->ordered()
            ->limit(4)
            ->get();

        return view('pages.spare-part-detail', [
            'title' => $product->name . ' - Sparepart Hartono Motor',
            'metaDescription' => $product->short_description . ' - ' . $product->category->name . ' berkualitas di Hartono Motor Sidoarjo. ' . ($product->is_original ? 'Original' : 'Aftermarket') . ' dengan harga terbaik. Konsultasi gratis via WhatsApp!',
            'metaKeywords' => $product->name . ', ' . $product->category->name . ', sparepart mobil sidoarjo, hartono motor, ' . ($product->is_original ? 'sparepart original' : 'sparepart aftermarket'),
            'ogImage' => $product->main_image_url,
            'canonicalUrl' => route('spare-parts.show', $product->slug),
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
