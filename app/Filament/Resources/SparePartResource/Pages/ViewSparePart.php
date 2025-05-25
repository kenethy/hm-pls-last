<?php

namespace App\Filament\Resources\SparePartResource\Pages;

use App\Filament\Resources\SparePartResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSparePart extends ViewRecord
{
    protected static string $resource = SparePartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Produk')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nama Produk'),
                        Infolists\Components\TextEntry::make('category.name')
                            ->label('Kategori')
                            ->badge(),
                        Infolists\Components\TextEntry::make('brand')
                            ->label('Merek'),
                        Infolists\Components\TextEntry::make('part_number')
                            ->label('Nomor Part'),
                        Infolists\Components\TextEntry::make('condition')
                            ->label('Kondisi')
                            ->badge(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Harga & Stok')
                    ->schema([
                        Infolists\Components\TextEntry::make('price')
                            ->label('Harga')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('original_price')
                            ->label('Harga Asli')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('stock_quantity')
                            ->label('Stok')
                            ->badge(),
                        Infolists\Components\TextEntry::make('minimum_stock')
                            ->label('Stok Minimum'),
                        Infolists\Components\TextEntry::make('warranty_period')
                            ->label('Garansi'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Deskripsi')
                    ->schema([
                        Infolists\Components\TextEntry::make('short_description')
                            ->label('Deskripsi Singkat'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Deskripsi Lengkap')
                            ->html(),
                    ])
                    ->columns(1),

                Infolists\Components\Section::make('Gambar')
                    ->schema([
                        Infolists\Components\ImageEntry::make('featured_image')
                            ->label('Gambar Utama'),
                        Infolists\Components\ImageEntry::make('images')
                            ->label('Galeri')
                            ->limit(5),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Pengaturan')
                    ->schema([
                        Infolists\Components\IconEntry::make('is_featured')
                            ->label('Produk Unggulan')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('is_best_seller')
                            ->label('Best Seller')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('is_original')
                            ->label('Original')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('order')
                            ->label('Urutan'),
                    ])
                    ->columns(4),
            ]);
    }
}
