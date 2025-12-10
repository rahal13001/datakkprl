<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Konsultasi - {{ $client->ticket_number }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 5px;
            vertical-align: top;
        }
        .meta-table .label {
            font-weight: bold;
            width: 150px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            background-color: #f3f4f6;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #3b82f6;
        }
        .content {
            margin-bottom: 30px;
            text-align: justify;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
            font-size: 12px;
        }
        .signature-box {
            display: inline-block;
            text-align: center;
            width: 200px;
        }
        .signature-space {
            height: 80px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Konsultasi</h1>
        <p>Aplikasi Layanan KKPRL</p>
    </div>

    <table class="meta-table">
        <tr>
            <td class="label">Nomor Tiket</td>
            <td>: {{ $client->ticket_number }}</td>
        </tr>
        <tr>
            <td class="label">Nama Pemohon</td>
            <td>: {{ $client->contact_details['name'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Instansi</td>
            <td>: {{ $client->contact_details['agency'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Layanan</td>
            <td>: {{ $client->service->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Konsultasi</td>
            <td>: {{ $client->schedules->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M Y'))->join(', ') }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>: {{ ucfirst($report->status ?? 'Draft') }}</td>
        </tr>
    </table>

    <div class="section-title">Hasil Konsultasi</div>

    <div class="content">
        {!! $report->content ?? '<p>Belum ada isi laporan.</p>' !!}
    </div>

    <div class="footer">
        <div class="signature-box">
            <p>Dicetak pada: {{ now()->format('d M Y') }}</p>
            <div class="signature-space"></div>
            <p><strong>{{ auth()->user()->name ?? 'Petugas' }}</strong></p>
        </div>
    </div>
</body>
</html>
