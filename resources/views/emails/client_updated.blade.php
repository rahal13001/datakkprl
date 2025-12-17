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

    <p>Silakan cek jadwal terbaru atau unduh tiket Anda melalui tautan di bawah ini:</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $url }}" style="background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">
            Cek Status
        </a>
    </div>

    <p style="font-size: 12px; color: #666;">Atau salin tautan berikut: <br>{{ $url }}</p>
    
    <p>Salam,<br>
    Admin Layanan KKPRL</p>
</body>
</html>
