<div class="min-h-screen">
    <style>
        [x-cloak] { display: none !important; }

        .feedback-shell {
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.10), transparent 32%),
                radial-gradient(circle at top right, rgba(20, 184, 166, 0.10), transparent 28%),
                linear-gradient(180deg, #f8fbff 0%, #fffdf8 100%);
        }

        .feedback-panel {
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.08);
        }

        .feedback-input {
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .feedback-input:focus {
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
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

            <div class="hidden md:flex items-center gap-5 lg:gap-6">
                <a href="{{ route('landing') }}#home" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Home</a>
                <a href="{{ route('landing') }}#services" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Layanan</a>
                <a href="{{ route('landing') }}#knowledge" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Regulasi</a>
                <a href="{{ route('service-performance-results') }}" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Hasil Kinerja</a>
                <a href="{{ route('check-status') }}" class="text-sm font-medium text-slate-600 hover:text-brand-black transition-colors">Cek Status</a>
                <a href="{{ route('public-feedback') }}" class="px-6 py-2.5 bg-brand-black text-white text-sm font-medium rounded-full hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 hover:shadow-2xl hover:-translate-y-0.5 flex items-center gap-2">
                    <span>Masukan Publik</span>
                    <i class="fa-solid fa-message text-[10px]"></i>
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
            <a href="{{ route('service-performance-results') }}" class="block text-sm font-medium text-slate-600">Hasil Kinerja</a>
            <a href="{{ route('check-status') }}" class="block text-sm font-medium text-slate-600">Cek Status</a>
            <a href="{{ route('public-feedback') }}" class="block w-full text-center px-6 py-3 bg-brand-black text-white text-sm font-medium rounded-xl">Masukan Publik</a>
        </div>
    </nav>

    <main class="feedback-shell">
        <section class="pt-32 pb-12 lg:pt-36 lg:pb-16">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="max-w-4xl">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-slate-200 bg-white/80 backdrop-blur-sm mb-6">
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-blue"></span>
                        <span class="text-xs font-mono font-semibold text-slate-500 uppercase tracking-widest">Kanal Publik</span>
                    </div>
                    <h1 class="text-4xl lg:text-6xl font-bold text-slate-950 tracking-tight mb-5">Masukan Publik & Saran Layanan</h1>
                    <p class="text-lg text-slate-500 leading-relaxed">
                        Kanal ini berdampingan dengan survei kepuasan pasca-layanan. Anda dapat mengirim masukan secara anonim, memilih satu atau beberapa petugas, atau menyampaikan masukan umum tanpa memilih siapa pun.
                    </p>
                </div>
            </div>
        </section>

        <section class="pb-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid gap-8 xl:grid-cols-[minmax(320px,0.9fr)_minmax(0,1.1fr)]">
                    <aside class="space-y-6">
                  

                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Petunjuk Singkat</p>
                            <ul class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600">
                                <li>Masukan utama wajib diisi agar tim kami memahami konteks persoalan atau apresiasi Anda.</li>
                                <li>Pemilihan petugas bersifat opsional dan dapat memilih lebih dari satu nama.</li>
                                <li>Jika memilih anonim, nama pengirim tidak akan diminta.</li>
                            </ul>
                        </div>
                    </aside>

                    <section class="feedback-panel rounded-[2rem] border border-white/70 bg-white/95 p-6 shadow-sm backdrop-blur md:p-8">
                        @if($hasSubmitted)
                            <div class="mb-8 rounded-2xl border border-green-100 bg-green-50 px-5 py-4 text-sm text-green-700">
                                Terima kasih. Masukan publik Anda sudah tersimpan dan akan ikut direkap pada halaman hasil kinerja layanan.
                            </div>
                        @endif

                        @php
                            $officerOptions = $availableOfficers
                                ->map(fn ($officer) => [
                                    'id' => (string) $officer->id,
                                    'name' => $officer->name,
                                    'jabatan' => $officer->jabatan ?: 'Petugas layanan',
                                ])
                                ->values();
                        @endphp

                        <form wire:submit="submit" class="space-y-8">
                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-brand-blue">
                                    <span class="flex items-start gap-3">
                                        <input type="radio" wire:model.live="is_anonymous" value="1" class="mt-1 h-4 w-4 border-slate-300 text-brand-blue focus:ring-brand-blue">
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-900">Kirim sebagai anonim</span>
                                            <span class="mt-1 block text-sm text-slate-500">Identitas pengirim tidak diminta pada formulir publik ini.</span>
                                        </span>
                                    </span>
                                </label>

                                <label class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-brand-blue">
                                    <span class="flex items-start gap-3">
                                        <input type="radio" wire:model.live="is_anonymous" value="0" class="mt-1 h-4 w-4 border-slate-300 text-brand-blue focus:ring-brand-blue">
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-900">Tampilkan sebagai non-anonim</span>
                                            <span class="mt-1 block text-sm text-slate-500">Nama tetap opsional untuk laporan publik, tetapi akan kami simpan sebagai identitas pengirim.</span>
                                        </span>
                                    </span>
                                </label>
                            </div>

                            @if(! $is_anonymous)
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Pengirim</label>
                                    <input
                                        type="text"
                                        wire:model.defer="submitter_name"
                                        class="feedback-input w-full rounded-[1.75rem] border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-800 outline-none"
                                        placeholder="Contoh: Pemohon layanan / masyarakat umum"
                                    >
                                    @error('submitter_name') <span class="mt-2 block text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <div>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <label class="text-sm font-semibold text-slate-700">Pilih Petugas Terkait</label>
                                    <span class="text-xs font-medium uppercase tracking-widest text-slate-400">Opsional, bisa lebih dari satu</span>
                                </div>

                                <div
                                    x-data="{
                                        open: false,
                                        query: '',
                                        selected: @entangle('selectedOfficerIds').live,
                                        officers: @js($officerOptions),
                                        normalizedSelected() {
                                            return (this.selected ?? []).map((value) => String(value));
                                        },
                                        selectedOfficers() {
                                            const picked = this.normalizedSelected();
                                            return this.officers.filter((officer) => picked.includes(String(officer.id)));
                                        },
                                        filteredOfficers() {
                                            const query = this.query.trim().toLowerCase();
                                            const picked = this.normalizedSelected();
                                            const remaining = this.officers.filter((officer) => !picked.includes(String(officer.id)));

                                            if (!query) {
                                                return remaining;
                                            }

                                            return remaining.filter((officer) =>
                                                [officer.name, officer.jabatan]
                                                    .filter(Boolean)
                                                    .some((value) => value.toLowerCase().includes(query))
                                            );
                                        },
                                        isSelected(id) {
                                            return this.normalizedSelected().includes(String(id));
                                        },
                                        toggleOfficer(id) {
                                            const key = String(id);

                                            if (this.isSelected(key)) {
                                                this.selected = (this.selected ?? []).filter((value) => String(value) !== key);
                                                return;
                                            }

                                            this.selected = [...(this.selected ?? []), key];
                                        },
                                        removeOfficer(id) {
                                            const key = String(id);
                                            this.selected = (this.selected ?? []).filter((value) => String(value) !== key);
                                        },
                                        clearSelection() {
                                            this.selected = [];
                                            this.query = '';
                                            this.open = false;
                                        },
                                        openPicker() {
                                            this.open = true;
                                            this.$nextTick(() => this.$refs.queryInput?.focus());
                                        },
                                        closePicker() {
                                            this.open = false;
                                        },
                                        selectedCountLabel() {
                                            const total = this.selectedOfficers().length;

                                            if (!total) {
                                                return 'Belum ada petugas dipilih';
                                            }

                                            return `${total} petugas dipilih`;
                                        }
                                    }"
                                    class="mt-4"
                                >
                                    @if($officerOptions->isNotEmpty())
                                        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                                            <div class="border-b border-slate-100 bg-gradient-to-br from-slate-50 via-white to-blue-50/50 p-4 md:p-5">
                                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                    <div class="space-y-3">
                                                        <div>
                                                            <p class="text-sm font-semibold text-slate-900">Petugas yang dipilih</p>
                                                            <p class="mt-1 text-sm text-slate-500">Cari nama atau jabatan, lalu pilih petugas yang ingin menerima masukan ini.</p>
                                                        </div>

                                                        <div x-show="selectedOfficers().length" x-cloak class="flex flex-wrap gap-2">
                                                            <template x-for="officer in selectedOfficers()" :key="officer.id">
                                                                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm">
                                                                    <span x-text="officer.name"></span>
                                                                    <button type="button" @click="removeOfficer(officer.id)" class="text-slate-400 transition hover:text-slate-700">
                                                                        <i class="fa-solid fa-xmark text-xs"></i>
                                                                    </button>
                                                                </div>
                                                            </template>
                                                        </div>

                                                        <div x-show="!selectedOfficers().length" class="flex items-start gap-3 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-3 text-sm text-slate-500">
                                                            <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                                                <i class="fa-solid fa-user-check text-xs"></i>
                                                            </span>
                                                            <p>Pilih petugas jika masukan ini ingin diarahkan ke nama tertentu. Anda juga boleh melewati bagian ini untuk mengirim masukan umum.</p>
                                                        </div>
                                                    </div>

                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white">
                                                            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-white/15 px-1.5 text-[11px]" x-text="selectedOfficers().length"></span>
                                                            <span x-text="selectedCountLabel()"></span>
                                                        </span>
                                                        <button
                                                            type="button"
                                                            @click="open ? closePicker() : openPicker()"
                                                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                                                        >
                                                            <i class="fa-solid text-[10px]" :class="open ? 'fa-xmark' : 'fa-magnifying-glass'"></i>
                                                            <span x-text="open ? 'Tutup' : (selectedOfficers().length ? 'Ubah Pilihan' : 'Pilih Petugas')"></span>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="clearSelection()"
                                                            x-show="selectedOfficers().length"
                                                            x-cloak
                                                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                                        >
                                                            <i class="fa-solid fa-rotate-left text-[10px]"></i>
                                                            Reset
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="space-y-4 p-4 md:p-5">
                                                <div class="flex items-center justify-between gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                                    <div class="flex min-w-0 items-center gap-3">
                                                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm">
                                                            <i class="fa-solid fa-users text-sm"></i>
                                                        </span>
                                                        <div class="min-w-0">
                                                            <p class="text-sm font-semibold text-slate-900">Status pilihan petugas</p>
                                                            <p class="mt-1 truncate text-sm text-slate-500" x-text="selectedCountLabel()"></p>
                                                        </div>
                                                    </div>
                                                    <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-full bg-white px-3 text-sm font-bold text-slate-700 shadow-sm" x-text="selectedOfficers().length"></span>
                                                </div>

                                                <div
                                                    x-show="open"
                                                    x-cloak
                                                    @keydown.escape.window="closePicker()"
                                                    @click.outside="closePicker()"
                                                    class="mt-4 rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-4"
                                                >
                                                    <div class="rounded-[1.25rem] border border-slate-200 bg-white p-3 shadow-sm">
                                                        <div class="relative">
                                                            <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                                            <input
                                                                x-ref="queryInput"
                                                                type="text"
                                                                x-model="query"
                                                                class="feedback-input w-full rounded-xl border border-transparent bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-800 outline-none"
                                                                placeholder="Cari petugas berdasarkan nama atau jabatan"
                                                            >
                                                        </div>

                                                        <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                                                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Daftar Petugas</p>
                                                            <p class="text-xs text-slate-500" x-text="`${filteredOfficers().length} tersedia`"></p>
                                                        </div>

                                                        <div class="mt-3 max-h-80 overflow-y-auto pr-1">
                                                            <div class="grid gap-2">
                                                                <template x-if="!filteredOfficers().length">
                                                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                                                                        <span x-show="normalizedSelected().length < officers.length">Tidak ada petugas yang cocok dengan pencarian Anda.</span>
                                                                        <span x-show="normalizedSelected().length >= officers.length">Semua petugas sudah dipilih. Hapus salah satu chip jika ingin mengganti pilihan.</span>
                                                                    </div>
                                                                </template>

                                                                <template x-for="officer in filteredOfficers()" :key="officer.id">
                                                                    <button
                                                                        type="button"
                                                                        @click="toggleOfficer(officer.id); query = ''; closePicker()"
                                                                        class="group flex w-full items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-4 text-left transition hover:-translate-y-0.5 hover:border-brand-blue hover:shadow-md"
                                                                    >
                                                                        <div class="min-w-0">
                                                                            <p class="text-sm font-semibold text-slate-900" x-text="officer.name"></p>
                                                                            <p class="mt-1 text-sm text-slate-500" x-text="officer.jabatan"></p>
                                                                        </div>
                                                                        <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-400 transition group-hover:border-brand-blue group-hover:text-brand-blue">
                                                                            <i class="fa-solid fa-plus text-xs"></i>
                                                                        </span>
                                                                    </button>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <p class="border-t border-slate-100 px-4 py-4 text-sm text-slate-500 md:px-5">
                                                Anda dapat membiarkan bagian ini kosong jika ingin mengirim masukan umum tanpa menyebut petugas tertentu.
                                            </p>
                                        </div>
                                    @else
                                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                                            Belum ada data petugas yang dapat dipilih.
                                        </div>
                                    @endif
                                </div>
                                @error('selectedOfficerIds.*') <span class="mt-2 block text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Masukan Utama</label>
                                <textarea
                                    wire:model.defer="feedback"
                                    rows="5"
                                    class="feedback-input w-full rounded-[1.75rem] border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-800 outline-none"
                                    placeholder="Tulis kritik, apresiasi, atau pengalaman yang ingin Anda sampaikan."
                                ></textarea>
                                @error('feedback') <span class="mt-2 block text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Saran Perbaikan</label>
                                <textarea
                                    wire:model.defer="suggestion"
                                    rows="4"
                                    class="feedback-input w-full rounded-[1.75rem] border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-800 outline-none"
                                    placeholder="Opsional. Tuliskan saran yang menurut Anda bisa membantu peningkatan layanan."
                                ></textarea>
                                @error('suggestion') <span class="mt-2 block text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-slate-500">Jika tidak memilih petugas, masukan akan direkap sebagai masukan umum pada hasil kinerja layanan.</p>
                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-brand-black px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    <span>Kirim Masukan</span>
                                    <i class="fa-solid fa-paper-plane text-[11px]"></i>
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </section>
    </main>
</div>
