<?php

namespace App\Filament\Resources\EnhancedGalleryResource\Pages;

use App\Filament\Resources\EnhancedGalleryResource;
use App\Filament\Widgets\EnhancedGalleryWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Gallery;
use App\Models\GalleryCategory;
use Illuminate\Support\Str;
use Filament\Forms;
use Filament\Notifications\Notification;

class ListGalleries extends ListRecords
{
    protected static string $resource = EnhancedGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Foto Baru')
                ->icon('heroicon-o-plus'),
                
            Actions\Action::make('uploadMultiple')
                ->label('Upload Batch')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('category_id')
                        ->label('Kategori')
                        ->options(GalleryCategory::pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nama Kategori')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description')
                                ->label('Deskripsi')
                                ->rows(2),
                        ]),

                    Forms\Components\FileUpload::make('images')
                        ->label('Gambar (Multiple)')
                        ->multiple()
                        ->image()
                        ->imageEditor()
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9')
                        ->imageResizeTargetWidth('1200')
                        ->imageResizeTargetHeight('675')
                        ->directory('galleries')
                        ->visibility('public')
                        ->maxFiles(10)
                        ->helperText('Maksimal 10 file, ukuran maksimal 5MB per file')
                        ->required(),

                    Forms\Components\TextInput::make('title_prefix')
                        ->label('Awalan Judul')
                        ->helperText('Akan ditambahkan nomor urut di belakangnya')
                        ->required(),
                        
                    Forms\Components\Toggle::make('is_featured')
                        ->label('Tampilkan di Halaman Utama')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $category_id = $data['category_id'];
                    $title_prefix = $data['title_prefix'];
                    $is_featured = $data['is_featured'] ?? false;
                    $images = $data['images'];
                    
                    $count = 0;
                    
                    foreach ($images as $index => $image) {
                        Gallery::create([
                            'title' => $title_prefix . ' ' . ($index + 1),
                            'slug' => Str::slug($title_prefix . ' ' . ($index + 1)),
                            'image_path' => $image,
                            'category_id' => $category_id,
                            'is_featured' => $is_featured,
                            'order' => 0,
                        ]);
                        
                        $count++;
                    }
                    
                    Notification::make()
                        ->title('Berhasil mengupload ' . $count . ' gambar')
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('openGalleryManager')
                ->label('Gallery Manager')
                ->icon('heroicon-o-photo')
                ->color('warning')
                ->url(route('admin.simple-gallery'))
                ->openUrlInNewTab(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EnhancedGalleryWidget::class,
        ];
    }
}
