<?php

namespace App\Filament\Resources\WhatsAppFollowUpMessageResource\Pages;

use App\Filament\Resources\WhatsAppFollowUpMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsAppFollowUpMessage extends ViewRecord
{
    protected static string $resource = WhatsAppFollowUpMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
