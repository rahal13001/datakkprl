<main data-kkprl-wizard class="mx-auto min-h-screen w-full max-w-5xl px-4 pb-32 pt-10 text-slate-900 sm:px-6 lg:px-8">
    <script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

                <style>
        .ck-editor__editable_inline { min-height: 250px; }
        .ck.ck-editor__main > .ck-editor__editable { border-bottom-left-radius: 0.75rem !important; border-bottom-right-radius: 0.75rem !important; }
        .ck.ck-toolbar { border-top-left-radius: 0.75rem !important; border-top-right-radius: 0.75rem !important; background-color: #f8fafc !important; border-bottom: 1px solid #e2e8f0 !important; }
    </style>
    
    <!-- Modern Header & Progress -->
    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden relative mb-8">
        <div class="bg-slate-50 border-b border-slate-100 p-6 lg:p-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold uppercase tracking-wider mb-3">
                        <i class="fa-solid fa-file-contract"></i> Proposal KKPRL
                    </span>
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Formulir Penyusunan</h1>
                    <p class="text-slate-500 mt-2 max-w-2xl text-sm leading-relaxed">
                        Draft disimpan secara otomatis. Dokumen ini merupakan bahan pendukung pra-review petugas LPRL, bukan izin resmi. Lanjutkan proses ke OSS/e-Sea setelah draft final Anda disetujui.
                    </p>
                    @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($lastSavedAt)
                        <p class="text-xs font-medium text-emerald-600 mt-3 flex items-center gap-1.5">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Terakhir tersimpan: {{ $lastSavedAt }}

                        </p>
                    @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-500/30">
                        <span class="text-2xl font-bold">{{ collect($chapterProgress)->sum('completed') }}</span>
                    </div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mt-2">Field Terisi</p>
                </div>
            </div>

            <!-- Progress Bar -->
            <?php
                $chaptersCount = count($relevantChapters);
                $currentIndex = array_search($currentChapter, $relevantChapters);
                $progressPercent = $chaptersCount > 1 ? ($currentIndex / ($chaptersCount - 1)) * 100 : 0;
            ?>
            <div class="relative pt-4">
                <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-600 transition-all duration-500 ease-out" style="width: {{ $progressPercent }}%"></div>
                </div>
                
                <div class="flex justify-between mt-4">
                    @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?>@endif<?php $__currentLoopData = $relevantChapters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $chapter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?>@endif
                        <?php
                            $state = $chapterProgress[$chapter]['status'] ?? 'incomplete';
                            $isActive = $chapter === $currentChapter;
                            $isPast = $idx < $currentIndex;
                        ?>
                        <div class="flex flex-col items-center cursor-pointer transition-transform hover:scale-105" wire:click="$set('currentStep', {{ $idx + 1 }})">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border-2 mb-2 transition-colors
                                {{ $isActive ? 'border-blue-600 bg-blue-600 text-white shadow-md' : 
                                   ($isPast ? 'border-blue-600 bg-white text-blue-600' : 'border-slate-300 bg-white text-slate-400') }}">
                                {{ $idx + 1 }}

                            </div>
                            <span class="text-xs font-bold uppercase tracking-wider hidden sm:block
                                {{ $isActive ? 'text-blue-700' : ($isPast ? 'text-slate-600' : 'text-slate-400') }}">
                                Bab {{ $idx + 1 }}

                            </span>
                            
                            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($state === 'complete')
                                <span class="text-[10px] text-emerald-500 mt-1 font-medium"><i class="fa-solid fa-check"></i> Selesai</span>
                            @elseif($state === 'error')
                                <span class="text-[10px] text-red-500 mt-1 font-medium"><i class="fa-solid fa-triangle-exclamation"></i> Error</span>
                            @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
                        </div>
                    @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?>@endif@endforeach@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?>@endif
                </div>
            </div>
        </div>
    </div>

    <!-- System Notifications -->
    @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($locked)
        <div class="mb-8 bg-emerald-50 border border-emerald-200 rounded-2xl p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                <i class="fa-solid fa-lock text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-emerald-900 text-lg">Proposal Terkunci (Sedang Ditinjau)</h3>
                <p class="text-sm text-emerald-700 mt-1 leading-relaxed">
                    Proposal Anda telah dikirim dan saat ini sedang dalam proses pratinjau oleh petugas LPRL Sorong. Seluruh data dan lampiran tidak dapat diubah sementara waktu.
                </p>
            </div>
        </div>
    @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif

    @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($revisionNote)
        <div class="mb-8 bg-amber-50 border border-amber-200 rounded-2xl p-5 flex items-start gap-4 shadow-sm">
            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 shrink-0">
                <i class="fa-solid fa-comment-dots text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-amber-900 text-lg">Catatan Revisi Petugas</h3>
                <p class="text-sm text-amber-800 mt-2 p-3 bg-white/60 rounded-xl whitespace-pre-wrap font-medium">{{ $revisionNote }}</p>
            </div>
        </div>
    @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif

    @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif<?php $__errorArgs = ['draft'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <div class="mb-8 bg-red-50 border border-red-200 text-red-600 px-5 py-4 rounded-xl text-sm font-medium flex items-center shadow-sm">
            <i class="fa-solid fa-circle-exclamation mr-3 text-lg"></i> {{ $message }}

        </div>
    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
    @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($errors->any())
        <div class="mb-8 bg-red-50 border border-red-200 rounded-2xl p-5 shadow-sm">
            <h4 class="font-bold text-red-800 flex items-center mb-3">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i> Perhatian! Mohon periksa isian Anda:
            </h4>
            <ul class="list-disc list-inside text-sm text-red-700 space-y-1 ml-2 font-medium">
                @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?>@endif<?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?>@endif<li>{{ $error }}</li>@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?>@endif@endforeach@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?>@endif
            </ul>
        </div>
    @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif

    @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif<?php if (! ($locked)): ?>
    <form wire:submit="saveDraft" class="space-y-8">
        
        <!-- ==============================
             BAB 1: FORM STATIS
        =============================== -->
        @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($currentChapter === 'bag-1')
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden relative">
                <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-8 text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-48 h-48 bg-white opacity-5 rounded-full blur-3xl"></div>
                    <div class="relative z-10">
                        <h2 class="text-2xl font-extrabold flex items-center">
                            <i class="fa-solid fa-file-signature mr-4 text-blue-200 text-3xl"></i>
                            Bab 1 — Rencana Bangunan & Instalasi Laut
                        </h2>
                        <p class="text-blue-100 mt-3 text-[15px] opacity-90 max-w-3xl leading-relaxed">
                            Formulir ini dirancang untuk memudahkan Anda. Jika Anda menjumpai input angka seperti Luas atau Kedalaman, pastikan memisahkan desimal menggunakan tanda <strong class="text-white bg-white/20 px-2 py-0.5 rounded shadow-sm">titik (.)</strong>.
                        </p>
                    </div>
                </div>
                
                <div class="p-8 lg:p-10 space-y-12 bg-slate-50/50">
                    <!-- Kelompok Identitas -->
                    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-100 hover:border-blue-100 transition-colors">
                        <div class="flex items-center mb-8 pb-4 border-b border-slate-100">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 font-bold mr-4 shrink-0 shadow-inner">
                                <i class="fa-regular fa-id-card"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 tracking-tight">Identitas Pemohon</h3>
                        </div>
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Pemohon Lengkap</label>
                                <input type="text" wire:model.blur="payload.bag-1.applicant_name" placeholder="Sesuai KTP" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Jabatan</label>
                                <input type="text" wire:model.blur="payload.bag-1.applicant_position" placeholder="Cth: Direktur Utama" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Instansi / Perusahaan</label>
                                <input type="text" wire:model.blur="payload.bag-1.institution_name" placeholder="Nama PT/CV/Koperasi" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nomor KTP (NIK)</label>
                                <input type="text" wire:model.blur="payload.bag-1.identity_number" placeholder="16 digit NIK" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">NPWP</label>
                                <input type="text" wire:model.blur="payload.bag-1.tax_number" placeholder="Nomor Pokok Wajib Pajak" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Email Aktif</label>
                                <input type="email" wire:model.blur="payload.bag-1.email" placeholder="email@domain.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Telepon / WhatsApp</label>
                                <input type="text" wire:model.blur="payload.bag-1.phone_alternative" placeholder="0812..." class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Alamat Lengkap</label>
                                <textarea wire:model.blur="payload.bag-1.address" rows="3" placeholder="Jl. Raya..." class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Kelompok Lokasi -->
                    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-100 hover:border-blue-100 transition-colors">
                        <div class="flex items-center mb-8 pb-4 border-b border-slate-100">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 font-bold mr-4 shrink-0 shadow-inner">
                                <i class="fa-solid fa-map-location-dot"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 tracking-tight">Lokasi Administratif</h3>
                        </div>
                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?>@endif<?php $__currentLoopData = ['province' => 'Provinsi', 'regency' => 'Kabupaten / Kota', 'subdistrict' => 'Kecamatan', 'village' => 'Desa / Kelurahan']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?>@endif
                                <div>
                                    <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">{{ $label }}</label>
                                    <input wire:model.blur="payload.bag-1.{{ $field }}" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                                </div>
                            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?>@endif@endforeach@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?>@endif
                        </div>
                    </div>

                    <!-- Kelompok Perairan -->
                    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-100 hover:border-blue-100 transition-colors">
                        <div class="flex items-center mb-8 pb-4 border-b border-slate-100">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 font-bold mr-4 shrink-0 shadow-inner">
                                <i class="fa-solid fa-water"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 tracking-tight">Dimensi Perairan & Koordinat</h3>
                        </div>
                        <div class="grid gap-6">
                            <!-- Baris 1: Nama Perairan -->
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Perairan / Laut</label>
                                <input wire:model.blur="payload.bag-1.water_name" type="text" placeholder="Cth: Perairan Teluk Bintuni" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            
                            <!-- Baris 2: Luas & Kedalaman (Bersebelahan di layar lebar) -->
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center justify-between">Dimensi (Luas / Panjang) <span class="text-[10px] font-normal text-amber-600 bg-amber-50 px-2 py-0.5 rounded">Gunakan Titik</span></label>
                                    <div class="flex gap-2">
                                        <input wire:model.blur="payload.bag-1.water_area" type="text" placeholder="Cth: 12.5" class="w-2/3 px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                                        <div class="relative w-1/3">
                                            <select wire:model.blur="payload.bag-1.water_area_unit" class="w-full appearance-none px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                                                <option value="">-- Satuan --</option>
                                                <option value="Hektar (Ha)">Hektar (Ha)</option>
                                                <option value="Kilometer (Km)">Kilometer (Km)</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center px-2 md:px-3 pointer-events-none text-slate-400"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center justify-between">Kedalaman (Meter) <span class="text-[10px] font-normal text-amber-600 bg-amber-50 px-2 py-0.5 rounded">Gunakan Titik</span></label>
                                    <input wire:model.blur="payload.bag-1.depth" type="text" placeholder="Cth: -5.2" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                                </div>
                            </div>
                            
                            <div x-data="coordinateManager({!! \Illuminate\Support\Js::from($payload['bag-1']['coordinates_raw'] ?? '[]')->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($payload['bag-1']['shape_type'] ?? 'polygon')->toHtml() ?>, $wire)" class="border border-slate-200 rounded-xl bg-slate-50 p-5 shadow-sm">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <h4 class="text-[14px] font-bold text-slate-700 uppercase tracking-wider flex items-center">
                                            <i class="fa-solid fa-map-location-dot mr-2 text-blue-500"></i> Koordinat Lokasi
                                        </h4>
                                        <p class="text-xs text-slate-500 mt-1">Gambar pada peta, atau isi manual format DD / DMS.</p>
                                    </div>
                                    <button type="button" @click="addPoint()" class="px-4 py-2 bg-blue-100 text-blue-700 hover:bg-blue-200 rounded-lg text-xs font-bold transition-colors shadow-sm">
                                        <i class="fa-solid fa-plus mr-1"></i> Tambah Titik
                                    </button>
                                </div>
                                
                                <div class="mb-5 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Bentuk Area Peta</label>
                                            <div class="flex space-x-5">
                                                <label class="flex items-center text-sm font-medium cursor-pointer text-slate-700 hover:text-blue-600 transition-colors">
                                                    <input type="radio" x-model="shapeType" value="point" class="w-4 h-4 mr-2 text-blue-600 focus:ring-blue-500 border-slate-300"> 
                                                    Titik (Point)
                                                </label>
                                                <label class="flex items-center text-sm font-medium cursor-pointer text-slate-700 hover:text-blue-600 transition-colors">
                                                    <input type="radio" x-model="shapeType" value="line" class="w-4 h-4 mr-2 text-blue-600 focus:ring-blue-500 border-slate-300"> 
                                                    Garis (Line)
                                                </label>
                                                <label class="flex items-center text-sm font-medium cursor-pointer text-slate-700 hover:text-blue-600 transition-colors">
                                                    <input type="radio" x-model="shapeType" value="polygon" class="w-4 h-4 mr-2 text-blue-600 focus:ring-blue-500 border-slate-300"> 
                                                    Area (Polygon)
                                                </label>
                                            </div>
                                        </div>
                                        <button type="button" @click="clearMap()" class="px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-[11px] font-bold transition-colors border border-red-100">
                                            <i class="fa-solid fa-trash-can mr-1"></i> Bersihkan Peta
                                        </button>
                                    </div>
                                    <div id="coordinate-map" wire:ignore style="height: 400px; z-index: 0;" class="w-full rounded-lg border border-slate-300 z-0"></div>
                                </div>

                                <div class="space-y-4">
                                    <template x-for="(point, index) in points" :key="index">
                                        <div class="bg-white border border-slate-200 p-4 rounded-xl relative shadow-sm group">
                                            <button type="button" @click="removePoint(index)" x-show="points.length > 1" class="absolute -top-3 -right-3 w-7 h-7 bg-red-100 text-red-600 rounded-full flex items-center justify-center border border-red-200 hover:bg-red-500 hover:text-white transition-colors opacity-0 group-hover:opacity-100">
                                                <i class="fa-solid fa-times text-xs"></i>
                                            </button>
                                            
                                            <div class="flex items-center mb-3 border-b border-slate-100 pb-2">
                                                <span class="bg-blue-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full mr-2" x-text="'Titik ' + (index + 1)"></span>
                                            </div>

                                            <div class="grid lg:grid-cols-2 gap-4">
                                                <!-- LATITUDE -->
                                                <div class="space-y-3 bg-white border border-slate-100 p-4 rounded-xl shadow-sm">
                                                    <h5 class="text-[11px] font-bold text-blue-600 uppercase tracking-wider border-b border-slate-100 pb-2"><i class="fa-solid fa-arrows-up-down mr-1"></i> Latitude (Lintang Y)</h5>
                                                    
                                                    <div>
                                                        <label class="block text-[10px] uppercase text-slate-400 mb-1">Decimal Degree (DD)</label>
                                                        <input type="number" step="any" x-model="point.lat_dd" @input="syncFromDD(index, true)" placeholder="Cth: -0.8712" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none text-sm font-mono transition-colors">
                                                    </div>
                                                    
                                                    <div class="pt-1">
                                                        <label class="block text-[10px] uppercase text-slate-400 mb-1">Derajat Menit Detik (DMS)</label>
                                                        <div class="grid grid-cols-4 gap-2">
                                                            <div class="relative">
                                                                <input type="number" x-model="point.lat_d" @input="syncFromDMS(index, true)" class="w-full px-2 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none text-sm font-mono text-center pr-4">
                                                                <span class="absolute right-2 top-2.5 text-slate-400 font-bold">°</span>
                                                            </div>
                                                            <div class="relative">
                                                                <input type="number" x-model="point.lat_m" @input="syncFromDMS(index, true)" class="w-full px-2 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none text-sm font-mono text-center pr-4">
                                                                <span class="absolute right-2 top-2.5 text-slate-400 font-bold">'</span>
                                                            </div>
                                                            <div class="relative">
                                                                <input type="number" step="any" x-model="point.lat_s" @input="syncFromDMS(index, true)" class="w-full px-2 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none text-sm font-mono text-center pr-4">
                                                                <span class="absolute right-2 top-2.5 text-slate-400 font-bold">"</span>
                                                            </div>
                                                            <div>
                                                                <select x-model="point.lat_dir" @change="syncFromDMS(index, true)" class="w-full px-1 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none text-sm font-bold text-center appearance-none cursor-pointer">
                                                                    <option value="N">U/N</option>
                                                                    <option value="S">S/S</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- LONGITUDE -->
                                                <div class="space-y-3 bg-white border border-slate-100 p-4 rounded-xl shadow-sm">
                                                    <h5 class="text-[11px] font-bold text-blue-600 uppercase tracking-wider border-b border-slate-100 pb-2"><i class="fa-solid fa-arrows-left-right mr-1"></i> Longitude (Bujur X)</h5>
                                                    
                                                    <div>
                                                        <label class="block text-[10px] uppercase text-slate-400 mb-1">Decimal Degree (DD)</label>
                                                        <input type="number" step="any" x-model="point.lng_dd" @input="syncFromDD(index, false)" placeholder="Cth: 131.2912" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none text-sm font-mono transition-colors">
                                                    </div>
                                                    
                                                    <div class="pt-1">
                                                        <label class="block text-[10px] uppercase text-slate-400 mb-1">Derajat Menit Detik (DMS)</label>
                                                        <div class="grid grid-cols-4 gap-2">
                                                            <div class="relative">
                                                                <input type="number" x-model="point.lng_d" @input="syncFromDMS(index, false)" class="w-full px-2 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none text-sm font-mono text-center pr-4">
                                                                <span class="absolute right-2 top-2.5 text-slate-400 font-bold">°</span>
                                                            </div>
                                                            <div class="relative">
                                                                <input type="number" x-model="point.lng_m" @input="syncFromDMS(index, false)" class="w-full px-2 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none text-sm font-mono text-center pr-4">
                                                                <span class="absolute right-2 top-2.5 text-slate-400 font-bold">'</span>
                                                            </div>
                                                            <div class="relative">
                                                                <input type="number" step="any" x-model="point.lng_s" @input="syncFromDMS(index, false)" class="w-full px-2 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none text-sm font-mono text-center pr-4">
                                                                <span class="absolute right-2 top-2.5 text-slate-400 font-bold">"</span>
                                                            </div>
                                                            <div>
                                                                <select x-model="point.lng_dir" @change="syncFromDMS(index, false)" class="w-full px-1 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none text-sm font-bold text-center appearance-none cursor-pointer">
                                                                    <option value="E">T/E</option>
                                                                    <option value="W">B/W</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kelompok Rencana Kegiatan -->
                    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-100 hover:border-blue-100 transition-colors">
                        <div class="flex items-center mb-8 pb-4 border-b border-slate-100">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 font-bold mr-4 shrink-0 shadow-inner">
                                <i class="fa-solid fa-industry"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 tracking-tight">Rencana Kegiatan Usaha</h3>
                        </div>
                        
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Kegiatan Utama</label>
                                <input wire:model.blur="payload.bag-1.main_activity" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Kegiatan Penunjang</label>
                                <input wire:model.blur="payload.bag-1.supporting_activity" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Sifat Usaha</label>
                                <div class="relative">
                                    <select wire:model.blur="payload.bag-1.business_status" class="w-full appearance-none px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                                        <option value="">-- Pilih --</option>
                                        <option value="Berusaha">Berusaha</option>
                                        <option value="Non-Berusaha">Non-Berusaha</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Status Strategis</label>
                                <div class="relative">
                                    <select wire:model.blur="payload.bag-1.strategic_status" class="w-full appearance-none px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                                        <option value="">-- Pilih --</option>
                                        <option value="Strategis Nasional">Proyek Strategis Nasional (PSN)</option>
                                        <option value="Non-Strategis">Non-Strategis / Reguler</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                                </div>
                            </div>
                            
                            <div class="sm:col-span-2">
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Bidang Kegiatan Usaha</label>
                                <input wire:model.blur="payload.bag-1.business_field" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>

                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Status Eksisting</label>
                                <input wire:model.blur="payload.bag-1.activity_status" type="text" placeholder="Status saat ini (Misal: Kosong / Sudah Terbangun)" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Jadwal Pembangunan (Estimasi)</label>
                                <input wire:model.blur="payload.bag-1.schedule" type="text" placeholder="Cth: Januari 2027 - Desember 2028" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Pekerja Pria</label>
                                <input wire:model.blur="payload.bag-1.workforce_male" type="number" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            <div>
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Pekerja Wanita</label>
                                <input wire:model.blur="payload.bag-1.workforce_female" type="number" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>

                            <div class="sm:col-span-2 md:col-span-1">
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center justify-between">Nilai Investasi <span class="text-[10px] font-normal text-amber-600 bg-amber-50 px-2 py-0.5 rounded">Gunakan Titik</span></label>
                                <div class="flex gap-2">
                                    <div class="relative w-1/3">
                                        <select wire:model.blur="payload.bag-1.investment_unit" class="w-full appearance-none px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                                            <option value="">-- Mata Uang --</option>
                                            <option value="IDR (Rupiah)">IDR</option>
                                            <option value="USD (Dolar)">USD</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center px-2 md:px-3 pointer-events-none text-slate-400"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                                    </div>
                                    <input wire:model.blur="payload.bag-1.investment_value" type="text" placeholder="mis. 5000000.50" class="w-2/3 px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                                </div>
                            </div>
                            <div class="hidden md:block"></div> <!-- Spacer to keep grid balanced -->

                            <div class="sm:col-span-2">
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Jenis Dokumen Lingkungan</label>
                                <input wire:model.blur="payload.bag-1.document_type" type="text" placeholder="Amdal / UKL-UPL" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Keterangan Tambahan</label>
                                <textarea wire:model.blur="payload.bag-1.other_matters" rows="3" placeholder="Informasi pendukung lain..." class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition-all font-medium text-slate-700"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Site Plan & Kondisi Tambahan -->
                    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 p-6 md:p-8 rounded-3xl border border-indigo-100 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 opacity-10">
                            <i class="fa-solid fa-compass text-9xl transform translate-x-1/3 -translate-y-1/3"></i>
                        </div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center mb-8 pb-4 border-b border-indigo-200/50">
                                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold mr-4 shrink-0 shadow-lg shadow-indigo-600/30">
                                    <i class="fa-regular fa-map"></i>
                                </div>
                                <h3 class="text-xl font-bold text-indigo-950 tracking-tight">Rencana Tapak (Site Plan)</h3>
                            </div>
                            
                            <div class="space-y-8">
                                <!-- Rich Text Editor -->
                                <div>
                                    <label class="block text-[13px] font-bold text-indigo-900 uppercase tracking-wider mb-2">Narasi Deskriptif</label>
                                    <p class="text-xs text-indigo-700 mb-4 bg-indigo-100/50 p-3 rounded-lg border border-indigo-200 inline-block">
                                        <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Gunakan editor modern di bawah ini untuk membuat format yang rapi (Tebal, Miring, Poin, Angka).
                                    </p>
                                    
                                    <div wire:ignore x-data="{
                                        editorInstance: null,
                                        init() {
                                            if (typeof ClassicEditor === 'undefined') {
                                                console.error('CKEditor is not loaded');
                                                return;
                                            }
                                            
                                            ClassicEditor
                                                .create(this.$refs.editor, {
                                                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'insertTable', 'blockQuote', 'undo', 'redo'],
                                                    table: {
                                                        contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
                                                    }
                                                })
                                                .then(editor => {
                                                    this.editorInstance = editor;
                                                    
                                                    // Set initial value
                                                    const existing = window.Livewire.find('{{ $_instance->getId() }}').get('payload.bag-1.site_plan_description');
                                                    if (existing) {
                                                        editor.setData(existing);
                                                    }
                                                    
                                                    // Sync with Livewire
                                                    editor.model.document.on('change:data', () => {
                                                        window.Livewire.find('{{ $_instance->getId() }}').set('payload.bag-1.site_plan_description', editor.getData());
                                                    });
                                                })
                                                .catch(error => {
                                                    console.error('Error initializing CKEditor:', error);
                                                });
                                        }
                                    }" class="bg-white rounded-xl shadow-sm border border-indigo-200 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 transition-all text-slate-700">
                                        <div x-ref="editor" class="text-[14.5px]">Mulai mengetik deskripsi Anda di sini...</div>
                                    </div>
                                </div>
                                
                                <div class="grid gap-6 sm:grid-cols-3">
                                    <div>
                                        <label class="block text-[13px] font-bold text-indigo-900 uppercase tracking-wider mb-2">Reklamasi</label>
                                        <div class="relative">
                                            <select wire:model.live="payload.includes_reclamation" required class="w-full appearance-none px-4 py-3 rounded-xl border border-indigo-200 bg-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition-all cursor-pointer font-medium text-slate-700 shadow-sm">
                                                <option value="">-- Pilih --</option>
                                                <option value="1">Ya, Ada Reklamasi</option>
                                                <option value="0">Tidak Ada</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-indigo-400"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[13px] font-bold text-indigo-900 uppercase tracking-wider mb-2">Posisi Ke Daratan</label>
                                        <div class="relative">
                                            <select wire:model.live="payload.land_relation" required class="w-full appearance-none px-4 py-3 rounded-xl border border-indigo-200 bg-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition-all cursor-pointer font-medium text-slate-700 shadow-sm">
                                                <option value="">-- Pilih --</option>
                                                <option value="adjacent">Berhimpitan (Nempel)</option>
                                                <option value="not_adjacent">Terpisah (Lepas Pantai)</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-indigo-400"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[13px] font-bold text-indigo-900 uppercase tracking-wider mb-2">Perizinan Lain</label>
                                        <div class="relative">
                                            <select wire:model.live="payload.has_existing_permits" required class="w-full appearance-none px-4 py-3 rounded-xl border border-indigo-200 bg-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition-all cursor-pointer font-medium text-slate-700 shadow-sm">
                                                <option value="">-- Pilih --</option>
                                                <option value="1">Ada Izin Lain</option>
                                                <option value="0">Belum Ada</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-indigo-400"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quill CSS & JS (moved to top visually, but placed here) -->
                <style>
                    .ql-toolbar.ql-snow { border-top: none; border-left: none; border-right: none; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px; }
                    .ql-container.ql-snow { border: none; font-family: inherit; font-size: 14.5px; }
                    .ql-editor { min-height: 250px; padding: 20px; }
                </style>
            </div>
            
        <!-- ==============================
             BAB 2-5: AI CHATBOT
        =============================== -->
        @elseif(in_array($currentChapter, ['bag-2', 'bag-3', 'bag-4', 'bag-5']))
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden relative p-6 md:p-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-extrabold text-slate-900">
                        Bab {{ str_replace('bag-', '', $currentChapter) }} — Interaksi Cerdas Bersama AI
                    </h2>
                    <p class="text-sm text-slate-500 mt-3 max-w-2xl mx-auto leading-relaxed">
                        Anda tidak perlu mengisi formulir panjang. Cukup jawab pertanyaan yang diajukan oleh Asisten AI kami seperti sedang mengobrol biasa. AI akan menyusun kalimat baku secara otomatis untuk Anda.
                    </p>
                </div>
                
                <div class="w-full">
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('kkprl-ai-chat', ['proposalId' => $proposalId, 'chapter' => $currentChapter]);

