<?php

namespace App\Filament\Resources\PromoResource\Pages;

use App\Filament\Resources\PromoResource;
use App\Filament\Widgets\PromoUploaderWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPromos extends ListRecords
{
    protected static string $resource = PromoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Promo Baru')
                ->icon('heroicon-o-plus'),
            Actions\Action::make('openPromoUploader')
                ->label('Promo Uploader')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('success')
                ->url(route('admin.promo-uploader'))
                ->openUrlInNewTab(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PromoUploaderWidget::class,
        ];
    }
}
