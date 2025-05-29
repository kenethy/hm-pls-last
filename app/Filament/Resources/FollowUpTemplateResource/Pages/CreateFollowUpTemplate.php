<?php

namespace App\Filament\Resources\FollowUpTemplateResource\Pages;

use App\Filament\Resources\FollowUpTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFollowUpTemplate extends CreateRecord
{
    protected static string $resource = FollowUpTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
