<div class="relative">
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
                    <h1 class="font-mono font-bold text-lg text-slate-900 tracking-tight">LPSPL.SORONG</h1>
                    <p class="text-[10px] text-slate-500 font-medium tracking-widest uppercase">Official Platform V2</p>
                </div>
            </div>

            <!-- Menu (Clean Text) -->
            <div class="hidden md:flex items-center gap-8">
                <a href="#home" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Home</a>
                <a href="#services" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Layanan</a>
                <a href="#knowledge" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Regulasi</a>
                
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
                        Transformasi digital perizinan Kementerian Kelautan & Perikanan. 
                        Transparan, presisi, dan terintegrasi teknologi AI.
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

    <!-- Services (Bento Grid Layout) -->
    <section id="services" class="py-24 bg-brand-surface/50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between items-end mb-12">
                <h2 class="text-3xl font-bold text-slate-900 tracking-tight">Layanan Utama</h2>
                <a href="#booking" class="hidden md:flex items-center gap-2 text-sm font-medium text-brand-blue hover:underline">
                    Lihat Prosedur <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Bento Grid -->
            <div class="grid md:grid-cols-3 gap-6">
                
                <!-- Large Card -->
                <div class="md:col-span-2 bg-white rounded-3xl p-8 border border-slate-100 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-brand-blue flex items-center justify-center text-xl mb-6">
                            <i class="fa-regular fa-comments"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-2">Konsultasi PKKPRL</h3>
                        <p class="text-slate-500 leading-relaxed max-w-md mb-8">
                            Diskusi mendalam mengenai persyaratan teknis dan tata cara penerbitan Persetujuan Kesesuaian Kegiatan Pemanfaatan Ruang Laut.
                        </p>
                        <a href="#booking" class="px-6 py-3 rounded-full border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 hover:border-slate-300 transition-all inline-block">
                            Pilih Jadwal
                        </a>
                    </div>
                    <!-- Decoration -->
                    <div class="absolute right-0 bottom-0 w-64 h-64 bg-gradient-to-tl from-blue-50 to-transparent rounded-tl-full opacity-50 group-hover:scale-110 transition-transform duration-500"></div>
                </div>

                <!-- Tall Card -->
                <div class="bg-brand-black text-white rounded-3xl p-8 shadow-xl flex flex-col justify-between relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-white text-xl mb-6">
                            <i class="fa-solid fa-magnifying-glass-chart"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Asistensi Teknis</h3>
                        <p class="text-slate-400 text-sm leading-relaxed mb-6">
                            Verifikasi lapangan dan validasi dokumen teknis perizinan.
                        </p>
                    </div>
                    <a href="#booking" class="w-full py-3 rounded-full bg-white text-slate-900 text-center text-sm font-bold hover:bg-slate-100 transition-all block">
                        Reservasi
                    </a>
                    <!-- Deco -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-brand-blue rounded-bl-full opacity-20 filter blur-xl group-hover:opacity-40 transition-opacity"></div>
                </div>

            </div>
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

    <!-- AI Floating Widget (Minimalist Pill) -->
    <div x-data="{ open: false }" class="fixed bottom-8 right-8 z-50 flex flex-col items-end">
        
        <!-- Chat Interface -->
        <div x-show="open" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 scale-95"
             class="mb-4 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden flex flex-col">
            
            <div class="p-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wide">LPSPL AI</span>
                </div>
                <button @click="open = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="h-64 bg-white p-4 overflow-y-auto space-y-4">
                <div class="flex gap-3">
                    <div class="w-6 h-6 rounded-full bg-brand-black text-white flex items-center justify-center text-[10px] flex-shrink-0">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="bg-slate-100 text-slate-600 text-xs p-3 rounded-2xl rounded-tl-none leading-relaxed">
                        Halo. Saya asisten virtual yang membaca seluruh regulasi LPSPL. Tanyakan apa saja.
                    </div>
                </div>
            </div>

            <div class="p-3 border-t border-slate-100">
                <div class="flex bg-slate-50 rounded-full px-1 py-1 border border-slate-200 focus-within:border-brand-blue transition-colors">
                    <input type="text" placeholder="Tanya sesuatu..." class="flex-1 bg-transparent px-3 text-xs focus:outline-none text-slate-700">
                    <button class="w-7 h-7 bg-brand-black text-white rounded-full flex items-center justify-center hover:bg-slate-800 transition-colors">
                        <i class="fa-solid fa-arrow-up text-[10px]"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Trigger Button -->
        <button @click="open = !open" class="group flex items-center gap-3 pl-5 pr-2 py-2 bg-white border border-slate-200 text-slate-800 rounded-full shadow-lg hover:shadow-xl hover:border-brand-blue/30 transition-all duration-300">
            <span class="text-xs font-bold tracking-wide">Tanya AI</span>
            <div class="w-8 h-8 bg-brand-black rounded-full flex items-center justify-center text-white group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
            </div>
        </button>
    </div>

</div>
