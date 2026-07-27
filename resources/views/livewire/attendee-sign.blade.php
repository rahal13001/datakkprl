<div class="min-h-screen pt-24 pb-20 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">

        {{-- Header --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl shadow-lg shadow-blue-500/30 mb-5">
                <i class="fa-solid fa-file-signature text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl lg:text-4xl font-bold text-slate-900 tracking-tight mb-3">
                Persetujuan <span class="text-gradient">Berita Acara</span>
            </h1>
            <p class="text-base text-slate-500 font-light max-w-lg mx-auto">
                Lengkapi data diri dan bubuhkan tanda tangan Anda untuk berita acara pendampingan permohonan.
            </p>
        </div>

        {{-- Meeting Context Card --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl p-6 border border-white/20 shadow-lg shadow-slate-200/50 mb-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-clipboard-list text-blue-600 text-sm"></i>
                </div>
                <h2 class="font-bold text-slate-900">Detail Berita Acara</h2>
            </div>

            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div class="space-y-3">
                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Nomor BA</span>
                        <p class="text-slate-800 font-semibold mt-0.5">{{ $beritaAcara->nomor_berita_acara ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Tanggal Pelaksanaan</span>
                        <p class="text-slate-800 font-semibold mt-0.5">
                            {{ $beritaAcara->tanggal_pelaksanaan ? $beritaAcara->tanggal_pelaksanaan->translatedFormat('l, d F Y') : '-' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Layanan</span>
                        <p class="text-slate-800 font-semibold mt-0.5">{{ $client->service->name ?? '-' }}</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Pemohon</span>
                        <p class="text-slate-800 font-semibold mt-0.5">{{ $client->name }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Lokasi Permohonan</span>
                        <p class="text-slate-800 font-semibold mt-0.5">{{ $beritaAcara->lokasi_permohonan ?? '-' }}</p>
                    </div>
                    @if($beritaAcara->kbli)
                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">KBLI</span>
                        <p class="text-slate-800 font-semibold mt-0.5">{{ $beritaAcara->kbli }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Hasil Pendampingan (Minutes content) --}}
            @if(!empty($beritaAcara->hasil_pendampingan) && trim(strip_tags($beritaAcara->hasil_pendampingan)) !== '')
            <style>
                .ba-rich-content { color: #334155; font-size: 0.9rem; line-height: 1.7; background-color: #ffffff; }
                .ba-rich-content * { background-color: transparent !important; }
                .ba-rich-content p { margin-bottom: 1em; }
                .ba-rich-content p:last-child { margin-bottom: 0; }
                .ba-rich-content strong, .ba-rich-content b { font-weight: 600; color: #0f172a; }
                .ba-rich-content em, .ba-rich-content i { font-style: italic; }
                .ba-rich-content ul { list-style-type: disc; list-style-position: outside; padding-left: 2em; margin-bottom: 1em; margin-top: 0.5em; }
                .ba-rich-content ol { list-style-type: decimal; list-style-position: outside; padding-left: 2em; margin-bottom: 1em; margin-top: 0.5em; }
                .ba-rich-content li { margin-bottom: 0.5em; padding-left: 0.25em; }
                .ba-rich-content li::marker { color: #64748b; font-weight: 500; }
                .ba-rich-content li > p { margin-top: 0; margin-bottom: 0; display: block; }
                .ba-rich-content table { width: 100%; border-collapse: collapse; margin-block: 1.25em; border-radius: 0.5rem; overflow: hidden; }
                .ba-rich-content th, .ba-rich-content td { border: 1px solid #cbd5e1; padding: 0.75rem 1rem; text-align: left; vertical-align: top; }
                .ba-rich-content th { background-color: #f8fafc; font-weight: 600; color: #1e293b; }
                .ba-rich-content h1, .ba-rich-content h2, .ba-rich-content h3 { font-weight: 600; color: #0f172a; margin-top: 1.5em; margin-bottom: 0.75em; }
            </style>
            <div class="mt-8 pt-6 border-t border-slate-100">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest block mb-4">Isi Berita Acara</span>
                <div class="ba-rich-content p-6 rounded-xl border border-slate-100 shadow-sm">
                    {!! app(\App\Services\SafeRichText::class)->sanitize($beritaAcara->hasil_pendampingan) !!}
                </div>
            </div>
            @endif

            {{-- Auto-approved Banner --}}
            @if($autoApproved)
                <div class="mt-5 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                    <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-clock-rotate-left text-amber-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-amber-800 font-semibold text-sm">Batas Waktu Telah Terlewati</p>
                        <p class="text-amber-700 text-xs mt-1">
                            Batas konfirmasi berita acara (3 hari kerja) telah lewat.
                            Seluruh peserta rapat dianggap telah menyetujui dan menandatangani berita acara ini.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Already Signed State --}}
        @if($alreadySigned)
            <div class="bg-white rounded-2xl overflow-hidden shadow-lg border border-slate-100 animate-fade-in-up">
                <div class="p-8 text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center text-white mx-auto mb-5 shadow-lg shadow-emerald-500/30">
                        <i class="fa-solid fa-circle-check text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-2">Terima Kasih!</h3>
                    <p class="text-slate-500 mb-6">Data dan tanda tangan Anda telah tersimpan dengan aman.</p>

                    {{-- Confirmed Data Summary --}}
                    <div class="bg-slate-50 rounded-xl p-5 text-left max-w-md mx-auto space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Nama</span>
                            <span class="font-semibold text-slate-800">{{ $attendee->nama }}</span>
                        </div>
                        @if($attendee->jabatan)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Jabatan</span>
                            <span class="font-semibold text-slate-800">{{ $attendee->jabatan }}</span>
                        </div>
                        @endif
                        @if($attendee->instansi)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Instansi</span>
                            <span class="font-semibold text-slate-800">{{ $attendee->instansi }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Dikonfirmasi</span>
                            <span class="font-semibold text-slate-800">{{ $attendee->confirmed_at?->translatedFormat('d M Y, H:i') }} WIT</span>
                        </div>
                    </div>

                    {{-- Signature Preview --}}
                    @if($existingSignatureDataUri)
                        <div class="mt-6">
                            <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-3">Tanda Tangan Tersimpan</p>
                            <div class="inline-block border border-slate-200 rounded-xl p-3 bg-white shadow-sm">
                                <img src="{{ $existingSignatureDataUri }}" alt="Tanda Tangan" class="max-h-24">
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        {{-- Signing Form --}}
        @else
            <form wire:submit="submit" class="space-y-6">

                {{-- Personal Data Section --}}
                <div class="bg-white/80 backdrop-blur-xl rounded-2xl p-6 border border-white/20 shadow-lg shadow-slate-200/50">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-9 h-9 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-user-pen text-indigo-600 text-sm"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-900">Data Diri Peserta</h2>
                            <p class="text-xs text-slate-500">Lengkapi data Anda sebagai peserta rapat.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        {{-- Nama --}}
                        <div>
                            <label for="nama" class="block text-sm font-medium text-slate-700 mb-1.5">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nama" wire:model="nama"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-sm"
                                placeholder="Nama lengkap Anda">
                            @error('nama') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            {{-- Jabatan --}}
                            <div>
                                <label for="jabatan" class="block text-sm font-medium text-slate-700 mb-1.5">Jabatan</label>
                                <input type="text" id="jabatan" wire:model="jabatan"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-sm"
                                    placeholder="Jabatan Anda">
                            </div>

                            {{-- Instansi --}}
                            <div>
                                <label for="instansi" class="block text-sm font-medium text-slate-700 mb-1.5">Instansi</label>
                                <input type="text" id="instansi" wire:model="instansi"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-sm"
                                    placeholder="Nama instansi">
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            {{-- Email --}}
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                                <input type="email" id="email" wire:model="email"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-sm"
                                    placeholder="email@contoh.com">
                                @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            {{-- No HP --}}
                            <div>
                                <label for="no_hp" class="block text-sm font-medium text-slate-700 mb-1.5">No. HP / WhatsApp</label>
                                <input type="text" id="no_hp" wire:model="no_hp"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-sm"
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
                            <p class="text-xs text-slate-500">Bubuhkan tanda tangan Anda pada area di bawah.</p>
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
                        <div class="relative border-2 border-dashed border-slate-200 rounded-xl overflow-hidden bg-white hover:border-blue-300 transition-colors">
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
                                Hapus Tanda Tangan
                            </button>
                        </div>
                    </div>

                    @error('tanda_tangan')
                        <p class="text-red-500 text-xs mt-2 flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Consent Notice --}}
                <div class="bg-blue-50/80 backdrop-blur-xl rounded-2xl p-5 border border-blue-100">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                        <div class="text-sm text-blue-700">
                            <p class="font-semibold mb-1">Pernyataan</p>
                            <p class="text-blue-600 text-xs leading-relaxed">
                                Dengan menandatangani dan mengirimkan formulir ini, saya menyatakan bahwa data yang saya isi adalah benar
                                dan saya hadir dalam kegiatan pendampingan permohonan tersebut di atas.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full py-4 rounded-2xl font-bold text-base shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all duration-200 flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed"
                    style="background: linear-gradient(to bottom right, #2563eb, #0891b2); color: white; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);"
                >
                    <span wire:loading.remove>
                        <i class="fa-solid fa-paper-plane"></i>
                        Kirim Konfirmasi & Tanda Tangan
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
        <div class="text-center mt-10">
            <p class="text-xs text-slate-400" style="color: #94a3b8;">
                <i class="fa-solid fa-lock mr-1"></i>
                Tanda tangan Anda disimpan secara terenkripsi dan aman.
            </p>
        </div>
    </div>
</div>
