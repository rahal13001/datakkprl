<!DOCTYPE html>
<html>
<head>
    <title>Jadwal Konsultasi Ditetapkan</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2 style="color: #2563eb;">Jadwal Konsultasi Telah Ditetapkan</h2>
    <p>Halo, <strong>{{ $client->name }}</strong>.</p>
    
    <p>Permohonan konsultasi Anda telah diterima dan petugas telah dijadwalkan untuk melayani Anda.</p>
    
    <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0 0 10px;"><strong>Nomor Tiket:</strong> {{ $client->ticket_number }}</p>
        <p style="margin: 0 0 10px;"><strong>Layanan:</strong> {{ $client->service->name }}</p>
        <p style="margin: 0;"><strong>Status:</strong> <span style="color: #2563eb; font-weight: bold;">Dijadwalkan</span></p>
    </div>

    @if($client->schedules->count() > 0)
    <div style="margin: 20px 0;">
        <h3 style="color: #4b5563;">Detail Jadwal:</h3>
        @foreach($client->schedules as $schedule)
            <div style="border-left: 4px solid #2563eb; padding-left: 15px; margin-bottom: 15px;">
                <p style="margin: 5px 0;"><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($schedule->date)->translatedFormat('l, d F Y') }}</p>
                <p style="margin: 5px 0;"><strong>Waktu:</strong> {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }} WIT</p>
                <p style="margin: 5px 0;"><strong>Tipe:</strong> {{ $schedule->is_online ? 'Online (Daring)' : 'Offline (Tatap Muka)' }}</p>
                @if($schedule->is_online && $schedule->meeting_link)
                    <p style="margin: 5px 0;"><strong>Link Meeting:</strong> <a href="{{ $schedule->meeting_link }}">{{ $schedule->meeting_link }}</a></p>
                @endif
            </div>
        @endforeach
    </div>
    @endif

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
