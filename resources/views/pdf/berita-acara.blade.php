<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Berita Acara - {{ $beritaAcara->nomor_berita_acara ?? $client->ticket_number }}</title>
    <style>
        @page {
            margin: 2.5cm 2.5cm 2cm 2.5cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000;
            line-height: 1.5;
        }
        p, div, li, td, th {
            line-height: 1.5;
        }
        .header-image {
            width: 100%;
            margin-bottom: 20px;
        }
        .page-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 0;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .nomor-ba {
            text-align: center;
            font-size: 12pt;
            margin-bottom: 2px;
        }
        .nama-di {
            text-align: center;
            font-size: 12pt;
            margin-bottom: 15px;
        }
        .paragraph {
            text-align: justify;
            text-indent: 1.25cm;
            margin-bottom: 5px;
            margin-top: 0;
        }
        .paragraph-no-indent {
            text-align: justify;
            margin-bottom: 5px;
            margin-top: 0;
        }
        .attendee-list {
            margin-left: 1.25cm;
            margin-top: 2px;
            margin-bottom: 8px;
            padding-left: 20px;
        }
        .attendee-list li {
            margin-bottom: 0;
            padding: 0;
        }
        .hasil-content {
            margin-left: 0.5cm;
            margin-bottom: 8px;
            text-align: justify;
        }
        .hasil-content p {
            margin-top: 2px;
            margin-bottom: 2px;
        }
        .hasil-content ol,
        .hasil-content ul {
            margin-top: 2px;
            margin-bottom: 2px;
            padding-left: 20px;
        }
        .hasil-content li {
            margin-bottom: 1px;
        }
        .hasil-content table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 8px;
        }
        .hasil-content table,
        .hasil-content th,
        .hasil-content td {
            border: 1px solid #000;
        }
        .hasil-content th,
        .hasil-content td {
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }
        .closing-text {
            text-align: justify;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        /* Signature table */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 20px;
        }
        .signature-table th,
        .signature-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
            font-size: 11pt;
        }
        .signature-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .signature-table .sig-cell {
            text-align: center;
            height: 50px;
            vertical-align: middle;
        }
        .signature-table .sig-cell img {
            max-height: 45px;
            max-width: 110px;
        }
        .lampiran-section {
            margin-top: 20px;
            page-break-before: always;
        }
        .lampiran-title {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 8px;
            text-decoration: underline;
        }
        .lampiran-img {
            max-width: 100%;
            max-height: 700px;
            margin-bottom: 8px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    {{-- KOP SURAT --}}
    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('img/kop_surat_lprlsorong.png'))) }}" class="header-image" alt="Kop Surat">

    {{-- TITLE --}}
    <div class="page-title">BERITA ACARA PENDAMPINGAN PERMOHONAN</div>

    {{-- NOMOR --}}
    <div class="nomor-ba">
        Nomor {{ $beritaAcara->nomor_berita_acara ?? '...' }}
    </div>

    {{-- NAMA di LOKASI --}}
    <div class="nama-di">
        {{ $client->name }} di {{ $beritaAcara->lokasi_permohonan ?? '(Calon Lokasi Permohonan)' }}
    </div>

    {{-- PARAGRAF PEMBUKA --}}
    @php
        $tanggal = $beritaAcara->tanggal_pelaksanaan;
        $hariArr = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulanArr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $hari = $hariArr[$tanggal->dayOfWeek];
        $tgl = $tanggal->day;
        $bulan = $bulanArr[$tanggal->month];
        $tahun = $tanggal->year;

        // Determine activity from metadata or service
        $namaKegiatan = $client->service->name ?? '(Nama Kegiatan)';
        $jenisPermohonan = $beritaAcara->getJenisPermohonanLabel();
        $kbliText = $beritaAcara->kbli ? "dengan KBLI ({$beritaAcara->kbli})" : '';
        $lokasiPermohonan = $beritaAcara->lokasi_permohonan ?? '(Calon Lokasi Permohonan)';
    @endphp

    <p class="paragraph">
        Pada hari ini {{ $hari }} tanggal {{ $tgl }} Bulan {{ $bulan }} Tahun {{ $tahun }}, kami yang bertanda tangan
        di bawah ini telah melaksanakan pendampingan permohonan atas rencana
        permohonan {{ $jenisPermohonan }} untuk permohonan {{ $namaKegiatan }}
        {{ $kbliText }} oleh {{ $client->name }} di {{ $lokasiPermohonan }}
        yang dihadiri oleh:
    </p>

    {{-- DAFTAR HADIR --}}
    <ol class="attendee-list">
        @foreach($beritaAcara->attendees as $attendee)
            <li>{{ $attendee->nama }}{{ $attendee->jabatan ? ' (' . $attendee->jabatan . ')' : '' }}{{ $attendee->instansi ? ' — ' . $attendee->instansi : '' }};</li>
        @endforeach
    </ol>

    {{-- HASIL --}}
    <p class="paragraph-no-indent">
        Berdasarkan hasil pelaksanaan pendampingan permohonan, diperoleh hasil
        sebagai berikut:
    </p>

    <div class="hasil-content">
        {!! app(\App\Services\SafeRichText::class)->sanitize($beritaAcara->hasil_pendampingan ?? '<em>(Belum diisi)</em>') !!}
    </div>

    {{-- PENUTUP --}}
    <p class="closing-text">
        Demikian berita acara ini dibuat dengan sebenar-benarnya, untuk dapat
        dipergunakan sebagaimana mestinya.
    </p>

    {{-- TABEL TANDA TANGAN --}}
    {{-- Only show attendees who are designated as signatories (is_signatory = true) --}}
    @php
        $signatories = $beritaAcara->attendees->filter(function ($attendee) {
            return $attendee->is_signatory;
        });
    @endphp

    @if($signatories->isNotEmpty())
    <p class="paragraph-no-indent" style="font-weight: bold; margin-top: 15px; margin-bottom: 5px;">
        Yang Menandatangani:
    </p>
    <table class="signature-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 30%;">Nama</th>
                <th style="width: 35%;">Jabatan/Instansi</th>
                <th style="width: 30%;">Tanda tangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($signatories as $index => $attendee)
            <tr>
                <td style="text-align: center;">{{ $loop->iteration }}</td>
                <td>{{ $attendee->nama }}</td>
                <td>{{ $attendee->getJabatanInstansiLabel() }}</td>
                <td class="sig-cell">
                    @if($attendee->tanda_tangan)
                        @php
                            $sigDataUri = $signatureService->retrieveAsDataUri($attendee->tanda_tangan);
                        @endphp
                        @if($sigDataUri)
                            <img src="{{ $sigDataUri }}" alt="Tanda Tangan">
                        @else
                            -
                        @endif
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- DAFTAR HADIR (Attendance List) --}}
    {{-- @php
        $allAttendees = $beritaAcara->attendees;
    @endphp

    @if($allAttendees->isNotEmpty())
    <p class="paragraph-no-indent" style="font-weight: bold; margin-top: 15px; margin-bottom: 5px;">
        Daftar Hadir:
    </p>
    <table class="signature-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 30%;">Nama</th>
                <th style="width: 30%;">Jabatan/Instansi</th>
                <th style="width: 20%;">Status</th>
                <th style="width: 15%;">Dikonfirmasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allAttendees as $attendee)
            <tr>
                <td style="text-align: center;">{{ $loop->iteration }}</td>
                <td>{{ $attendee->nama }}</td>
                <td>{{ $attendee->getJabatanInstansiLabel() }}</td>
                <td style="text-align: center;">
                    @if($attendee->confirmed_at)
                        <span style="color: green;">✓ Hadir</span>
                    @elseif($beritaAcara->isAutoApproved())
                        <span style="color: #b45309;">Otomatis Disetujui</span>
                    @else
                        <span style="color: gray;">Menunggu</span>
                    @endif
                </td>
                <td style="text-align: center; font-size: 10pt;">
                    {{ $attendee->confirmed_at?->format('d/m/Y H:i') ?? '-' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif --}}

    {{-- LAMPIRAN I: PETA HASIL PLOTTING --}}
    @php
        $privateFiles = app(\App\Services\PrivateFileService::class);
    @endphp

    @if($beritaAcara->lampiran_peta)
    <div class="lampiran-section">
        <div class="lampiran-title">Lampiran I (Peta Hasil Plotting)</div>
        @php
            $petaPath = $beritaAcara->lampiran_peta;
            $ext = strtolower(pathinfo($petaPath, PATHINFO_EXTENSION));
        @endphp
        @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
            @if($privateFiles->exists($petaPath))
                <img src="data:image/{{ $ext }};base64,{{ base64_encode($privateFiles->get($petaPath)) }}" class="lampiran-img">
            @endif
        @else
            <p><em>Lampiran peta tersedia dalam format {{ strtoupper($ext) }}. Lihat file terlampir.</em></p>
        @endif
    </div>
    @endif

    {{-- LAMPIRAN II: DOKUMENTASI --}}
    @if($beritaAcara->lampiran_dokumentasi && count($beritaAcara->lampiran_dokumentasi) > 0)
    <div class="lampiran-section">
        <div class="lampiran-title">Lampiran II (Dokumentasi)</div>
        @foreach($beritaAcara->lampiran_dokumentasi as $doc)
            @if($privateFiles->exists($doc))
                <div style="margin-bottom: 10px; text-align: center;">
                    <img src="data:image/jpeg;base64,{{ base64_encode($privateFiles->get($doc)) }}" class="lampiran-img">
                </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- LAMPIRAN III: HAL LAINNYA --}}
    @if($beritaAcara->lampiran_lainnya && count($beritaAcara->lampiran_lainnya) > 0)
    <div class="lampiran-section">
        <div class="lampiran-title">Lampiran III (Hal lainnya yang berkembang)*</div>
        @foreach($beritaAcara->lampiran_lainnya as $file)
            @php
                $fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            @endphp
            @if(in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']) && $privateFiles->exists($file))
                <div style="margin-bottom: 10px; text-align: center;">
                    <img src="data:image/{{ $fileExt }};base64,{{ base64_encode($privateFiles->get($file)) }}" class="lampiran-img">
                </div>
            @else
                <p>File: {{ basename($file) }}</p>
            @endif
        @endforeach
    </div>
    @endif

</body>
</html>
