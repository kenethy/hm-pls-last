<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceReportTemplateResource\Pages;
use App\Models\ServiceReportTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;

class ServiceReportTemplateResource extends Resource
{
    protected static ?string $model = ServiceReportTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Servis & Booking';

    protected static ?string $navigationLabel = 'Template Laporan';

    protected static ?string $modelLabel = 'Template Laporan';

    protected static ?string $pluralModelLabel = 'Template Laporan';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Template')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Template')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('vehicle_type')
                            ->label('Tipe Kendaraan')
                            ->maxLength(255)
                            ->helperText('Opsional, untuk template khusus tipe kendaraan tertentu'),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Template Default')
                            ->helperText('Jika diaktifkan, template ini akan digunakan sebagai default saat membuat laporan baru')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, ServiceReportTemplate $record = null) {
                                if ($state && $record) {
                                    // Unset other default templates
                                    ServiceReportTemplate::where('id', '!=', $record->id)
                                        ->where('is_default', true)
                                        ->update(['is_default' => false]);
                                }
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Checklist Pemeriksaan')
                    ->schema([
                        Forms\Components\Repeater::make('checklist_items')
                            ->label('Titik Pemeriksaan')
                            ->schema([
                                Forms\Components\TextInput::make('inspection_point')
                                    ->label('Titik Pemeriksaan')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->defaultItems(50)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['inspection_point'] ?? null),
                    ]),

                Forms\Components\Section::make('Layanan & Rekomendasi')
                    ->schema([
                        Forms\Components\Repeater::make('services_performed')
                            ->label('Layanan yang Dilakukan')
                            ->schema([
                                Forms\Components\TextInput::make('service_name')
                                    ->label('Nama Layanan')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(2),
                            ])
                            ->defaultItems(1)
                            ->columns(2),

                        Forms\Components\RichEditor::make('recommendations')
                            ->label('Rekomendasi Default')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('warranty_info')
                            ->label('Informasi Garansi Default')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                            ])
                            ->default('<p>Garansi Tune-Up 2 Minggu</p><p>Syarat dan ketentuan berlaku.</p>')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Template')
                    ->searchable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label('Tipe Kendaraan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceReportTemplates::route('/'),
            'create' => Pages\CreateServiceReportTemplate::route('/create'),
            'edit' => Pages\EditServiceReportTemplate::route('/{record}/edit'),
        ];
    }
}
