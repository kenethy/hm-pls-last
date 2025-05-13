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
                Tables\Columns\TextColumn::make('membership.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListMembershipPointHistories::route('/'),
            'create' => Pages\CreateMembershipPointHistory::route('/create'),
            'edit' => Pages\EditMembershipPointHistory::route('/{record}/edit'),
        ];
    }
}
