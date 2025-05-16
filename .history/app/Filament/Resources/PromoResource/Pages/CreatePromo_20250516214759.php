<?php

namespace App\Filament\Resources\PromoResource\Pages;

use App\Filament\Resources\PromoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\View\View;

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

    protected function getFooter(): ?string
    {
        // Include the JavaScript helper
        $this->registerJsFile(asset('js/promo-image-upload.js'));

        return null;
    }
}
