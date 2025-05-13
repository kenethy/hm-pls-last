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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('membership_id')
                    ->relationship('membership', 'id')
                    ->required(),
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'id'),
                Forms\Components\TextInput::make('points')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('type')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('reference'),
                Forms\Components\TextInput::make('created_by')
                    ->numeric(),
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
