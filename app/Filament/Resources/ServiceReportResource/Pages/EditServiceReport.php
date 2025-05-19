<?php

namespace App\Filament\Resources\ServiceReportResource\Pages;

use App\Filament\Resources\ServiceReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceReport extends EditRecord
{
    protected static string $resource = ServiceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('checklist')
                ->label('Edit Checklist')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(fn () => $this->getResource()::getUrl('checklist', ['record' => $this->record]))
                ->color('warning'),
        ];
    }
}
