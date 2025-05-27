<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MechanicRatingResource\Pages;
use App\Filament\Resources\MechanicRatingResource\RelationManagers;
use App\Models\MechanicRating;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MechanicRatingResource extends Resource
{
    protected static ?string $model = MechanicRating::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationLabel = 'Rating Montir';
    protected static ?string $modelLabel = 'Rating Montir';
    protected static ?string $pluralModelLabel = 'Rating Montir';
    protected static ?string $navigationGroup = 'Laporan & Analisis';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'id')
                    ->required(),
                Forms\Components\Select::make('mechanic_id')
                    ->relationship('mechanic', 'name')
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name'),
                Forms\Components\TextInput::make('customer_name')
                    ->required(),
                Forms\Components\TextInput::make('customer_phone')
                    ->tel()
                    ->required(),
                Forms\Components\TextInput::make('rating')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('comment')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('service_type')
                    ->required(),
                Forms\Components\TextInput::make('vehicle_info'),
                Forms\Components\DateTimePicker::make('service_date')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mechanic.name')
                    ->label('Montir')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('No. Telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn(string $state): string => str_repeat('⭐', (int) $state) . ' (' . $state . '/5)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Jenis Servis')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle_info')
                    ->label('Kendaraan')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Komentar')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('service_date')
                    ->label('Tanggal Servis')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Rating')
                    ->date('d M Y H:i')
                    ->sortable(),
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
            'index' => Pages\ListMechanicRatings::route('/'),
            'create' => Pages\CreateMechanicRating::route('/create'),
            'edit' => Pages\EditMechanicRating::route('/{record}/edit'),
        ];
    }
}
