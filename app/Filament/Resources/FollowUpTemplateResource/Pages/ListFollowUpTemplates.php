<?php

namespace App\Filament\Resources\FollowUpTemplateResource\Pages;

use App\Filament\Resources\FollowUpTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFollowUpTemplates extends ListRecords
{
    protected static string $resource = FollowUpTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
