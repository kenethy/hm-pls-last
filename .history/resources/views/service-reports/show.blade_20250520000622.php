<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report->title }} - {{ $report->customer_name }}</title>
    <meta name="description" content="Laporan digital servis kendaraan {{ $report->license_plate }} di Hartono Motor">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        },
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom Styles -->
    <style>
        [x-cloak] {
            display: none !important;
        }

        .status-ok {
            @apply bg-green-500 text-white font-bold px-3 py-1 border-green-600;
        }

        .status-warning {
            @apply bg-yellow-100 text-yellow-800 border-yellow-200;
        }

        .status-needs-repair {
            @apply bg-red-100 text-red-800 border-red-200;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-after: always;
            }

            body {
                font-size: 12pt;
            }

            .print-full-width {
                width: 100% !important;
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Header Banner -->
    <div class="bg-primary-600 text-white py-3 px-4 no-print">
        <div class="container mx-auto max-w-4xl flex flex-col md:flex-row justify-between items-center">
            <p>Laporan ini tersedia hingga: <span class="font-semibold">{{ $report->expires_at->format('d F Y')
                    }}</span></p>
            <div class="flex space-x-4 mt-2 md:mt-0">
                <a href="https://hartonomotor.com" target="_blank" class="text-white hover:text-primary-200 transition">
                    <span class="sr-only">Website</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </a>
                <a href="https://instagram.com/hartonomotor" target="_blank"
                    class="text-white hover:text-primary-200 transition">
                    <span class="sr-only">Instagram</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"
                            clip-rule="evenodd" />
                    </svg>
                </a>
                <a href="https://tiktok.com/@hartonomotor" target="_blank"
                    class="text-white hover:text-primary-200 transition">
                    <span class="sr-only">TikTok</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z" />
                    </svg>
                </a>
                <a href="https://facebook.com/hartonomotor" target="_blank"
                    class="text-white hover:text-primary-200 transition">
                    <span class="sr-only">Facebook</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"
                            clip-rule="evenodd" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between items-center mb-8 border-b pb-6">
            <div class="flex items-center mb-4 md:mb-0">
                <div class="mr-4">
                    <img src="{{ asset('images/logo/logo.png') }}" alt="Hartono Motor"
                        class="h-20 md:h-24 object-contain">
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-primary-700">Hartono Motor</h1>
                    <p class="text-gray-600">Bengkel Mobil Terpercaya</p>
                    <div class="mt-1 flex space-x-3">
                        <a href="https://hartonomotor.com" target="_blank"
                            class="text-gray-500 hover:text-primary-600 transition text-sm">
                            hartonomotor.com
                        </a>
                        <a href="tel:+6281234567890" class="text-gray-500 hover:text-primary-600 transition text-sm">
                            0812-3456-7890
                        </a>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Tanggal Servis:</p>
                <p class="font-semibold">{{ $report->service_date->format('d F Y') }}</p>
                <p class="mt-2 text-sm text-gray-500">Kode Laporan:</p>
                <p class="font-mono font-semibold">{{ $report->code }}</p>
            </div>
        </header>

        <!-- Title -->
        <div class="text-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-primary-700">{{ $report->title }}</h2>
            <p class="text-gray-600 mt-2">Laporan pemeriksaan menyeluruh untuk kendaraan Anda</p>
        </div>

        <!-- Customer Info -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Informasi Pelanggan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Nama Pelanggan:</p>
                    <p class="font-semibold">{{ $report->customer_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Nomor Plat:</p>
                    <p class="font-semibold">{{ $report->license_plate }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Model Kendaraan:</p>
                    <p class="font-semibold">{{ $report->car_model }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Teknisi:</p>
                    <p class="font-semibold">{{ $report->technician_name ?? 'Tim Hartono Motor' }}</p>
                </div>
            </div>
        </div>

        <!-- Checklist -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Checklist Pemeriksaan 50 Titik</h3>

            @if($report->checklistItems->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th
                                class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                No</th>
                            <th
                                class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Titik Pemeriksaan</th>
                            <th
                                class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th
                                class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($report->checklistItems as $index => $item)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $item->inspection_point }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-{{ $item->status }}">
                                    {{ $item->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $item->notes }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-gray-500 italic">Tidak ada item checklist yang tersedia.</p>
            @endif
        </div>

        <!-- Services Performed -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Layanan yang Dilakukan</h3>

            @if(is_array($report->services_performed) && count($report->services_performed) > 0)
            <ul class="space-y-4">
                @foreach($report->services_performed as $service)
                <li class="border-l-4 border-primary-500 pl-4">
                    <p class="font-medium text-gray-800">{{ $service['service_name'] ?? '' }}</p>
                    @if(isset($service['description']) && !empty($service['description']))
                    <p class="text-sm text-gray-600 mt-1">{{ $service['description'] }}</p>
                    @endif
                </li>
                @endforeach
            </ul>
            @else
            <p class="text-gray-500 italic">Tidak ada layanan yang tercatat.</p>
            @endif

            @if(is_array($report->additional_services) && count($report->additional_services) > 0)
            <h4 class="text-md font-semibold text-gray-700 mt-6 mb-3">Layanan Tambahan</h4>
            <ul class="space-y-4">
                @foreach($report->additional_services as $service)
                <li class="border-l-4 border-green-500 pl-4">
                    <p class="font-medium text-gray-800">{{ $service['service_name'] ?? '' }}</p>
                    @if(isset($service['description']) && !empty($service['description']))
                    <p class="text-sm text-gray-600 mt-1">{{ $service['description'] }}</p>
                    @endif
                </li>
                @endforeach
            </ul>
            @endif
        </div>

        <!-- Summary & Recommendations -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <!-- Summary -->
            @if($report->summary)
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Ringkasan</h3>
                <div class="prose prose-sm max-w-none">
                    {!! $report->summary !!}
                </div>
            </div>
            @endif

            <!-- Recommendations -->
            @if($report->recommendations)
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Rekomendasi</h3>
                <div class="prose prose-sm max-w-none">
                    {!! $report->recommendations !!}
                </div>
            </div>
            @endif
        </div>

        <!-- Warranty Info -->
        @if($report->warranty_info)
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Informasi Garansi</h3>
            <div class="prose prose-sm max-w-none">
                {!! $report->warranty_info !!}
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex flex-wrap justify-center gap-4 mb-8 no-print">
            <a href="{{ route('service-reports.download', $report->code) }}"
                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download PDF
            </a>

            <button onclick="window.print()"
                class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak
            </button>

            <a href="https://wa.me/?text={{ urlencode('Laporan servis kendaraan saya di Hartono Motor: ' . route('service-reports.show', $report->code)) }}"
                target="_blank"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                </svg>
                Bagikan via WhatsApp
            </a>
        </div>

        <!-- Footer -->
        <footer class="text-center mt-12 border-t pt-6">
            <div class="bg-gray-100 rounded-lg p-6 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <img src="{{ asset('images/logo/logo.png') }}" alt="Hartono Motor"
                            class="h-16 mx-auto md:mx-0 mb-2">
                        <p class="text-primary-700 font-semibold">Hartono Motor</p>
                        <p class="text-gray-600 text-sm">Bengkel Mobil Terpercaya</p>
                    </div>
                    <div class="text-center md:text-right">
                        <p class="text-gray-700 font-medium mb-1">Hubungi Kami</p>
                        <p class="text-gray-600 text-sm mb-1">Jl. Samanhudi No 2, Kebonsari, Sidoarjo (Jasem)</p>
                        <p class="text-gray-600 text-sm mb-1">Telp: 0821-3520-2581</p>
                        <p class="text-gray-600 text-sm">Email: hartonomotor1979@gmail.com</p>
                    </div>
                </div>
            </div>

            <p class="text-gray-500 text-sm">© {{ date('Y') }} Hartono Motor. Semua hak dilindungi.</p>

            <div class="flex justify-center mt-4 space-x-6">
                <a href="https://wa.me/6282135202581" target="_blank"
                    class="text-gray-500 hover:text-primary-600 transition">
                    <span class="sr-only">WhatsApp</span>
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                    </svg>
                </a>
                <a href="https://instagram.com/hartonomotorsidoarjo" target="_blank"
                    class="text-gray-500 hover:text-primary-600 transition">
                    <span class="sr-only">Instagram</span>
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"
                            clip-rule="evenodd" />
                    </svg>
                </a>
                <a href="https://tiktok.com/@hartonomotorsidoarjo" target="_blank"
                    class="text-gray-500 hover:text-primary-600 transition">
                    <span class="sr-only">TikTok</span>
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z" />
                    </svg>
                </a>
                <a href="https://facebook.com/hartonomotorsidoarjo" target="_blank"
                    class="text-gray-500 hover:text-primary-600 transition">
                    <span class="sr-only">Facebook</span>
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"
                            clip-rule="evenodd" />
                    </svg>
                </a>
                <a href="https://hartonomotor.com" target="_blank"
                    class="text-gray-500 hover:text-primary-600 transition">
                    <span class="sr-only">Website</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </a>
            </div>
        </footer>
    </div>
</body>

</html>