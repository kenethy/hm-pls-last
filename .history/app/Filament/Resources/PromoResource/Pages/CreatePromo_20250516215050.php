<?php

namespace App\Filament\Resources\PromoResource\Pages;

use App\Filament\Resources\PromoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePromo extends CreateRecord
{
    protected static string $resource = PromoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(static::getResource()::getUrl())
                ->color('gray'),
        ];
    }

    public function getScripts(): array
    {
        return [
            asset('js/promo-image-upload.js'),
        ];
    }
}
