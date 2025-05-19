<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceReportResource\Pages;
use App\Models\Mechanic;
use App\Models\Service;
use App\Models\ServiceReport;
use App\Models\ServiceReportTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class ServiceReportResource extends Resource
{
    protected static ?string $model = ServiceReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Servis & Booking';

    protected static ?string $navigationLabel = 'Laporan Digital';

    protected static ?string $modelLabel = 'Laporan Digital';

    protected static ?string $pluralModelLabel = 'Laporan Digital';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        // Get default template for checklist items
        $defaultTemplate = \App\Models\ServiceReportTemplate::getDefault();
        $defaultChecklistItems = [];

        if ($defaultTemplate && is_array($defaultTemplate->checklist_items)) {
            $defaultChecklistItems = collect($defaultTemplate->checklist_items)->map(function ($item) {
                return [
                    'inspection_point' => $item['inspection_point'],
                    'status' => 'ok',
                    'notes' => '',
                ];
            })->toArray();
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Laporan')
                    ->description('Informasi dasar tentang laporan digital')
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->label('Servis')
                            ->options(function () {
                                return Service::where('status', 'completed')
                                    ->get()
                                    ->mapWithKeys(function ($service) {
                                        return [$service->id => "{$service->customer_name} - {$service->license_plate}"];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $service = Service::find($state);
                                    if ($service) {
                                        $set('customer_name', $service->customer_name);
                                        $set('license_plate', $service->license_plate);
                                        $set('car_model', $service->car_model);
                                        $set('service_date', $service->completed_at ?? now());

                                        // Set technician name if available
                                        if ($service->mechanics->isNotEmpty()) {
                                            $set('technician_name', $service->mechanics->first()->name);
                                        }

                                        // Always load checklist items from default template
                                        $template = \App\Models\ServiceReportTemplate::getDefault();
                                        if ($template && is_array($template->checklist_items)) {
                                            $checklistItems = collect($template->checklist_items)->map(function ($item) {
                                                return [
                                                    'inspection_point' => $item['inspection_point'],
                                                    'status' => 'ok',
                                                    'notes' => '',
                                                ];
                                            })->toArray();

                                            // Force reset and set checklist items
                                            $set('checklist_items', []);
                                            // Small delay to ensure the reset takes effect
                                            usleep(100000); // 100ms delay
                                            $set('checklist_items', $checklistItems);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('title')
                            ->label('Judul Laporan')
                            ->default('Laporan Digital Paket Napas Baru Premium')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('customer_name')
                            ->label('Nama Pelanggan')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('license_plate')
                            ->label('Nomor Plat')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('car_model')
                            ->label('Model Kendaraan')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('technician_name')
                            ->label('Teknisi Penanggung Jawab')
                            ->options(function () {
                                return Mechanic::active()
                                    ->get()
                                    ->mapWithKeys(function ($mechanic) {
                                        return [$mechanic->name => $mechanic->name];
                                    })
                                    ->toArray();
                            })
                            ->searchable(),

                        Forms\Components\DateTimePicker::make('service_date')
                            ->label('Tanggal Servis')
                            ->required(),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Tanggal Kedaluwarsa')
                            ->default(now()->addDays(7))
                            ->required()
                            ->helperText('Laporan akan kedaluwarsa setelah tanggal ini'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Ringkasan Pekerjaan')
                    ->schema([
                        Forms\Components\Repeater::make('services_performed')
                            ->label('Layanan yang Dilakukan')
                            ->schema([
                                Forms\Components\TextInput::make('service_name')
                                    ->label('Nama Layanan')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(2),
                            ])
                            ->defaultItems(1)
                            ->columns(2),

                        Forms\Components\Repeater::make('additional_services')
                            ->label('Layanan Tambahan')
                            ->schema([
                                Forms\Components\TextInput::make('service_name')
                                    ->label('Nama Layanan')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(2),
                            ])
                            ->columns(2),

                        Forms\Components\RichEditor::make('summary')
                            ->label('Ringkasan Pekerjaan')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Rekomendasi & Garansi')
                    ->schema([
                        Forms\Components\RichEditor::make('recommendations')
                            ->label('Rekomendasi')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('warranty_info')
                            ->label('Informasi Garansi')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                            ])
                            ->default('<p>Garansi Tune-Up 2 Minggu</p><p>Syarat dan ketentuan berlaku.</p>')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Checklist Pemeriksaan 50 Titik')
                    ->description('Isi status dan catatan untuk setiap titik pemeriksaan')
                    ->schema([
                        Forms\Components\Repeater::make('checklist_items')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('inspection_point')
                                    ->label('Titik Pemeriksaan')
                                    ->required()
                                    ->readonly()
                                    ->columnSpan(2),
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'ok' => 'OK',
                                        'warning' => 'Waspada',
                                        'needs_repair' => 'Harus Diperbaiki',
                                    ])
                                    ->default('ok')
                                    ->required(),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(1),
                            ])
                            ->columns(4)
                            ->itemLabel(fn(array $state): ?string => $state['inspection_point'] ?? null)
                            ->collapsible()
                            ->collapsed(true)
                            ->reorderable(false)
                            ->addActionLabel('Tambah Titik Pemeriksaan')
                            ->hiddenLabel()
                            ->defaultItems(count($defaultChecklistItems))
                            ->afterStateHydrated(function ($state, Forms\Set $set) use ($defaultChecklistItems) {
                                // Always set the checklist items on form load
                                $set('checklist_items', $defaultChecklistItems);
                            })
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->limit(30),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('license_plate')
                    ->label('Nomor Plat')
                    ->searchable(),

                Tables\Columns\TextColumn::make('car_model')
                    ->label('Model Kendaraan')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('service_date')
                    ->label('Tanggal Servis')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Kedaluwarsa')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->color(fn(ServiceReport $record) => $record->hasExpired() ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Unik')
                    ->searchable()
                    ->copyable()
                    ->color('primary'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ]),
                Tables\Filters\Filter::make('expires_at')
                    ->form([
                        Forms\Components\DatePicker::make('expires_from')
                            ->label('Kedaluwarsa Dari'),
                        Forms\Components\DatePicker::make('expires_until')
                            ->label('Kedaluwarsa Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['expires_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('expires_at', '>=', $date),
                            )
                            ->when(
                                $data['expires_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('expires_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->color('success'),
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\Action::make('checklist')
                    ->label('Checklist')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn(ServiceReport $record) => route('filament.admin.resources.service-reports.checklist', $record))
                    ->color('warning'),
                Tables\Actions\Action::make('share')
                    ->label('Bagikan')
                    ->icon('heroicon-o-share')
                    ->color('primary')
                    ->action(function (ServiceReport $record) {
                        return Notification::make()
                            ->title('Link laporan disalin ke clipboard')
                            ->body(new HtmlString('Link: <strong>' . $record->getUrl() . '</strong>'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListServiceReports::route('/'),
            'create' => Pages\CreateServiceReport::route('/create'),
            'edit' => Pages\EditServiceReport::route('/{record}/edit'),
            'view' => Pages\ViewServiceReport::route('/{record}'),
            'checklist' => Pages\EditServiceReportChecklist::route('/{record}/checklist'),
        ];
    }
}