$__keyOuter = $__key ?? null;

$__key = 'chat-'.$currentChapter;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1374602930-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split) !!}
                </div>
            </div>
        @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif

        <!-- ==============================
             LAMPIRAN (ATTACHMENTS)
        =============================== -->
        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden relative mt-8">
            <div class="bg-slate-50 border-b border-slate-100 p-6 md:p-8">
                <h2 class="text-xl font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-paperclip mr-3 text-slate-400"></i> Lampiran Dokumen Bab Ini
                </h2>
                <p class="text-sm text-slate-500 mt-2">Maks. 10 file & 15 MB per bab (PDF, JPG, PNG, WebP).</p>
            </div>
            
            <div class="p-6 md:p-8 space-y-8">
                <!-- Upload Area (Drag & Drop UI mimic) -->
                <div class="border-2 border-dashed border-slate-200 hover:border-blue-400 bg-slate-50 hover:bg-blue-50/30 transition-all rounded-2xl p-8 relative flex flex-col items-center justify-center text-center group cursor-pointer">
                    <input id="kkprl-pending-files" name="pending_files[]" wire:model="pendingFiles" type="file" multiple accept="application/pdf,image/jpeg,image/png,image/webp" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                    <div class="w-16 h-16 bg-white rounded-full shadow-sm flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-blue-500"></i>
                    </div>
                    <p class="font-bold text-slate-700 text-lg">Tarik & Letakkan File Di Sini</p>
                    <p class="text-sm text-slate-400 mt-1">Atau klik area ini untuk mencari file di komputer Anda</p>
                    
                    <div wire:loading wire:target="pendingFiles" class="mt-4 text-blue-600 font-medium text-sm flex items-center bg-blue-100 px-4 py-2 rounded-lg">
                        <i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Membaca file...
                    </div>
                </div>

                <!-- Preview Selected Files -->
                @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($pendingFiles)
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                        <h4 class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-3"><i class="fa-solid fa-list-check mr-2"></i>File Siap Diunggah</h4>
                        <div class="flex flex-wrap gap-2">
                            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?>@endif<?php $__currentLoopData = $pendingFiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?>@endif
                                <div class="bg-white px-3 py-2 rounded-lg shadow-sm border border-blue-100 text-sm font-medium text-slate-700 flex items-center">
                                    <i class="fa-solid fa-file-lines text-blue-400 mr-2"></i>
                                    <span class="truncate max-w-[200px]">{{ is_string($file) ? $file : $file->getClientOriginalName() }}</span>
                                </div>
                            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?>@endif@endforeach@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?>@endif
                        </div>
                    </div>
                @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif

                <!-- Attachment Settings Form -->
                <div class="grid md:grid-cols-2 gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                    <div>
                        <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Penempatan File</label>
                        <div class="relative">
                            <select wire:model="attachmentPlacement" class="w-full appearance-none px-4 py-3 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium transition-all shadow-sm">
                                <option value="appendix">Taruh di Lampiran Belakang</option>
                                <option value="inline">Sisipkan di Tengah Dokumen (Inline)</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Teks Rujukan (Jika Inline)</label>
                        <select wire:model="attachmentAnchor" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium transition-all shadow-sm" {{ $attachmentPlacement !== 'inline' ? 'disabled' : '' }}>
                            <option value="">-- Pilih Form Tempat Menyisipkan --</option>
                            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?>@endif<?php $__currentLoopData = \App\Domain\Kkprl\ProposalFieldCatalog::definitionsForPayload($attachmentChapter, $payload); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anchorDefinition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?>@endif
                                <option value="{{ $anchorDefinition['key'] }}">{{ $anchorDefinition['label'] }}</option>
                            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?>@endif@endforeach@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?>@endif
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Pilih form ini hanya jika Anda memilih Penempatan File: Inline.</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[13px] font-bold text-slate-500 uppercase tracking-wider mb-2">Keterangan / Caption Gambar</label>
                        <input wire:model="attachmentCaption" type="text" placeholder="Beri deskripsi singkat untuk foto/dokumen ini..." class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium transition-all shadow-sm">
                    </div>
                </div>

                @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif<?php $__errorArgs = ['attachments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm font-bold text-red-600 bg-red-50 p-3 rounded-lg"><i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ $message }}</p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
                @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($attachmentMessage) <p class="text-sm font-bold text-emerald-600 bg-emerald-50 p-3 rounded-lg"><i class="fa-solid fa-check mr-1"></i> {{ $attachmentMessage }}</p> @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif

                <div class="flex justify-end">
                    <button type="button" wire:click="uploadAttachments" wire:loading.attr="disabled" wire:target="uploadAttachments, pendingFiles" class="px-8 py-3.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl shadow-lg transition-all disabled:opacity-50 flex items-center">
                        <i class="fa-solid fa-cloud-arrow-up mr-2" wire:loading.remove wire:target="uploadAttachments"></i>
                        <i class="fa-solid fa-circle-notch fa-spin mr-2" wire:loading wire:target="uploadAttachments"></i>
                        <span wire:loading.remove wire:target="uploadAttachments">Unggah Sekarang</span>
                        <span wire:loading wire:target="uploadAttachments">Mengunggah...</span>
                    </button>
                </div>

                <!-- Uploaded Files List -->
                @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($chapterAttachments->isNotEmpty())
                    <div class="pt-8 border-t border-slate-200">
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4"><i class="fa-solid fa-folder-open mr-2 text-slate-400"></i> File Terunggah</h3>
                        <div class="grid sm:grid-cols-2 gap-4">
                            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?>@endif<?php $__currentLoopData = $chapterAttachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?>@endif
                                <div <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'chapter-attachment-'.e($attachment->id).''; ?>wire:key="chapter-attachment-{{ $attachment->id }}" class="flex items-center justify-between p-4 bg-white border border-slate-200 rounded-xl shadow-sm hover:border-slate-300 transition-colors">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <div class="w-10 h-10 rounded bg-blue-50 text-blue-500 flex items-center justify-center shrink-0">
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </div>
                                        <div class="overflow-hidden">
                                            <p class="font-bold text-sm text-slate-700 truncate" title="{{ $attachment->original_name }}">{{ $attachment->original_name }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">
                                                {{ strtoupper($attachment->placement) }} • {{ number_format($attachment->size / 1024, 1) }} KB
                                            </p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="deleteAttachment({{ $attachment->id }})" wire:confirm="Yakin ingin menghapus file ini?" class="w-8 h-8 rounded-full bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center shrink-0 ml-2" title="Hapus File">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </button>
                                </div>
                            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?>@endif@endforeach@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?>@endif
                        </div>
                    </div>
                @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
            </div>
        </div>

        <!-- ==============================
             NAVIGATION FOOTER
        =============================== -->
        <div class="flex flex-col-reverse md:flex-row items-center justify-between gap-4 pt-8">
            <button type="button" wire:click="previous" @if($currentStep === 1): echo 'disabled'; endif; ?> class="w-full md:w-auto px-6 py-4 bg-white border-2 border-slate-200 text-slate-600 font-bold rounded-2xl hover:bg-slate-50 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                <i class="fa-solid fa-arrow-left mr-2"></i> Halaman Sebelumnya
            </button>
            
            <div class="w-full md:w-auto flex flex-col md:flex-row gap-3">
                <button type="submit" formnovalidate wire:loading.attr="disabled" class="w-full md:w-auto px-6 py-4 bg-slate-800 text-white font-bold rounded-2xl shadow-lg hover:bg-slate-900 transition-all flex items-center justify-center">
                    <i class="fa-regular fa-floppy-disk mr-2" wire:loading.remove></i>
                    <i class="fa-solid fa-circle-notch fa-spin mr-2" wire:loading></i>
                    <span wire:loading.remove>Simpan Draft</span>
                    <span wire:loading>Menyimpan...</span>
                </button>
                
                <button type="button" wire:click="next" <?php if($currentStep === count($relevantChapters)): echo 'disabled'; endif; ?> class="w-full md:w-auto px-8 py-4 bg-blue-600 text-white font-bold rounded-2xl shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition-all flex items-center justify-center disabled:opacity-40 disabled:cursor-not-allowed">
                    Selanjutnya <i class="fa-solid fa-arrow-right ml-2"></i>
                </button>
                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($currentStep === count($relevantChapters))
                    <button type="button" wire:click="submitProposal" wire:loading.attr="disabled" wire:target="submitProposal" class="w-full md:w-auto px-8 py-4 bg-gradient-to-r from-emerald-500 to-green-600 text-white font-bold rounded-2xl shadow-lg shadow-emerald-500/30 hover:from-emerald-600 hover:to-green-700 transition-all flex items-center justify-center">
                        <i class="fa-solid fa-paper-plane mr-2" wire:loading.remove wire:target="submitProposal"></i>
                        <i class="fa-solid fa-circle-notch fa-spin mr-2" wire:loading wire:target="submitProposal"></i>
                        <span wire:loading.remove wire:target="submitProposal">Kirim ke Petugas</span>
                        <span wire:loading wire:target="submitProposal">Memproses...</span>
                    </button>
                @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
            </div>
        </div>
        
        @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($saved)
            <div class="fixed bottom-6 right-6 bg-slate-900 text-white px-6 py-3 rounded-2xl shadow-2xl flex items-center animate-bounce z-50">
                <i class="fa-solid fa-check-circle text-emerald-400 mr-3 text-xl"></i>
                <span class="font-medium text-sm">Draft berhasil disimpan otomatis.</span>
            </div>
        @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
    </form>
    @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif

    <!-- ==============================
         DOWNLOAD DOCUMENTS SECTION
    =============================== -->
    <div class="mt-16 bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden relative">
        <div class="bg-amber-50 border-b border-amber-100 p-6 md:p-8 flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 shrink-0">
                <i class="fa-solid fa-file-word text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-amber-900">Download Draft Otomatis</h2>
                <p class="text-sm text-amber-700 mt-1">Dokumen Word (DOCX) dan PDF dibuat secara privat berdasarkan isian Anda.</p>
            </div>
        </div>
        
        <div class="p-6 md:p-8">
            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif<?php $__errorArgs = ['documents'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mb-4 text-sm font-bold text-red-600 bg-red-50 p-3 rounded-lg">{{ $message }}</p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($documentMessage) <p class="mb-4 text-sm font-bold text-emerald-600 bg-emerald-50 p-3 rounded-lg">{{ $documentMessage }}</p> @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
            
            <div class="grid md:grid-cols-2 gap-4">
                @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?>@endif<?php $__currentLoopData = $relevantChapters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chapter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?>@endif
                    <?php ($chapterState = $chapterProgress[$chapter] ?? ['status' => 'incomplete']); ?>
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 rounded-2xl border border-slate-200 p-5 hover:border-amber-300 transition-colors bg-white">
                        <div class="text-center sm:text-left">
                            <div class="font-bold text-slate-800 text-lg">{{ str_replace('bag-', 'Bab ', $chapter) }}</div>
                            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($chapterState['status'] === 'complete')
                                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md inline-block mt-1">Data Lengkap</span>
                            @else
                                <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-md inline-block mt-1">Data Belum Lengkap</span>
                            @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
                        </div>
                        
                        @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($chapterState['status'] === 'complete')
                            <button type="button" wire:click="generateChapterDocuments('{{ $chapter }}')" wire:loading.attr="disabled" wire:target="generateChapterDocuments" class="w-full sm:w-auto px-5 py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-800 font-bold rounded-xl transition-colors flex items-center justify-center disabled:opacity-50">
                                <i class="fa-solid fa-gears mr-2" wire:loading.remove wire:target="generateChapterDocuments"></i>
                                <i class="fa-solid fa-circle-notch fa-spin mr-2" wire:loading wire:target="generateChapterDocuments"></i>
                                <span wire:loading.remove wire:target="generateChapterDocuments">Buat Dokumen</span>
                                <span wire:loading wire:target="generateChapterDocuments">Membuat...</span>
                            </button>
                        @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
                    </div>
                @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?>@endif@endforeach@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?>@endif
            </div>

            @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($generatedDocuments->isNotEmpty())
                <div class="mt-8 pt-8 border-t border-slate-200">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4">File Siap Unduh</h3>
                    <div class="flex flex-wrap gap-3">
                        @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?>@endif<?php $__currentLoopData = $generatedDocuments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?>@endif
                            <a href="{{ route('kkprl.proposal.document.download', $document) }}" class="inline-flex items-center px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm hover:border-amber-400 hover:shadow-md transition-all group">
                                @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if BLOCK]><![endif]-->@endif@if($document->format === 'pdf')
                                    <i class="fa-solid fa-file-pdf text-red-500 text-xl mr-3 group-hover:scale-110 transition-transform"></i>
                                @else
                                    <i class="fa-solid fa-file-word text-blue-600 text-xl mr-3 group-hover:scale-110 transition-transform"></i>
                                @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
                                <div>
                                    <p class="font-bold text-slate-700 text-sm">{{ str_replace('bag-', 'Bab ', $document->chapter) }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">{{ $document->format }}</p>
                                </div>
                                <i class="fa-solid fa-download ml-4 text-slate-300 group-hover:text-amber-600"></i>
                            </a>
                        @if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?>@endif@endforeach@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?>@endif
                    </div>
                </div>
            @endif@if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent())<!--[if ENDBLOCK]><![endif]-->@endif
        </div>
    </div>
</main>
<script>
    function coordinateManager(initialRaw, initialShape, wire) {
        return {
            points: [],
            shapeType: initialShape || 'polygon',
            map: null,
            layerGroup: null,
            syncTimeout: null,
            isDrawing: false,

            init() {
                try {
                    let initialData = initialRaw;
                    if (typeof initialData === 'string') initialData = JSON.parse(initialData);
                    
                    if (Array.isArray(initialData) && initialData.length > 0) {
                        this.points = initialData;
                    } else {
                        this.addPoint();
                    }
                } catch(e) {
                    this.addPoint();
                }
                
                this.$watch('points', (value) => {
                    if(!this.isDrawing) this.drawOnMap();
                    
                    let raw = JSON.stringify(value);
                    let text = this.formatPointsText(value);
                    
                    if (this.syncTimeout) clearTimeout(this.syncTimeout);
                    this.syncTimeout = setTimeout(() => {
                        wire.updateCoordinates(raw, text, this.shapeType);
                    }, 500);
                }, { deep: true });

                this.$watch('shapeType', (value) => {
                    this.drawOnMap();
                    let raw = JSON.stringify(this.points);
                    let text = this.formatPointsText(this.points);
                    wire.updateCoordinates(raw, text, this.shapeType);
                });

                setTimeout(() => {
                    this.initMap();
                }, 200);
            },

            initMap() {
                if (typeof L === 'undefined') return;
                
                let center = [-2.5489, 118.0149];
                let zoom = 5;

                this.map = L.map('coordinate-map').setView(center, zoom);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(this.map);
                
                this.layerGroup = L.featureGroup().addTo(this.map);

                this.map.on('click', (e) => {
                    this.addPointFromMap(e.latlng.lat, e.latlng.lng);
                });

                this.drawOnMap();

                setTimeout(() => {
                    if (this.layerGroup.getLayers().length > 0) {
                        this.map.fitBounds(this.layerGroup.getBounds(), { padding: [30, 30], maxZoom: 14 });
                    }
                }, 500);
            },

            addPointFromMap(lat, lng) {
                this.isDrawing = true;
                
                let dmsLat = this.calcDMS(lat, true);
                let dmsLng = this.calcDMS(lng, false);
                
                let newPoint = {
                    lat_dd: lat.toFixed(6), lat_d: dmsLat.d, lat_m: dmsLat.m, lat_s: dmsLat.s, lat_dir: dmsLat.dir,
                    lng_dd: lng.toFixed(6), lng_d: dmsLng.d, lng_m: dmsLng.m, lng_s: dmsLng.s, lng_dir: dmsLng.dir
                };

                let isDefaultEmpty = this.points.length === 1 && this.points[0].lat_dd === '' && this.points[0].lng_dd === '';
                
                if (isDefaultEmpty) {
                    this.points[0] = newPoint;
                } else {
                    this.points.push(newPoint);
                }
                
                this.drawOnMap();
                this.isDrawing = false;
            },

            updatePointFromMap(index, lat, lng) {
                this.isDrawing = true;
                let dmsLat = this.calcDMS(lat, true);
                let dmsLng = this.calcDMS(lng, false);
                
                this.points[index].lat_dd = lat.toFixed(6);
                this.points[index].lat_d = dmsLat.d;
                this.points[index].lat_m = dmsLat.m;
                this.points[index].lat_s = dmsLat.s;
                this.points[index].lat_dir = dmsLat.dir;

                this.points[index].lng_dd = lng.toFixed(6);
                this.points[index].lng_d = dmsLng.d;
                this.points[index].lng_m = dmsLng.m;
                this.points[index].lng_s = dmsLng.s;
                this.points[index].lng_dir = dmsLng.dir;
                
                this.drawOnMap();
                this.isDrawing = false;
            },

            drawOnMap() {
                if (!this.map || !this.layerGroup) return;
                this.layerGroup.clearLayers();
                
                let latlngs = [];
                this.points.forEach((p, index) => {
                    let lat = parseFloat(p.lat_dd);
                    let lng = parseFloat(p.lng_dd);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        latlngs.push([lat, lng]);
                        
                        let marker = L.marker([lat, lng], { draggable: true }).addTo(this.layerGroup);
                        marker.bindTooltip("Titik " + (index + 1), {permanent: true, direction: 'top', className: "bg-blue-600 text-white font-bold text-[10px] border-0 rounded px-1.5 py-0.5", offset: [0, -35] });
                        
                        marker.on('dragend', (e) => {
                            let newPos = e.target.getLatLng();
                            this.updatePointFromMap(index, newPos.lat, newPos.lng);
                        });
                    }
                });

                if (latlngs.length > 1) {
                    if (this.shapeType === 'polygon' && latlngs.length >= 3) {
                        L.polygon(latlngs, { color: '#ef4444', fillColor: '#ef4444', fillOpacity: 0.3, weight: 3 }).addTo(this.layerGroup);
                    } else if (this.shapeType === 'line' || (this.shapeType === 'polygon' && latlngs.length === 2)) {
                        L.polyline(latlngs, { color: '#ef4444', weight: 4 }).addTo(this.layerGroup);
                    }
                }
            },

            clearMap() {
                this.points = [{
                    lat_dd: '', lat_d: '', lat_m: '', lat_s: '', lat_dir: 'S',
                    lng_dd: '', lng_d: '', lng_m: '', lng_s: '', lng_dir: 'E'
                }];
                if (this.map) this.map.setView([-2.5489, 118.0149], 5);
            },

            calcDMS(dd, isLat) {
                let dir = dd < 0 ? (isLat ? 'S' : 'W') : (isLat ? 'N' : 'E');
                let absDd = Math.abs(dd);
                let d = Math.floor(absDd);
                let minFloat = (absDd - d) * 60;
                let m = Math.floor(minFloat);
                let s = ((minFloat - m) * 60).toFixed(4);
                return { d, m, s, dir };
            },

            addPoint() {
                this.points.push({
                    lat_dd: '', lat_d: '', lat_m: '', lat_s: '', lat_dir: 'S',
                    lng_dd: '', lng_d: '', lng_m: '', lng_s: '', lng_dir: 'E'
                });
            },
            removePoint(index) {
                this.points.splice(index, 1);
                if (this.points.length === 0) {
                    this.addPoint();
                }
            },
            syncFromDD(index, isLat) {
                let point = this.points[index];
                let dd = parseFloat(isLat ? point.lat_dd : point.lng_dd);
                
                if (isNaN(dd)) {
                    if (isLat) { point.lat_d = ''; point.lat_m = ''; point.lat_s = ''; }
                    else { point.lng_d = ''; point.lng_m = ''; point.lng_s = ''; }
                    return;
                }
                
                let dms = this.calcDMS(dd, isLat);
                
                if (isLat) {
                    point.lat_d = dms.d; point.lat_m = dms.m; point.lat_s = dms.s; point.lat_dir = dms.dir;
                } else {
                    point.lng_d = dms.d; point.lng_m = dms.m; point.lng_s = dms.s; point.lng_dir = dms.dir;
                }
            },
            syncFromDMS(index, isLat) {
                let point = this.points[index];
                
                let d = parseFloat(isLat ? point.lat_d : point.lng_d) || 0;
                let m = parseFloat(isLat ? point.lat_m : point.lng_m) || 0;
                let s = parseFloat(isLat ? point.lat_s : point.lng_s) || 0;
                let dir = isLat ? point.lat_dir : point.lng_dir;
                
                let dd = d + (m / 60) + (s / 3600);
                if (dir === 'S' || dir === 'W') {
                    dd = dd * -1;
                }
                
                if (isLat) {
                    point.lat_dd = dd.toFixed(6);
                } else {
                    point.lng_dd = dd.toFixed(6);
                }
            },
            formatPointsText(pts) {
                return pts.map((p, i) => {
                    if (!p.lat_dd && !p.lng_dd) return null;
                    return `Titik ${i+1}: Latitude ${p.lat_d}° ${p.lat_m}' ${p.lat_s}" ${p.lat_dir} (${p.lat_dd}), Longitude ${p.lng_d}° ${p.lng_m}' ${p.lng_s}" ${p.lng_dir} (${p.lng_dd})`;
                }).filter(Boolean).join('\n');
            }
        }
    }
</script>
<?php /**PATH C:\laragon\www\listdatakkprl\resources\views/livewire/kkprl-proposal-wizard.blade.php ENDPATH**/ ?>