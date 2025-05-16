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
        // Include the JavaScript helper
        $this->registerJsFile(asset('js/promo-image-upload.js'));

        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(static::getResource()::getUrl())
                ->color('gray'),
        ];
    }
}
