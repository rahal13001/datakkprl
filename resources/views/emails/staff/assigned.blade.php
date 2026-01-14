<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style>
        body {
            background-color: #f4f6f8;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f6f8;
            padding: 40px 0;
        }
        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border-top: 5px solid #0ea5e9; /* Professional Blue */
        }
        .email-header {
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid #edf2f7;
            background-color: #f8fafc;
        }
        .logo-text {
            font-size: 20px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .email-body {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
        }
        .info-row {
            display: flex;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .label {
            width: 120px;
            font-weight: 600;
            color: #64748b;
        }
        .value {
            color: #334155;
            flex: 1;
            font-weight: 500;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0ea5e9;
            color: #ffffff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
        }
        .footer {
            padding: 24px;
            background-color: #f8fafc;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #edf2f7;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-content">
            <div class="email-header">
                <div class="logo-text">Layanan KKPRL</div>
            </div>
            
            <div class="email-body">
                <div class="greeting">Halo, {{ $staffName }}</div>
                <p>Anda telah ditunjuk untuk menangani sesi konsultasi berikut ini:</p>

                <div class="info-card">
                    <div class="info-row">
                        <span class="label">Klien</span>
                        <span class="value">
                            {{ $assignment->schedule->client->name }}
                            @if($assignment->schedule->client->instance)
                                / {{ $assignment->schedule->client->instance }}
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Layanan</span>
                        <span class="value">{{ $serviceName }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Tiket</span>
                        <span class="value" style="font-family: monospace; letter-spacing: 0.5px;">{{ $ticketNumber }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Tanggal</span>
                        <span class="value">{{ $date }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Pukul</span>
                        <span class="value">{{ $time }} WIB</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Lokasi</span>
                        <span class="value">
                            @if($isOnline)
                                <a href="{{ $meetingLink }}" target="_blank" style="color: #0ea5e9; text-decoration: none;">Link Pertemuan &rarr;</a>
                            @else
                                Kantor LPSPL Sorong
                            @endif
                        </span>
                    </div>
                    @if($isOnline && !$meetingLink)
                    <div class="info-row">
                        <span class="label">Link</span>
                        <span class="value" style="color: #ef4444; font-size: 13px; font-style: italic;">(Belum tersedia)</span>
                    </div>
                    @endif
                </div>

                <p style="font-size: 14px; color: #64748b; line-height: 1.6;">
                    Mohon persiapkan dokumen yang diperlukan dan hadir tepat waktu. Jika ada halangan, harap segera hubungi administrator.
                </p>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="{{ $dashboardUrl }}" class="btn">Buka Detail Klien</a>
                </div>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                Loka Pengelolaan Sumber Daya Pesisir dan Laut (LPSPL) Sorong
            </div>
        </div>
    </div>
</body>
</html>
