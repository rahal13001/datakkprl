<x-layouts.app title="Pedoman Perolehan Data Proposal KKPRL Skala Kecil">
    <main class="mx-auto min-h-screen max-w-6xl px-4 py-12 sm:px-6">
        <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 font-semibold text-sky-700">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Kembali ke Beranda
        </a>
        <header class="my-8">
            <h1 class="text-3xl font-bold text-slate-950 sm:text-4xl">Pedoman Perolehan Data Proposal KKPRL Skala Kecil</h1>
        </header>
        <div class="space-y-8">
            @forelse ($guides as $guide)
                <section id="pedoman-{{ $guide->id }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="judul-pedoman-{{ $guide->id }}">
                    <div class="p-6">
                        <h2 id="judul-pedoman-{{ $guide->id }}" class="text-2xl font-bold text-slate-950">{{ $guide->title }}</h2>
                        @if (filled($guide->description))
                            <p class="mt-3 whitespace-pre-line leading-relaxed text-slate-600">{{ $guide->description }}</p>
                        @endif
                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('pedoman-kkprl.pdf', ['guide' => $guide, 'view_token' => (string) \Illuminate\Support\Str::uuid()]) }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-sky-700 px-4 py-3 font-semibold text-sky-700">Buka PDF <span class="sr-only">{{ $guide->title }} (tab baru)</span></a>
                            <a href="{{ route('pedoman-kkprl.download', $guide) }}" class="rounded-xl bg-sky-700 px-4 py-3 font-semibold text-white">Unduh PDF <span class="sr-only">{{ $guide->title }}</span></a>
                        </div>
                        <dl class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-600" aria-label="Statistik dokumen">
                            <div class="flex items-center gap-2">
                                <dt class="inline-flex items-center gap-2"><i class="fa-solid fa-eye" aria-hidden="true"></i> Telah dilihat</dt>
                                <dd class="font-semibold text-slate-950">{{ number_format((int) $guide->view_count, 0, ',', '.') }} kali</dd>
                            </div>
                            <div class="flex items-center gap-2">
                                <dt class="inline-flex items-center gap-2"><i class="fa-solid fa-download" aria-hidden="true"></i> Telah diunduh</dt>
                                <dd class="font-semibold text-slate-950">{{ number_format((int) $guide->download_count, 0, ',', '.') }} kali</dd>
                            </div>
                        </dl>
                    </div>
                    <iframe src="{{ route('pedoman-kkprl.pdf', ['guide' => $guide, 'view_token' => (string) \Illuminate\Support\Str::uuid()]) }}" title="{{ $guide->title }}" loading="lazy" class="h-[72vh] min-h-[400px] w-full border-0 bg-slate-100"></iframe>
                </section>
            @empty
                <p class="rounded-2xl border border-slate-200 bg-white p-6">Pedoman sedang diperbarui. Silakan kembali lagi nanti.</p>
            @endforelse
        </div>
    </main>
</x-layouts.app>
