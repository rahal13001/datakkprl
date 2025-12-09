<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ticket - {{ $client->ticket_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header-image {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: none;
        }
        .page-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 30px;
            letter-spacing: 1px;
            text-decoration: underline;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-top: 25px;
            margin-bottom: 15px;
            color: #000;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .label-col {
            width: 35%;
            font-weight: bold;
            color: #555;
        }
        .separator-col {
            width: 5%;
            text-align: center;
        }
        .value-col {
            width: 60%;
            color: #000;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border: 1px solid #000;
        }
        .schedule-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        .schedule-table td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 11px;
        }
        .qr-section {
            margin-top: 50px;
            text-align: right;
        }
        .qr-wrapper {
            display: inline-block;
            text-align: center;
        }
        .footer {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 9px;
            color: #666;
            text-align: left;
        }
        /* Utilities */
        .text-upper { text-transform: uppercase; }
        .text-bold { font-weight: bold; }
        .text-red { color: #d32f2f; }
    </style>
</head>
<body>
    <div class="container">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('img/kop_surat.png'))) }}" class="header-image" alt="Kop Surat">

        <div class="page-title">BUKTI PENDAFTARAN KONSULTASI</div>

        <div class="section-title">DATA PENDAFTARAN</div>
        <table class="info-table">
            <tr>
                <td class="label-col">Nomor Tiket</td>
                <td class="separator-col">:</td>
                <td class="value-col text-bold">{{ $client->ticket_number }}</td>
            </tr>
            <tr>
                <td class="label-col">Status</td>
                <td class="separator-col">:</td>
                <td class="value-col">
                    {{ match($client->status) {
                        'pending' => 'Menunggu',
                        'scheduled' => 'Dijadwalkan',
                        'in_progress' => 'Sedang Berlangsung',
                        'waiting_approval' => 'Menunggu Persetujuan',
                        'finished' => 'Selesai',
                        'canceled' => 'Dibatalkan',
                        default => $client->status
                    } }}
                </td>
            </tr>
            <tr>
                <td class="label-col">Layanan</td>
                <td class="separator-col">:</td>
                <td class="value-col">{{ $client->service->name }}</td>
            </tr>
            <tr>
                <td class="label-col">Tanggal Daftar</td>
                <td class="separator-col">:</td>
                <td class="value-col">{{ $client->created_at->translatedFormat('d F Y') }}</td>
            </tr>
        </table>

        <div class="section-title">DATA PEMOHON</div>
        <table class="info-table">
            <tr>
                <td class="label-col">Nama Lengkap</td>
                <td class="separator-col">:</td>
                <td class="value-col text-upper text-bold">{{ $client->contact_details['name'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label-col">Instansi / Lembaga</td>
                <td class="separator-col">:</td>
                <td class="value-col">{{ $client->contact_details['agency'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label-col">Email / Kontak</td>
                <td class="separator-col">:</td>
                <td class="value-col">
                    {{ $client->contact_details['email'] ?? '-' }} 
                    @if(isset($client->contact_details['wa'])) / {{ $client->contact_details['wa'] }} @endif
                </td>
            </tr>
        </table>

        <div class="section-title">JADWAL KONSULTASI</div>
        @if($client->schedules->isNotEmpty())
        <table class="schedule-table">
            <thead>
                <tr>
                    <th width="25%">TANGGAL</th>
                    <th width="20%">WAKTU</th>
                    <th width="15%">TIPE</th>
                    <th width="40%">KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($client->schedules as $schedule)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($schedule->date)->translatedFormat('l, d F Y') }}</td>
                    <td>{{ $schedule->start_time }} - {{ $schedule->end_time }}</td>
                    <td class="text-upper">{{ $schedule->is_online ? 'Online' : 'Offline' }}</td>
                    <td style="vertical-align: top;">
                        @if($schedule->meeting_link)
                            <div style="font-size: 10px; font-weight: bold; margin-bottom: 2px; color: #333;">Link Meeting</div>
                            <table style="width: 100%; border: none;">
                                <tr>
                                    <td style="width: 12px; padding: 0; border: none; vertical-align: top;">
                                        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAMCAYAAABWdVznAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAbElEQVR4nGP4//8/AypgGPz///+MqIqR5DAYsCggSYI0T2BgYDBgYGBg+P///38g/w0qBwImAvkfQJoZgLQIkG8A1QICjgwMDg4M7OzsDJ+fPgXKM0DNBbkGqgYkC9IMVAN3tRvBL6Ad/4E4FwB03iom6675qgAAAABJRU5ErkJggg==" width="10" alt="link" style="margin-top: 2px;">
                                    </td>
                                    <td style="padding: 0 0 0 5px; border: none; word-wrap: break-word; word-break: break-all; font-size: 10px; color: #2563eb;">
                                        <a href="{{ $schedule->meeting_link }}" style="color: #2563eb; text-decoration: none;">{{ $schedule->meeting_link }}</a>
                                    </td>
                                </tr>
                            </table>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <p style="font-style: italic; color: #888;">Belum ada jadwal yang ditentukan.</p>
        @endif

        <div class="qr-section">
            <div class="qr-wrapper">
                <p style="margin-bottom: 5px; font-size: 10px; color: #555;">Scan untuk Verifikasi</p>
                <!-- QR Code Generation (Base64 SVG) -->
                <img src="data:image/svg+xml;base64,{{ base64_encode(QrCode::format('svg')->size(100)->generate($client->ticket_number)) }}" alt="QR Code" width="100">
                <p style="margin-top: 5px; font-size: 9px; font-family: monospace;">{{ $client->ticket_number }}</p>
            </div>
        </div>

        <div class="footer">
            <p>Halaman ini adalah bukti sah pendaftaran konsultasi layanan KKPRL.</p>
            <p>Dicetak secara otomatis oleh sistem pada tanggal {{ now()->translatedFormat('d F Y, H:i') }} WIB.</p>
        </div>
    </div>
</body>
</html>
