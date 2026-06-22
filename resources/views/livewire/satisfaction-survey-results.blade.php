<div class="min-h-screen">
    <nav x-data="{ mobileOpen: false }"
         class="fixed w-full z-50 transition-all duration-300 top-0 glass py-3">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 flex justify-between items-center">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
                <img
                    src="{{ asset('img/logokkp.png') }}"
                    alt="Logo KKP"
                    class="h-11 w-11 object-contain transition-transform group-hover:scale-105"
                >
                <div class="leading-tight">
                    <h1 class="font-mono font-bold text-lg text-slate-900 tracking-tight">LPRL SORONG</h1>
                    <p class="text-[10px] text-slate-500 font-medium tracking-widest uppercase">Official Platform V2</p>
                </div>
            </a>

            <div class="hidden md:flex items-center gap-5 lg:gap-6">
                <a href="{{ route('landing') }}#home" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Home</a>
                <a href="{{ route('landing') }}#services" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Layanan</a>
                <a href="{{ route('landing') }}#knowledge" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Regulasi</a>
                <a href="{{ route('belajar-kkprl') }}" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Belajar KKPRL</a>
                <div x-data="{ resultsOpen: false }" class="relative">
                    <button
                        type="button"
                        @click="resultsOpen = !resultsOpen"
                        @click.outside="resultsOpen = false"
                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-black transition-colors"
                    >
                        <span>Hasil</span>
                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="{ 'rotate-180': resultsOpen }"></i>
                    </button>

                    <div
                        x-show="resultsOpen"
                        x-transition
                        class="absolute left-0 top-full mt-4 w-56 rounded-2xl border border-slate-100 bg-white p-2 shadow-xl shadow-slate-200/70"
                    >
                        <a href="{{ route('satisfaction-survey-results') }}" class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 text-sm font-semibold text-brand-black">
                            <i class="fa-solid fa-chart-simple w-4 text-slate-500"></i>
                            <span>Hasil SKM</span>
                        </a>
                        <a href="{{ route('service-performance-results') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-brand-black">
                            <i class="fa-solid fa-ranking-star w-4 text-slate-400"></i>
                            <span>Hasil Kinerja</span>
                        </a>
                    </div>
                </div>
                <a href="{{ route('check-status') }}" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Cek Status</a>

                <a href="{{ route('landing') }}#booking" class="px-6 py-2.5 bg-brand-black text-white text-sm font-medium rounded-full hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 hover:shadow-2xl hover:-translate-y-0.5 flex items-center gap-2">
                    <span>Reservasi</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <button @click="mobileOpen = !mobileOpen" class="md:hidden text-slate-900">
                <i class="fa-solid fa-bars-staggered text-xl"></i>
            </button>
        </div>

        <div x-show="mobileOpen" x-transition class="md:hidden glass border-t border-slate-100 p-6 space-y-4">
            <a href="{{ route('landing') }}#home" class="block text-sm font-medium text-slate-600">Home</a>
            <a href="{{ route('landing') }}#services" class="block text-sm font-medium text-slate-600">Layanan</a>
            <a href="{{ route('landing') }}#knowledge" class="block text-sm font-medium text-slate-600">Regulasi</a>
            <a href="{{ route('belajar-kkprl') }}" class="block text-sm font-medium text-slate-600">Belajar KKPRL</a>
            <div class="space-y-3">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Hasil</p>
                <a href="{{ route('satisfaction-survey-results') }}" class="block pl-3 text-sm font-semibold text-slate-900">Hasil SKM</a>
                <a href="{{ route('service-performance-results') }}" class="block pl-3 text-sm font-medium text-slate-600">Hasil Kinerja</a>
            </div>
            <a href="{{ route('check-status') }}" class="block text-sm font-medium text-slate-600">Cek Status</a>
            <a href="{{ route('landing') }}#booking" class="block w-full text-center px-6 py-3 bg-brand-black text-white text-sm font-medium rounded-xl">Reservasi</a>
        </div>
    </nav>

    <main>
        <section class="pt-32 pb-12 lg:pt-36 lg:pb-16">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-slate-200 bg-white/80 backdrop-blur-sm mb-6">
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-blue"></span>
                        <span class="text-xs font-mono font-semibold text-slate-500 uppercase tracking-widest">Survei Kepuasan Masyarakat</span>
                    </div>
                    <h1 class="text-4xl lg:text-6xl font-bold text-slate-950 tracking-tight mb-5">Hasil Survei Kepuasan Masyarakat</h1>
                    <p class="text-lg text-slate-500 leading-relaxed">
                        Publikasi hasil penilaian kepuasan masyarakat terhadap layanan LPRL Sorong, ditampilkan per triwulan sesuai data yang tersedia.
                    </p>
                </div>
            </div>
        </section>

        <section class="pb-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="flex flex-col gap-4 border-y border-slate-200 bg-white/70 py-6 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-slate-400">Filter Data</p>
                        <p class="mt-1 text-slate-600">Pilih tahun publikasi berdasarkan data hasil SKM yang sudah tersedia.</p>
                    </div>

                    <label class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-slate-700">Tahun</span>
                        <select wire:model.live="selectedYear" class="min-w-36 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-blue-100">
                            @forelse($availableYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @empty
                                <option value="">Belum ada data</option>
                            @endforelse
                        </select>
                    </label>
                </div>

                @if($results->isNotEmpty())
                    <div class="mt-10 grid gap-8 lg:grid-cols-2">
                        @foreach($results as $result)
                            <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                                <a href="{{ $result->image_url }}" target="_blank" rel="noopener noreferrer" class="block bg-slate-50">
                                    <img
                                        src="{{ $result->image_url }}"
                                        alt="{{ $result->display_title }}"
                                        class="aspect-[4/3] w-full object-contain"
                                        loading="lazy"
                                    >
                                </a>

                                <div class="p-6">
                                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-400">
                                        <span>{{ $result->quarter_label }}</span>
                                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                        <span>{{ $result->year }}</span>
                                    </div>
                                    <h2 class="mt-3 text-2xl font-bold text-slate-950">{{ $result->display_title }}</h2>

                                    @if($result->description)
                                        <p class="mt-3 text-sm leading-relaxed text-slate-500">{{ $result->description }}</p>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="mt-10 rounded-lg border border-dashed border-slate-300 bg-white/80 px-6 py-16 text-center">
                        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <i class="fa-solid fa-chart-simple text-xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-950">Belum ada hasil SKM yang dipublikasikan</h2>
                        <p class="mx-auto mt-3 max-w-xl text-slate-500">
                            Data hasil survei akan tampil di halaman ini setelah admin mengunggah dan mempublikasikan gambar hasil SKM.
                        </p>
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>
