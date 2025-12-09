<!DOCTYPE html>
<html>
<head>
    <title>Update Status Konsultasi</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2 style="color: #2563eb;">Update Informasi Konsultasi</h2>
    <p>Halo, <strong>{{ $client->contact_details['name'] ?? 'Sobat Bahari' }}</strong>.</p>
    
    <p>Terdapat perubahan informasi pada tiket konsultasi Anda.</p>
    
    <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0 0 10px;"><strong>Nomor Tiket:</strong> {{ $client->ticket_number }}</p>
        <p style="margin: 0 0 10px;"><strong>Layanan:</strong> {{ $client->service->name }}</p>
        <p style="margin: 0;"><strong>Status Terkini:</strong> 
            @switch($client->status)
                @case('pending') Menunggu @break
                @case('scheduled') Dijadwalkan @break
                @case('in_progress') Sedang Berlangsung @break
                @case('waiting_approval') Menunggu Persetujuan @break
                @case('finished') Selesai @break
                @case('canceled') Dibatalkan @break
                @default {{ $client->status }}
            @endswitch
        </p>
    </div>

    @if($client->status === 'scheduled')
        <p>Silakan cek jadwal terbaru yang telah kami tetapkan.</p>
    @endif

    <p>Anda dapat mengunduh bukti tiket terbaru melalui tautan yang tersedia di dashboard kami (menggunakan Access Token Anda).</p>
    
    <p>Salam,<br>
    Admin Layanan KKPRL</p>
</body>
</html>
