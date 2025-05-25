<?php

namespace App\Filament\Resources\SparePartResource\Pages;

use App\Filament\Resources\SparePartResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSparePart extends CreateRecord
{
    protected static string $resource = SparePartResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate meta fields if not provided
        if (empty($data['meta_title'])) {
            $data['meta_title'] = $data['name'] . ' - Hartono Motor';
        }

        if (empty($data['meta_description'])) {
            $data['meta_description'] = $data['short_description'] ?? 
                'Beli ' . $data['name'] . ' berkualitas di Hartono Motor. Sparepart original dan aftermarket dengan harga terbaik.';
        }

        return $data;
    }
}
