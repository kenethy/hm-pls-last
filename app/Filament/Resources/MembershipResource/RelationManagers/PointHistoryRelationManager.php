<?php

namespace App\Filament\Resources\MembershipResource\RelationManagers;

use App\Models\MembershipPointHistory;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PointHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'pointHistory';

    protected static ?string $title = 'Riwayat Poin';

    protected static ?string $recordTitleAttribute = 'points';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('points')
                    ->label('Jumlah Poin')
                    ->required()
                    ->numeric(),

                Forms\Components\Select::make('type')
                    ->label('Tipe')
                    ->options([
                        'manual' => 'Penambahan Manual',
                        'service' => 'Servis',
                        'bonus' => 'Bonus',
                        'promo' => 'Promo',
                        'redeem' => 'Penukaran',
                        'expired' => 'Kadaluarsa',
                        'adjustment' => 'Penyesuaian',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->required(),

                Forms\Components\TextInput::make('reference')
                    ->label('Referensi')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('points')
            ->columns([
                Tables\Columns\TextColumn::make('points')
                    ->label('Poin')
                    ->numeric()
                    ->sortable()
                    ->color(fn(int $state): string => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'manual' => 'Penambahan Manual',
                        'service' => 'Servis',
                        'bonus' => 'Bonus',
                        'promo' => 'Promo',
                        'redeem' => 'Penukaran',
                        'expired' => 'Kadaluarsa',
                        'adjustment' => 'Penyesuaian',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'manual', 'service', 'bonus', 'promo' => 'success',
                        'redeem', 'expired', 'adjustment' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable(),

                Tables\Columns\TextColumn::make('service.invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'manual' => 'Penambahan Manual',
                        'service' => 'Servis',
                        'bonus' => 'Bonus',
                        'promo' => 'Promo',
                        'redeem' => 'Penukaran',
                        'expired' => 'Kadaluarsa',
                        'adjustment' => 'Penyesuaian',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Poin')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        return $data;
                    })
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Poin berhasil ditambahkan')
                            ->body('Poin telah berhasil ditambahkan ke akun membership.')
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions needed
            ]);
    }
}
