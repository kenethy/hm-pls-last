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
        // Get the default template
        $template = ServiceReportTemplate::getDefault();

        if ($template) {
            // Create checklist items from the template
            $order = 1;
            foreach ($template->checklist_items as $item) {
                $this->record->checklistItems()->create([
                    'order' => $order++,
                    'inspection_point' => $item['inspection_point'],
                    'status' => 'ok', // Default status
                    'notes' => '',
                ]);
            }

            Notification::make()
                ->title('Checklist berhasil dibuat dari template')
                ->success()
                ->send();
        }
    }
}
