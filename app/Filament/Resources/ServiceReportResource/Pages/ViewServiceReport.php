<?php

namespace App\Filament\Resources\ServiceReportResource\Pages;

use App\Filament\Resources\ServiceReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ViewServiceReport extends ViewRecord
{
    protected static string $resource = ServiceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('checklist')
                ->label('Edit Checklist')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(fn () => $this->getResource()::getUrl('checklist', ['record' => $this->record]))
                ->color('warning'),
            Actions\Action::make('share')
                ->label('Bagikan')
                ->icon('heroicon-o-share')
                ->color('primary')
                ->action(function () {
                    return Notification::make()
                        ->title('Link laporan disalin ke clipboard')
                        ->body(new HtmlString('Link: <strong>' . $this->record->getUrl() . '</strong>'))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('preview')
                ->label('Pratinjau')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->url(fn () => $this->record->getUrl())
                ->openUrlInNewTab(),
        ];
    }
}
