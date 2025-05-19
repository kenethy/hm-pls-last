<?php

namespace App\Filament\Resources\ServiceReportTemplateResource\Pages;

use App\Filament\Resources\ServiceReportTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceReportTemplate extends EditRecord
{
    protected static string $resource = ServiceReportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If this is set as default, unset other defaults
        if ($data['is_default'] ?? false) {
            \App\Models\ServiceReportTemplate::where('id', '!=', $this->record->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        return $data;
    }
}
