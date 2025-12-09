<!DOCTYPE html>
<html>
<head>
    <title>Pendaftaran Berhasil</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2 style="color: #2563eb;">Pendaftaran Konsultasi Berhasil</h2>
    <p>Halo, <strong>{{ $client->contact_details['name'] ?? 'Sobat Bahari' }}</strong>.</p>
    
    <p>Terima kasih telah mendaftar layanan konsultasi di <strong>Loka Pengelolaan Sumberdaya Pesisir dan Laut Sorong</strong>.</p>
    
    <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0 0 10px;"><strong>Nomor Tiket:</strong> {{ $client->ticket_number }}</p>
        <p style="margin: 0 0 10px;"><strong>Layanan:</strong> {{ $client->service->name }}</p>
        <p style="margin: 0;"><strong>Status:</strong> {{ ucfirst($client->status) }}</p>
    </div>

    <div style="background: #fff1f2; border: 1px solid #e11d48; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #be123c;">PENTING: Access Token</h3>
        <p>Gunakan Access Token ini untuk melihat status atau mengelola tiket Anda. <strong>Jangan bagikan kode ini kepada siapapun selain petugas.</strong></p>
        <p style="font-family: monospace; font-size: 24px; font-weight: bold; background: #fff; padding: 10px; text-align: center; border: 1px dashed #e11d48;">
            {{ $client->access_token }}
        </p>
    </div>

    <p>Anda akan menerima email pemberitahuan selanjutnya jika jadwal konsultasi telah ditentukan.</p>
    
    <p>Salam,<br>
    Admin Layanan KKPRL</p>
</body>
</html>
