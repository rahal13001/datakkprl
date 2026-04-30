<!DOCTYPE html>
<html>
<head>
    <title>Berita Acara Selesai</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2 style="color: #2563eb;">Berita Acara Selesai</h2>
    <p>Halo, <strong>{{ $client->name }}</strong>.</p>

    <p>Berita acara untuk layanan konsultasi Anda telah dinyatakan <strong>SELESAI</strong>.</p>

    <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0 0 10px;"><strong>Nomor Tiket:</strong> {{ $client->ticket_number }}</p>
        <p style="margin: 0 0 10px;"><strong>Nomor Berita Acara:</strong> {{ $beritaAcara->nomor_berita_acara ?: '-' }}</p>
        <p style="margin: 0 0 10px;"><strong>Layanan:</strong> {{ $client->service?->name ?: '-' }}</p>
        <p style="margin: 0;"><strong>Tanggal Pelaksanaan:</strong> {{ $beritaAcara->tanggal_pelaksanaan?->format('d M Y') ?: '-' }}</p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <p>Berita acara dapat diunduh setelah Anda mengisi survei kepuasan layanan.</p>
        <a href="{{ $checkStatusUrl }}" style="background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin: 4px;">
            Cek Status & Isi Survei
        </a>
        <a href="{{ $downloadUrl }}" style="background-color: #059669; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin: 4px;">
            Unduh Berita Acara
        </a>
    </div>

    <p>Jika tombol unduh mengarahkan Anda ke halaman cek status, silakan selesaikan survei terlebih dahulu.</p>

    <p>Salam,<br>
    Admin Layanan KKPRL</p>
</body>
</html>
