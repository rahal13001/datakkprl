<div class="min-h-screen pt-24 pb-20 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="max-w-2xl mx-auto">

        {{-- Header --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl shadow-lg shadow-orange-500/30 mb-5">
                <i class="fa-solid fa-clipboard-user text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl lg:text-4xl font-bold text-slate-900 tracking-tight mb-3">
                Daftar Hadir <span class="text-gradient" style="background-image: linear-gradient(to right, #d97706, #ea580c); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Pertemuan</span>
            </h1>
            <p class="text-base text-slate-500 font-light max-w-lg mx-auto">
                Silakan lengkapi form di bawah ini untuk menyatakan kehadiran Anda beserta tanda tangan untuk keperluan Berita Acara pendampingan.
            </p>
        </div>

        {{-- Meeting Context Card --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl p-6 border border-white/20 shadow-lg shadow-slate-200/50 mb-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-calendar-check text-orange-600 text-sm"></i>
                </div>
                <h2 class="font-bold text-slate-900">Informasi Kegiatan</h2>
            </div>

            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div class="space-y-3">
                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Layanan</span>
                        <p class="text-slate-800 font-semibold mt-0.5">{{ $client->service->name ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Tanggal</span>
                        <p class="text-slate-800 font-semibold mt-0.5">
                            {{ $beritaAcara->tanggal_pelaksanaan ? $beritaAcara->tanggal_pelaksanaan->translatedFormat('l, d F Y') : '-' }}
                        </p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Pemohon / Subjek</span>
                        <p class="text-slate-800 font-semibold mt-0.5">{{ $client->name }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Lokasi Konsultasi</span>
                        <p class="text-slate-800 font-semibold mt-0.5">{{ $client->consultationLocation->name ?? 'Daring / Online' }}</p>
                    </div>
                </div>
            </div>

            @if(!$beritaAcara->attendance_is_open)
                <div class="mt-5 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-lock text-red-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-red-800 font-semibold text-sm">Daftar Hadir Ditutup</p>
                        <p class="text-red-700 text-xs mt-1">
                            Akses pengisian daftar hadir untuk kegiatan ini sedang ditutup oleh petugas. Anda tidak dapat mengisi form pada saat ini.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        @if(!$beritaAcara->attendance_is_open)
            {{-- Form Closed --}}
            <div class="text-center py-10">
                <p class="text-slate-400 italic">Formulir tidak lagi menerima tanggapan.</p>
            </div>
        @elseif($successfullySubmitted)
            {{-- Success State --}}
            <div class="bg-white rounded-2xl overflow-hidden shadow-lg border border-slate-100 animate-fade-in-up">
                <div class="p-8 text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center text-white mx-auto mb-5 shadow-lg shadow-emerald-500/30">
                        <i class="fa-solid fa-check-double text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-2">Sukses!</h3>
                    <p class="text-slate-500 mb-6">Kehadiran dan tanda tangan Anda berhasil direkam.</p>
                    <p class="text-sm text-slate-400">Anda dapat menutup halaman ini.</p>
                </div>
            </div>
        @else
            {{-- Form State --}}
            <form wire:submit="submit" class="space-y-6">

                {{-- Personal Data Section --}}
                <div class="bg-white/80 backdrop-blur-xl rounded-2xl p-6 border border-white/20 shadow-lg shadow-slate-200/50">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-9 h-9 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-id-card text-indigo-600 text-sm"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-900">Data Diri Peserta</h2>
                            <p class="text-xs text-slate-500">Mohon lengkapi sesuai identitas Anda.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        {{-- Nama --}}
                        <div>
                            <label for="nama" class="block text-sm font-medium text-slate-700 mb-1.5">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nama" wire:model="nama"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all outline-none text-sm"
                                placeholder="Nama lengkap Anda beserta gelar (opsional)">
                            @error('nama') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            {{-- Jabatan --}}
                            <div>
                                <label for="jabatan" class="block text-sm font-medium text-slate-700 mb-1.5">Jabatan</label>
                                <input type="text" id="jabatan" wire:model="jabatan"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all outline-none text-sm"
                                    placeholder="Cth: Staf Perizinan">
                            </div>

                            {{-- Instansi --}}
                            <div>
                                <label for="instansi" class="block text-sm font-medium text-slate-700 mb-1.5">Instansi Asal</label>
                                <input type="text" id="instansi" wire:model="instansi"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all outline-none text-sm"
                                    placeholder="Cth: PT Makmur Jaya">
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            {{-- Email --}}
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email Akses (Jika ada)</label>
                                <input type="email" id="email" wire:model="email"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all outline-none text-sm"
                                    placeholder="email@contoh.com">
                                @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            {{-- No HP --}}
                            <div>
                                <label for="no_hp" class="block text-sm font-medium text-slate-700 mb-1.5">No. WhatsApp / HP</label>
                                <input type="text" id="no_hp" wire:model="no_hp"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all outline-none text-sm"
                                    placeholder="08xx-xxxx-xxxx">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Signature Section --}}
                <div class="bg-white/80 backdrop-blur-xl rounded-2xl p-6 border border-white/20 shadow-lg shadow-slate-200/50">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-signature text-emerald-600 text-sm"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-900">Tanda Tangan <span class="text-red-500 text-sm">*</span></h2>
                            <p class="text-xs text-slate-500">Bubuhkan tanda tangan Anda dengan jari atau kursor.</p>
                        </div>
                    </div>

                    <div
                        x-data="{
                            canvas: null,
                            ctx: null,
                            isDrawing: false,
                            hasSignature: false,
                            lastX: 0,
                            lastY: 0,

                            init() {
                                this.canvas = this.$refs.signCanvas;
                                this.ctx = this.canvas.getContext('2d');
                                this.ctx.strokeStyle = '#1e293b';
                                this.ctx.lineWidth = 2.5;
                                this.ctx.lineCap = 'round';
                                this.ctx.lineJoin = 'round';

                                this.ctx.fillStyle = '#ffffff';
                                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
                            },

                            getPos(e) {
                                const rect = this.canvas.getBoundingClientRect();
                                const scaleX = this.canvas.width / rect.width;
                                const scaleY = this.canvas.height / rect.height;

                                if (e.touches) {
                                    return {
                                        x: (e.touches[0].clientX - rect.left) * scaleX,
                                        y: (e.touches[0].clientY - rect.top) * scaleY
                                    };
                                }
                                return {
                                    x: (e.clientX - rect.left) * scaleX,
                                    y: (e.clientY - rect.top) * scaleY
                                };
                            },

                            startDrawing(e) {
                                e.preventDefault();
                                this.isDrawing = true;
                                const pos = this.getPos(e);
                                this.lastX = pos.x;
                                this.lastY = pos.y;
                                this.ctx.beginPath();
                                this.ctx.moveTo(pos.x, pos.y);
                            },

                            draw(e) {
                                if (!this.isDrawing) return;
                                e.preventDefault();
                                const pos = this.getPos(e);
                                this.ctx.lineTo(pos.x, pos.y);
                                this.ctx.stroke();
                                this.lastX = pos.x;
                                this.lastY = pos.y;
                            },

                            stopDrawing() {
                                if (this.isDrawing) {
                                    this.isDrawing = false;
                                    this.hasSignature = true;
                                    this.save();
                                }
                            },

                            save() {
                                const dataUrl = this.canvas.toDataURL('image/png');
                                $wire.set('tanda_tangan', dataUrl);
                            },

                            clear() {
                                this.ctx.fillStyle = '#ffffff';
                                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
                                this.ctx.strokeStyle = '#1e293b';
                                this.hasSignature = false;
                                $wire.set('tanda_tangan', null);
                            }
                        }"
                    >
                        <div class="relative border-2 border-dashed border-slate-200 rounded-xl overflow-hidden bg-white hover:border-orange-300 transition-colors">
                            <canvas
                                x-ref="signCanvas"
                                width="500"
                                height="160"
                                class="cursor-crosshair w-full touch-none"
                                style="aspect-ratio: 500/160;"
                                @mousedown="startDrawing($event)"
                                @mousemove="draw($event)"
                                @mouseup="stopDrawing()"
                                @mouseleave="stopDrawing()"
                                @touchstart="startDrawing($event)"
                                @touchmove="draw($event)"
                                @touchend="stopDrawing()"
                            ></canvas>

                            {{-- Placeholder --}}
                            <div
                                x-show="!hasSignature"
                                class="absolute inset-0 flex items-center justify-center pointer-events-none"
                            >
                                <div class="text-center">
                                    <i class="fa-solid fa-pen-fancy text-slate-300 text-2xl mb-2"></i>
                                    <p class="text-slate-400 text-sm italic">Tanda tangan di sini</p>
                                </div>
                            </div>
                        </div>

                        {{-- Clear button --}}
                        <div class="flex justify-end mt-2">
                            <button
                                type="button"
                                @click="clear()"
                                x-show="hasSignature"
                                x-transition
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-500 bg-red-50 hover:bg-red-100 rounded-lg transition-colors"
                            >
                                <i class="fa-solid fa-eraser text-xs"></i>
                                Hapus
                            </button>
                        </div>
                    </div>

                    @error('tanda_tangan')
                        <p class="text-red-500 text-xs mt-2 flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full py-4 rounded-2xl font-bold text-base shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all duration-200 flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed"
                    style="background: linear-gradient(to bottom right, #d97706, #ea580c); color: white; box-shadow: 0 10px 15px -3px rgba(234, 88, 12, 0.3);"
                >
                    <span wire:loading.remove>
                        <i class="fa-solid fa-paper-plane"></i>
                        Kirim Daftar Hadir
                    </span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Menyimpan...
                    </span>
                </button>
            </form>
        @endif
        
        {{-- Footer --}}
        <div class="text-center mt-10 text-xs text-slate-400">
            <p>
                <i class="fa-solid fa-shield-halved mr-1"></i>
                Data dan tanda tangan Anda dilindungi secara enkripsi.
            </p>
        </div>
    </div>
</div>
