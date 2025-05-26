<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Servis Montir - {{ $record->mechanic->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <h2 class="text-xl font-bold">Riwayat Servis Montir: {{ $record->mechanic->name }}</h2>
            <p class="text-sm text-gray-500">
                Periode:
                @if($record->is_cumulative)
                Kumulatif (semua waktu)
                @if($record->period_reset_at)
                - sejak {{ $record->period_reset_at->format('d M Y') }}
                @endif
                @elseif($record->week_start && $record->week_end)
                {{ $record->week_start->format('d M Y') }} - {{ $record->week_end->format('d M Y') }}
                @elseif($record->period_start && $record->period_end)
                {{ $record->period_start->format('d M Y') }} - {{ $record->period_end->format('d M Y') }}
                @else
                Periode tidak ditentukan
                @endif
            </p>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
                    <h3 class="text-sm font-medium text-gray-500">Total Servis</h3>
                    <p class="text-2xl font-bold">{{ $record->services_count }}</p>
                </div>

                <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
                    <h3 class="text-sm font-medium text-gray-500">Total Biaya Jasa</h3>
                    <p class="text-2xl font-bold">Rp {{ number_format($record->total_labor_cost, 0, ',', '.') }}</p>
                </div>

                <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
                    <h3 class="text-sm font-medium text-gray-500">Status Pembayaran</h3>
                    <p class="text-2xl font-bold">
                        @if($record->is_paid)
                        <span class="text-green-600">Sudah Dibayar</span>
                        @else
                        <span class="text-red-600">Belum Dibayar</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <p class="text-sm font-medium text-gray-700">
                    @if($record->is_cumulative)
                    Berikut adalah daftar semua servis yang telah dikerjakan oleh montir ini.
                    @else
                    Berikut adalah daftar servis yang telah diselesaikan oleh montir pada periode ini.
                    @endif
                </p>
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium">
                    @php
                    $title = match($status) {
                    'completed' => 'Servis yang Telah Diselesaikan',
                    'in_progress' => 'Servis dalam Pengerjaan',
                    'cancelled' => 'Servis yang Dibatalkan',
                    default => 'Semua Servis',
                    };
                    @endphp
                    {{ $title }}
                </h3>
                <a href="{{ route('filament.admin.resources.mechanic-reports.edit', ['record' => $record->id]) }}"
                    class="px-4 py-2 bg-gray-200 rounded-md text-gray-700 hover:bg-gray-300">
                    Kembali
                </a>
            </div>

            <!-- Filter Section -->
            <div class="bg-white p-4 rounded-lg border border-gray-200 mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Filter Servis</h4>

                <!-- Status Filter -->
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Status
                        Servis</label>
                    <div class="flex flex-wrap gap-2">
                        <a href="?status=completed&payment_status={{ $paymentStatus ?? 'all' }}&date_range={{ $dateRange ?? 'all_time' }}&start_date={{ $customStartDate }}&end_date={{ $customEndDate }}"
                            class="px-3 py-1 text-sm font-medium rounded-md {{ $status === 'completed' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' }}">
                            Selesai
                        </a>
                        <a href="?status=in_progress&payment_status={{ $paymentStatus ?? 'all' }}&date_range={{ $dateRange ?? 'all_time' }}&start_date={{ $customStartDate }}&end_date={{ $customEndDate }}"
                            class="px-3 py-1 text-sm font-medium rounded-md {{ $status === 'in_progress' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' }}">
                            Dalam Pengerjaan
                        </a>
                        <a href="?status=cancelled&payment_status={{ $paymentStatus ?? 'all' }}&date_range={{ $dateRange ?? 'all_time' }}&start_date={{ $customStartDate }}&end_date={{ $customEndDate }}"
                            class="px-3 py-1 text-sm font-medium rounded-md {{ $status === 'cancelled' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' }}">
                            Dibatalkan
                        </a>
                        <a href="?status=all&payment_status={{ $paymentStatus ?? 'all' }}&date_range={{ $dateRange ?? 'all_time' }}&start_date={{ $customStartDate }}&end_date={{ $customEndDate }}"
                            class="px-3 py-1 text-sm font-medium rounded-md {{ $status === 'all' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' }}">
                            Semua Status
                        </a>
                    </div>
                </div>

                <!-- Payment Status Info -->
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <p class="text-sm text-blue-700">
                            <strong>Status Pembayaran:</strong>
                            @if($record->is_paid)
                            <span class="text-green-600 font-medium">Sudah Dibayar</span>
                            @if($record->paid_at)
                            pada {{ $record->paid_at->format('d M Y H:i') }}
                            @endif
                            @else
                            <span class="text-red-600 font-medium">Belum Dibayar</span>
                            @endif
                            - Status pembayaran berlaku untuk semua servis dalam laporan ini.
                        </p>
                    </div>
                </div>

                @if($record->is_cumulative)
                <!-- Date Range Filter (only for cumulative reports) -->
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Rentang
                        Tanggal</label>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <a href="?status={{ $status }}&payment_status={{ $paymentStatus ?? 'all' }}&date_range=all_time"
                            class="px-3 py-1 text-sm font-medium rounded-md {{ ($dateRange ?? 'all_time') === 'all_time' ? 'bg-purple-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' }}">
                            Semua Waktu
                        </a>
                        <a href="?status={{ $status }}&payment_status={{ $paymentStatus ?? 'all' }}&date_range=last_7_days"
                            class="px-3 py-1 text-sm font-medium rounded-md {{ ($dateRange ?? 'all_time') === 'last_7_days' ? 'bg-purple-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' }}">
                            7 Hari Terakhir
                        </a>
                        <a href="?status={{ $status }}&payment_status={{ $paymentStatus ?? 'all' }}&date_range=last_30_days"
                            class="px-3 py-1 text-sm font-medium rounded-md {{ ($dateRange ?? 'all_time') === 'last_30_days' ? 'bg-purple-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' }}">
                            30 Hari Terakhir
                        </a>
                        <a href="?status={{ $status }}&payment_status={{ $paymentStatus ?? 'all' }}&date_range=last_3_months"
                            class="px-3 py-1 text-sm font-medium rounded-md {{ ($dateRange ?? 'all_time') === 'last_3_months' ? 'bg-purple-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' }}">
                            3 Bulan Terakhir
                        </a>
                    </div>

                    <!-- Custom Date Range -->
                    <form method="GET" class="flex flex-wrap gap-2 items-end">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="payment_status" value="{{ $paymentStatus ?? 'all' }}">
                        <input type="hidden" name="date_range" value="custom">

                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Dari Tanggal</label>
                            <input type="date" name="start_date" value="{{ $customStartDate }}"
                                class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" value="{{ $customEndDate }}"
                                class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <button type="submit"
                            class="px-3 py-1 text-sm font-medium bg-blue-500 text-white rounded-md hover:bg-blue-600">
                            Filter
                        </button>
                    </form>
                </div>
                @endif
            </div>

            @if($services->isEmpty())
            <div class="bg-white p-6 text-center border border-gray-300 rounded-xl">
                <p class="text-gray-500">Tidak ada servis yang ditemukan dengan status tersebut.</p>
            </div>
            @else
            <div class="overflow-hidden overflow-x-auto border border-gray-300 rounded-xl">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jenis Servis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama Pelanggan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nomor Plat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nomor Nota</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Biaya Jasa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal Masuk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal Selesai</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($services as $service)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{
                                $service->service_type }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $service->customer_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $service->license_plate }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $service->invoice_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp {{
                                number_format($service->labor_cost, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($service->status == 'completed')
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>
                                @elseif($service->status == 'in_progress')
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Dalam
                                    Pengerjaan</span>
                                @elseif($service->status == 'cancelled')
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Dibatalkan</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{
                                $service->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $service->completed_at ?
                                $service->completed_at->format('d M Y H:i') : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <a href="{{ route('filament.admin.resources.services.edit', ['record' => $service->id]) }}"
                                    class="text-blue-600 hover:text-blue-900">Lihat Detail</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</body>

</html>