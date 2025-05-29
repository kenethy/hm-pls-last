<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppConfigResource\Pages;
use App\Models\WhatsAppConfig;
use App\Services\WhatsAppService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WhatsAppConfigResource extends Resource
{
    protected static ?string $model = WhatsAppConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Konfigurasi WhatsApp';

    protected static ?string $modelLabel = 'Konfigurasi WhatsApp';

    protected static ?string $pluralModelLabel = 'Konfigurasi WhatsApp';

    protected static ?string $navigationGroup = 'WhatsApp Integration';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Konfigurasi API')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Konfigurasi')
                            ->required()
                            ->default('Default WhatsApp Config')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('api_url')
                            ->label('URL API WhatsApp')
                            ->required()
                            ->url()
                            ->default('http://localhost:3000')
                            ->helperText('URL server WhatsApp API (contoh: http://localhost:3000)'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('api_username')
                                    ->label('Username API')
                                    ->helperText('Username untuk Basic Auth (opsional)'),

                                Forms\Components\TextInput::make('api_password')
                                    ->label('Password API')
                                    ->password()
                                    ->helperText('Password untuk Basic Auth (opsional)'),
                            ]),
                    ]),

                Forms\Components\Section::make('Konfigurasi Webhook')
                    ->schema([
                        Forms\Components\TextInput::make('webhook_secret')
                            ->label('Webhook Secret')
                            ->required()
                            ->default('secret')
                            ->helperText('Secret key untuk validasi webhook'),

                        Forms\Components\TextInput::make('webhook_url')
                            ->label('Webhook URL')
                            ->url()
                            ->helperText('URL untuk menerima webhook dari WhatsApp API'),
                    ]),

                Forms\Components\Section::make('Auto Reply')
                    ->schema([
                        Forms\Components\Toggle::make('auto_reply_enabled')
                            ->label('Aktifkan Auto Reply')
                            ->default(false),

                        Forms\Components\Textarea::make('auto_reply_message')
                            ->label('Pesan Auto Reply')
                            ->rows(3)
                            ->visible(fn (Forms\Get $get) => $get('auto_reply_enabled'))
                            ->helperText('Pesan yang akan dikirim otomatis saat menerima pesan'),
                    ]),

                Forms\Components\Section::make('Status & Catatan')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Hanya satu konfigurasi yang bisa aktif'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('api_url')
                    ->label('URL API')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('connection_status_display')
                    ->label('Koneksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Connected' => 'success',
                        'Disconnected' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('last_connected_at')
                    ->label('Terakhir Terhubung')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\Action::make('test_connection')
                    ->label('Test Koneksi')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (WhatsAppConfig $record) {
                        $service = new WhatsAppService();
                        $result = $service->testConnection();

                        if ($result['success']) {
                            Notification::make()
                                ->title('Koneksi Berhasil')
                                ->success()
                                ->body($result['message'])
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Koneksi Gagal')
                                ->danger()
                                ->body($result['message'])
                                ->send();
                        }
                    }),

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
            'index' => Pages\ListWhatsAppConfigs::route('/'),
            'create' => Pages\CreateWhatsAppConfig::route('/create'),
            'edit' => Pages\EditWhatsAppConfig::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc');
    }
}
