<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MechanicReportResource\Pages;
use App\Models\MechanicReport;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class MechanicReportResource extends Resource
{
    protected static ?string $model = MechanicReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Servis & Booking';

    protected static ?string $navigationLabel = 'Rekap Montir';

    protected static ?string $modelLabel = 'Rekap Montir';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && ($user->role === 'admin' || $user->role === 'staff');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Rekap')
                    ->schema([
                        Forms\Components\Select::make('mechanic_id')
                            ->label('Montir')
                            ->relationship('mechanic', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Toggle::make('is_cumulative')
                            ->label('Laporan Kumulatif')
                            ->default(true)
                            ->disabled()
                            ->helperText('Laporan kumulatif menampilkan total dari semua servis'),

                        Forms\Components\DatePicker::make('period_start')
                            ->label('Periode Mulai')
                            ->visible(fn(callable $get) => !$get('is_cumulative'))
                            ->disabled(),

                        Forms\Components\DatePicker::make('period_end')
                            ->label('Periode Akhir')
                            ->visible(fn(callable $get) => !$get('is_cumulative'))
                            ->disabled(),

                        Forms\Components\TextInput::make('services_count')
                            ->label('Jumlah Servis')
                            ->numeric()
                            ->default(0)
                            ->disabled(),

                        Forms\Components\TextInput::make('total_labor_cost')
                            ->label('Total Biaya Jasa')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('last_calculated_at')
                            ->label('Terakhir Dihitung')
                            ->disabled()
                            ->visible(fn(callable $get) => $get('is_cumulative')),

                        Forms\Components\DateTimePicker::make('period_reset_at')
                            ->label('Direset Pada')
                            ->disabled()
                            ->visible(fn(callable $get) => $get('is_cumulative')),
                    ])->columns(2),

                Forms\Components\Section::make('Status Pembayaran')
                    ->schema([
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Sudah Dibayar')
                            ->default(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('paid_at', now());
                                } else {
                                    $set('paid_at', null);
                                }
                            }),

                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Tanggal Pembayaran')
                            ->visible(fn(callable $get) => $get('is_paid'))
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Catatan tambahan tentang pembayaran')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mechanic.name')
                    ->label('Nama Montir')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_cumulative')
                    ->label('Tipe')
                    ->boolean()
                    ->trueIcon('heroicon-o-infinity')
                    ->falseIcon('heroicon-o-calendar-days')
                    ->trueColor('primary')
                    ->falseColor('gray')
                    ->tooltip(fn($record) => $record->is_cumulative ? 'Laporan Kumulatif' : 'Laporan Periode'),

                Tables\Columns\TextColumn::make('period_display')
                    ->label('Periode')
                    ->searchable(false)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('services_count')
                    ->label('Jumlah Servis')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_labor_cost')
                    ->label('Total Biaya Jasa')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Status Pembayaran')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Tanggal Pembayaran')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_paid')
                    ->label('Status Pembayaran')
                    ->options([
                        '1' => 'Sudah Dibayar',
                        '0' => 'Belum Dibayar',
                    ]),

                Tables\Filters\Filter::make('week')
                    ->label('Filter Tanggal')
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['week_start'] && !$data['week_end']) {
                            return null;
                        }

                        $indicator = 'Periode: ';

                        if ($data['week_start']) {
                            $indicator .= 'Dari ' . \Carbon\Carbon::parse($data['week_start'])->format('d M Y');
                        }

                        if ($data['week_end']) {
                            $indicator .= ($data['week_start'] ? ' ' : '') . 'Sampai ' . \Carbon\Carbon::parse($data['week_end'])->format('d M Y');
                        }

                        return $indicator;
                    })
                    ->form([
                        Forms\Components\DatePicker::make('week_start')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('week_end')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['week_start'],
                                function (Builder $query, $date) {
                                    // If the selected date is not a Monday (start of week),
                                    // we need to find reports that include this date
                                    return $query->where(function ($q) use ($date) {
                                        // Find reports where week_start is before or equal to the selected date
                                        // AND week_end is after or equal to the selected date
                                        // OR where week_start is after or equal to the selected date
                                        $q->where(function ($subQ) use ($date) {
                                            $subQ->where('week_start', '<=', $date)
                                                ->where('week_end', '>=', $date);
                                        })->orWhere('week_start', '>=', $date);
                                    });
                                }
                            )
                            ->when(
                                $data['week_end'],
                                function (Builder $query, $date) {
                                    // Similar logic for end date
                                    return $query->where(function ($q) use ($date) {
                                        // Find reports where week_end is before or equal to the selected date
                                        // OR where week_start is before or equal to the selected date
                                        // AND week_end is after or equal to the selected date
                                        $q->where('week_end', '<=', $date)
                                            ->orWhere(function ($subQ) use ($date) {
                                                $subQ->where('week_start', '<=', $date)
                                                    ->where('week_end', '>=', $date);
                                            });
                                    });
                                }
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('recalculateReport')
                    ->label('Perbarui')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->tooltip('Menghitung ulang statistik kumulatif dari data servis terbaru')
                    ->visible(fn(MechanicReport $record) => $record->is_cumulative)
                    ->action(function (MechanicReport $record) {
                        try {
                            $record->recalculateCumulative();

                            Notification::make()
                                ->title('Laporan kumulatif berhasil diperbarui')
                                ->success()
                                ->body('Statistik kumulatif telah dihitung ulang dari data servis terbaru.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal memperbarui laporan')
                                ->danger()
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('resetCumulative')
                    ->label('Reset')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->tooltip('Reset laporan kumulatif dan arsipkan data saat ini')
                    ->visible(fn(MechanicReport $record) => $record->is_cumulative)
                    ->requiresConfirmation()
                    ->modalHeading('Reset Laporan Kumulatif')
                    ->modalDescription('Apakah Anda yakin ingin mereset laporan kumulatif? Data saat ini akan diarsipkan dan laporan akan dimulai dari nol.')
                    ->modalSubmitActionLabel('Ya, Reset')
                    ->action(function (MechanicReport $record) {
                        try {
                            $record->resetCumulative('manual_reset_from_admin');

                            Notification::make()
                                ->title('Laporan kumulatif berhasil direset')
                                ->success()
                                ->body('Data lama telah diarsipkan dan laporan dimulai dari nol.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal mereset laporan')
                                ->danger()
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Tandai Dibayar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(MechanicReport $record) => !$record->is_paid)
                    ->action(function (MechanicReport $record) {
                        $record->markAsPaid();

                        Notification::make()
                            ->title('Rekap montir telah ditandai sebagai dibayar')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('viewServices')
                    ->label('Riwayat Servis')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('info')
                    ->url(fn(MechanicReport $record) => route('mechanic.services.history', ['id' => $record->id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAsPaidBulk')
                        ->label('Tandai Dibayar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(function ($record) {
                                if (!$record->is_paid) {
                                    $record->markAsPaid();
                                }
                            });

                            Notification::make()
                                ->title('Rekap montir telah ditandai sebagai dibayar')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // No relation managers needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMechanicReports::route('/'),
            'edit' => Pages\EditMechanicReport::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }
}
