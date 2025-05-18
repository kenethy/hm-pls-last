<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageTemplateResource\Pages;
use App\Filament\Resources\MessageTemplateResource\RelationManagers;
use App\Models\MessageTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListMessageTemplates::route('/'),
            'create' => Pages\CreateMessageTemplate::route('/create'),
            'edit' => Pages\EditMessageTemplate::route('/{record}/edit'),
        ];
    }
}
