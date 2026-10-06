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
                            <a href="{{ route('pedoman-kkprl.pdf', $guide) }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-sky-700 px-4 py-3 font-semibold text-sky-700">Buka PDF <span class="sr-only">{{ $guide->title }} (tab baru)</span></a>
                            <a href="{{ route('pedoman-kkprl.download', $guide) }}" class="rounded-xl bg-sky-700 px-4 py-3 font-semibold text-white">Unduh PDF <span class="sr-only">{{ $guide->title }}</span></a>
                        </div>
                    </div>
                    <iframe src="{{ route('pedoman-kkprl.pdf', $guide) }}" title="{{ $guide->title }}" loading="lazy" class="h-[72vh] min-h-[400px] w-full border-0 bg-slate-100"></iframe>
                </section>
            @empty
                <p class="rounded-2xl border border-slate-200 bg-white p-6">Pedoman sedang diperbarui. Silakan kembali lagi nanti.</p>
            @endforelse
        </div>
    </main>
</x-layouts.app>
