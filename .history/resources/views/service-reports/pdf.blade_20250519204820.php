<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report->title }} - {{ $report->customer_name }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
        }
        
        .logo {
            height: 60px;
            margin-right: 15px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        
        .company-tagline {
            font-size: 14px;
            color: #666;
            margin: 0;
        }
        
        .date-info {
            text-align: right;
        }
        
        .date-label {
            font-size: 12px;
            color: #666;
            margin: 0;
        }
        
        .date-value {
            font-weight: bold;
            margin: 0;
        }
        
        .title-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .report-title {
            font-size: 24px;
            font-weight: bold;
            color: #0284c7;
            margin: 0;
        }
        
        .report-subtitle {
            font-size: 14px;
            color: #666;
            margin: 5px 0 0;
        }
        
        .section {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin: 0 0 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .customer-info {
            display: flex;
            flex-wrap: wrap;
        }
        
        .info-item {
            width: 50%;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            margin: 0;
        }
        
        .info-value {
            font-weight: bold;
            margin: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background-color: #f3f4f6;
            text-align: left;
            padding: 10px;
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .status-ok {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .status-warning {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .status-needs-repair {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        
        .service-item {
            margin-bottom: 15px;
            padding-left: 15px;
            border-left: 4px solid #0ea5e9;
        }
        
        .service-name {
            font-weight: bold;
            margin: 0;
        }
        
        .service-description {
            font-size: 12px;
            color: #666;
            margin: 5px 0 0;
        }
        
        .additional-service {
            border-left-color: #10b981;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 11px;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-container">
                <img src="{{ public_path('images/logo.png') }}" alt="Hartono Motor" class="logo">
                <div>
                    <h1 class="company-name">Hartono Motor</h1>
                    <p class="company-tagline">Bengkel Mobil Terpercaya</p>
                </div>
            </div>
            <div class="date-info">
                <p class="date-label">Tanggal Servis:</p>
                <p class="date-value">{{ $report->service_date->format('d F Y') }}</p>
                <p class="date-label" style="margin-top: 10px;">Kode Laporan:</p>
                <p class="date-value" style="font-family: monospace;">{{ $report->unique_code }}</p>
            </div>
        </div>
        
        <!-- Title -->
        <div class="title-section">
            <h2 class="report-title">{{ $report->title }}</h2>
            <p class="report-subtitle">Laporan pemeriksaan menyeluruh untuk kendaraan Anda</p>
        </div>
        
        <!-- Customer Info -->
        <div class="section">
            <h3 class="section-title">Informasi Pelanggan</h3>
            <div class="customer-info">
                <div class="info-item">
                    <p class="info-label">Nama Pelanggan:</p>
                    <p class="info-value">{{ $report->customer_name }}</p>
                </div>
                <div class="info-item">
                    <p class="info-label">Nomor Plat:</p>
                    <p class="info-value">{{ $report->license_plate }}</p>
                </div>
                <div class="info-item">
                    <p class="info-label">Model Kendaraan:</p>
                    <p class="info-value">{{ $report->car_model }}</p>
                </div>
                <div class="info-item">
                    <p class="info-label">Teknisi:</p>
                    <p class="info-value">{{ $report->technician_name ?? 'Tim Hartono Motor' }}</p>
                </div>
            </div>
        </div>
        
        <!-- Checklist -->
        <div class="section">
            <h3 class="section-title">Checklist Pemeriksaan 50 Titik</h3>
            
            @if($report->checklistItems->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 45%;">Titik Pemeriksaan</th>
                            <th style="width: 20%;">Status</th>
                            <th style="width: 30%;">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report->checklistItems as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->inspection_point }}</td>
                                <td>
                                    <span class="status-badge status-{{ $item->status }}">
                                        {{ $item->status_label }}
                                    </span>
                                </td>
                                <td>{{ $item->notes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="font-style: italic; color: #666;">Tidak ada item checklist yang tersedia.</p>
            @endif
        </div>
        
        <!-- Services Performed -->
        <div class="section">
            <h3 class="section-title">Layanan yang Dilakukan</h3>
            
            @if(is_array($report->services_performed) && count($report->services_performed) > 0)
                @foreach($report->services_performed as $service)
                    <div class="service-item">
                        <p class="service-name">{{ $service['service_name'] ?? '' }}</p>
                        @if(isset($service['description']) && !empty($service['description']))
                            <p class="service-description">{{ $service['description'] }}</p>
                        @endif
                    </div>
                @endforeach
            @else
                <p style="font-style: italic; color: #666;">Tidak ada layanan yang tercatat.</p>
            @endif
            
            @if(is_array($report->additional_services) && count($report->additional_services) > 0)
                <h4 style="margin-top: 20px; font-size: 14px; color: #444;">Layanan Tambahan</h4>
                @foreach($report->additional_services as $service)
                    <div class="service-item additional-service">
                        <p class="service-name">{{ $service['service_name'] ?? '' }}</p>
                        @if(isset($service['description']) && !empty($service['description']))
                            <p class="service-description">{{ $service['description'] }}</p>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
        
        <!-- Summary & Recommendations -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <!-- Summary -->
            @if($report->summary)
                <div style="width: 48%;" class="section">
                    <h3 class="section-title">Ringkasan</h3>
                    <div>
                        {!! $report->summary !!}
                    </div>
                </div>
            @endif
            
            <!-- Recommendations -->
            @if($report->recommendations)
                <div style="width: 48%;" class="section">
                    <h3 class="section-title">Rekomendasi</h3>
                    <div>
                        {!! $report->recommendations !!}
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Warranty Info -->
        @if($report->warranty_info)
            <div class="section">
                <h3 class="section-title">Informasi Garansi</h3>
                <div>
                    {!! $report->warranty_info !!}
                </div>
            </div>
        @endif
        
        <!-- Footer -->
        <div class="footer">
            <p>© {{ date('Y') }} Hartono Motor. Semua hak dilindungi.</p>
            <p>WhatsApp: 081234567890 | Instagram: @hartonomotor | Website: hartonomotor.com</p>
        </div>
    </div>
</body>
</html>
