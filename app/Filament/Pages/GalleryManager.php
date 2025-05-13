<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class GalleryManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static string $view = 'filament.pages.gallery-manager';
    
    protected static ?string $navigationLabel = 'Gallery Manager';
    
    protected static ?string $title = 'Gallery Manager';
    
    protected static ?string $navigationGroup = 'Konten Website';
    
    protected static ?int $navigationSort = 10;
    
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user() && (auth()->user()->role === 'admin' || auth()->user()->role === 'super_admin');
    }
}
