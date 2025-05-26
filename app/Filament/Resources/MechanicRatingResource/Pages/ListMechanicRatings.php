<?php

namespace App\Filament\Resources\MechanicRatingResource\Pages;

use App\Filament\Resources\MechanicRatingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMechanicRatings extends ListRecords
{
    protected static string $resource = MechanicRatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
