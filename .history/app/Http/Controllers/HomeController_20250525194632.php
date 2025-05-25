<?php

namespace App\Http\Controllers;

use App\Models\Promo;

class HomeController extends Controller
{
    public function index()
    {
        // Get featured promos
        $featuredPromos = Promo::active()->featured()->latest()->take(3)->get();

        // Get ending soon promos
        $endingSoonPromos = Promo::active()
            ->whereRaw('DATEDIFF(end_date, NOW()) < 3')
            ->whereRaw('DATEDIFF(end_date, NOW()) >= 0')
            ->latest()
            ->take(2)
            ->get();

        return view('pages.home', [
            'title' => 'Hartono Motor - Bengkel & Toko Sparepart Terpercaya di Sidoarjo',
            'metaDescription' => 'Hartono Motor - Bengkel terpercaya & toko sparepart terlengkap di Sidoarjo. Servis profesional, oli mesin, kampas rem, kopling, busi, dan sparepart berkualitas. Belanja online di Tokopedia & Shopee!',
            'metaKeywords' => 'bengkel mobil sidoarjo, toko sparepart mobil, oli mesin, kampas rem, kampas kopling, busi mobil, servis mobil, tune up mesin, ganti oli, sparepart original, hartono motor, tokopedia, shopee',
            'ogImage' => asset('images/hero-bg.png'),
            'featuredPromos' => $featuredPromos,
            'endingSoonPromos' => $endingSoonPromos
        ]);
    }
}
