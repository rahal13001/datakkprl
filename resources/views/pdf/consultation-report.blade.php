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
        .page-break {
            page-break-before: always;
        }
        .gallery {
            text-align: center;
            margin-top: 20px;
        }
        .gallery-item {
            display: inline-block;
            width: 45%;
            margin: 5px;
            vertical-align: top;
        }
        .gallery-item img {
            width: 100%;
            height: auto;
            border: 1px solid #ddd;
            padding: 3px;
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
            <td>: {{ $client->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kategori</td>
            <td>: {{ match($client->booking_type) { 'personal' => 'Perorangan', 'company' => 'Instansi / Perusahaan', default => '-' } }}</td>
        </tr>
        <tr>
            <td class="label">Instansi / Lembaga</td>
            <td>: {{ $client->instance ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td>: {{ $client->address ?? '-' }}</td>
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

    @if(!empty($report->documentation))
        <div class="page-break"></div>
        <div class="section-title">Dokumentasi</div>
        <div class="gallery">
            @foreach($report->documentation as $image)
                @php
                    $path = public_path('storage/' . $image);
                    $exists = file_exists($path);
                @endphp
                <div class="gallery-item">
                    @if($exists)
                        <img src="{{ $path }}" alt="Dokumentasi">
                    @else
                        <div style="border:1px solid red; padding:10px;">
                            File not found: {{ $image }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="footer">
        <div class="signature-box">
            <p>Dicetak pada: {{ now()->format('d M Y') }}</p>
            <div class="signature-space"></div>
            <p><strong>{{ auth()->user()->name ?? 'Petugas' }}</strong></p>
        </div>
    </div>
</body>
</html>
