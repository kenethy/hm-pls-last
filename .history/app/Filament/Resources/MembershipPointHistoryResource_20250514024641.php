<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipPointHistoryResource\Pages;
use App\Filament\Resources\MembershipPointHistoryResource\RelationManagers;
use App\Models\Membership;
use App\Models\MembershipPointHistory;
use App\Models\Service;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MembershipPointHistoryResource extends Resource
{
    protected static ?string $model = MembershipPointHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Riwayat Poin';

    protected static ?string $modelLabel = 'Riwayat Poin Membership';

    protected static ?string $navigationGroup = 'Manajemen Pelanggan';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Membership')
                    ->schema([
                        Forms\Components\Select::make('membership_id')
                            ->label('Membership')
                            ->relationship('membership', 'membership_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                    ]),

                Forms\Components\Section::make('Detail Transaksi Poin')
                    ->schema([
                        Forms\Components\TextInput::make('points')
                            ->label('Jumlah Poin')
                            ->required()
                            ->numeric()
                            ->default(0),

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

                        Forms\Components\Select::make('service_id')
                            ->label('Servis Terkait')
                            ->relationship('service', 'invoice_number')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('reference')
                            ->label('Referensi')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Deskripsi')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('membership.membership_number')
                    ->label('Nomor Membership')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('membership.customer.name')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->sortable(),

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
                Tables\Filters\Filter::make('positive_points')
                    ->label('Poin Positif')
                    ->query(fn(Builder $query): Builder => $query->where('points', '>', 0)),
                Tables\Filters\Filter::make('negative_points')
                    ->label('Poin Negatif')
                    ->query(fn(Builder $query): Builder => $query->where('points', '<', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListMembershipPointHistories::route('/'),
            'create' => Pages\CreateMembershipPointHistory::route('/create'),
            'edit' => Pages\EditMembershipPointHistory::route('/{record}/edit'),
        ];
    }
}
