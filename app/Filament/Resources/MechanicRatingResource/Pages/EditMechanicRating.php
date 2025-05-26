<?php

namespace App\Filament\Resources\MechanicRatingResource\Pages;

use App\Filament\Resources\MechanicRatingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMechanicRating extends EditRecord
{
    protected static string $resource = MechanicRatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
