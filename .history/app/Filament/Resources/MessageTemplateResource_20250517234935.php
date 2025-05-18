<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageTemplateResource\Pages;
use App\Models\MessageTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class MessageTemplateResource extends Resource
{
    protected static ?string $model = MessageTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Template Pesan';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 3;

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

                        Forms\Components\Select::make('type')
                            ->label('Tipe Template')
                            ->options([
                                'follow_up' => 'Follow-up Standar',
                                'feedback' => 'Minta Feedback',
                                'promo' => 'Tawarkan Promo',
                                'custom' => 'Kustom',
                            ])
                            ->default('follow_up')
                            ->required(),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Template Default')
                            ->helperText('Jika diaktifkan, template ini akan menjadi default untuk tipe yang dipilih')
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->helperText('Nonaktifkan untuk menyembunyikan template ini dari daftar pilihan')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Konten Template')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('Konten Pesan')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'redo',
                                'undo',
                            ])
                            ->helperText('Gunakan variabel di bawah ini untuk menambahkan data dinamis ke template')
                            ->columnSpanFull(),

                        Forms\Components\Card::make()
                            ->schema([
                                Forms\Components\Placeholder::make('variables')
                                    ->label('Variabel yang Tersedia')
                                    ->content(
                                        '{customer_name} - Nama Pelanggan<br>' .
                                            '{vehicle_model} - Model Kendaraan<br>' .
                                            '{license_plate} - Nomor Plat<br>' .
                                            '{service_date} - Tanggal Servis<br>' .
                                            '{service_type} - Jenis Servis<br>' .
                                            '{service_description} - Deskripsi Servis<br>' .
                                            '{mechanic_names} - Nama Montir<br>' .
                                            '{invoice_number} - Nomor Nota<br>' .
                                            '{service_cost} - Biaya Servis'
                                    )
                                    ->columnSpanFull(),
                            ]),
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'follow_up' => 'success',
                        'feedback' => 'info',
                        'promo' => 'warning',
                        'custom' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'follow_up' => 'Follow-up',
                        'feedback' => 'Feedback',
                        'promo' => 'Promo',
                        'custom' => 'Kustom',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Template')
                    ->options([
                        'follow_up' => 'Follow-up Standar',
                        'feedback' => 'Minta Feedback',
                        'promo' => 'Tawarkan Promo',
                        'custom' => 'Kustom',
                    ]),

                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Template Default'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('toggleActive')
                        ->label('Toggle Status Aktif')
                        ->icon('heroicon-o-check-circle')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->is_active = !$record->is_active;
                                $record->save();
                            }
                        }),
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
            'index' => Pages\ListMessageTemplates::route('/'),
            'create' => Pages\CreateMessageTemplate::route('/create'),
            'edit' => Pages\EditMessageTemplate::route('/{record}/edit'),
        ];
    }
}
