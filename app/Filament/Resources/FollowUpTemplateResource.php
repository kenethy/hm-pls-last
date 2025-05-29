<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FollowUpTemplateResource\Pages;
use App\Models\FollowUpTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FollowUpTemplateResource extends Resource
{
    protected static ?string $model = FollowUpTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Template Follow-up';

    protected static ?string $modelLabel = 'Template Follow-up';

    protected static ?string $pluralModelLabel = 'Template Follow-up';

    protected static ?string $navigationGroup = 'WhatsApp Integration';

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

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(2),

                        Forms\Components\Select::make('trigger_event')
                            ->label('Event Pemicu')
                            ->options([
                                'service_completion' => 'Selesai Servis',
                                'booking_confirmation' => 'Konfirmasi Booking',
                                'payment_reminder' => 'Pengingat Pembayaran',
                                'custom' => 'Custom',
                            ])
                            ->default('service_completion')
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Pesan Template')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->label('Isi Pesan')
                            ->required()
                            ->rows(6)
                            ->helperText('Gunakan variabel seperti {customer_name}, {service_type}, {vehicle_info}, {completion_date}, {total_cost}, {workshop_name}'),

                        Forms\Components\Placeholder::make('available_variables_info')
                            ->label('Variabel yang Tersedia')
                            ->content(function () {
                                $variables = FollowUpTemplate::getAvailableVariables();
                                $content = '';
                                foreach ($variables as $variable => $description) {
                                    $content .= "• {$variable} - {$description}\n";
                                }
                                return $content;
                            }),
                    ]),

                Forms\Components\Section::make('Konfigurasi WhatsApp')
                    ->schema([
                        Forms\Components\Toggle::make('whatsapp_enabled')
                            ->label('Aktifkan WhatsApp')
                            ->default(true)
                            ->live(),

                        Forms\Components\Select::make('whatsapp_message_type')
                            ->label('Tipe Pesan WhatsApp')
                            ->options([
                                'text' => 'Teks',
                                'image' => 'Gambar',
                                'file' => 'File',
                                'contact' => 'Kontak',
                                'link' => 'Link',
                            ])
                            ->default('text')
                            ->visible(fn (Forms\Get $get) => $get('whatsapp_enabled')),

                        Forms\Components\Toggle::make('include_attachments')
                            ->label('Sertakan Lampiran')
                            ->default(false)
                            ->visible(fn (Forms\Get $get) => $get('whatsapp_enabled') && in_array($get('whatsapp_message_type'), ['image', 'file'])),

                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('File Lampiran')
                            ->directory('whatsapp-attachments')
                            ->visible(fn (Forms\Get $get) => $get('include_attachments')),

                        Forms\Components\Textarea::make('whatsapp_caption')
                            ->label('Caption WhatsApp')
                            ->rows(3)
                            ->visible(fn (Forms\Get $get) => $get('whatsapp_enabled') && in_array($get('whatsapp_message_type'), ['image', 'file']))
                            ->helperText('Caption untuk gambar atau file (opsional)'),
                    ]),

                Forms\Components\Section::make('Pengaturan Otomatis')
                    ->schema([
                        Forms\Components\Toggle::make('auto_send_on_completion')
                            ->label('Kirim Otomatis Saat Selesai')
                            ->default(false)
                            ->helperText('Kirim pesan secara otomatis ketika servis selesai'),

                        Forms\Components\TextInput::make('delay_minutes')
                            ->label('Delay (Menit)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Delay pengiriman dalam menit (0 = kirim langsung)'),
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

                Tables\Columns\TextColumn::make('trigger_event')
                    ->label('Event Pemicu')
                    ->formatStateUsing(fn (FollowUpTemplate $record): string => $record->getTriggerEventDisplay())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'service_completion' => 'success',
                        'booking_confirmation' => 'info',
                        'payment_reminder' => 'warning',
                        'custom' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('whatsapp_enabled')
                    ->label('WhatsApp')
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-left-right')
                    ->falseIcon('heroicon-o-chat-bubble-left-right')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('whatsapp_message_type')
                    ->label('Tipe Pesan')
                    ->formatStateUsing(fn (FollowUpTemplate $record): string => $record->getWhatsAppMessageTypeDisplay())
                    ->badge()
                    ->visible(fn (FollowUpTemplate $record) => $record->whatsapp_enabled),

                Tables\Columns\IconColumn::make('auto_send_on_completion')
                    ->label('Auto Send')
                    ->boolean()
                    ->trueIcon('heroicon-o-cog-6-tooth')
                    ->falseIcon('heroicon-o-cog-6-tooth')
                    ->trueColor('info')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Digunakan')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('whatsapp_sent_count')
                    ->label('WhatsApp Terkirim')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Terakhir Digunakan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trigger_event')
                    ->label('Event Pemicu')
                    ->options([
                        'service_completion' => 'Selesai Servis',
                        'booking_confirmation' => 'Konfirmasi Booking',
                        'payment_reminder' => 'Pengingat Pembayaran',
                        'custom' => 'Custom',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),

                Tables\Filters\TernaryFilter::make('whatsapp_enabled')
                    ->label('WhatsApp Aktif'),

                Tables\Filters\TernaryFilter::make('auto_send_on_completion')
                    ->label('Auto Send'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListFollowUpTemplates::route('/'),
            'create' => Pages\CreateFollowUpTemplate::route('/create'),
            'view' => Pages\ViewFollowUpTemplate::route('/{record}'),
            'edit' => Pages\EditFollowUpTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc');
    }
}
