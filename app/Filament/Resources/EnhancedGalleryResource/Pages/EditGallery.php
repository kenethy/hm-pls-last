<?php

namespace App\Filament\Resources\EnhancedGalleryResource\Pages;

use App\Filament\Resources\EnhancedGalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGallery extends EditRecord
{
    protected static string $resource = EnhancedGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('viewOnSite')
                ->label('Lihat di Website')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->url(fn () => route('gallery.show', $this->record->slug))
                ->openUrlInNewTab(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
