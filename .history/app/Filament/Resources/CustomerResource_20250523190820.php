<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Models\Membership;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Pelanggan';

    protected static ?string $modelLabel = 'Pelanggan';

    protected static ?string $navigationGroup = 'Manajemen Pelanggan';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->required()
                            ->tel()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'male' => 'Laki-laki',
                                'female' => 'Perempuan',
                                'other' => 'Lainnya',
                            ]),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->maxDate(now()),
                    ]),

                Forms\Components\Section::make('Alamat')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('city')
                            ->label('Kota')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Informasi Tambahan')
                    ->schema([
                        Forms\Components\Select::make('source')
                            ->label('Sumber')
                            ->options([
                                'website' => 'Website',
                                'referral' => 'Referensi',
                                'social_media' => 'Media Sosial',
                                'google' => 'Google',
                                'walk_in' => 'Langsung Datang',
                                'other' => 'Lainnya',
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),

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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Customer $record) => $record->isMember() ? 'Member' : null)
                    ->badge(fn(Customer $record) => $record->isMember() ? 'Member' : null)
                    ->color(fn(Customer $record) => $record->isMember() ? 'success' : null),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Nomor Telepon')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nomor telepon disalin!')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('city')
                    ->label('Kota')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('service_count')
                    ->label('Jumlah Servis')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Pengeluaran')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_service_date')
                    ->label('Servis Terakhir')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ])
                    ->placeholder('Semua Status')
                    ->multiple(),

                Tables\Filters\SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                        'other' => 'Lainnya',
                    ])
                    ->placeholder('Semua Jenis Kelamin')
                    ->multiple(),

                Tables\Filters\SelectFilter::make('city')
                    ->label('Kota')
                    ->options(function () {
                        return Customer::whereNotNull('city')
                            ->where('city', '!=', '')
                            ->distinct()
                            ->pluck('city', 'city')
                            ->toArray();
                    })
                    ->placeholder('Semua Kota')
                    ->multiple(),

                Tables\Filters\Filter::make('has_services')
                    ->label('Memiliki Servis')
                    ->query(fn(Builder $query) => $query->where('service_count', '>', 0)),

                Tables\Filters\Filter::make('no_services')
                    ->label('Belum Pernah Servis')
                    ->query(fn(Builder $query) => $query->where('service_count', 0)),

                Tables\Filters\Filter::make('is_member')
                    ->label('Member')
                    ->query(fn(Builder $query) => $query->whereHas('membership', function ($q) {
                        $q->where('is_active', true);
                    })),

                Tables\Filters\Filter::make('is_not_member')
                    ->label('Bukan Member')
                    ->query(fn(Builder $query) => $query->whereDoesntHave('membership')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('createMembership')
                        ->label('Buat Membership')
                        ->icon('heroicon-o-identification')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('membership_number')
                                ->label('Nomor Membership')
                                ->required()
                                ->unique(table: 'memberships', column: 'membership_number')
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
                                ->default(fn() => now()->addYear()),

                            Forms\Components\TextInput::make('points')
                                ->label('Poin Awal')
                                ->numeric()
                                ->default(0),

                            Forms\Components\Textarea::make('notes')
                                ->label('Catatan')
                                ->rows(3),
                        ])
                        ->action(function (Customer $record, array $data) {
                            if ($record->isMember()) {
                                Notification::make()
                                    ->title('Pelanggan sudah menjadi member')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $membership = $record->membership()->create([
                                'membership_number' => $data['membership_number'],
                                'card_type' => $data['card_type'],
                                'points' => $data['points'],
                                'lifetime_points' => $data['points'],
                                'join_date' => $data['join_date'],
                                'expiry_date' => $data['expiry_date'],
                                'is_active' => true,
                                'notes' => $data['notes'],
                            ]);

                            if ($data['points'] > 0) {
                                $membership->pointHistory()->create([
                                    'points' => $data['points'],
                                    'type' => 'manual',
                                    'description' => 'Poin awal saat pendaftaran membership',
                                    'created_by' => Auth::id(),
                                ]);
                            }

                            Notification::make()
                                ->title('Membership berhasil dibuat')
                                ->success()
                                ->send();
                        })
                        ->visible(fn(Customer $record) => !$record->isMember()),
                    Tables\Actions\Action::make('manageMembership')
                        ->label('Kelola Membership')
                        ->icon('heroicon-o-identification')
                        ->color('primary')
                        ->url(fn(Customer $record) => route('filament.admin.resources.memberships.edit', ['record' => $record->membership]))
                        ->visible(fn(Customer $record) => $record->isMember()),
                    Tables\Actions\Action::make('sendWhatsApp')
                        ->label('WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->url(function (Customer $record) {
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

                            return "https://wa.me/{$phone}";
                        })
                        ->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsActive')
                        ->label('Tandai Aktif')
                        ->icon('heroicon-o-check-circle')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('markAsInactive')
                        ->label('Tandai Tidak Aktif')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('exportCustomers')
                        ->label('Export ke CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            // Logic to export customers to CSV
                            return response()->streamDownload(function () use ($records) {
                                $csv = fopen('php://output', 'w');

                                // Add headers
                                fputcsv($csv, [
                                    'ID',
                                    'Nama',
                                    'Telepon',
                                    'Email',
                                    'Alamat',
                                    'Kota',
                                    'Tanggal Lahir',
                                    'Jenis Kelamin',
                                    'Jumlah Servis',
                                    'Total Pengeluaran',
                                    'Servis Terakhir',
                                    'Status',
                                    'Terdaftar Pada'
                                ]);

                                // Add data
                                foreach ($records as $record) {
                                    fputcsv($csv, [
                                        $record->id,
                                        $record->name,
                                        $record->phone,
                                        $record->email,
                                        $record->address,
                                        $record->city,
                                        $record->birth_date ? $record->birth_date->format('Y-m-d') : '',
                                        $record->gender,
                                        $record->service_count,
                                        $record->total_spent,
                                        $record->last_service_date ? $record->last_service_date->format('Y-m-d') : '',
                                        $record->is_active ? 'Aktif' : 'Tidak Aktif',
                                        $record->created_at->format('Y-m-d H:i:s'),
                                    ]);
                                }

                                fclose($csv);
                            }, 'customers.csv');
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ServicesRelationManager::class,
            RelationManagers\VehiclesRelationManager::class,
            RelationManagers\MembershipsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'admin';
    }
}
