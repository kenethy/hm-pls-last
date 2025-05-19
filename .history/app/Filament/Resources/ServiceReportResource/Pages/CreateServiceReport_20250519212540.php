<?php

namespace App\Filament\Resources\ServiceReportResource\Pages;

use App\Filament\Resources\ServiceReportResource;
use App\Models\ServiceReportTemplate;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateServiceReport extends CreateRecord
{
    protected static string $resource = ServiceReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('checklist', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        // Get the form data
        $data = $this->data;

        // Check if checklist_items exists in the form data
        if (isset($data['checklist_items']) && is_array($data['checklist_items'])) {
            // Create checklist items from the form data
            $order = 1;
            foreach ($data['checklist_items'] as $item) {
                $this->record->checklistItems()->create([
                    'order' => $order++,
                    'inspection_point' => $item['inspection_point'],
                    'status' => $item['status'] ?? 'ok',
                    'notes' => $item['notes'] ?? '',
                ]);
            }

            Notification::make()
                ->title('Checklist berhasil disimpan')
                ->success()
                ->send();
        }
    }
}
