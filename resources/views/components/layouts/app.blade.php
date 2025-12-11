<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Layanan Konsultasi LPSPL - Sorong' }}</title>
    <link rel="icon" href="{{ asset('img/logokkp.jpg') }}" type="image/jpeg">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-slate-600 bg-white antialiased selection:bg-brand-blue selection:text-white">

    <!-- Aurora Background (Subtle Art) -->
    <div class="fixed top-0 left-0 w-full h-full -z-10 overflow-hidden bg-white pointer-events-none">
        <!-- Biru Laut Halus -->
        <div class="absolute top-0 left-0 w-96 h-96 bg-blue-100 rounded-full blur-[80px] opacity-60 mix-blend-multiply animate-blob"></div>
        <!-- Cyan Halus -->
        <div class="absolute top-0 right-20 w-96 h-96 bg-cyan-100 rounded-full blur-[80px] opacity-60 mix-blend-multiply animate-blob" style="animation-delay: 2s"></div>
        <!-- Emas Pudar Halus -->
        <div class="absolute bottom-0 left-20 w-72 h-72 bg-yellow-50 rounded-full blur-[80px] opacity-60 mix-blend-multiply animate-blob" style="animation-delay: 4s"></div>
    </div>

    {{ $slot }}

</body>
</html>
