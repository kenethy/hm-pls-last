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
                    ->label('Montir')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('week_period')
                    ->label('Periode Minggu')
                    ->searchable(false)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('services_count')
                    ->label('Jumlah Servis')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_labor_cost')
                    ->label('Total Biaya Jasa')
                    ->searchable(false)
                    ->sortable(false),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Status Bayar')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('archive_reason')
                    ->label('Alasan Arsip')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('archived_at')
                    ->label('Diarsipkan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('archive_reason')
                    ->label('Alasan Arsip')
                    ->options([
                        'weekly_to_cumulative_migration' => 'Migrasi ke Kumulatif',
                        'manual_reset_from_admin' => 'Reset Manual Admin',
                        'system_migration' => 'Migrasi Sistem',
                    ]),

                Tables\Filters\Filter::make('archived_date')
                    ->label('Tanggal Arsip')
                    ->form([
                        Forms\Components\DatePicker::make('archived_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('archived_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['archived_from'],
                                fn(Builder $query, $date) => $query->whereDate('archived_at', '>=', $date)
                            )
                            ->when(
                                $data['archived_until'],
                                fn(Builder $query, $date) => $query->whereDate('archived_at', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail'),
            ])
            ->bulkActions([
                // No bulk actions for archives - read-only
            ])
            ->defaultSort('archived_at', 'desc');
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
