<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Membership;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'membership';

    protected static ?string $title = 'Membership';

    protected static ?string $recordTitleAttribute = 'membership_number';

    public static function canViewForRecord(Model $ownerRecord): bool
    {
        return $ownerRecord->isMember();
    }

    public function form(Form $form): Form
    {
        return $form
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

                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('membership_number')
            ->columns([
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
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Membership')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['customer_id'] = $this->ownerRecord->id;
                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Membership berhasil dibuat')
                            ->body('Pelanggan ini sekarang terdaftar sebagai member.')
                    ),
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
                ]),
            ])
            ->bulkActions([
                // No bulk actions needed
            ]);
    }
}
