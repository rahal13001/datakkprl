<div class="min-h-screen belajar-page">
    <style>
        .belajar-hero {
            padding: 8rem 0 3rem;
        }

        .belajar-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 3rem;
            align-items: center;
        }

        .belajar-hero-copy {
            max-width: 760px;
        }

        .belajar-title {
            font-size: clamp(2.5rem, 4vw, 4rem);
            line-height: 1.05;
            letter-spacing: 0;
        }

        .belajar-description {
            max-width: 820px;
            font-size: 1.125rem;
            line-height: 1.75;
        }

        .belajar-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            min-height: 132px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.82);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .belajar-stat {
            display: flex;
            min-width: 0;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.25rem 1rem;
            text-align: center;
        }

        .belajar-stat + .belajar-stat {
            border-left: 1px solid #e2e8f0;
        }

        .belajar-stat-value {
            font-size: 2rem;
            line-height: 1;
            font-weight: 800;
            color: #334155;
        }

        .belajar-stat-label {
            margin-top: 0.75rem;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .belajar-filter-card {
            display: grid;
            grid-template-columns: minmax(280px, 1fr) 240px 170px 132px;
            gap: 1rem;
            align-items: end;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.86);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        }

        .belajar-field {
            display: grid;
            gap: 0.55rem;
            min-width: 0;
        }

        .belajar-field-label {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .belajar-control {
            width: 100%;
            min-height: 3.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            background: white;
            color: #334155;
            font-size: 0.95rem;
            font-weight: 650;
            outline: none;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .belajar-control:focus {
            border-color: #0057ff;
            box-shadow: 0 0 0 4px rgba(0, 87, 255, 0.12);
        }

        .belajar-search-wrap {
            position: relative;
        }

        .belajar-search-wrap i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .belajar-search {
            padding: 0 1.25rem 0 2.75rem;
        }

        .belajar-select {
            padding: 0 1.25rem;
        }

        .belajar-reset {
            display: inline-flex;
            min-height: 3.25rem;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            background: white;
            color: #334155;
            font-size: 0.95rem;
            font-weight: 800;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
            transition: border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .belajar-reset:hover {
            border-color: #cbd5e1;
            color: #0f172a;
            transform: translateY(-1px);
        }

        .belajar-content-section {
            margin-top: 3.5rem;
        }

        .belajar-section-header {
            margin-bottom: 1.5rem;
        }

        @media (max-width: 1023px) {
            .belajar-hero {
                padding: 7.25rem 0 2rem;
            }

            .belajar-hero-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .belajar-filter-card {
                grid-template-columns: 1fr 1fr;
            }

            .belajar-filter-search,
            .belajar-filter-reset {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 640px) {
            .belajar-hero {
                padding-top: 6.5rem;
            }

            .belajar-description {
                font-size: 1rem;
            }

            .belajar-stats {
                min-height: 92px;
            }

            .belajar-stat {
                padding: 1rem 0.75rem;
            }

            .belajar-stat-value {
                font-size: 1.65rem;
            }

            .belajar-stat-label {
                font-size: 0.62rem;
            }

            .belajar-filter-card {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .belajar-filter-search,
            .belajar-filter-reset {
                grid-column: auto;
            }
        }
    </style>
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
                <a href="{{ route('belajar-kkprl') }}" class="text-sm font-semibold text-brand-black transition-colors">Belajar KKPRL</a>
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
            <a href="{{ route('belajar-kkprl') }}" class="block text-sm font-semibold text-slate-900">Belajar KKPRL</a>
            <a href="{{ route('check-status') }}" class="block text-sm font-medium text-slate-600">Cek Status</a>
            <a href="{{ route('landing') }}#booking" class="block w-full text-center px-6 py-3 bg-brand-black text-white text-sm font-medium rounded-xl">Reservasi</a>
        </div>
    </nav>

    <main>
        <section class="belajar-hero">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="belajar-hero-grid">
                    <div class="belajar-hero-copy">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-slate-200 bg-white/80 backdrop-blur-sm mb-6">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-blue"></span>
                            <span class="text-xs font-mono font-semibold text-slate-500 uppercase tracking-widest">Referensi Publik</span>
                        </div>
                        <h1 class="belajar-title font-bold text-slate-950 mb-5">Belajar KKPRL</h1>
                        <p class="belajar-description text-slate-500">
                            Kumpulan referensi&nbsp;pembelajaran publik tentang KKPRL, dikelompokkan berdasarkan topik agar masyarakat dapat menemukan PDF dan video rujukan dengan cepat.
                        </p>
                    </div>

                    <div class="belajar-stats">
                        <div class="belajar-stat">
                            <p class="belajar-stat-value">{{ $categories->count() }}</p>
                            <p class="belajar-stat-label">Kategori</p>
                        </div>
                        <div class="belajar-stat">
                            <p class="belajar-stat-value">{{ $groups->count() }}</p>
                            <p class="belajar-stat-label">Grup</p>
                        </div>
                        <div class="belajar-stat">
                            <p class="belajar-stat-value">{{ $materials->count() }}</p>
                            <p class="belajar-stat-label">Materi</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="pb-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="belajar-filter-card">
                    <label class="belajar-field belajar-filter-search">
                        <span class="belajar-field-label">Cari Referensi</span>
                        <div class="belajar-search-wrap">
                            <i class="fa-solid fa-magnifying-glass"></i>
                                <input
                                    type="search"
                                    wire:model.live.debounce.400ms="search"
                                    placeholder="Cari kategori, grup, atau materi..."
                                    class="belajar-control belajar-search"
                                >
                        </div>
                    </label>

                    <label class="belajar-field">
                        <span class="belajar-field-label">Kategori</span>
                        <select wire:model.live="selectedCategory" class="belajar-control belajar-select">
                            <option value="">Semua kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="belajar-field">
                        <span class="belajar-field-label">Jenis</span>
                        <select wire:model.live="selectedType" class="belajar-control belajar-select">
                            <option value="">Semua</option>
                            <option value="pdf">PDF</option>
                            <option value="video">Video</option>
                        </select>
                    </label>

                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="belajar-reset belajar-filter-reset"
                    >
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                        <span>Reset</span>
                    </button>
                </div>

                @if($featuredGroups->isNotEmpty())
                    <section class="belajar-content-section">
                        <div class="belajar-section-header flex items-end justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-widest text-slate-400">Unggulan</p>
                                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Grup Pembelajaran Pilihan</h2>
                            </div>
                        </div>

                        <div class="grid gap-6 lg:grid-cols-3">
                            @foreach($featuredGroups as $group)
                                <article class="group overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                                    <a href="{{ route('belajar-kkprl.group', $group) }}" class="block">
                                        @if($group->thumbnail_path)
                                            <img src="{{ Storage::disk('public')->url($group->thumbnail_path) }}" alt="{{ $group->title }}" class="aspect-[16/9] w-full object-cover">
                                        @else
                                            <div class="flex aspect-[16/9] items-center justify-center bg-slate-50 text-slate-300">
                                                <i class="fa-solid fa-book-open text-4xl"></i>
                                            </div>
                                        @endif
                                    </a>
                                    <div class="p-6">
                                        <p class="text-xs font-bold uppercase tracking-widest text-brand-blue">{{ $group->category->name }}</p>
                                        <h3 class="mt-3 text-xl font-bold text-slate-950 group-hover:text-brand-blue">{{ $group->title }}</h3>
                                        <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-slate-500">{{ $group->description ?: 'Referensi pembelajaran KKPRL yang disusun untuk publik.' }}</p>
                                        <div class="mt-5 flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $group->published_materials_count }} materi</span>
                                            @foreach($group->materials->pluck('type')->unique()->values() as $type)
                                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $type === 'pdf' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600' }}">{{ strtoupper($type) }}</span>
                                            @endforeach
                                        </div>
                                        <a href="{{ route('belajar-kkprl.group', $group) }}" class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-brand-blue">
                                            <span>Buka Grup</span>
                                            <i class="fa-solid fa-arrow-right-long text-xs"></i>
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="belajar-content-section">
                    <div class="belajar-section-header flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-slate-400">Materi</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Materi Terbaru dan Unggulan</h2>
                        </div>
                        <p class="text-sm text-slate-500">{{ $materials->count() }}&nbsp;materi tampil</p>
                    </div>

                    @if($materials->isNotEmpty())
                        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach($materials as $material)
                                <article class="flex h-full flex-col rounded-lg border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $material->isPdf() ? 'bg-red-50 text-red-500' : 'bg-blue-50 text-blue-500' }}">
                                            <i class="fa-solid {{ $material->isPdf() ? 'fa-file-pdf' : 'fa-circle-play' }} text-lg"></i>
                                        </div>
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $material->isPdf() ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600' }}">{{ $material->isPdf() ? 'PDF' : 'Video' }}</span>
                                    </div>
                                    <h3 class="mt-5 text-lg font-bold text-slate-950">{{ $material->title }}</h3>
                                    <p class="mt-2 text-sm font-semibold text-slate-500">{{ $material->group->title }}</p>
                                    <p class="mt-3 line-clamp-3 flex-1 text-sm leading-relaxed text-slate-500">{{ $material->description ?: 'Materi referensi Belajar KKPRL.' }}</p>
                                    <div class="mt-6 flex items-center justify-between gap-4 border-t border-slate-100 pt-4">
                                        <span class="text-xs font-semibold text-slate-400">{{ number_format($material->view_count) }} dilihat</span>
                                        <a href="{{ route('belajar-kkprl.group', $material->group) }}" class="text-sm font-bold text-brand-blue">Lihat Grup</a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-slate-300 bg-white/80 px-6 py-12 text-center">
                            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <i class="fa-solid fa-book-open text-xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-slate-950">Belum ada materi yang cocok</h3>
                            <p class="mx-auto mt-3 max-w-xl text-slate-500">Coba ubah kata kunci atau filter untuk menampilkan referensi pembelajaran lain.</p>
                        </div>
                    @endif
                </section>

                <section class="belajar-content-section">
                    <div class="belajar-section-header flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-slate-400">Daftar Grup</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Semua Grup Pembelajaran</h2>
                        </div>
                        <p class="text-sm text-slate-500">{{ $groups->count() }}&nbsp;grup tampil</p>
                    </div>

                    @if($groups->isNotEmpty())
                        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach($groups as $group)
                                <article class="flex h-full flex-col rounded-lg border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                                    <div class="flex items-start gap-4">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                            <i class="fa-solid fa-layer-group"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-widest text-brand-blue">{{ $group->category->name }}</p>
                                            <h3 class="mt-2 text-xl font-bold text-slate-950">{{ $group->title }}</h3>
                                        </div>
                                    </div>
                                    <p class="mt-4 line-clamp-3 flex-1 text-sm leading-relaxed text-slate-500">{{ $group->description ?: 'Referensi pembelajaran KKPRL yang disusun untuk publik.' }}</p>
                                    <div class="mt-5 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $group->published_materials_count }} materi</span>
                                        @foreach($group->materials->pluck('type')->unique()->values() as $type)
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $type === 'pdf' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600' }}">{{ strtoupper($type) }}</span>
                                        @endforeach
                                    </div>
                                    <a href="{{ route('belajar-kkprl.group', $group) }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-brand-black px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                        <span>Buka Grup</span>
                                        <i class="fa-solid fa-arrow-right text-xs"></i>
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-slate-300 bg-white/80 px-6 py-16 text-center">
                            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <i class="fa-solid fa-folder-open text-xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-slate-950">Belum ada grup pembelajaran</h3>
                            <p class="mx-auto mt-3 max-w-xl text-slate-500">
                                Grup yang sudah dipublikasikan dari kategori aktif akan tampil di halaman ini.
                            </p>
                        </div>
                    @endif
                </section>
            </div>
        </section>
    </main>
</div>
