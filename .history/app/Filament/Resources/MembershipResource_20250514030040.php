<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipResource\Pages;
use App\Filament\Resources\MembershipResource\RelationManagers;
use App\Models\Customer;
use App\Models\Membership;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MembershipResource extends Resource
{
    protected static ?string $model = Membership::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Membership';

    protected static ?string $modelLabel = 'Membership';

    protected static ?string $navigationGroup = 'Manajemen Pelanggan';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pelanggan')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Pelanggan')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Nomor Telepon')
                                    ->required()
                                    ->tel()
                                    ->unique()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),
                            ])
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action
                                    ->modalHeading('Tambah Pelanggan Baru')
                                    ->modalSubmitActionLabel('Tambah Pelanggan')
                                    ->modalWidth('lg');
                            }),
                    ]),

                Forms\Components\Section::make('Detail Membership')
                    ->schema([
                        Forms\Components\TextInput::make('membership_number')
                            ->label('Nomor Membership')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn() => Membership::generateMembershipNumber())
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Select::make('card_type')
                            ->label('Tipe Kartu')
                            ->options([
                                'regular' => 'Regular',
                                'silver' => 'Silver',
                                'gold' => 'Gold',
                                'platinum' => 'Platinum',
                            ])
                            ->required()
                            ->default('regular'),

                        Forms\Components\DatePicker::make('join_date')
                            ->label('Tanggal Bergabung')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Tanggal Kadaluarsa')
                            ->default(fn() => now()->addYear())
                            ->minDate(fn(Get $get) => $get('join_date')),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Poin')
                    ->schema([
                        Forms\Components\TextInput::make('points')
                            ->label('Poin Saat Ini')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->dehydrated(),

                        Forms\Components\TextInput::make('lifetime_points')
                            ->label('Total Poin Seumur Hidup')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Catatan')
                    ->schema([
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
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.phone')
                    ->label('Nomor Telepon')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nomor telepon disalin!')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('membership_number')
                    ->label('Nomor Membership')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nomor membership disalin!')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('card_type')
                    ->label('Tipe Kartu')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'regular' => 'gray',
                        'silver' => 'zinc',
                        'gold' => 'warning',
                        'platinum' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->searchable(),

                Tables\Columns\TextColumn::make('points')
                    ->label('Poin')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lifetime_points')
                    ->label('Total Poin')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('join_date')
                    ->label('Tanggal Bergabung')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Tanggal Kadaluarsa')
                    ->date()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('card_type')
                    ->label('Tipe Kartu')
                    ->options([
                        'regular' => 'Regular',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'platinum' => 'Platinum',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('addPoints')
                        ->label('Tambah Poin')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('points')
                                ->label('Jumlah Poin')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->default(1),
                            Forms\Components\Select::make('type')
                                ->label('Tipe')
                                ->options([
                                    'manual' => 'Penambahan Manual',
                                    'bonus' => 'Bonus',
                                    'promo' => 'Promo',
                                ])
                                ->required()
                                ->default('manual'),
                            Forms\Components\Textarea::make('description')
                                ->label('Deskripsi')
                                ->required(),
                        ])
                        ->action(function (Membership $record, array $data): void {
                            $record->addPoints(
                                $data['points'],
                                $data['type'],
                                $data['description']
                            );

                            Notification::make()
                                ->title('Poin berhasil ditambahkan')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('deductPoints')
                        ->label('Kurangi Poin')
                        ->icon('heroicon-o-minus-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\TextInput::make('points')
                                ->label('Jumlah Poin')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->default(1),
                            Forms\Components\Select::make('type')
                                ->label('Tipe')
                                ->options([
                                    'redeem' => 'Penukaran',
                                    'expired' => 'Kadaluarsa',
                                    'adjustment' => 'Penyesuaian',
                                ])
                                ->required()
                                ->default('redeem'),
                            Forms\Components\Textarea::make('description')
                                ->label('Deskripsi')
                                ->required(),
                        ])
                        ->action(function (Membership $record, array $data): void {
                            // Check if there are enough points
                            if ($record->points < $data['points']) {
                                Notification::make()
                                    ->title('Poin tidak cukup')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $record->deductPoints(
                                $data['points'],
                                $data['type'],
                                $data['description']
                            );

                            Notification::make()
                                ->title('Poin berhasil dikurangi')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('sendWhatsApp')
                        ->label('WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->url(function (Membership $record) {
                            // Format the phone number
                            $phone = preg_replace('/[^0-9]/', '', $record->customer->phone);

                            // If the number starts with 0, replace it with 62
                            if (substr($phone, 0, 1) === '0') {
                                $phone = '62' . substr($phone, 1);
                            }
                            // If the number doesn't start with 62, add it
                            elseif (substr($phone, 0, 2) !== '62') {
                                $phone = '62' . $phone;
                            }

                            return "https://wa.me/{$phone}";
                        })
                        ->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('addPointsBulk')
                        ->label('Tambah Poin (Massal)')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('points')
                                ->label('Jumlah Poin')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->default(1),
                            Forms\Components\Select::make('type')
                                ->label('Tipe')
                                ->options([
                                    'manual' => 'Penambahan Manual',
                                    'bonus' => 'Bonus',
                                    'promo' => 'Promo',
                                ])
                                ->required()
                                ->default('manual'),
                            Forms\Components\Textarea::make('description')
                                ->label('Deskripsi')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->addPoints(
                                    $data['points'],
                                    $data['type'],
                                    $data['description']
                                );
                                $count++;
                            }

                            Notification::make()
                                ->title("Poin berhasil ditambahkan ke {$count} member")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PointHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemberships::route('/'),
            'create' => Pages\CreateMembership::route('/create'),
            'edit' => Pages\EditMembership::route('/{record}/edit'),
        ];
    }
}
