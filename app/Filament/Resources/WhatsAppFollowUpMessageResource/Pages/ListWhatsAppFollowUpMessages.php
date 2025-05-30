<?php

namespace App\Filament\Resources\WhatsAppFollowUpMessageResource\Pages;

use App\Filament\Resources\WhatsAppFollowUpMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppFollowUpMessages extends ListRecords
{
    protected static string $resource = WhatsAppFollowUpMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
