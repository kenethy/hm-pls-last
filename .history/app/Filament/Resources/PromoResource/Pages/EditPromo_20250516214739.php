<?php

namespace App\Filament\Resources\PromoResource\Pages;

use App\Filament\Resources\PromoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\View\View;

class EditPromo extends EditRecord
{
    protected static string $resource = PromoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(static::getResource()::getUrl())
                ->color('gray'),
        ];
    }

    protected function getFooter(): View
    {
        return view('filament.pages.promo.footer');
    }
}
