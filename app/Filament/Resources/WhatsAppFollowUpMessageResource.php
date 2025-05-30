<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppFollowUpMessageResource\Pages;
use App\Models\WhatsAppFollowUpMessage;
use App\Jobs\SendWhatsAppFollowUpJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class WhatsAppFollowUpMessageResource extends Resource
{
    protected static ?string $model = WhatsAppFollowUpMessage::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationLabel = 'WhatsApp Follow-ups';
    protected static ?string $modelLabel = 'WhatsApp Follow-up';
    protected static ?string $pluralModelLabel = 'WhatsApp Follow-ups';
    protected static ?int $navigationSort = 101;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Message Details')
                    ->schema([
                        Forms\Components\Select::make('message_template_id')
                            ->label('Message Template')
                            ->relationship('messageTemplate', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('message_content')
                            ->label('Message Content')
                            ->required()
                            ->rows(4),
                    ]),

                Forms\Components\Section::make('Scheduling')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Scheduled At')
                            ->helperText('Leave empty to send immediately'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'sent' => 'Sent',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Service Reference')
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->label('Related Service')
                            ->relationship('service', 'id')
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('message_content')
                    ->label('Message')
                    ->limit(50)
                    ->tooltip(function (WhatsAppFollowUpMessage $record): string {
                        return $record->message_content;
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'sent',
                        'danger' => 'failed',
                        'secondary' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('retry_count')
                    ->label('Retries')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('scheduled')
                    ->form([
                        Forms\Components\DatePicker::make('scheduled_from')
                            ->label('Scheduled From'),
                        Forms\Components\DatePicker::make('scheduled_until')
                            ->label('Scheduled Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['scheduled_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_at', '>=', $date),
                            )
                            ->when(
                                $data['scheduled_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send_now')
                    ->label('Send Now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (WhatsAppFollowUpMessage $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (WhatsAppFollowUpMessage $record) {
                        try {
                            SendWhatsAppFollowUpJob::dispatch($record);
                            
                            Notification::make()
                                ->title('Message Queued')
                                ->body('Follow-up message has been queued for immediate sending.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to Queue Message')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (WhatsAppFollowUpMessage $record): bool => $record->status === 'failed' && $record->canRetry())
                    ->requiresConfirmation()
                    ->action(function (WhatsAppFollowUpMessage $record) {
                        try {
                            $record->resetForRetry();
                            SendWhatsAppFollowUpJob::dispatch($record);
                            
                            Notification::make()
                                ->title('Message Retry Queued')
                                ->body('Failed message has been reset and queued for retry.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to Retry Message')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (WhatsAppFollowUpMessage $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (WhatsAppFollowUpMessage $record) {
                        $record->update(['status' => 'cancelled']);
                        
                        Notification::make()
                            ->title('Message Cancelled')
                            ->body('Follow-up message has been cancelled.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('send_selected')
                        ->label('Send Selected')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $queued = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    SendWhatsAppFollowUpJob::dispatch($record);
                                    $queued++;
                                }
                            }
                            
                            Notification::make()
                                ->title('Messages Queued')
                                ->body("{$queued} messages have been queued for sending.")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('cancel_selected')
                        ->label('Cancel Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $cancelled = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update(['status' => 'cancelled']);
                                    $cancelled++;
                                }
                            }
                            
                            Notification::make()
                                ->title('Messages Cancelled')
                                ->body("{$cancelled} messages have been cancelled.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsAppFollowUpMessages::route('/'),
            'create' => Pages\CreateWhatsAppFollowUpMessage::route('/create'),
            'view' => Pages\ViewWhatsAppFollowUpMessage::route('/{record}'),
            'edit' => Pages\EditWhatsAppFollowUpMessage::route('/{record}/edit'),
        ];
    }
}
