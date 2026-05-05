<div class="relative">
    <style>
        /* Service Card Hover Effects */
        .service-card-stripe {
            background: linear-gradient(to right, var(--accent-from), var(--accent-to));
        }
        .service-card-icon {
            background-color: var(--accent-bg);
            color: var(--accent-text);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .service-card:hover .service-card-icon {
            transform: scale(1.15);
            box-shadow: 0 0 0 8px color-mix(in srgb, var(--accent-text) 12%, transparent);
        }
        .service-card-inner {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .service-card:hover .service-card-inner {
            transform: translateY(-6px);
            box-shadow: 0 25px 50px -12px color-mix(in srgb, var(--accent-from) 20%, transparent),
                        0 8px 20px rgba(0, 0, 0, 0.06);
            border-color: var(--accent-ring);
        }
        .service-card-cta {
            color: var(--accent-text);
        }
        .service-card-arrow {
            background-color: var(--accent-bg);
            color: var(--accent-text);
        }
        .service-card-glow {
            background: radial-gradient(circle, color-mix(in srgb, var(--accent-from) 10%, transparent), transparent 70%);
        }
    </style>
    <!-- Navbar (Minimalist Sticky) -->
    <nav x-data="{ scrolled: false, mobileOpen: false }" 
         @scroll.window="scrolled = (window.pageYOffset > 20)"
         class="fixed w-full z-50 transition-all duration-300 top-0"
         :class="{ 'glass py-3': scrolled, 'bg-transparent py-6': !scrolled }">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 flex justify-between items-center">
            
            <!-- Logo Modern -->
            <div class="flex items-center gap-3 group cursor-pointer">
                <!-- Simbol Minimalis -->
                <div class="w-10 h-10 bg-brand-black text-white rounded-lg flex items-center justify-center shadow-lg group-hover:scale-105 transition-transform">
                    <i class="fa-solid fa-anchor text-sm"></i>
                </div>
                <div class="leading-tight">
                    <h1 class="font-mono font-bold text-lg text-slate-900 tracking-tight">LPRL SORONG</h1>
                    <p class="text-[10px] text-slate-500 font-medium tracking-widest uppercase">Official Platform V2</p>
                </div>
            </div>

            <!-- Menu (Clean Text) -->
            <div class="hidden md:flex items-center gap-8">
                <a href="#home" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Home</a>
                <a href="#services" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Layanan</a>
                <a href="#knowledge" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Regulasi</a>
                <a href="{{ route('check-status') }}" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Cek Status</a>
                
                <!-- Action Button -->
                <a href="#booking" class="px-6 py-2.5 bg-brand-black text-white text-sm font-medium rounded-full hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 hover:shadow-2xl hover:-translate-y-0.5 flex items-center gap-2">
                    <span>Reservasi</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <!-- Mobile Trigger -->
            <button @click="mobileOpen = !mobileOpen" class="md:hidden text-slate-900">
                <i class="fa-solid fa-bars-staggered text-xl"></i>
            </button>
        </div>
        
        <!-- Mobile Menu (Simple Dropdown) -->
        <div x-show="mobileOpen" x-transition class="md:hidden glass border-t border-slate-100 p-6 space-y-4">
            <a href="#home" class="block text-sm font-medium text-slate-600">Home</a>
            <a href="#services" class="block text-sm font-medium text-slate-600">Layanan</a>
            <a href="#knowledge" class="block text-sm font-medium text-slate-600">Regulasi</a>
            <a href="{{ route('check-status') }}" class="block text-sm font-medium text-slate-600">Cek Status</a>
            <a href="#booking" class="block w-full text-center px-6 py-3 bg-brand-black text-white text-sm font-medium rounded-xl">Reservasi</a>
        </div>
    </nav>

    <!-- Hero Section (Swiss Style Layout) -->
    <section id="home" class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12 items-end">
                
                <!-- Typography Main -->
                <div class="lg:col-span-6 relative z-10">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-slate-200 bg-white/50 backdrop-blur-sm mb-8">
                        <span class="w-2 h-2 rounded-full bg-brand-blue animate-pulse"></span>
                        <span class="text-xs font-mono font-medium text-slate-500 uppercase tracking-wide">System Online</span>
                    </div>

                    <h1 class="text-6xl lg:text-8xl font-bold text-slate-900 tracking-tight leading-[0.95] mb-8">
                        Tata Kelola <br>
                        <span class="text-gradient">Ruang Laut.</span>
                    </h1>
                    
                    <p class="text-xl text-slate-500 font-light max-w-xl leading-relaxed mb-10">
                        Transformasi digital layanan perizinan LPRL Sorong, Direktorat Jenderal Penataan Ruang Laut, Kementerian Kelautan & Perikanan. 
                        Transparan dan presisi.
                    </p>

                    <div class="flex flex-wrap gap-4">
                        <a href="#booking" class="px-8 py-4 bg-brand-blue text-white rounded-xl font-medium shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition-all flex items-center gap-3">
                            Buat Janji Temu
                        </a>
                        <a href="#knowledge" class="px-8 py-4 bg-white border border-slate-200 text-slate-700 rounded-xl font-medium hover:border-slate-300 transition-all flex items-center gap-3">
                            <i class="fa-solid fa-book-open text-slate-400"></i>
                            Arsip Regulasi
                        </a>
                    </div>
                </div>

                <!-- Abstract Visual (Right) -->
                <div class="lg:col-span-6 relative h-full min-h-[300px] flex items-end justify-end lg:justify-center">
                    <!-- Modern Card Stack -->
                    <!-- Anti Gratifikasi Image -->
                    <div class="relative w-full transition-transform hover:scale-105 duration-500 ease-out">
                         <img src="{{ asset('img/anti_gratifikasi.svg') }}" 
                              alt="Anti Gratifikasi" 
                              class="w-full h-auto drop-shadow-2xl animate-float">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="activity-chart" class="py-24">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="mb-12 max-w-3xl">
                <!-- <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-slate-200 bg-white/80 backdrop-blur-sm mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-cyan"></span>
                    <span class="text-xs font-mono font-semibold text-slate-500 uppercase tracking-widest">Data Publik</span>
                </div> -->
                <h2 class="text-4xl lg:text-5xl font-bold text-slate-900 tracking-tight mb-4">Grafik Distribusi Pemohon</h2>
                <p class="text-slate-500 text-lg">Grafik publik untuk melihat distribusi pemohon berdasarkan sifat kegiatan.</p>
            </div>

            <livewire:public-dashboard-charts />
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-28 relative overflow-hidden">
        <!-- Background Decoration -->
        <div class="absolute inset-0 bg-gradient-to-b from-white via-slate-50/80 to-white"></div>
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[800px] bg-brand-blue/[0.03] rounded-full blur-3xl"></div>
        
        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-slate-200 bg-white/80 backdrop-blur-sm mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-blue"></span>
                    <span class="text-xs font-mono font-semibold text-slate-500 uppercase tracking-widest">Layanan Kami</span>
                </div>
                <h2 class="text-4xl lg:text-5xl font-bold text-slate-900 tracking-tight mb-4">Layanan Utama</h2>
                <p class="text-slate-500 max-w-2xl mx-auto text-lg">Solusi lengkap untuk kebutuhan perizinan pemanfaatan ruang laut Anda</p>
            </div>

            <!-- Service Cards Grid -->
            @if($services->count() > 0)
            @php
                $gridCols = match(true) {
                    $services->count() === 1 => 'lg:grid-cols-1',
                    $services->count() === 2 => 'lg:grid-cols-2',
                    default => 'lg:grid-cols-3',
                };

                $accents = [
                    ['from' => '#2563eb', 'to' => '#06b6d4', 'bg' => '#eff6ff', 'text' => '#2563eb', 'ring' => '#bfdbfe'],
                    ['from' => '#7c3aed', 'to' => '#6366f1', 'bg' => '#f5f3ff', 'text' => '#7c3aed', 'ring' => '#ddd6fe'],
                    ['from' => '#059669', 'to' => '#14b8a6', 'bg' => '#ecfdf5', 'text' => '#059669', 'ring' => '#a7f3d0'],
                    ['from' => '#f59e0b', 'to' => '#f97316', 'bg' => '#fffbeb', 'text' => '#d97706', 'ring' => '#fde68a'],
                    ['from' => '#e11d48', 'to' => '#ec4899', 'bg' => '#fff1f2', 'text' => '#e11d48', 'ring' => '#fecdd3'],
                ];
            @endphp
            <div class="grid sm:grid-cols-2 {{ $gridCols }} gap-8">

                @foreach($services as $index => $service)
                @php $a = $accents[$index % count($accents)]; @endphp
                <div class="group relative service-card"
                     style="--accent-from: {{ $a['from'] }}; --accent-to: {{ $a['to'] }}; --accent-bg: {{ $a['bg'] }}; --accent-text: {{ $a['text'] }}; --accent-ring: {{ $a['ring'] }};">
                    <!-- Card -->
                    <div class="service-card-inner relative bg-white rounded-2xl border border-slate-100 overflow-hidden h-full flex flex-col">
                        
                        <!-- Gradient Top Accent -->
                        <div class="h-1.5 service-card-stripe"></div>
                        
                        <!-- Card Content -->
                        <div class="p-8 flex flex-col flex-1">
                            <!-- Icon & Number -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="service-card-icon w-14 h-14 rounded-2xl flex items-center justify-center text-xl">
                                    <i class="{{ $service->icon ?? 'fa-solid fa-concierge-bell' }}"></i>
                                </div>
                                <span class="text-5xl font-black text-slate-100 group-hover:text-slate-200 transition-colors font-mono select-none">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            </div>

                            <!-- Text -->
                            <h3 class="text-xl font-bold text-slate-900 mb-3 transition-colors">{{ $service->name }}</h3>
                            <p class="text-slate-500 text-sm leading-relaxed flex-1 mb-8">
                                {{ $service->description ?? 'Layanan resmi LPRL Sorong untuk kebutuhan penataan ruang laut.' }}
                            </p>

                            <!-- CTA -->
                            <a href="#booking" class="service-card-cta inline-flex items-center gap-2 text-sm font-semibold group/btn">
                                <span>Reservasi Sekarang</span>
                                <div class="service-card-arrow w-6 h-6 rounded-full flex items-center justify-center group-hover/btn:translate-x-1 transition-transform">
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </div>
                            </a>
                        </div>

                        <!-- Hover Decoration -->
                        <div class="service-card-glow absolute -bottom-16 -right-16 w-48 h-48 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-500 blur-2xl pointer-events-none"></div>
                    </div>
                </div>
                @endforeach

            </div>
            @else
            <!-- Empty State -->
            <div class="text-center py-20">
                <div class="w-20 h-20 rounded-3xl bg-slate-100 text-slate-300 flex items-center justify-center text-3xl mx-auto mb-6">
                    <i class="fa-solid fa-compass"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-400 mb-2">Belum Ada Layanan</h3>
                <p class="text-slate-400 text-sm">Layanan akan segera tersedia. Silakan kunjungi kembali nanti.</p>
            </div>
            @endif
        </div>
    </section>

    <!-- Knowledge & AI Section (Clean Lists) -->
    <section id="knowledge" class="py-24">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 grid lg:grid-cols-2 gap-16">
            
            <!-- Regulations -->
            <div>
                <h3 class="font-mono text-sm font-bold text-slate-400 uppercase tracking-widest mb-8">Dokumen & Regulasi</h3>
                
                <div x-data="{ expanded: false }" class="space-y-4">
                    @forelse($regulations as $index => $regulation)
                    <!-- Item -->
                    <a href="{{ route('regulation.download', $regulation->slug) }}" 
                       target="_blank" 
                       x-show="{{ $index }} < 3 || expanded"
                       x-collapse
                       class="group block p-5 rounded-2xl border border-slate-100 bg-white hover:border-brand-blue/30 hover:shadow-lg hover:shadow-blue-500/5 transition-all">
                        <div class="flex justify-between items-start">
                            <div class="flex gap-4">
                                <div class="w-10 h-10 rounded-lg bg-red-50 text-red-500 flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </div>
                                <div>
                                    <h4 class="text-slate-900 font-bold group-hover:text-brand-blue transition-colors">{{ $regulation->title }}</h4>
                                    <p class="text-sm text-slate-500 mt-1">{{ $regulation->document_number ?? 'Dokumen Publik' }} • {{ $regulation->download_count }} Downloads</p>
                                </div>
                            </div>
                            <div class="w-8 h-8 rounded-full border border-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-brand-blue group-hover:text-white group-hover:border-transparent transition-all">
                                <i class="fa-solid fa-arrow-down text-xs"></i>
                            </div>
                        </div>
                    </a>
                    @empty
                    <div class="text-sm text-slate-500 italic">Belum ada regulasi yang diunggah.</div>
                    @endforelse

                     <!-- Toggle Link -->
                    @if(count($regulations) > 3)
                    <div class="mt-6 text-center md:text-left">
                        <button @click="expanded = !expanded" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-blue hover:text-brand-black transition-colors border-b border-transparent hover:border-brand-black pb-1">
                            <span x-text="expanded ? 'Tutup Arsip' : 'Lihat Arsip Lengkap'"></span> 
                            <i class="fa-solid" :class="expanded ? 'fa-arrow-up-long' : 'fa-arrow-right-long'"></i>
                        </button>
                    </div>
                    @endif
                </div>
            </div>

            <!-- FAQ -->
            <div>
                <h3 class="font-mono text-sm font-bold text-slate-400 uppercase tracking-widest mb-8">Pertanyaan Umum</h3>
                <div x-data="{ 
                    active: null,
                    showAll: false
                }" class="space-y-3">
                    @forelse($faqs as $index => $faq)
                        <div x-show="{{ $index }} < 5 || showAll" x-collapse class="border-b border-slate-100 last:border-0">
                            <button @click="active === {{ $index }} ? active = null : active = {{ $index }}" class="w-full py-4 flex justify-between items-center text-left group">
                                <span class="text-slate-800 font-medium group-hover:text-brand-blue transition-colors">{{ $faq->question }}</span>
                                <i class="fa-solid fa-plus text-slate-300 text-xs transition-transform duration-300" :class="active === {{ $index }} ? 'rotate-45' : ''"></i>
                            </button>
                            <div x-show="active === {{ $index }}" x-collapse class="pb-4 text-slate-500 text-sm leading-relaxed">
                                <p>{{ $faq->answer }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-500 italic">Belum ada FAQ.</div>
                    @endforelse

                    <!-- Toggle Link -->
                    @if(count($faqs) > 5)
                    <div class="mt-6 text-center md:text-left">
                        <button @click="showAll = !showAll" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-brand-blue transition-colors">
                            <span x-text="showAll ? 'Sembunyikan' : 'Lihat Semua Pertanyaan'"></span>
                            <i class="fa-solid" :class="showAll ? 'fa-minus-circle' : 'fa-circle-question'"></i>
                        </button>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </section>

    <!-- Public Links Section -->
    <section id="public-links" class="py-24 border-t border-slate-100 bg-white/70">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="max-w-3xl mb-14">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-slate-200 bg-white/80 backdrop-blur-sm mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-blue"></span>
                    <span class="text-xs font-mono font-semibold text-slate-500 uppercase tracking-widest">Informasi Publik</span>
                </div>
                <h2 class="text-4xl lg:text-5xl font-bold text-slate-900 tracking-tight mb-4">Tautan Resmi Terkait</h2>
                <p class="text-slate-500 text-lg">Akses kanal informasi layanan publik, pengaduan masyarakat, dan website organisasi terkait untuk mendapatkan informasi tambahan yang terpercaya.</p>
            </div>

            @php
                $publicLinks = [
                    [
                        'title' => 'SIPPN',
                        'description' => 'Informasi pelayanan publik nasional dan standar layanan instansi pemerintah di Indonesia.',
                        'url' => 'https://sippn.menpan.go.id/',
                        'icon' => 'fa-solid fa-building-columns',
                        'accent' => 'blue',
                    ],
                    [
                        'title' => 'LAPOR!',
                        'description' => 'Kanal pengaduan masyarakat resmi pemerintah Indonesia untuk aspirasi dan pelaporan publik.',
                        'url' => 'https://www.lapor.go.id/',
                        'icon' => 'fa-solid fa-bullhorn',
                        'accent' => 'emerald',
                    ],
                    [
                        'title' => 'Timur Bersinar',
                        'description' => 'Website LPRL Sorong untuk informasi profil, kegiatan, dan publikasi lainnya.',
                        'url' => 'https://timurbersinar.com/',
                        'icon' => 'fa-solid fa-globe',
                        'accent' => 'amber',
                    ],
                ];

                $publicLinkAccentClasses = [
                    'blue' => [
                        'icon' => 'bg-blue-50 text-blue-600',
                        'ring' => 'group-hover:border-blue-200',
                        'title' => 'group-hover:text-blue-700',
                        'arrow' => 'group-hover:bg-blue-600 group-hover:border-blue-600',
                    ],
                    'emerald' => [
                        'icon' => 'bg-emerald-50 text-emerald-600',
                        'ring' => 'group-hover:border-emerald-200',
                        'title' => 'group-hover:text-emerald-700',
                        'arrow' => 'group-hover:bg-emerald-600 group-hover:border-emerald-600',
                    ],
                    'amber' => [
                        'icon' => 'bg-amber-50 text-amber-600',
                        'ring' => 'group-hover:border-amber-200',
                        'title' => 'group-hover:text-amber-700',
                        'arrow' => 'group-hover:bg-amber-500 group-hover:border-amber-500',
                    ],
                ];
            @endphp

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach($publicLinks as $link)
                    @php $accent = $publicLinkAccentClasses[$link['accent']]; @endphp
                    <a href="{{ $link['url'] }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="group block h-full rounded-3xl border border-slate-100 bg-white p-7 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl {{ $accent['ring'] }}">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl {{ $accent['icon'] }}">
                                <i class="{{ $link['icon'] }}"></i>
                            </div>
                            <div class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-400 transition-all {{ $accent['arrow'] }} group-hover:text-white">
                                <i class="fa-solid fa-arrow-up-right-from-square text-sm"></i>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <h3 class="text-2xl font-bold text-slate-900 transition-colors {{ $accent['title'] }}">{{ $link['title'] }}</h3>
                            <p class="text-sm leading-relaxed text-slate-500">{{ $link['description'] }}</p>
                            <p class="text-sm font-semibold text-slate-700 break-all">{{ parse_url($link['url'], PHP_URL_HOST) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Booking Section -->
    <section id="booking" class="py-24 bg-brand-surface border-t border-slate-200">
        <div class="max-w-4xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                 <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-slate-200 bg-white mb-6">
                    <span class="text-xs font-mono font-medium text-slate-500 uppercase tracking-wide">Reservasi Online</span>
                </div>
                <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 tracking-tight mb-4">Mulai Jadwalkan Konsultasi</h2>
                <p class="text-slate-500">Silakan lengkapi formulir berikut untuk mendapatkan tiket antrian.</p>
            </div>

            <!-- Booking Wizard Component -->
            <livewire:booking-wizard />
            
        </div>
    </section>




    {{-- AI Chat Widget --}}
    @livewire('ai-chat-widget')

</div>
