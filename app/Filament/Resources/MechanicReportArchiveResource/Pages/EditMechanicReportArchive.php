<?php

namespace App\Filament\Resources\MechanicReportArchiveResource\Pages;

use App\Filament\Resources\MechanicReportArchiveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMechanicReportArchive extends EditRecord
{
    protected static string $resource = MechanicReportArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
