<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan - {{ $client->ticket_number }}</title>
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
        .label-col { width: 35%; font-weight: bold; color: #555; }
        .separator-col { width: 5%; text-align: center; }
        .value-col { width: 60%; color: #000; }
        .content-box {
            margin-top: 10px;
            text-align: justify;
        }
        .docs-grid {
            width: 100%;
            margin-top: 10px;
        }
        .docs-row {
            width: 100%;
            margin-bottom: 15px;
        }
        .doc-item {
            display: inline-block;
            width: 48%; /* fit two in a row roughly with spacing */
            margin-right: 1%;
            vertical-align: top;
            text-align: center;
        }
        .doc-img {
            max-width: 100%;
            max-height: 250px;
            border: 1px solid #ccc;
            padding: 2px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 9px;
            color: #666;
            text-align: left;
        }
        .text-upper { text-transform: uppercase; }
        .text-bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('img/kop_surat.png'))) }}" class="header-image" alt="Kop Surat">

        <div class="page-title">LAPORAN HASIL KONSULTASI</div>

        <div class="section-title">DATA PEMOHON</div>
        <table class="info-table">
            <tr>
                <td class="label-col">Nomor Tiket</td>
                <td class="separator-col">:</td>
                <td class="value-col text-bold">{{ $client->ticket_number }}</td>
            </tr>
            <tr>
                <td class="label-col">Nama Lengkap</td>
                <td class="separator-col">:</td>
                <td class="value-col text-upper text-bold">{{ $client->name }}</td>
            </tr>
            <tr>
                <td class="label-col">Instansi / Lembaga</td>
                <td class="separator-col">:</td>
                <td class="value-col">{{ $client->instance ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label-col">Layanan</td>
                <td class="separator-col">:</td>
                <td class="value-col">{{ $client->service->name }}</td>
            </tr>
            <tr>
                <td class="label-col">Tanggal Konsultasi</td>
                <td class="separator-col">:</td>
                <td class="value-col">
                    @if($client->schedules->isNotEmpty())
                        {{ $client->schedules->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->translatedFormat('d F Y'))->sort()->implode(', ') }}
                    @else
                        -
                    @endif
                </td>
            </tr>
        </table>

        <div class="section-title">ISI LAPORAN</div>
        <div class="content-box">
            {!! $report->content !!}
        </div>

        @if($report->documentation && count($report->documentation) > 0)
        <div class="section-title">DOKUMENTASI</div>
        <div class="docs-grid">
            @foreach($report->documentation as $doc)
                @if(\Illuminate\Support\Facades\Storage::disk('public')->exists($doc))
                <div class="doc-item">
                    <img src="data:image/jpeg;base64,{{ base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($doc)) }}" class="doc-img">
                </div>
                @endif
            @endforeach
        </div>
        @endif

        <div class="footer">
            <p>Dokumen ini adalah laporan resmi hasil layanan konsultasi KKPRL.</p>
            <p>Dicetak secara otomatis oleh sistem pada tanggal {{ now()->timezone('Asia/Jayapura')->translatedFormat('d F Y, H:i') }} WIT.</p>
            <p style="margin-top: 10px; font-style: italic; font-weight: bold;">Dokumen ini bukan merupakan dokumen hukum yang menjadi dasar hukum formil pengurusan izin KKPRL.</p>
        </div>
    </div>
</body>
</html>
