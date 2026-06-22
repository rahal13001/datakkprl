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

            <div class="hidden md:flex items-center gap-6 lg:gap-8">
                <a href="{{ route('landing') }}#home" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Home</a>
                <a href="{{ route('landing') }}#services" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Layanan</a>
                <a href="{{ route('landing') }}#knowledge" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Regulasi</a>
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
                        <a href="{{ route('satisfaction-survey-results') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-brand-black">
                            <i class="fa-solid fa-chart-simple w-4 text-slate-400"></i>
                            <span>Hasil SKM</span>
                        </a>
                        <a href="{{ route('service-performance-results') }}" class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 text-sm font-semibold text-brand-black">
                            <i class="fa-solid fa-ranking-star w-4 text-slate-500"></i>
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
            <div class="space-y-3">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Hasil</p>
                <a href="{{ route('satisfaction-survey-results') }}" class="block pl-3 text-sm font-medium text-slate-600">Hasil SKM</a>
                <a href="{{ route('service-performance-results') }}" class="block pl-3 text-sm font-semibold text-slate-900">Hasil Kinerja</a>
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
                        <span class="text-xs font-mono font-semibold text-slate-500 uppercase tracking-widest">Informasi Kinerja Layanan</span>
                    </div>
                    <h1 class="text-4xl lg:text-6xl font-bold text-slate-950 tracking-tight mb-5">Hasil Kinerja Layanan</h1>
                    <p class="text-lg text-slate-500 leading-relaxed">
                        Publikasi informasi kinerja layanan LPRL Sorong per triwulan, dilengkapi rekap kinerja petugas dengan nama yang ditampilkan sebagai inisial.
                    </p>
                </div>
            </div>
        </section>

        <section class="pb-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="flex flex-col gap-5 border-y border-slate-200 bg-white/70 py-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-slate-400">Filter Capaian Organisasi</p>
                        <p class="mt-1 text-slate-600">Pilih tahun dan triwulan untuk menampilkan informasi capaian kinerja organisasi yang dipublikasikan.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:flex lg:items-center">
                        <label class="grid gap-2 sm:block">
                            <span class="text-sm font-semibold text-slate-700">Tahun</span>
                            <select wire:model.live="selectedYear" class="w-full min-w-36 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-blue-100 sm:mt-2 lg:mt-0">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="grid gap-2 sm:block">
                            <span class="text-sm font-semibold text-slate-700">Triwulan</span>
                            <select wire:model.live="selectedQuarter" class="w-full min-w-44 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-blue-100 sm:mt-2 lg:mt-0">
                                @foreach($availableQuarters as $quarter)
                                    <option value="{{ $quarter }}">{{ \App\Models\ServicePerformanceResult::QUARTERS[$quarter] ?? 'Triwulan ' . $quarter }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>

                <div class="mt-10 space-y-8">
                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(280px,420px)] lg:items-start">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-widest text-slate-400">{{ $periodLabel }}</p>
                                <h2 class="mt-3 text-2xl font-bold text-slate-950 md:text-3xl">{{ $result?->display_title ?? 'Informasi kinerja belum dipublikasikan' }}</h2>

                                @if($result?->description)
                                    <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-500 md:text-base">{{ $result->description }}</p>
                                @else
                                    <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-500 md:text-base">Data capaian organisasi akan tampil setelah admin mempublikasikannya. Tabel kinerja petugas memiliki filter terpisah di bawah.</p>
                                @endif
                            </div>

                            @if($result?->image_url)
                                <a href="{{ $result->image_url }}" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-lg bg-slate-50">
                                    <img
                                        src="{{ $result->image_url }}"
                                        alt="{{ $result->display_title }}"
                                        class="aspect-[4/3] w-full object-contain"
                                        loading="lazy"
                                    >
                                </a>
                            @endif
                        </div>
                    </section>

                    <section>
                        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-200 px-6 py-5">
                                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                                    <div>
                                        <h2 class="text-2xl font-bold text-slate-950">Tabel Kinerja Petugas</h2>
                                        <p class="mt-1 text-sm text-slate-500">Nama petugas ditampilkan dalam bentuk inisial untuk publikasi terbuka.</p>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2 xl:flex xl:items-end">
                                        <label class="grid gap-2">
                                            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Tahun Tabel</span>
                                            <select wire:model.live="tableYear" class="w-full min-w-36 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-blue-100">
                                                @foreach($availableTableYears as $year)
                                                    <option value="{{ $year }}">{{ $year }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <label class="grid gap-2">
                                            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Triwulan Tabel</span>
                                            <select wire:model.live="tableQuarter" class="w-full min-w-48 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-blue-100">
                                                <option value="">Semua Triwulan</option>
                                                @foreach($availableTableQuarters as $quarter)
                                                    <option value="{{ $quarter }}">{{ \App\Models\ServicePerformanceResult::QUARTERS[$quarter] ?? 'Triwulan ' . $quarter }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>
                                </div>

                                <div class="mt-5 flex flex-wrap items-center gap-3">
                                    <span class="rounded-full bg-slate-100 px-4 py-1.5 text-sm font-semibold text-slate-600">{{ $tablePeriodLabel }}</span>
                                    <span class="text-sm text-slate-400">{{ $staffRows->count() }} petugas tampil</span>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[920px] table-fixed divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="w-[18%] px-6 py-4 text-left font-semibold text-slate-700">Petugas</th>
                                            <th class="w-[13%] px-5 py-4 text-right font-semibold text-slate-700">Penilaian</th>
                                            <th class="w-[13%] px-5 py-4 text-right font-semibold text-slate-700">Aktivitas</th>
                                            <th class="w-[14%] px-5 py-4 text-right font-semibold text-slate-700">Total Skor</th>
                                            <th class="w-[14%] px-5 py-4 text-right font-semibold text-slate-700">Rata-rata</th>
                                            <th class="w-[14%] px-5 py-4 text-right font-semibold text-slate-700">Bintang</th>
                                            <th class="w-[14%] px-6 py-4 text-right font-semibold text-slate-700">Tertinggi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @forelse($staffRows as $row)
                                            <tr class="transition-colors hover:bg-slate-50/80">
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex h-10 min-w-16 items-center justify-center rounded-full bg-slate-100 px-3 font-bold tracking-wide text-slate-900">
                                                        {{ $performanceService->initials($row->name) }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-4 text-right font-medium text-slate-700">{{ number_format((int) $row->rated_sessions) }}</td>
                                                <td class="px-5 py-4 text-right font-medium text-slate-700">{{ number_format((int) $row->service_activities_count) }}</td>
                                                <td class="px-5 py-4 text-right text-slate-700">{{ number_format((int) $row->total_score) }}</td>
                                                <td class="px-5 py-4 text-right font-semibold text-slate-900">{{ number_format((float) $row->average_score, 2) }}</td>
                                                <td class="px-5 py-4 text-right text-slate-700">{{ number_format((float) $row->average_stars, 2) }} / 5</td>
                                                <td class="px-6 py-4 text-right text-slate-700">{{ number_format((int) $row->highest_score) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                                    Belum ada data kinerja layanan pada periode ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </section>
    </main>
</div>
