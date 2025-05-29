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
                            ->default('http://whatsapp-api:3000')
                            ->helperText('URL server WhatsApp API (contoh: http://whatsapp-api:3000 untuk Docker atau http://localhost:3000 untuk lokal)'),

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
                            ->default(fn() => url('/api/whatsapp/webhook'))
                            ->helperText('URL untuk menerima webhook dari WhatsApp API. Default: ' . url('/api/whatsapp/webhook')),
                    ]),

                Forms\Components\Section::make('Auto Reply')
                    ->schema([
                        Forms\Components\Toggle::make('auto_reply_enabled')
                            ->label('Aktifkan Auto Reply')
                            ->default(false),

                        Forms\Components\Textarea::make('auto_reply_message')
                            ->label('Pesan Auto Reply')
                            ->rows(3)
                            ->visible(fn(Forms\Get $get) => $get('auto_reply_enabled'))
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
                    ->color(fn(string $state): string => match ($state) {
                        'Connected' => 'success',
                        'Disconnected' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('auth_status_display')
                    ->label('Status WhatsApp')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Authenticated' => 'success',
                        'Not Authenticated' => 'warning',
                        default => 'gray',
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
                        // Temporarily activate this config for testing
                        $originalActive = WhatsAppConfig::getActive();
                        if ($originalActive && $originalActive->id !== $record->id) {
                            $originalActive->update(['is_active' => false]);
                        }
                        $record->update(['is_active' => true]);

                        $service = new WhatsAppService();
                        $result = $service->testConnection();

                        // Restore original active config if different
                        if ($originalActive && $originalActive->id !== $record->id) {
                            $record->update(['is_active' => false]);
                            $originalActive->update(['is_active' => true]);
                        }

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

                Tables\Actions\Action::make('authenticate')
                    ->label('Autentikasi WhatsApp')
                    ->icon('heroicon-o-qr-code')
                    ->color('warning')
                    ->url(fn(WhatsAppConfig $record) => static::getExternalApiUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn(WhatsAppConfig $record) => $record->is_active),

                Tables\Actions\Action::make('get_qr_code')
                    ->label('Dapatkan QR Code')
                    ->icon('heroicon-o-camera')
                    ->color('info')
                    ->action(function (WhatsAppConfig $record) {
                        $service = new WhatsAppService();
                        $result = $service->getQRCode();

                        if ($result['success']) {
                            Notification::make()
                                ->title('QR Code Berhasil Dibuat')
                                ->success()
                                ->body('QR Code telah dibuat. Silakan scan dengan WhatsApp Anda.')
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view_qr')
                                        ->label('Lihat QR Code')
                                        ->url($result['qr_url'])
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Gagal Membuat QR Code')
                                ->danger()
                                ->body($result['message'])
                                ->send();
                        }
                    })
                    ->visible(fn(WhatsAppConfig $record) => $record->is_active),

                Tables\Actions\Action::make('test_message')
                    ->label('Test Pesan')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Nomor Telepon')
                            ->required()
                            ->placeholder('08123456789 atau 628123456789')
                            ->helperText('Masukkan nomor telepon untuk test pesan'),

                        Forms\Components\Textarea::make('message')
                            ->label('Pesan Test')
                            ->required()
                            ->default('Halo! Ini adalah pesan test dari sistem Hartono Motor. Jika Anda menerima pesan ini, berarti integrasi WhatsApp berhasil!')
                            ->rows(4),
                    ])
                    ->action(function (array $data, WhatsAppConfig $record): void {
                        $service = new WhatsAppService();
                        $result = $service->sendTextMessage(
                            phoneNumber: $data['phone_number'],
                            message: $data['message'],
                            triggeredBy: 'manual_test'
                        );

                        if ($result['success']) {
                            Notification::make()
                                ->title('Pesan Berhasil Dikirim')
                                ->success()
                                ->body('Pesan test telah berhasil dikirim ke ' . $data['phone_number'])
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Gagal Mengirim Pesan')
                                ->danger()
                                ->body($result['message'])
                                ->send();
                        }
                    })
                    ->visible(fn(WhatsAppConfig $record) => $record->is_active),

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

    /**
     * Get external API URL that can be accessed from browser.
     */
    public static function getExternalApiUrl(WhatsAppConfig $record): string
    {
        // Convert internal Docker URL to external accessible URL
        $internalUrl = $record->api_url;

        // Replace Docker internal hostnames with external domain
        $externalUrl = str_replace([
            'http://whatsapp-api:3000',
            'http://hartono-whatsapp-api:3000',
            'http://localhost:3000'
        ], [
            'http://hartonomotor.xyz:3000',
            'http://hartonomotor.xyz:3000',
            'http://hartonomotor.xyz:3000'
        ], $internalUrl);

        // If no replacement was made, default to hartonomotor.xyz:3000
        if ($externalUrl === $internalUrl) {
            $externalUrl = 'http://hartonomotor.xyz:3000';
        }

        return $externalUrl;
    }

    /**
     * Get QR code URL for external access.
     */
    public static function getQRCodeUrl(WhatsAppConfig $record): string
    {
        return static::getExternalApiUrl($record) . '/app/login';
    }
}
