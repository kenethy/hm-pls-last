<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Certificate Mobil Sehat - {{ $report->customer_name }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 14px;
            line-height: 1.6;
            background: #f8f9fa;
        }

        .certificate-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 8px solid #dc2626;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .certificate-header {
            text-align: center;
            border-bottom: 3px solid #dc2626;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo-section {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo {
            height: 80px;
            margin-right: 20px;
        }

        .company-info h1 {
            font-size: 28px;
            font-weight: bold;
            color: #dc2626;
            margin: 0;
            letter-spacing: 2px;
        }

        .company-info p {
            font-size: 14px;
            color: #666;
            margin: 5px 0 0 0;
        }

        .certificate-title {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin: 20px 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .certificate-subtitle {
            font-size: 16px;
            color: #6b7280;
            font-style: italic;
            margin: 0;
        }

        .certificate-body {
            margin: 30px 0;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            padding: 8px 15px;
            vertical-align: top;
            width: 50%;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
        }

        .certificate-number {
            color: #dc2626 !important;
            font-family: 'Courier New', monospace;
        }

        .health-status-section {
            background: #f3f4f6;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
            border: 2px solid #e5e7eb;
        }

        .health-status-title {
            font-size: 14px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .health-metrics {
            display: flex;
            justify-content: center;
            gap: 40px;
        }

        .health-metric {
            text-align: center;
        }

        .health-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .health-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
        }

        .status-sangat-sehat { color: #059669; }
        .status-sehat { color: #10b981; }
        .status-cukup-sehat { color: #f59e0b; }
        .status-perlu-perhatian { color: #f97316; }
        .status-perlu-perbaikan { color: #dc2626; }

        .score-value {
            color: #dc2626;
        }

        .certificate-statement {
            text-align: center;
            font-size: 16px;
            line-height: 1.8;
            color: #374151;
            margin: 30px 0;
            padding: 20px;
            background: #fef2f2;
            border-radius: 8px;
            border-left: 4px solid #dc2626;
        }

        .certificate-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 40px;
            border-top: 2px solid #dc2626;
            padding-top: 20px;
        }

        .issuer-info {
            text-align: left;
        }

        .issuer-info .issuer-label {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .issuer-info .issuer-name {
            font-size: 16px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 3px;
        }

        .issuer-info .issuer-address {
            font-size: 11px;
            color: #6b7280;
            line-height: 1.4;
        }

        .digital-seal {
            text-align: center;
        }

        .seal-box {
            width: 80px;
            height: 80px;
            background: #fef2f2;
            border: 3px solid #dc2626;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }

        .seal-icon {
            width: 40px;
            height: 40px;
            background: #dc2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .seal-checkmark {
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .seal-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
        }

        .verification-info {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .verification-code {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            font-weight: bold;
            color: #dc2626;
            background: #f3f4f6;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 5px;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(220, 38, 38, 0.05);
            font-weight: bold;
            z-index: -1;
            pointer-events: none;
        }

        @page {
            margin: 15mm;
            size: A4;
        }
    </style>
</head>

<body>
    <!-- Watermark -->
    <div class="watermark">HARTONO MOTOR</div>

    <div class="certificate-container">
        <!-- Certificate Header -->
        <div class="certificate-header">
            <div class="logo-section">
                <img src="{{ public_path('images/logo/logo.png') }}" alt="Hartono Motor" class="logo">
                <div class="company-info">
                    <h1>HARTONO MOTOR</h1>
                    <p>Bengkel Mobil Terpercaya</p>
                </div>
            </div>
            <h2 class="certificate-title">Sertifikat Kesehatan Kendaraan</h2>
            <p class="certificate-subtitle">Vehicle Health Certificate</p>
        </div>

        <!-- Certificate Body -->
        <div class="certificate-body">
            <!-- Vehicle Information -->
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-cell">
                        <div class="info-label">Nomor Sertifikat</div>
                        <div class="info-value certificate-number">{{ $report->certificate_number }}</div>
                    </div>
                    <div class="info-cell">
                        <div class="info-label">Tanggal Pemeriksaan</div>
                        <div class="info-value">{{ $report->certificate_issued_date->format('d F Y') }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-cell">
                        <div class="info-label">Nama Pemilik</div>
                        <div class="info-value">{{ $report->customer_name }}</div>
                    </div>
                    <div class="info-cell">
                        <div class="info-label">Berlaku Hingga</div>
                        <div class="info-value">{{ $report->certificate_valid_until->format('d F Y') }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-cell">
                        <div class="info-label">Nomor Plat</div>
                        <div class="info-value">{{ $report->license_plate }}</div>
                    </div>
                    <div class="info-cell">
                        <div class="info-label">Teknisi</div>
                        <div class="info-value">{{ $report->technician_name ?? 'Tim Hartono Motor' }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-cell">
                        <div class="info-label">Model Kendaraan</div>
                        <div class="info-value">{{ $report->car_model }}</div>
                    </div>
                    <div class="info-cell">
                        <div class="info-label">Kode Verifikasi</div>
                        <div class="info-value certificate-number">{{ $report->certificate_verification_code }}</div>
                    </div>
                </div>
            </div>

            <!-- Health Status -->
            <div class="health-status-section">
                <div class="health-status-title">Status Kesehatan Kendaraan</div>
                <div class="health-metrics">
                    <div class="health-metric">
                        <div class="health-value status-{{ strtolower(str_replace(' ', '-', $report->health_status)) }}">
                            {{ $report->health_status }}
                        </div>
                        <div class="health-label">Kondisi Umum</div>
                    </div>
                    <div class="health-metric">
                        <div class="health-value score-value">{{ $report->overall_condition_score }}%</div>
                        <div class="health-label">Skor Kondisi</div>
                    </div>
                </div>
            </div>

            <!-- Certificate Statement -->
            <div class="certificate-statement">
                Berdasarkan pemeriksaan menyeluruh yang telah dilakukan sesuai dengan standar pemeriksaan 50 titik, 
                kendaraan dengan nomor plat <strong>{{ $report->license_plate }}</strong> telah dinyatakan dalam kondisi 
                <strong>{{ $report->health_status }}</strong> dengan skor kondisi <strong>{{ $report->overall_condition_score }}%</strong>. 
                Sertifikat ini berlaku hingga <strong>{{ $report->certificate_valid_until->format('d F Y') }}</strong>.
            </div>
        </div>

        <!-- Certificate Footer -->
        <div class="certificate-footer">
            <div class="issuer-info">
                <div class="issuer-label">Diterbitkan secara digital oleh:</div>
                <div class="issuer-name">HARTONO MOTOR</div>
                <div class="issuer-address">
                    Jl. Samanhudi No 2, Kebonsari, Sidoarjo (Jasem)<br>
                    Telp: 0821-3520-2581<br>
                    Email: hartonomotor1979@gmail.com
                </div>
            </div>
            <div class="digital-seal">
                <div class="seal-box">
                    <div class="seal-icon">
                        <div class="seal-checkmark">✓</div>
                    </div>
                </div>
                <div class="seal-label">Digital Seal</div>
            </div>
        </div>

        <!-- Verification Info -->
        <div class="verification-info">
            <p style="font-size: 11px; color: #6b7280; margin: 0;">
                Untuk memverifikasi keaslian sertifikat ini, gunakan kode verifikasi:
            </p>
            <div class="verification-code">{{ $report->certificate_verification_code }}</div>
        </div>
    </div>
</body>

</html>
