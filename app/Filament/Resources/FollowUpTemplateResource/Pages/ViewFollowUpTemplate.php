<?php

namespace App\Filament\Resources\FollowUpTemplateResource\Pages;

use App\Filament\Resources\FollowUpTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFollowUpTemplate extends ViewRecord
{
    protected static string $resource = FollowUpTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
