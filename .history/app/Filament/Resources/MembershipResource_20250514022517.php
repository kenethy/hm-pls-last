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
        return $user && $user->role === 'admin';
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
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('membership_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('card_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lifetime_points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('join_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
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
            'index' => Pages\ListMemberships::route('/'),
            'create' => Pages\CreateMembership::route('/create'),
            'edit' => Pages\EditMembership::route('/{record}/edit'),
        ];
    }
}
