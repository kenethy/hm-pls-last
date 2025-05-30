<?php

namespace App\Filament\Resources\WhatsAppFollowUpMessageResource\Pages;

use App\Filament\Resources\WhatsAppFollowUpMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppFollowUpMessage extends EditRecord
{
    protected static string $resource = WhatsAppFollowUpMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
