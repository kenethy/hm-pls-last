<?php

namespace App\Filament\Resources\FollowUpTemplateResource\Pages;

use App\Filament\Resources\FollowUpTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFollowUpTemplate extends EditRecord
{
    protected static string $resource = FollowUpTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
