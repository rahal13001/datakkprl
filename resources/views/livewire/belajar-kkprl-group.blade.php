<div class="min-h-screen belajar-group-page">
    <style>
        .belajar-group-hero {
            padding: 7.5rem 0 3rem;
        }

        .belajar-group-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 2.25rem;
            align-items: start;
        }

        .belajar-group-copy {
            max-width: 780px;
        }

        .belajar-group-title {
            max-width: 820px;
            font-size: clamp(2rem, 3vw, 3.25rem);
            line-height: 1.12;
            letter-spacing: 0;
        }

        .belajar-group-description {
            max-width: 720px;
            font-size: 1.05rem;
            line-height: 1.75;
        }

        .belajar-group-thumb {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.82);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .belajar-group-thumb img,
        .belajar-group-thumb-placeholder {
            width: 100%;
            height: 190px;
            object-fit: contain;
        }

        .belajar-group-thumb-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
        }

        .belajar-material-header {
            display: flex;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, 0.58);
        }

        .belajar-material-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.5rem;
        }

        .belajar-material-card {
            display: flex;
            min-height: 100%;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: white;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
        }

        .belajar-material-media {
            flex: 0 0 172px;
            width: 172px;
            min-height: 100%;
            background: #f8fafc;
        }

        .belajar-material-media img,
        .belajar-material-placeholder {
            width: 100%;
            height: 100%;
            min-height: 180px;
            object-fit: contain;
            padding: 0.6rem;
        }

        .belajar-material-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .belajar-material-body {
            display: flex;
            min-width: 0;
            flex: 1;
            flex-direction: column;
            padding: 1.25rem;
        }

        .belajar-material-type {
            align-self: flex-start;
        }

        .belajar-material-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: auto;
            padding-top: 1.25rem;
        }

        .belajar-material-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 2.55rem;
            border-radius: 9999px;
            padding: 0 1rem;
            font-size: 0.85rem;
            font-weight: 800;
            transition: border-color 0.2s ease, color 0.2s ease, background-color 0.2s ease;
        }

        .belajar-material-button-primary {
            background: #0f172a;
            color: white;
        }

        .belajar-material-button-primary:hover {
            background: #1e293b;
        }

        .belajar-material-button-secondary {
            border: 1px solid #e2e8f0;
            background: white;
            color: #475569;
        }

        .belajar-material-button-secondary:hover {
            border-color: #cbd5e1;
            color: #0f172a;
        }

        @media (max-width: 1023px) {
            .belajar-group-shell {
                grid-template-columns: 1fr;
            }

            .belajar-group-thumb {
                max-width: 420px;
            }

            .belajar-material-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .belajar-group-hero {
                padding-top: 6.5rem;
            }

            .belajar-material-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .belajar-material-grid {
                grid-template-columns: 1fr;
            }

            .belajar-material-card {
                flex-direction: column;
            }

            .belajar-material-media {
                width: 100%;
                min-height: 0;
            }

            .belajar-material-media img,
            .belajar-material-placeholder {
                height: 180px;
                min-height: 180px;
            }
        }
    </style>
    <nav x-data="{ mobileOpen: false }"
         class="fixed w-full z-50 transition-all duration-300 top-0 glass py-3">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 flex justify-between items-center">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
                <img src="{{ asset('img/logokkp.png') }}" alt="Logo KKP" class="h-11 w-11 object-contain transition-transform group-hover:scale-105">
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
        <section class="belajar-group-hero">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <a href="{{ route('belajar-kkprl') }}" class="mb-8 inline-flex items-center gap-2 text-sm font-bold text-brand-blue">
                    <i class="fa-solid fa-arrow-left-long text-xs"></i>
                    <span>Kembali ke Belajar KKPRL</span>
                </a>

                <div class="belajar-group-shell">
                    <div class="belajar-group-copy">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-slate-200 bg-white/80 backdrop-blur-sm mb-6">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-blue"></span>
                            <span class="text-xs font-mono font-semibold text-slate-500 uppercase tracking-widest">{{ $group->category->name }}</span>
                        </div>
                        <h1 class="belajar-group-title font-bold text-slate-950 mb-5">{{ $group->title }}</h1>
                        <p class="belajar-group-description text-slate-500">
                            {{ $group->description ?: 'Kumpulan materi referensi Belajar KKPRL yang dapat diakses publik tanpa login.' }}
                        </p>
                    </div>

                    <div class="belajar-group-thumb">
                        @if($group->thumbnail_path)
                            <img src="{{ Storage::disk('public')->url($group->thumbnail_path) }}" alt="{{ $group->title }}">
                        @else
                            <div class="belajar-group-thumb-placeholder">
                                <i class="fa-solid fa-book-open text-5xl"></i>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="pb-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="belajar-material-header mb-8">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-slate-400">Materi Grup</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Daftar Materi Pembelajaran</h2>
                    </div>
                    <span class="inline-flex w-fit items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600">
                        <i class="fa-solid fa-layer-group text-xs"></i>
                        {{ $group->materials->count() }} materi dipublikasikan
                    </span>
                </div>

                @if($group->materials->isNotEmpty())
                    <div class="belajar-material-grid">
                        @foreach($group->materials as $material)
                            <article class="belajar-material-card">
                                <div class="belajar-material-media">
                                    @if($material->isVideo() && $activeVideoSlug === $material->slug && $material->videoEmbedUrl())
                                        <iframe
                                            src="{{ $material->videoEmbedUrl() }}"
                                            title="{{ $material->title }}"
                                            class="h-full w-full"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            allowfullscreen
                                        ></iframe>
                                    @elseif($material->thumbnail_path)
                                        <img src="{{ Storage::disk('public')->url($material->thumbnail_path) }}" alt="{{ $material->title }}">
                                    @else
                                        <div class="belajar-material-placeholder {{ $material->isPdf() ? 'bg-red-50 text-red-300' : 'bg-blue-50 text-blue-300' }}">
                                            <i class="fa-solid {{ $material->isPdf() ? 'fa-file-pdf' : 'fa-circle-play' }} text-4xl"></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="belajar-material-body">
                                    <span class="belajar-material-type rounded-full px-3 py-1 text-xs font-bold {{ $material->isPdf() ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600' }}">{{ $material->isPdf() ? 'PDF' : 'Video' }}</span>
                                    <h3 class="mt-4 text-lg font-bold text-slate-950">{{ $material->title }}</h3>
                                    <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-slate-500">{{ $material->description ?: 'Materi referensi Belajar KKPRL.' }}</p>
                                    <div class="mt-5 flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-400">
                                        <span>{{ number_format($material->view_count) }} dilihat</span>
                                        @if($material->isPdf())
                                            <span>{{ number_format($material->download_count) }} diunduh</span>
                                        @endif
                                    </div>

                                    @if($material->isPdf())
                                        <div class="belajar-material-actions">
                                            <a
                                                href="{{ route('belajar-kkprl.material.pdf', $material) }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="belajar-material-button belajar-material-button-primary"
                                            >
                                                <i class="fa-solid fa-file-pdf text-xs"></i>
                                                <span>Buka PDF</span>
                                            </a>
                                            <a
                                                href="{{ route('belajar-kkprl.material.download', $material) }}"
                                                class="belajar-material-button belajar-material-button-secondary"
                                            >
                                                <i class="fa-solid fa-download text-xs"></i>
                                                <span>Unduh PDF</span>
                                            </a>
                                        </div>
                                    @elseif($material->safeVideoUrl())
                                        <div class="belajar-material-actions">
                                            @if($material->videoEmbedUrl())
                                                <button
                                                    type="button"
                                                    wire:click="showVideo('{{ $material->slug }}')"
                                                    class="belajar-material-button belajar-material-button-primary"
                                                >
                                                    <i class="fa-solid fa-circle-play text-xs"></i>
                                                    <span>{{ $activeVideoSlug === $material->slug ? 'Putar Ulang' : 'Tonton Video' }}</span>
                                                </button>
                                            @endif
                                            <button
                                                type="button"
                                                wire:click="openVideo('{{ $material->slug }}')"
                                                class="belajar-material-button belajar-material-button-secondary"
                                            >
                                                <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                                <span>Buka Sumber</span>
                                            </button>
                                        </div>
                                    @else
                                        <div class="mt-6 rounded-lg bg-slate-50 px-4 py-3 text-sm font-medium text-slate-500">
                                            URL video belum tersedia.
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white/80 px-6 py-16 text-center">
                        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <i class="fa-solid fa-book-open text-xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-950">Belum ada materi yang dipublikasikan</h3>
                        <p class="mx-auto mt-3 max-w-xl text-slate-500">
                            Materi PDF dan video akan tampil di sini setelah admin mempublikasikannya.
                        </p>
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>
