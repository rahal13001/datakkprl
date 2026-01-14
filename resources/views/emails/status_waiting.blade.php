<!DOCTYPE html>
<html>
<head>
    <title>Perubahan Status Konsultasi</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2 style="color: #d97706;">Perubahan Status Konsultasi</h2>
    <p>Halo, <strong>{{ $client->name }}</strong>.</p>
    
    <p>Status permohonan konsultasi Anda telah berubah menjadi <strong>Menunggu</strong>. Hal ini mungkin dikarenakan adanya perubahan jadwal atau penugasan petugas.</p>
    
    <div style="background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0 0 10px;"><strong>Nomor Tiket:</strong> {{ $client->ticket_number }}</p>
        <p style="margin: 0 0 10px;"><strong>Layanan:</strong> {{ $client->service->name }}</p>
        <p style="margin: 0;"><strong>Status Saat Ini:</strong> <span style="color: #d97706; font-weight: bold;">Menunggu</span></p>
    </div>

    <p>Kami sedang memproses ulang penjadwalan layanan Anda. Anda akan menerima notifikasi segera setelah jadwal baru ditetapkan.</p>

    <div style="background: #fff1f2; border: 1px solid #e11d48; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #be123c;">Akses Tiket</h3>
        <p>Anda dapat memantau status terkini menggunakan Access Token Anda.</p>
        <p style="font-family: monospace; font-size: 24px; font-weight: bold; background: #fff; padding: 10px; text-align: center; border: 1px dashed #e11d48;">
            {{ $client->access_token }}
        </p>
    </div>

    <p>Salam,<br>
    Admin Layanan KKPRL</p>
</body>
</html>
