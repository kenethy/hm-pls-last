<?php

namespace App\Http\Controllers;

class SparePartController extends Controller
{
    public function index()
    {
        return view('pages.spare-parts', [
            'title' => 'Toko Sparepart Mobil Terlengkap di Sidoarjo - Hartono Motor',
            'metaDescription' => 'Toko sparepart mobil terlengkap di Sidoarjo. Oli mesin, kampas rem, kopling, busi, dan ribuan sparepart original & aftermarket. Belanja online di Tokopedia, Shopee, Lazada. Konsultasi gratis via WhatsApp!',
            'metaKeywords' => 'toko sparepart mobil sidoarjo, oli mesin, kampas rem, kampas kopling, busi mobil, sparepart original, sparepart aftermarket, hartono motor, tokopedia, shopee, lazada, sparepart murah, sparepart berkualitas',
            'ogImage' => asset('images/sparepart/sparepart.png'),
            'canonicalUrl' => route('spare-parts')
        ]);
    }
}
