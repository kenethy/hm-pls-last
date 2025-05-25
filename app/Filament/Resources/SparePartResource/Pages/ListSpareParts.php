<?php

namespace App\Filament\Resources\SparePartResource\Pages;

use App\Filament\Resources\SparePartResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSpareParts extends ListRecords
{
    protected static string $resource = SparePartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge($this->getModel()::count()),
            
            'active' => Tab::make('Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge($this->getModel()::where('status', 'active')->count()),
            
            'featured' => Tab::make('Unggulan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_featured', true))
                ->badge($this->getModel()::where('is_featured', true)->count()),
            
            'best_seller' => Tab::make('Best Seller')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_best_seller', true))
                ->badge($this->getModel()::where('is_best_seller', true)->count()),
            
            'low_stock' => Tab::make('Stok Rendah')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereColumn('stock_quantity', '<=', 'minimum_stock'))
                ->badge($this->getModel()::whereColumn('stock_quantity', '<=', 'minimum_stock')->count()),
            
            'out_of_stock' => Tab::make('Stok Habis')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stock_quantity', 0))
                ->badge($this->getModel()::where('stock_quantity', 0)->count()),
        ];
    }
}
