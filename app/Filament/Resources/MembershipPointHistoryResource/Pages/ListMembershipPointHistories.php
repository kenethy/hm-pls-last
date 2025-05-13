<?php

namespace App\Filament\Resources\MembershipPointHistoryResource\Pages;

use App\Filament\Resources\MembershipPointHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMembershipPointHistories extends ListRecords
{
    protected static string $resource = MembershipPointHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
