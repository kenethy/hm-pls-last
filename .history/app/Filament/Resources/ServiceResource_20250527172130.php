<?php

namespace App\Filament\Resources;

use App\Events\ServiceStatusChanged;
use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Membership;
use App\Models\Service;
use App\Models\Vehicle; // Added import for Vehicle model
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Policies\ServicePolicy;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $recordTitleAttribute = 'customer_name';

    protected static ?string $navigationGroup = 'Servis & Booking';

    protected static ?int $navigationSort = 1;

    public static function getPolicy(): string
    {
        return ServicePolicy::class;
    }

    protected static ?string $navigationIcon = 'heroicon-o-wrench';

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $modelLabel = 'Servis Kendaraan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pelanggan')
                    ->schema([
                        Forms\Components\Select::make('booking_id')
                            ->label('Booking Terkait')
                            ->options(function () {
                                return Booking::where('status', '!=', 'cancelled')
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $booking = Booking::find($state);
                                    if ($booking) {
                                        $set('customer_name', $booking->name);
                                        $set('phone', $booking->phone);
                                        $set('car_model', $booking->car_model);
                                        $set('service_type', $booking->service_type);

                                        // Check if customer exists with this phone number
                                        $customer = Customer::where('phone', $booking->phone)->first();
                                        if ($customer) {
                                            $set('customer_id', $customer->id);
                                        }
                                    }
                                }
                            })
                            ->nullable(),

                        Forms\Components\Select::make('customer_id')
                            ->label('Pelanggan')
                            ->options(function () {
                                return Customer::whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $customer = Customer::find($state);
                                    if ($customer) {
                                        $set('customer_name', $customer->name);
                                        $set('phone', $customer->phone);

                                        // Reset vehicle_id when customer changes
                                        $set('vehicle_id', null);
                                        $set('car_model', null);
                                        $set('license_plate', null);
                                    }
                                }
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('phone')
                                    ->label('Nomor Telepon')
                                    ->required()
                                    ->tel()
                                    // ->unique() // Consider if unique is needed here or handled elsewhere
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    // ->unique() // Consider if unique is needed here or handled elsewhere
                                    ->maxLength(255),

                                Forms\Components\Select::make('gender')
                                    ->label('Jenis Kelamin')
                                    ->options([
                                        'male' => 'Laki-laki',
                                        'female' => 'Perempuan',
                                        'other' => 'Lainnya',
                                    ])
                                    ->default('male'),

                                Forms\Components\TextInput::make('city')
                                    ->label('Kota')
                                    ->maxLength(255),
                            ])
                            ->nullable(),

                        Forms\Components\TextInput::make('customer_name')
                            ->label('Nama Pelanggan')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->required()
                            ->tel()
                            ->maxLength(20)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $customer = Customer::where('phone', $state)->first();
                                    if ($customer) {
                                        $set('customer_id', $customer->id);
                                        $set('customer_name', $customer->name);
                                        $set('vehicle_id', null);
                                        $set('car_model', '');
                                        $set('license_plate', '');
                                        $vehicles = Vehicle::where('customer_id', $customer->id)
                                            ->where('is_active', true)
                                            ->get();
                                        if ($vehicles->count() > 0) {
                                            $vehicleInfo = $vehicles->map(function ($vehicle) {
                                                return "- {$vehicle->model} ({$vehicle->license_plate})";
                                            })->join("\n");
                                            Notification::make()
                                                ->title('Pelanggan ditemukan dengan kendaraan berikut:')
                                                ->body($vehicleInfo)
                                                ->success()
                                                ->send();
                                        } else {
                                            Notification::make()
                                                ->title('Pelanggan ditemukan')
                                                ->body('Pelanggan ini belum memiliki kendaraan terdaftar.')
                                                ->info()
                                                ->send();
                                        }
                                    } else {
                                        $set('customer_id', null);
                                        Notification::make()
                                            ->title('Pelanggan baru')
                                            ->body('Pelanggan dengan nomor telepon ini belum terdaftar. Silakan isi nama pelanggan.')
                                            ->info()
                                            ->send();
                                    }
                                }
                            }),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('vehicle_id')
                                    ->label('Pilih Kendaraan yang Sudah Ada')
                                    ->helperText('Pilih kendaraan yang sudah ada ATAU isi "Model Mobil" dan "Nomor Plat" di bawah untuk membuat kendaraan baru')
                                    ->options(function (callable $get) {
                                        $customerId = $get('customer_id');
                                        if (!$customerId) {
                                            return []; // Return empty array if no customer is selected
                                        }
                                        return Vehicle::where('customer_id', $customerId)
                                            ->get() // Use get() to fetch collection
                                            ->pluck('full_details', 'id') // Assuming full_details attribute exists on Vehicle model
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $vehicle = Vehicle::find($state);
                                            if ($vehicle) {
                                                $set('car_model', $vehicle->model);
                                                $set('license_plate', $vehicle->license_plate);
                                                $set('is_new_vehicle', false); // Mark as not a new vehicle
                                            }
                                        } else {
                                            // If no vehicle is selected, clear related fields and mark as new vehicle potentially
                                            $set('car_model', null);
                                            $set('license_plate', null);
                                            $set('is_new_vehicle', true);
                                        }
                                    })
                                    ->placeholder('-- Pilih Kendaraan yang Sudah Ada --')
                                    ->hidden(fn(callable $get) => empty($get('customer_id'))),

                                Forms\Components\Placeholder::make('vehicle_info')
                                    ->label('ATAU')
                                    ->content('Isi data kendaraan baru di bawah ini')
                                    ->hidden(fn(callable $get) => empty($get('customer_id'))),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('car_model')
                                    ->label('Model Mobil')
                                    ->required()
                                    ->maxLength(255)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        // If car model is updated manually, it implies a new or different vehicle
                                        if ($get('vehicle_id')) {
                                            // If an existing vehicle was selected, but model is changed,
                                            // we might want to treat it as a new vehicle or clear vehicle_id
                                            // For now, let's assume it means they want to register a new one
                                            // or update details of a new one they are entering.
                                            // $set('vehicle_id', null); // Optional: clear if model changes
                                        }
                                        $set('is_new_vehicle', true);
                                    }),

                                Forms\Components\TextInput::make('license_plate')
                                    ->label('Nomor Plat')
                                    ->required()
                                    ->maxLength(255) // Increased length for license plate
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        // If license plate is updated manually, it implies a new or different vehicle
                                        if ($get('vehicle_id')) {
                                            // Similar to car_model, changing license plate might mean a new vehicle
                                            // $set('vehicle_id', null); // Optional: clear if license plate changes
                                        }
                                        $set('is_new_vehicle', true);
                                    }),
                            ]),

                        Forms\Components\Hidden::make('is_new_vehicle')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Servis')
                    ->schema([
                        Forms\Components\Select::make('service_type')
                            ->label('Jenis Servis')
                            ->options([
                                'Servis Berkala' => 'Servis Berkala',
                                'Tune Up Mesin' => 'Tune Up Mesin',
                                'Servis AC' => 'Servis AC',
                                'Ganti Oli' => 'Ganti Oli',
                                'Perbaikan Rem' => 'Perbaikan Rem',
                                'Balancing' => 'Balancing',
                                'Cuci' => 'Cuci',
                                'Paket' => 'Paket',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Servis Berkala')
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi Servis')
                            ->placeholder('Jelaskan detail servis yang dilakukan')
                            ->rows(3),

                    ]),

                Forms\Components\Section::make('Waktu')
                    ->schema([
                        Forms\Components\DateTimePicker::make('entry_time')
                            ->label('Jam Masuk')
                            ->seconds(false)
                            ->timezone('Asia/Jakarta')
                            ->default(fn() => now())
                            ->disabled(fn($record) => $record && $record->entry_time)
                            ->helperText('Otomatis diisi saat servis dibuat'),

                        Forms\Components\DateTimePicker::make('exit_time')
                            ->label('Jam Keluar')
                            ->seconds(false)
                            ->timezone('Asia/Jakarta')
                            ->disabled(fn($record) => $record && $record->status !== 'completed')
                            ->helperText('Otomatis diisi saat servis ditandai selesai'),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status Servis')
                            ->options([
                                'in_progress' => 'Dalam Pengerjaan',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->default('in_progress')
                            ->selectablePlaceholder(false)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state === 'completed') {
                                    // Set exit_time jika status completed
                                    $set('exit_time', now()); // global now() is fine here
                                }
                            }),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Catatan tambahan tentang servis ini')
                            ->rows(3),
                    ]),

                Forms\Components\Section::make('Membership Points')
                    ->schema([
                        Forms\Components\Placeholder::make('membership_status')
                            ->label('Status Membership')
                            ->content(function (callable $get) {
                                $customerId = $get('customer_id');
                                if (!$customerId) {
                                    return 'Pilih pelanggan terlebih dahulu untuk melihat status membership.';
                                }

                                $customer = Customer::find($customerId);
                                if (!$customer) {
                                    return 'Pelanggan tidak ditemukan.';
                                }

                                if (!$customer->isMember()) {
                                    return 'Pelanggan ini belum menjadi member. Anda dapat mendaftarkan pelanggan ini sebagai member di halaman Membership.';
                                }

                                $membership = $customer->membership;
                                if (!$membership->is_active) {
                                    return "Pelanggan ini memiliki membership yang tidak aktif (#{$membership->membership_number}).";
                                }

                                return "Pelanggan ini adalah member aktif (#{$membership->membership_number}) dengan {$membership->points} poin.";
                            }),

                        Forms\Components\Toggle::make('add_membership_points')
                            ->label('Tambahkan Poin Membership')
                            ->helperText('Aktifkan untuk menambahkan poin ke akun membership pelanggan saat servis selesai.')
                            ->default(true)
                            ->visible(function (callable $get) {
                                $customerId = $get('customer_id');
                                if (!$customerId) {
                                    return false;
                                }

                                $customer = Customer::find($customerId);
                                if (!$customer || !$customer->isMember()) {
                                    return false;
                                }

                                $membership = $customer->membership;
                                return $membership->is_active;
                            }),

                        Forms\Components\TextInput::make('membership_points')
                            ->label('Jumlah Poin')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->visible(fn(callable $get) => $get('add_membership_points') === true),

                        Forms\Components\Textarea::make('membership_points_description')
                            ->label('Deskripsi Poin')
                            ->default(function (callable $get) {
                                $serviceType = $get('service_type');
                                $licensePlate = $get('license_plate');
                                return "Poin untuk servis {$serviceType} kendaraan {$licensePlate}";
                            })
                            ->required()
                            ->visible(fn(callable $get) => $get('add_membership_points') === true),
                    ])
                    ->visible(fn(callable $get) => $get('status') === 'completed'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Nomor Telepon')
                    ->searchable(),

                Tables\Columns\TextColumn::make('car_model')
                    ->label('Model Mobil')
                    ->searchable(),

                Tables\Columns\TextColumn::make('license_plate')
                    ->label('Nomor Plat')
                    ->searchable(),

                Tables\Columns\TextColumn::make('vehicle.full_details')
                    ->label('Kendaraan')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('service_type')
                    ->label('Jenis Servis')
                    ->searchable(),

                Tables\Columns\TextColumn::make('mechanics.name')
                    ->label('Montir')
                    ->listWithLineBreaks()
                    ->limitList(2),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Total Biaya')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'in_progress' => 'Dalam Pengerjaan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diupdate')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('entry_time')
                    ->label('Jam Masuk')
                    ->dateTime('d F Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('exit_time')
                    ->label('Jam Keluar')
                    ->dateTime('d F Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nomor Nota')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'in_progress' => 'Dalam Pengerjaan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->placeholder('Semua Status')
                    ->multiple(),

                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Pelanggan')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('vehicle_id')
                    ->label('Kendaraan')
                    ->relationship('vehicle', 'license_plate')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('license_plate')
                    ->label('Nomor Plat')
                    ->form([
                        Forms\Components\TextInput::make('license_plate')
                            ->label('Nomor Plat')
                            ->placeholder('Masukkan nomor plat')
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['license_plate'],
                                fn(Builder $query, $licensePlate): Builder => $query->where('license_plate', 'like', "%{$licensePlate}%"),
                            );
                    }),

                Tables\Filters\Filter::make('service_date')
                    ->label('Tanggal Servis')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markAsCompleted')
                    ->label('Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Service $record) => $record->status === 'in_progress')
                    ->form(function (Service $record) {
                        // Ambil montir yang sudah ada
                        $existingMechanics = $record->mechanics()->pluck('mechanic_id')->toArray();

                        // Ambil biaya jasa total dan biaya jasa per montir
                        $totalLaborCost = $record->labor_cost;

                        // Jika biaya jasa total adalah 0, set default ke nilai yang masuk akal
                        if ($totalLaborCost == 0) {
                            $totalLaborCost = 50000; // Default biaya jasa yang masuk akal
                        }

                        // Log untuk debugging
                        Log::info("Service #{$record->id} labor cost: {$totalLaborCost}");

                        // Inisialisasi mechanic_costs untuk montir yang sudah ada
                        $mechanicCosts = [];
                        foreach ($existingMechanics as $mechanicId) {
                            // Ambil biaya jasa montir dari pivot table jika ada
                            $mechanicLaborCost = $record->mechanics()
                                ->where('mechanic_id', $mechanicId)
                                ->first()
                                ?->pivot
                                ?->labor_cost;

                            // Jika biaya jasa montir adalah 0 atau null, gunakan biaya jasa total
                            if (!$mechanicLaborCost || $mechanicLaborCost == 0) {
                                $mechanicLaborCost = $totalLaborCost;
                            }

                            Log::info("Mechanic #{$mechanicId} labor cost: {$mechanicLaborCost}");

                            $mechanicCosts[] = [
                                'mechanic_id' => $mechanicId,
                                'labor_cost' => $mechanicLaborCost,
                            ];
                        }

                        return [
                            Forms\Components\Section::make('Informasi Nota')
                                ->schema([
                                    Forms\Components\TextInput::make('invoice_number')
                                        ->label('Nomor Nota')
                                        ->required()
                                        ->placeholder('Masukkan nomor nota')
                                        ->helperText('Nomor nota wajib diisi sebagai bukti fisik servis'),
                                ]),

                            Forms\Components\Section::make('Pilih Montir')
                                ->schema([
                                    Forms\Components\Select::make('mechanics')
                                        ->label('Montir yang Mengerjakan')
                                        ->options(function () {
                                            return Mechanic::where('is_active', true)
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->multiple()
                                        ->maxItems(2)
                                        ->preload()
                                        ->searchable()
                                        ->required()
                                        ->default($existingMechanics)
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            if (is_array($state)) {
                                                // Dapatkan mechanic_costs yang sudah ada
                                                $existingCosts = $get('mechanic_costs') ?: [];
                                                $existingCostsMap = [];

                                                // Buat map dari mechanic_id ke labor_cost
                                                foreach ($existingCosts as $cost) {
                                                    if (isset($cost['mechanic_id'])) {
                                                        $existingCostsMap[$cost['mechanic_id']] = $cost['labor_cost'];
                                                    }
                                                }

                                                // Buat mechanic_costs baru berdasarkan montir yang dipilih
                                                $mechanicCosts = [];
                                                foreach ($state as $mechanicId) {
                                                    $mechanicCosts[] = [
                                                        'mechanic_id' => $mechanicId,
                                                        'labor_cost' => $existingCostsMap[$mechanicId] ?? 0,
                                                    ];
                                                }

                                                $set('mechanic_costs', $mechanicCosts);
                                            }
                                        })
                                        ->helperText('Pilih maksimal 2 montir yang mengerjakan servis ini'),
                                ]),

                            Forms\Components\Section::make('Biaya Jasa per Montir')
                                ->schema([
                                    Forms\Components\Repeater::make('mechanic_costs')
                                        ->label(false)
                                        ->schema([
                                            Forms\Components\Select::make('mechanic_id')
                                                ->label('Montir')
                                                ->options(function () {
                                                    return Mechanic::where('is_active', true)
                                                        ->orderBy('name')
                                                        ->pluck('name', 'id')
                                                        ->toArray();
                                                })
                                                ->disabled()
                                                ->dehydrated(true)
                                                ->required(),

                                            Forms\Components\TextInput::make('labor_cost')
                                                ->label('Biaya Jasa')
                                                ->numeric()
                                                ->prefix('Rp')
                                                ->default(function ($state) use ($totalLaborCost) {
                                                    // Jika state sudah ada, gunakan state
                                                    if ($state && $state > 0) {
                                                        return $state;
                                                    }

                                                    // Jika tidak, gunakan biaya jasa total
                                                    return $totalLaborCost;
                                                })
                                                ->required(),
                                        ])
                                        ->itemLabel(function (array $state): ?string {
                                            return isset($state['mechanic_id']) && $state['mechanic_id'] ? Mechanic::find($state['mechanic_id'])?->name : null;
                                        })
                                        ->default($mechanicCosts) // Set default mechanic_costs
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)
                                        ->dehydrated()
                                        ->columns(2),
                                ])
                                ->visible(fn(Forms\Get $get) => is_array($get('mechanics')) && count($get('mechanics')) > 0),
                        ];
                    })
                    ->action(function (array $data, Service $record) {
                        // Debug: Tampilkan data yang diterima dengan lebih detail
                        Log::info('Mechanics data received for service #' . $record->id . ':', $data);
                        Log::info('Data structure:', ['type' => gettype($data), 'keys' => array_keys($data)]);

                        // Cek apakah ada montir yang sudah terpilih sebelumnya
                        $existingMechanics = $record->mechanics()->pluck('mechanic_id')->toArray();
                        Log::info('Existing mechanics for service #' . $record->id . ':', $existingMechanics);

                        // Validasi montir dengan lebih detail
                        if (empty($data['mechanics']) && empty($existingMechanics)) {
                            Notification::make()
                                ->title('Montir harus dipilih sebelum menyelesaikan servis')
                                ->body('Silakan pilih minimal 1 montir untuk menyelesaikan servis ini.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Gunakan montir yang sudah ada jika tidak ada yang dipilih
                        if (empty($data['mechanics']) && !empty($existingMechanics)) {
                            $data['mechanics'] = $existingMechanics;
                            Log::info('Using existing mechanics:', $existingMechanics);
                        }

                        // Dapatkan tanggal awal dan akhir minggu saat ini (Senin-Minggu)
                        $now = now();
                        $weekStart = $now->copy()->startOfWeek();
                        $weekEnd = $now->copy()->endOfWeek();

                        // Hapus semua relasi montir yang ada
                        $record->mechanics()->detach();

                        // Proses biaya jasa per montir
                        if (isset($data['mechanic_costs']) && is_array($data['mechanic_costs'])) {
                            Log::info('Mechanic costs data:', $data['mechanic_costs']);

                            // Hitung total biaya jasa dari semua montir
                            $totalLaborCost = 0;
                            foreach ($data['mechanic_costs'] as $costData) {
                                if (isset($costData['labor_cost']) && $costData['labor_cost'] > 0) {
                                    $totalLaborCost += (int)$costData['labor_cost'];
                                    Log::info("markAsCompleted: Adding labor cost: " . (int)$costData['labor_cost'] . " for mechanic ID: " . ($costData['mechanic_id'] ?? 'unknown'));
                                }
                            }

                            // Update total biaya jasa pada record
                            $record->labor_cost = $totalLaborCost;
                            $record->total_cost = $totalLaborCost; // Karena parts_cost sudah tidak digunakan

                            Log::info("markAsCompleted: Updated service #{$record->id} total labor cost: {$totalLaborCost}");

                            foreach ($data['mechanic_costs'] as $costData) {
                                if (isset($costData['mechanic_id']) && isset($costData['labor_cost'])) {
                                    $mechanicId = $costData['mechanic_id'];
                                    $laborCost = (int)$costData['labor_cost'];

                                    // Pastikan biaya jasa tidak 0, tapi jangan override nilai yang sudah diisi
                                    if ($laborCost == 0 && $totalLaborCost > 0 && count($data['mechanic_costs']) > 0) {
                                        $laborCost = $totalLaborCost / count($data['mechanic_costs']);
                                    } else if ($laborCost > 0) {
                                        // Gunakan nilai yang sudah diisi
                                        Log::info("Using existing labor cost for mechanic #{$mechanicId}: {$laborCost}");
                                    }

                                    Log::info("Setting labor cost for mechanic #{$mechanicId}: {$laborCost}");

                                    // Pastikan labor_cost adalah angka yang valid
                                    $laborCost = (float) $laborCost;

                                    // Log untuk debugging
                                    Log::info("Attaching mechanic #{$mechanicId} with labor_cost: {$laborCost} (type: " . gettype($laborCost) . ")");

                                    // Tambahkan montir dengan biaya jasa yang ditentukan
                                    $record->mechanics()->attach($mechanicId, [
                                        'labor_cost' => $laborCost,
                                        'invoice_number' => $data['invoice_number'] ?? null,
                                        'week_start' => $weekStart,
                                        'week_end' => $weekEnd,
                                    ]);

                                    // Log untuk debugging
                                    \Illuminate\Support\Facades\Log::info("Attached mechanic #{$mechanicId} with labor_cost: {$laborCost}", [
                                        'service_id' => $record->id,
                                        'mechanic_id' => $mechanicId,
                                        'labor_cost' => $laborCost,
                                        'week_start' => $weekStart,
                                        'week_end' => $weekEnd,
                                    ]);
                                }
                            }
                        } else {
                            // Fallback ke cara lama jika tidak ada data biaya jasa per montir
                            Log::info('No mechanic costs data, using default labor cost');

                            // Set default ke nilai yang masuk akal
                            $defaultLaborCost = 50000; // Default biaya jasa yang masuk akal

                            // Update total biaya jasa pada record
                            $totalLaborCost = $defaultLaborCost * count($data['mechanics']);
                            $record->labor_cost = $totalLaborCost;
                            $record->total_cost = $totalLaborCost;

                            // Log untuk debugging
                            Log::info("markAsCompleted fallback: Setting total cost for service #{$record->id} to {$totalLaborCost}");

                            // Simpan montir yang dipilih dengan biaya jasa default
                            foreach ($data['mechanics'] as $mechanicId) {
                                // Pastikan defaultLaborCost adalah angka yang valid
                                $defaultLaborCost = (float) $defaultLaborCost;

                                // Log untuk debugging
                                Log::info("Fallback: Attaching mechanic #{$mechanicId} with default labor_cost: {$defaultLaborCost} (type: " . gettype($defaultLaborCost) . ")");

                                $record->mechanics()->attach($mechanicId, [
                                    'labor_cost' => $defaultLaborCost,
                                    'invoice_number' => $data['invoice_number'] ?? null,
                                    'week_start' => $weekStart,
                                    'week_end' => $weekEnd,
                                ]);

                                // Log untuk debugging
                                \Illuminate\Support\Facades\Log::info("Attached mechanic #{$mechanicId} with default labor_cost: {$defaultLaborCost}", [
                                    'service_id' => $record->id,
                                    'mechanic_id' => $mechanicId,
                                    'labor_cost' => $defaultLaborCost,
                                    'week_start' => $weekStart,
                                    'week_end' => $weekEnd,
                                ]);
                            }
                        }

                        // Update status servis dan nomor nota
                        $record->status = 'completed';
                        $record->invoice_number = $data['invoice_number'] ?? null;
                        $record->completed_at = now(); // global now()
                        $record->exit_time = now(); // global now()

                        // Log untuk debugging
                        \Illuminate\Support\Facades\Log::info("Updating service #{$record->id} status to completed", [
                            'invoice_number' => $record->invoice_number,
                            'completed_at' => $record->completed_at,
                            'exit_time' => $record->exit_time,
                        ]);

                        // Simpan perubahan
                        $record->save();

                        // Dispatch event to update mechanic reports
                        Log::info("ServiceResource: Dispatching ServiceStatusChanged event for service #{$record->id}", [
                            'current_status' => $record->status,
                            'previous_status' => 'in_progress',
                        ]);

                        // Make sure the record is fresh from the database
                        $freshRecord = Service::with('mechanics')->find($record->id);
                        if ($freshRecord) {
                            Log::info("ServiceResource: Dispatching ServiceStatusChanged event for service #{$freshRecord->id} with mechanics", [
                                'mechanics_count' => $freshRecord->mechanics->count(),
                                'mechanics_ids' => $freshRecord->mechanics->pluck('id')->toArray(),
                            ]);

                            // Dispatch event synchronously
                            $listener = new \App\Listeners\UpdateMechanicReports();
                            $event = new ServiceStatusChanged($freshRecord, 'in_progress');
                            $listener->handle($event);

                            // Also dispatch through event system for other listeners
                            event($event);
                        } else {
                            Log::error("ServiceResource: Failed to find service #{$record->id} in the database");
                        }

                        // Trigger rating modal immediately after completion
                        static::triggerRatingModal($record);

                        Notification::make()
                            ->title('✅ Servis Selesai')
                            ->body("Servis untuk {$record->customer_name} telah selesai. Silakan berikan rating untuk montir.")
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Tables\Actions\Action::make('generateDigitalReport')
                    ->label('Buat Laporan Digital')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->visible(fn(Service $record) => $record->status === 'completed')
                    ->action(function (Service $record) {
                        // Get the default template
                        $template = \App\Models\ServiceReportTemplate::first();

                        if (!$template) {
                            // Create a default template if none exists
                            $template = \App\Models\ServiceReportTemplate::create([
                                'name' => 'Template Standar Napas Baru Premium',
                                'vehicle_type' => 'Semua Tipe',
                                'description' => 'Template standar untuk paket layanan Napas Baru Premium',
                                'is_default' => true,
                                'checklist_items' => [
                                    ['inspection_point' => 'Kondisi Oli Mesin'],
                                    ['inspection_point' => 'Filter Oli'],
                                    ['inspection_point' => 'Filter Udara'],
                                    ['inspection_point' => 'Filter Bahan Bakar'],
                                    ['inspection_point' => 'Kebocoran Oli'],
                                    ['inspection_point' => 'Kondisi Belt'],
                                    ['inspection_point' => 'Sistem Pendingin'],
                                    ['inspection_point' => 'Radiator & Selang'],
                                    ['inspection_point' => 'Kondisi Air Radiator'],
                                    ['inspection_point' => 'Kondisi Busi'],
                                ],
                                'services_performed' => [
                                    [
                                        'service_name' => 'Tune Up Mesin',
                                        'description' => 'Penyetelan dan pembersihan komponen mesin untuk performa optimal'
                                    ],
                                ],
                                'warranty_info' => '<p>Garansi Tune-Up 1 Minggu</p><p>Syarat dan ketentuan berlaku.</p>',
                                'recommendations' => '<p>Rekomendasi perawatan rutin.</p>',
                            ]);

                            Notification::make()
                                ->title('Template default dibuat')
                                ->body('Template default telah dibuat karena tidak ada template yang tersedia.')
                                ->success()
                                ->send();
                        }

                        // Check if a report already exists for this service
                        $existingReport = \App\Models\ServiceReport::where('service_id', $record->id)->first();

                        if ($existingReport) {
                            Notification::make()
                                ->title('Laporan sudah ada')
                                ->body('Laporan digital untuk servis ini sudah dibuat sebelumnya.')
                                ->warning()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->label('Lihat Laporan')
                                        ->url(route('filament.admin.resources.service-reports.view', $existingReport))
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                            return;
                        }

                        try {
                            // Create the report
                            $report = new \App\Models\ServiceReport([
                                'service_id' => $record->id,
                                'title' => 'Laporan Digital Paket Napas Baru Premium',
                                'code' => \App\Models\ServiceReport::generateUniqueCode(),
                                'customer_name' => $record->customer_name,
                                'license_plate' => $record->license_plate,
                                'car_model' => $record->car_model,
                                'technician_name' => $record->mechanics->first()->name ?? null,
                                'warranty_info' => $template->warranty_info,
                                'recommendations' => $template->recommendations,
                                'services_performed' => $template->services_performed,
                                'service_date' => $record->completed_at ?? now(),
                                'expires_at' => now()->addDays(7),
                            ]);

                            // Save the report first
                            $report->save();

                            // Initialize certificate data
                            $report->initializeCertificate();

                            // Create the checklist items
                            if (is_array($template->checklist_items)) {
                                $order = 1;
                                foreach ($template->checklist_items as $item) {
                                    $report->checklistItems()->create([
                                        'order' => $order++,
                                        'inspection_point' => $item['inspection_point'],
                                        'status' => 'ok', // Default status
                                        'notes' => '',
                                    ]);
                                }
                            }

                            Notification::make()
                                ->title('Laporan digital berhasil dibuat')
                                ->body('Laporan digital untuk servis ini telah berhasil dibuat.')
                                ->success()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->label('Lihat Laporan')
                                        ->url(route('filament.admin.resources.service-reports.view', $report))
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal membuat laporan')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('sendFollowUpWhatsApp')
                    ->label('Kirim Follow-up')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn(Service $record) => $record->status === 'completed')
                    ->form(function () {
                        // Get all active templates
                        $templates = \App\Models\MessageTemplate::active()->get();

                        // Group templates by type
                        $templateOptions = [];
                        foreach ($templates as $template) {
                            $templateOptions[$template->id] = $template->name . ' (' . match ($template->type) {
                                'follow_up' => 'Follow-up',
                                'feedback' => 'Feedback',
                                'promo' => 'Promo',
                                'custom' => 'Kustom',
                                default => $template->type,
                            } . ')';
                        }

                        return [
                            Forms\Components\Select::make('template_id')
                                ->label('Template Pesan')
                                ->options($templateOptions)
                                ->default(function () {
                                    // Get default follow-up template
                                    $defaultTemplate = \App\Models\MessageTemplate::where('type', 'follow_up')
                                        ->where('is_default', true)
                                        ->where('is_active', true)
                                        ->first();

                                    return $defaultTemplate ? $defaultTemplate->id : null;
                                })
                                ->selectablePlaceholder(false)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $template = \App\Models\MessageTemplate::find($state);
                                        if ($template) {
                                            $set('preview', $template->content);
                                        }
                                    }
                                }),

                            Forms\Components\Textarea::make('preview')
                                ->label('Preview Template')
                                ->disabled()
                                ->rows(6)
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('custom_message')
                                ->label('Pesan Tambahan (Opsional)')
                                ->placeholder('Tambahkan pesan khusus di sini (opsional)')
                                ->rows(3)
                                ->columnSpanFull(),
                        ];
                    })
                    ->action(function (array $data, Service $record): void {
                        // Format nomor telepon untuk WhatsApp
                        $phone = preg_replace('/[^0-9]/', '', $record->phone);
                        if (substr($phone, 0, 1) === '0') {
                            $phone = '62' . substr($phone, 1);
                        } elseif (substr($phone, 0, 2) !== '62') {
                            $phone = '62' . $phone;
                        }

                        // Get the selected template
                        $template = \App\Models\MessageTemplate::find($data['template_id']);
                        if (!$template) {
                            Notification::make()
                                ->title('Template tidak ditemukan')
                                ->body('Template yang dipilih tidak ditemukan. Silakan pilih template lain.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Get formatted content
                        $message = $template->getFormattedContent($record);

                        // Tambahkan pesan kustom jika ada
                        if (!empty($data['custom_message'])) {
                            $message .= "\n\n" . $data['custom_message'];
                        }

                        // Encode pesan untuk URL
                        $encodedMessage = urlencode($message);

                        // Buat URL WhatsApp
                        $whatsappUrl = "https://wa.me/{$phone}?text={$encodedMessage}";

                        // Tampilkan notifikasi sukses
                        Notification::make()
                            ->title('Pesan follow-up siap dikirim')
                            ->body('WhatsApp akan terbuka dengan pesan yang sudah disiapkan.')
                            ->success()
                            ->send();

                        // Redirect ke URL WhatsApp
                        redirect()->away($whatsappUrl);
                    }),
                Tables\Actions\Action::make('markAsCancelled')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Service $record) => $record->status === 'in_progress')
                    ->requiresConfirmation()
                    ->action(function (Service $record) {
                        // Get the previous status before changing it
                        $previousStatus = $record->status;

                        // Change status to cancelled
                        $record->status = 'cancelled';
                        $record->save();

                        // Make sure the record is fresh from the database
                        $freshRecord = Service::with('mechanics')->find($record->id);
                        if ($freshRecord) {
                            Log::info("ServiceResource: Dispatching ServiceStatusChanged event for service #{$freshRecord->id} with mechanics", [
                                'previous_status' => $previousStatus,
                                'current_status' => $freshRecord->status,
                                'mechanics_count' => $freshRecord->mechanics->count(),
                                'mechanics_ids' => $freshRecord->mechanics->pluck('id')->toArray(),
                            ]);

                            // Dispatch event synchronously
                            $listener = new \App\Listeners\UpdateMechanicReports();
                            $event = new ServiceStatusChanged($freshRecord, $previousStatus);
                            $listener->handle($event);

                            // Also dispatch through event system for other listeners
                            event($event);

                            // Force refresh mechanic reports
                            foreach ($freshRecord->mechanics as $mechanic) {
                                // Get week dates
                                $weekStart = $mechanic->pivot->week_start;
                                $weekEnd = $mechanic->pivot->week_end;

                                if (empty($weekStart) || empty($weekEnd)) {
                                    continue;
                                }

                                // Calculate total labor cost for completed services
                                $totalLaborCost = DB::table('mechanic_service')
                                    ->join('services', 'mechanic_service.service_id', '=', 'services.id')
                                    ->where('mechanic_service.mechanic_id', $mechanic->id)
                                    ->where('mechanic_service.week_start', $weekStart)
                                    ->where('mechanic_service.week_end', $weekEnd)
                                    ->where('services.status', 'completed')
                                    ->sum('mechanic_service.labor_cost');

                                // Count completed services
                                $servicesCount = DB::table('mechanic_service')
                                    ->join('services', 'mechanic_service.service_id', '=', 'services.id')
                                    ->where('mechanic_service.mechanic_id', $mechanic->id)
                                    ->where('mechanic_service.week_start', $weekStart)
                                    ->where('mechanic_service.week_end', $weekEnd)
                                    ->where('services.status', 'completed')
                                    ->count();

                                // Update the report
                                DB::table('mechanic_reports')
                                    ->where('mechanic_id', $mechanic->id)
                                    ->where('week_start', $weekStart)
                                    ->where('week_end', $weekEnd)
                                    ->update([
                                        'services_count' => $servicesCount,
                                        'total_labor_cost' => $totalLaborCost,
                                        'updated_at' => now(), // global now()
                                    ]);

                                Log::info("ServiceResource: Force updated mechanic report for mechanic #{$mechanic->id}", [
                                    'services_count' => $servicesCount,
                                    'total_labor_cost' => $totalLaborCost,
                                ]);
                            }
                        } else {
                            Log::error("ServiceResource: Failed to find service #{$record->id} in the database");
                        }

                        Notification::make()
                            ->title('Servis telah dibatalkan')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('sendWhatsApp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(function (Service $record) {
                        // Format the phone number
                        $phone = preg_replace('/[^0-9]/', '', $record->phone);

                        // If the number starts with 0, replace it with 62
                        if (substr($phone, 0, 1) === '0') {
                            $phone = '62' . substr($phone, 1);
                        }
                        // If the number doesn't start with 62, add it
                        elseif (substr($phone, 0, 2) !== '62') {
                            $phone = '62' . $phone;
                        }

                        // Create the message based on service status
                        $message = match ($record->status) {
                            'in_progress' => "Halo {$record->customer_name},\n\nMobil Anda ({$record->car_model}) sedang dalam proses servis di Hartono Motor.\n\nJenis Servis: {$record->service_type}\n\nKami akan menghubungi Anda kembali ketika servis telah selesai.\n\nTerima kasih,\nTim Hartono Motor",

                            'completed' => "Halo {$record->customer_name},\n\nServis mobil Anda ({$record->car_model}) di Hartono Motor telah SELESAI.\n\nJenis Servis: {$record->service_type}\n\nTotal Biaya: Rp " . number_format($record->total_cost, 0, ',', '.') . "\n\nMobil Anda sudah siap untuk diambil. Terima kasih telah mempercayakan kendaraan Anda pada kami.\n\nSalam,\nTim Hartono Motor",

                            'cancelled' => "Halo {$record->customer_name},\n\nKami ingin menginformasikan bahwa servis mobil Anda ({$record->car_model}) di Hartono Motor telah DIBATALKAN.\n\nJika Anda memiliki pertanyaan, silakan hubungi kami.\n\nTerima kasih,\nTim Hartono Motor",

                            default => "Halo {$record->customer_name},\n\nTerima kasih telah mempercayakan servis mobil Anda ({$record->car_model}) di Hartono Motor.\n\nJika ada pertanyaan, silakan hubungi kami.\n\nTerima kasih,\nTim Hartono Motor",
                        };

                        // URL encode the message
                        $encodedMessage = urlencode($message);

                        // Return the WhatsApp URL
                        return "https://wa.me/{$phone}?text={$encodedMessage}";
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->email === 'admin@hartonomotor.com'),
                    Tables\Actions\BulkAction::make('markAsCompletedBulk')
                        ->label('Tandai Selesai')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn() => Auth::user()->role === 'admin')
                        ->form([
                            Forms\Components\TextInput::make('invoice_number')
                                ->label('Nomor Nota')
                                ->required()
                                ->placeholder('Masukkan nomor nota')
                                ->helperText('Nomor nota wajib diisi sebagai bukti fisik servis'),

                            Forms\Components\Select::make('mechanics')
                                ->label('Montir yang Mengerjakan')
                                ->options(function () {
                                    return Mechanic::where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->maxItems(2)
                                ->preload()
                                ->searchable()
                                ->required()
                                ->helperText('Pilih maksimal 2 montir yang mengerjakan servis ini'),
                        ])
                        ->action(function (array $data, \Illuminate\Database\Eloquent\Collection $records) {
                            // Debug: Tampilkan data yang diterima dengan lebih detail
                            Log::info('Bulk action mechanics data received:', $data);
                            Log::info('Bulk action data structure:', ['type' => gettype($data), 'keys' => array_keys($data)]);

                            // Validasi montir dengan lebih detail
                            if (empty($data['mechanics'])) {
                                Notification::make()
                                    ->title('Montir harus dipilih sebelum menyelesaikan servis')
                                    ->body('Silakan pilih minimal 1 montir untuk menyelesaikan servis ini.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $records->each(function ($record) use ($data) {
                                if ($record->status === 'in_progress') {
                                    // Simpan montir yang dipilih
                                    $record->mechanics()->sync($data['mechanics']);

                                    // Hitung biaya jasa per montir - setiap montir mendapatkan biaya jasa penuh
                                    // Tidak perlu membagi biaya jasa, setiap montir mendapatkan biaya jasa penuh
                                    $laborCostPerMechanic = 50000; // Default labor cost

                                    // Log untuk debugging
                                    Log::info("markAsCompletedBulk: Setting default labor cost for service #{$record->id} to {$laborCostPerMechanic}");

                                    // Dapatkan tanggal awal dan akhir minggu saat ini (Senin-Minggu)
                                    $now = now(); // global now()
                                    $weekStart = $now->copy()->startOfWeek();
                                    $weekEnd = $now->copy()->endOfWeek();

                                    // Update biaya jasa untuk setiap montir
                                    foreach ($data['mechanics'] as $mechanicId) {
                                        // Pastikan laborCostPerMechanic adalah angka yang valid
                                        $laborCostPerMechanic = (float) $laborCostPerMechanic;

                                        // Log untuk debugging
                                        Log::info("Bulk action: Updating mechanic #{$mechanicId} with labor_cost: {$laborCostPerMechanic} (type: " . gettype($laborCostPerMechanic) . ")");

                                        $record->mechanics()->updateExistingPivot($mechanicId, [
                                            'labor_cost' => $laborCostPerMechanic,
                                            'invoice_number' => $data['invoice_number'] ?? null,
                                            'week_start' => $weekStart,
                                            'week_end' => $weekEnd,
                                        ]);
                                    }

                                    // Hitung total biaya jasa
                                    $totalLaborCost = $laborCostPerMechanic * count($data['mechanics']);

                                    // Update status servis dan nomor nota
                                    $record->status = 'completed';
                                    $record->invoice_number = $data['invoice_number'] ?? null;
                                    $record->completed_at = now(); // global now()
                                    $record->exit_time = now(); // global now()
                                    $record->labor_cost = $totalLaborCost;
                                    $record->total_cost = $totalLaborCost;

                                    // Log untuk debugging
                                    Log::info("markAsCompletedBulk: Setting total cost for service #{$record->id} to {$totalLaborCost}");

                                    // Log untuk debugging
                                    \Illuminate\Support\Facades\Log::info("Bulk action: Updating service #{$record->id} status to completed", [
                                        'invoice_number' => $record->invoice_number,
                                        'completed_at' => $record->completed_at,
                                        'exit_time' => $record->exit_time,
                                    ]);

                                    // Simpan perubahan
                                    $record->save();

                                    // Make sure the record is fresh from the database
                                    $freshRecord = Service::with('mechanics')->find($record->id);
                                    if ($freshRecord) {
                                        Log::info("ServiceResource: Dispatching ServiceStatusChanged event for service #{$freshRecord->id} with mechanics", [
                                            'mechanics_count' => $freshRecord->mechanics->count(),
                                            'mechanics_ids' => $freshRecord->mechanics->pluck('id')->toArray(),
                                        ]);

                                        // Dispatch event synchronously
                                        $listener = new \App\Listeners\UpdateMechanicReports();
                                        $event = new ServiceStatusChanged($freshRecord, 'in_progress');
                                        $listener->handle($event);

                                        // Also dispatch through event system for other listeners
                                        event($event);
                                    } else {
                                        Log::error("ServiceResource: Failed to find service #{$record->id} in the database");
                                    }
                                }
                            });

                            // Trigger rating modal for each completed service
                            $completedServices = $records->filter(fn($record) => $record->status === 'completed');

                            if ($completedServices->count() > 0) {
                                // For bulk completion, trigger modal for the first service
                                // and store others for sequential rating
                                $firstService = $completedServices->first();
                                static::triggerRatingModal($firstService);

                                // Store remaining services for sequential rating
                                if ($completedServices->count() > 1) {
                                    $remainingServices = $completedServices->skip(1)->map(function ($service) {
                                        return [
                                            'service_id' => $service->id,
                                            'customer_name' => $service->customer_name,
                                            'service_type' => $service->service_type,
                                            'vehicle_info' => $service->license_plate . ' - ' . $service->car_model,
                                            'mechanics' => $service->mechanics->map(function ($mechanic) {
                                                return [
                                                    'id' => $mechanic->id,
                                                    'name' => $mechanic->name,
                                                    'specialization' => $mechanic->specialization ?? 'Montir Umum'
                                                ];
                                            })->toArray()
                                        ];
                                    })->toArray();

                                    session(['pending_bulk_ratings' => $remainingServices]);
                                }
                            }

                            Notification::make()
                                ->title('✅ Bulk Completion Berhasil')
                                ->body("Berhasil menyelesaikan {$completedServices->count()} servis. Silakan berikan rating untuk montir.")
                                ->success()
                                ->persistent()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UpdatesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }

    // Add a hook to calculate the total cost before saving
    public static function beforeSave(Forms\Form $form): void
    {
        $state = $form->getState();

        // Log untuk debugging
        Log::info("ServiceResource: beforeSave called", [
            'state' => $state,
            'model_exists' => $form->model->exists ? 'yes' : 'no',
            'model_id' => $form->model->id ?? 'new',
        ]);

        // PENTING: Hitung total biaya jasa dari mechanic_costs jika ada
        $totalLaborCost = 0;
        if (isset($state['mechanic_costs']) && is_array($state['mechanic_costs'])) {
            foreach ($state['mechanic_costs'] as $costData) {
                if (isset($costData['labor_cost']) && $costData['labor_cost'] > 0) {
                    $totalLaborCost += (int)$costData['labor_cost'];
                    Log::info("ServiceResource: Adding labor cost: " . (int)$costData['labor_cost'] . " for mechanic ID: " . ($costData['mechanic_id'] ?? 'unknown'));
                }
            }

            Log::info("ServiceResource: Total labor cost calculated from mechanic_costs: {$totalLaborCost}");

            // Set labor_cost dan total_cost HANYA jika mechanic_costs ada dan valid
            $form->model->labor_cost = $totalLaborCost;
            $form->model->total_cost = $totalLaborCost;

            // Log untuk debugging
            Log::info("ServiceResource: Setting total_cost to {$totalLaborCost} from mechanic_costs");
        } else {
            // Jika tidak ada mechanic_costs, jangan ubah total_cost
            Log::info("ServiceResource: No mechanic_costs found, not updating total_cost");
        }

        // Tidak lagi menggunakan parts_cost
        $form->model->parts_cost = 0;

        // Jika ini adalah record baru, set entry_time ke waktu saat ini
        if (!$form->model->exists && !$form->model->entry_time) {
            $form->model->entry_time = now(); // global now()
        }

        if ($form->model->status === 'completed') {
            // Jika status completed, pastikan ada montir yang dipilih
            if ($form->model->mechanics()->count() === 0) {
                Notification::make()
                    ->title('Montir harus dipilih sebelum menyelesaikan servis')
                    ->danger()
                    ->send();

                // Kembalikan status ke in_progress
                $form->model->status = 'in_progress';
            } else {
                // Set completed_at dan exit_time jika belum diset
                if (!$form->model->completed_at) {
                    $form->model->completed_at = now(); // global now()
                }

                if (!$form->model->exit_time) {
                    $form->model->exit_time = now(); // global now()
                }

                // Hitung biaya jasa per montir - setiap montir mendapatkan biaya jasa penuh
                // Tidak perlu membagi biaya jasa, setiap montir mendapatkan biaya jasa penuh
                $laborCostPerMechanic = $form->model->labor_cost;

                // Dapatkan tanggal awal dan akhir minggu saat ini (Senin-Minggu)
                $now = now(); // global now()
                $weekStart = $now->copy()->startOfWeek();
                $weekEnd = $now->copy()->endOfWeek();

                // Update biaya jasa untuk setiap montir
                $form->model->mechanics()->each(function ($mechanic) use ($laborCostPerMechanic, $weekStart, $weekEnd) {
                    // Pastikan laborCostPerMechanic adalah angka yang valid
                    $validLaborCost = (float) $laborCostPerMechanic;

                    // Log untuk debugging
                    \Illuminate\Support\Facades\Log::info("beforeSave: Updating mechanic #{$mechanic->id} with labor_cost: {$validLaborCost} (type: " . gettype($validLaborCost) . ")");

                    $mechanic->pivot->labor_cost = $validLaborCost;
                    $mechanic->pivot->week_start = $weekStart;
                    $mechanic->pivot->week_end = $weekEnd;
                    $mechanic->pivot->save();
                });
            }
        }

        // We don't need to manually update mechanic reports here anymore
        // The Service model will automatically dispatch events when status changes
        // or when mechanics are assigned/removed

        // Process membership points if service is completed and points should be added
        $state = $form->getState();
        if (
            $form->model->status === 'completed' &&
            isset($state['add_membership_points']) &&
            $state['add_membership_points'] === true &&
            isset($state['membership_points']) &&
            $state['membership_points'] > 0 &&
            $form->model->customer_id
        ) {

            try {
                $customer = Customer::find($form->model->customer_id);
                if ($customer && $customer->isMember()) {
                    $membership = $customer->membership;
                    if ($membership && $membership->is_active) {
                        $points = (int) $state['membership_points'];
                        $description = $state['membership_points_description'] ?? "Poin untuk servis {$form->model->service_type}";

                        $membership->addPoints(
                            $points,
                            'service',
                            $description,
                            $form->model->invoice_number,
                            $form->model->id
                        );

                        Notification::make()
                            ->title('Poin membership berhasil ditambahkan')
                            ->body("{$points} poin telah ditambahkan ke akun membership {$customer->name}")
                            ->success()
                            ->send();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error adding membership points', [
                    'error' => $e->getMessage(),
                    'customer_id' => $form->model->customer_id,
                    'service_id' => $form->model->id,
                ]);

                Notification::make()
                    ->title('Gagal menambahkan poin membership')
                    ->body('Terjadi kesalahan saat menambahkan poin: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }

        // Process customer and vehicle information
        if ($form->model->phone && $form->model->customer_name && $form->model->license_plate && $form->model->car_model) {
            try {
                $vehicle = Vehicle::findOrCreateByPhoneAndPlate(
                    $form->model->phone,
                    $form->model->license_plate,
                    [
                        'customer_name' => $form->model->customer_name,
                        'car_model' => $form->model->car_model,
                    ]
                );

                if ($vehicle && $vehicle->customer) {
                    $form->model->customer_id = $vehicle->customer_id;
                    $form->model->vehicle_id = $vehicle->id;

                    Log::info("Service associated with customer: {$vehicle->customer_id} and vehicle: {$vehicle->id} ({$vehicle->model} - {$vehicle->license_plate})");

                    Notification::make()
                        ->title('Kendaraan berhasil diproses')
                        ->body("Kendaraan {$vehicle->model} ({$vehicle->license_plate}) milik {$vehicle->customer->name} berhasil diproses untuk servis ini.")
                        ->success()
                        ->send();
                } else {
                    Log::error('Failed to process vehicle and/or customer for service. Vehicle or customer object is null.', [
                        'phone' => $form->model->phone,
                        'license_plate' => $form->model->license_plate,
                        'vehicle_exists' => !is_null($vehicle),
                        'customer_exists_on_vehicle' => !is_null($vehicle) && !is_null($vehicle->customer),
                    ]);
                    Notification::make()
                        ->title('Gagal memproses kendaraan/pelanggan')
                        ->body('Tidak dapat membuat atau menemukan detail kendaraan/pelanggan terkait. Silakan periksa data dan coba lagi.')
                        ->danger()
                        ->send();
                    // Throw an exception to halt the saving process if vehicle/customer data is inconsistent.
                    throw new \Exception('Gagal memproses data kendaraan atau pelanggan untuk servis.');
                }
            } catch (\Exception $e) {
                Log::error('Exception during vehicle/customer processing in ServiceResource@beforeSave', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'phone' => $form->model->phone,
                    'license_plate' => $form->model->license_plate,
                ]);
                Notification::make()
                    ->title('Error Sistem')
                    ->body('Terjadi kesalahan saat memproses data kendaraan: ' . $e->getMessage())
                    ->danger()
                    ->send();
                // Rethrow the exception to halt the saving process if vehicle/customer creation fails.
                throw $e;
            }
        } else {
            Log::warning('Skipping vehicle/customer processing in ServiceResource@beforeSave due to missing data.', [
                'phone' => $form->model->phone,
                'customer_name' => $form->model->customer_name,
                'license_plate' => $form->model->license_plate,
                'car_model' => $form->model->car_model,
            ]);
            // Notify if critical fields for vehicle/customer creation are missing
            if (empty($form->model->license_plate) || empty($form->model->car_model)) {
                Notification::make()
                    ->title('Data Kendaraan Tidak Lengkap')
                    ->body('Nomor plat atau model mobil tidak boleh kosong untuk membuat atau menghubungkan kendaraan.')
                    ->warning()
                    ->send();
            }
        }
    }

    /**
     * Trigger rating popup notification after service completion
     */
    protected static function triggerRatingPopup(Service $service): void
    {
        // Prepare service data for JavaScript
        $serviceData = [
            'service_id' => $service->id,
            'customer_name' => $service->customer_name,
            'service_type' => $service->service_type,
            'vehicle_info' => $service->license_plate . ' - ' . $service->car_model,
            'mechanics' => $service->mechanics->map(function ($mechanic) {
                return [
                    'id' => $mechanic->id,
                    'name' => $mechanic->name,
                    'specialization' => $mechanic->specialization ?? 'Montir Umum'
                ];
            })->toArray()
        ];

        // Store service data for immediate rating modal display
        session(['pending_rating_service' => $serviceData]);
        session(['show_rating_modal_direct' => true]);

        // Send a simple success notification (optional)
        Notification::make()
            ->title('✅ Servis Selesai')
            ->body("Servis untuk {$service->customer_name} telah selesai. Modal rating akan muncul untuk mengumpulkan feedback.")
            ->success()
            ->duration(3000) // Short duration
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }

    // Pembaruan laporan montir sekarang ditangani oleh event ServiceUpdated dan listener UpdateMechanicReports
}
