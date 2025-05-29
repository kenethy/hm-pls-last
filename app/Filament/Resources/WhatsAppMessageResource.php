<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppMessageResource\Pages;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WhatsAppMessageResource extends Resource
{
    protected static ?string $model = WhatsAppMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-oval-left-ellipsis';

    protected static ?string $navigationLabel = 'Pesan WhatsApp';

    protected static ?string $modelLabel = 'Pesan WhatsApp';

    protected static ?string $pluralModelLabel = 'Pesan WhatsApp';

    protected static ?string $navigationGroup = 'WhatsApp Integration';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pesan')
                    ->schema([
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Nomor Telepon')
                            ->required()
                            ->tel()
                            ->helperText('Format: 628123456789'),

                        Forms\Components\Select::make('message_type')
                            ->label('Tipe Pesan')
                            ->options([
                                'text' => 'Teks',
                                'image' => 'Gambar',
                                'file' => 'File',
                                'contact' => 'Kontak',
                                'link' => 'Link',
                                'location' => 'Lokasi',
                            ])
                            ->default('text')
                            ->required(),

                        Forms\Components\Textarea::make('content')
                            ->label('Isi Pesan')
                            ->required()
                            ->rows(4),

                        Forms\Components\Textarea::make('caption')
                            ->label('Caption')
                            ->rows(2)
                            ->visible(fn (Forms\Get $get) => in_array($get('message_type'), ['image', 'file'])),
                    ]),

                Forms\Components\Section::make('Relasi')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('service_id')
                            ->label('Service')
                            ->relationship('service', 'id')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('follow_up_template_id')
                            ->label('Template Follow-up')
                            ->relationship('followUpTemplate', 'name')
                            ->searchable()
                            ->preload(),
                    ]),

                Forms\Components\Section::make('Status & Metadata')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Menunggu',
                                'sent' => 'Terkirim',
                                'delivered' => 'Diterima',
                                'read' => 'Dibaca',
                                'failed' => 'Gagal',
                            ])
                            ->default('pending'),

                        Forms\Components\Toggle::make('is_automated')
                            ->label('Otomatis')
                            ->default(false),

                        Forms\Components\Select::make('triggered_by')
                            ->label('Dipicu Oleh')
                            ->options([
                                'manual' => 'Manual',
                                'service_completion' => 'Selesai Servis',
                                'scheduled' => 'Terjadwal',
                            ])
                            ->default('manual'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Nomor Telepon')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('message_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'text' => 'Teks',
                        'image' => 'Gambar',
                        'file' => 'File',
                        'contact' => 'Kontak',
                        'link' => 'Link',
                        'location' => 'Lokasi',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'gray',
                        'image' => 'info',
                        'file' => 'warning',
                        'contact' => 'success',
                        'link' => 'primary',
                        'location' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('content')
                    ->label('Isi Pesan')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (WhatsAppMessage $record): string => $record->getStatusDisplay())
                    ->color(fn (WhatsAppMessage $record): string => $record->getStatusColor()),

                Tables\Columns\IconColumn::make('is_automated')
                    ->label('Otomatis')
                    ->boolean()
                    ->trueIcon('heroicon-o-cog-6-tooth')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('info')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('triggered_by')
                    ->label('Dipicu Oleh')
                    ->formatStateUsing(fn (WhatsAppMessage $record): string => $record->getTriggeredByDisplay())
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Dikirim')
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
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'sent' => 'Terkirim',
                        'delivered' => 'Diterima',
                        'read' => 'Dibaca',
                        'failed' => 'Gagal',
                    ]),

                Tables\Filters\SelectFilter::make('message_type')
                    ->label('Tipe Pesan')
                    ->options([
                        'text' => 'Teks',
                        'image' => 'Gambar',
                        'file' => 'File',
                        'contact' => 'Kontak',
                        'link' => 'Link',
                        'location' => 'Lokasi',
                    ]),

                Tables\Filters\TernaryFilter::make('is_automated')
                    ->label('Otomatis'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('resend')
                    ->label('Kirim Ulang')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (WhatsAppMessage $record) => in_array($record->status, ['failed', 'pending']))
                    ->action(function (WhatsAppMessage $record) {
                        $service = new WhatsAppService();
                        $result = $service->sendTextMessage(
                            phoneNumber: $record->phone_number,
                            message: $record->content,
                            serviceId: $record->service_id,
                            customerId: $record->customer_id,
                            followUpTemplateId: $record->follow_up_template_id,
                            isAutomated: $record->is_automated,
                            triggeredBy: 'manual'
                        );

                        if ($result['success']) {
                            Notification::make()
                                ->title('Pesan Berhasil Dikirim Ulang')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Gagal Mengirim Ulang Pesan')
                                ->danger()
                                ->body($result['message'])
                                ->send();
                        }
                    }),

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
            'index' => Pages\ListWhatsAppMessages::route('/'),
            'create' => Pages\CreateWhatsAppMessage::route('/create'),
            'view' => Pages\ViewWhatsAppMessage::route('/{record}'),
            'edit' => Pages\EditWhatsAppMessage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'service', 'followUpTemplate']);
    }
}
