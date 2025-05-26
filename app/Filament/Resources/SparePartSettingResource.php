<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SparePartSettingResource\Pages;
use App\Models\SparePartSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SparePartSettingResource extends Resource
{
    protected static ?string $model = SparePartSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Sparepart';

    protected static ?string $modelLabel = 'Pengaturan Sparepart';

    protected static ?string $pluralModelLabel = 'Pengaturan Sparepart';

    protected static ?string $navigationGroup = 'Manajemen Sparepart';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengaturan')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Kunci Pengaturan')
                            ->required()
                            ->maxLength(255)
                            ->unique(SparePartSetting::class, 'key', ignoreRecord: true)
                            ->disabled(fn ($record) => $record !== null), // Disable editing key for existing records

                        Forms\Components\Select::make('type')
                            ->label('Tipe Data')
                            ->options([
                                'text' => 'Teks',
                                'textarea' => 'Teks Panjang',
                                'boolean' => 'Ya/Tidak',
                                'number' => 'Angka',
                            ])
                            ->required()
                            ->live()
                            ->disabled(fn ($record) => $record !== null), // Disable editing type for existing records

                        Forms\Components\TextInput::make('description')
                            ->label('Deskripsi')
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Nilai Pengaturan')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->label('Nilai')
                            ->required()
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['text', 'number'])),

                        Forms\Components\Textarea::make('value')
                            ->label('Nilai')
                            ->required()
                            ->rows(4)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'textarea'),

                        Forms\Components\Toggle::make('value')
                            ->label('Nilai')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'boolean'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Kunci')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'gray',
                        'textarea' => 'blue',
                        'boolean' => 'green',
                        'number' => 'orange',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('value')
                    ->label('Nilai')
                    ->limit(30)
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->type === 'boolean') {
                            return $state ? 'Ya' : 'Tidak';
                        }
                        return $state;
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'text' => 'Teks',
                        'textarea' => 'Teks Panjang',
                        'boolean' => 'Ya/Tidak',
                        'number' => 'Angka',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !in_array($record->key, [
                        'pricing_notification_enabled',
                        'pricing_notification_title',
                        'pricing_notification_message',
                        'pricing_notification_cta_text',
                        'pricing_notification_whatsapp_number',
                        'pricing_notification_display_type',
                    ])), // Prevent deletion of core settings
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('key');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSparePartSettings::route('/'),
            'create' => Pages\CreateSparePartSetting::route('/create'),
            'edit' => Pages\EditSparePartSetting::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}
