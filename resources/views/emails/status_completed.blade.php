<!DOCTYPE html>
<html>
<head>
    <title>Layanan Konsultasi Selesai</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2 style="color: #059669;">Layanan Konsultasi Selesai</h2>
    <p>Halo, <strong>{{ $client->name }}</strong>.</p>
    
    <p>Layanan konsultasi Anda telah dinyatakan <strong>SELESAI</strong>. Terima kasih telah menggunakan layanan Loka Pengelolaan Sumberdaya Pesisir dan Laut Sorong.</p>
    
    <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0 0 10px;"><strong>Nomor Tiket:</strong> {{ $client->ticket_number }}</p>
        <p style="margin: 0 0 10px;"><strong>Layanan:</strong> {{ $client->service->name }}</p>
        <p style="margin: 0;"><strong>Status:</strong> <span style="color: #059669; font-weight: bold;">Selesai</span></p>
    </div>

    @if($client->latestConsultationReport)
    <div style="text-align: center; margin: 30px 0;">
        <p>Silakan memberikan penilaian layanan dan mengunduh laporan hasil konsultasi melalui tombol di bawah ini:</p>
        <!-- Redirect to Check Status Page with auto-login params -->
        <a href="{{ route('check-status', ['ticket' => $client->ticket_number, 'token' => $client->access_token]) }}" style="background-color: #059669; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
            Cek Status Layanan
        </a>
    </div>
    @endif

    <p>Jika Anda memiliki pertanyaan lebih lanjut, jangan ragu untuk menghubungi kami kembali.</p>

    <p>Salam,<br>
    Admin Layanan KKPRL</p>
</body>
</html>
