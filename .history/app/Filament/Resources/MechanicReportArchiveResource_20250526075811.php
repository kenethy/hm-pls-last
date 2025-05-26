<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MechanicReportArchiveResource\Pages;
use App\Models\MechanicReportArchive;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MechanicReportArchiveResource extends Resource
{
    protected static ?string $model = MechanicReportArchive::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Arsip Rekap Montir';

    protected static ?string $modelLabel = 'Arsip Rekap Montir';

    protected static ?string $pluralModelLabel = 'Arsip Rekap Montir';

    protected static ?string $navigationGroup = 'Manajemen Montir';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Arsip')
                    ->schema([
                        Forms\Components\Select::make('mechanic_id')
                            ->label('Montir')
                            ->relationship('mechanic', 'name')
                            ->disabled(),

                        Forms\Components\DatePicker::make('week_start')
                            ->label('Minggu Mulai')
                            ->disabled(),

                        Forms\Components\DatePicker::make('week_end')
                            ->label('Minggu Akhir')
                            ->disabled(),

                        Forms\Components\TextInput::make('services_count')
                            ->label('Jumlah Servis')
                            ->disabled(),

                        Forms\Components\TextInput::make('total_labor_cost')
                            ->label('Total Biaya Jasa')
                            ->prefix('Rp')
                            ->disabled(),

                        Forms\Components\TextInput::make('archive_reason')
                            ->label('Alasan Arsip')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('archived_at')
                            ->label('Diarsipkan Pada')
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->disabled()
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mechanic.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('week_start')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('week_end')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('services_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_labor_cost')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_paid')
                    ->boolean(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('archived_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('archive_reason')
                    ->searchable(),
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
            'index' => Pages\ListMechanicReportArchives::route('/'),
            'create' => Pages\CreateMechanicReportArchive::route('/create'),
            'edit' => Pages\EditMechanicReportArchive::route('/{record}/edit'),
        ];
    }
}
