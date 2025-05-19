<?php

namespace App\Filament\Resources\ServiceReportTemplateResource\Pages;

use App\Filament\Resources\ServiceReportTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceReportTemplate extends CreateRecord
{
    protected static string $resource = ServiceReportTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If this is set as default, unset other defaults
        if ($data['is_default'] ?? false) {
            \App\Models\ServiceReportTemplate::where('is_default', true)
                ->update(['is_default' => false]);
        }

        return $data;
    }
}
