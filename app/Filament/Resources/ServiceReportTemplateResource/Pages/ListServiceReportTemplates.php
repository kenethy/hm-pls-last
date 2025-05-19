<?php

namespace App\Filament\Resources\ServiceReportTemplateResource\Pages;

use App\Filament\Resources\ServiceReportTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceReportTemplates extends ListRecords
{
    protected static string $resource = ServiceReportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
