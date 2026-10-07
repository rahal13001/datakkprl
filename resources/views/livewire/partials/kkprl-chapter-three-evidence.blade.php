<section class="min-w-0 rounded-2xl border border-blue-200 bg-white p-4 shadow-sm sm:p-6" aria-labelledby="evidence-heading">
    <h3 id="evidence-heading" class="text-lg font-bold text-slate-900">Bukti dan analisis kondisi perairan</h3>
    <p class="mt-2 text-sm leading-relaxed text-slate-600">Pilih topik yang ingin dibahas. Anda dapat mengunggah gambar, memakai data CSV/Excel untuk pasang surut, atau membuat peta batimetri dan penampang dasar laut langsung di sini. Hasilnya tersedia untuk Anda tinjau. Semua bukti bersifat opsional; Anda tetap dapat bercerita melalui chat.</p>

    @if($evidenceCoordinates !== '')
        <details class="mt-4 rounded-xl bg-slate-50 p-3">
            <summary class="cursor-pointer text-sm font-semibold text-slate-700">Lihat koordinat lokasi dari Bab 1</summary>
            <p class="mt-2 text-xs text-slate-600">Gunakan titik lokasi ini saat mencari area pada peta sumber. Anda dapat memilih dan menyalin teksnya.</p>
            <pre class="mt-2 whitespace-pre-wrap break-words text-xs text-slate-800">{{ $evidenceCoordinates }}</pre>
        </details>
    @else
        <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">Koordinat Bab 1 belum tersedia. Lengkapi lokasi agar gambar peta yang Anda pilih sesuai dengan lokasi usaha.</p>
    @endif

    <fieldset @disabled($locked) class="mt-5 min-w-0 space-y-4">
        <legend class="font-semibold text-slate-800">1. Pilih bagian dan sumber bukti</legend>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="evidence-field" class="block text-sm font-medium text-slate-700">Bagian yang ingin dijelaskan</label>
                <select id="evidence-field" wire:model.live="evidenceField" wire:loading.attr="disabled" wire:target="analyzeEvidence,analyzeTideData,calculateTideData,generateTideChart,uploadEvidence,uploadTideData,generateSeabedProfile,generateBathymetry" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm">
                    @foreach($evidenceFields as $key => $definition)
                        <option value="{{ $key }}">{{ $definition['label'] }}</option>
                    @endforeach
                </select>
                @error('evidenceField') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="evidence-source" class="block text-sm font-medium text-slate-700">Sumber gambar atau data</label>
                <select id="evidence-source" wire:model.live="evidenceSource" wire:loading.attr="disabled" wire:target="analyzeEvidence,analyzeTideData,calculateTideData,generateTideChart,uploadEvidence,uploadTideData,generateSeabedProfile,generateBathymetry" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm">
                    @foreach($evidenceSources as $key => $source)
                        <option value="{{ $key }}">{{ $source['label'] }}</option>
                    @endforeach
                </select>
                @error('evidenceSource') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        @php($selectedSource = $evidenceSources[$evidenceSource] ?? $evidenceSources['other'])
        <div class="space-y-2 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-slate-700">
            @if($selectedSource['url'] !== '')
                <a href="{{ $selectedSource['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-block font-semibold text-blue-700 underline">Buka {{ $selectedSource['label'] }} ↗</a>
            @endif
            <p>{{ $evidenceFields[$evidenceField]['guide'] ?? 'Pilih bagian yang ingin dijelaskan.' }}</p>
            @if(!in_array($evidenceField, ['seabed_profile', 'bathymetry']))
                <p>Cari lokasi sesuai koordinat Bab 1. Ambil tangkapan layar yang memperlihatkan penanda lokasi, legenda warna, skala/satuan dan tanggal data. Untuk foto lapangan, sertakan lokasi dan tanggal pengambilan.</p>
            @endif
            <p class="text-xs leading-relaxed text-slate-600">{{ $selectedSource['notice'] }}</p>
            @if(isset($selectedSource['terms_url']))
                <a href="{{ $selectedSource['terms_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-block text-xs text-blue-700 underline">Baca ketentuan sumber ↗</a>
            @endif
        </div>

        @if($evidenceField === 'bathymetry')
            @include('livewire.partials.kkprl-bathymetry-builder')
            <p class="text-sm text-slate-600">Memiliki gambar dari sumber lain? Form unggah di bawah tetap tersedia. Peta yang dibuat langsung selalu memakai GMRT; catatan sumber berikut berlaku untuk gambar unggahan.</p>
        @endif
        @if($evidenceField === 'seabed_profile')
            @include('livewire.partials.kkprl-seabed-profile-builder')
            <p class="text-sm text-slate-600">Sudah memiliki grafik dari sumber lain? Gunakan form unggah gambar di bawah. Catatan sumber berikut berlaku untuk gambar yang Anda unggah; grafik yang dibuat langsung memakai sumber dan kredit GMRT otomatis.</p>
        @endif
        @if($evidenceField === 'bathymetry')
            <details class="rounded-xl border border-slate-200 p-3">
                <summary class="cursor-pointer text-sm font-semibold text-blue-700">Unggah gambar batimetri dari sumber lain (opsional)</summary>
                <div class="mt-4 space-y-4">
        @endif
        <h4 class="font-semibold text-slate-800">2. Catat sumber dan unggah bukti</h4>
        @if($evidenceSource === 'other')
            <div>
                <label for="evidence-source-name" class="block text-sm font-medium text-slate-700">Nama sumber</label>
                <input id="evidence-source-name" wire:model="evidenceSourceName" maxlength="200" class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm">
                @error('evidenceSourceName') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif
        <div>
            <label for="evidence-url" class="block text-sm font-medium text-slate-700">Tautan sumber @if($evidenceSource === 'field_photo') (opsional) @endif</label>
            <input id="evidence-url" type="url" wire:model="evidenceUrl" maxlength="2000" placeholder="https://..." class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm">
            @error('evidenceUrl') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="evidence-period" class="block text-sm font-medium text-slate-700">Tahun/periode data atau tanggal foto</label>
                <input id="evidence-period" wire:model="evidencePeriod" maxlength="200" placeholder="Contoh: 2025 atau 5 Oktober 2026" class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">Boleh kosong jika belum diketahui; jangan menebak.</p>
                @error('evidencePeriod') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="evidence-accessed" class="block text-sm font-medium text-slate-700">Tanggal akses atau dokumentasi</label>
                <input id="evidence-accessed" type="date" wire:model="evidenceAccessedAt" max="{{ now()->toDateString() }}" class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm">
                @error('evidenceAccessedAt') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label for="evidence-credit" class="block text-sm font-medium text-slate-700">Kredit pembuat atau penyedia data</label>
            <textarea id="evidence-credit" wire:model="evidenceCredit" rows="2" maxlength="500" class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm"></textarea>
            <p class="mt-1 text-xs text-slate-500">Tambahkan versi, nama/ID produk, stasiun atau kredit dataset yang tertera pada gambar sumber.</p>
            @error('evidenceCredit') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="evidence-notes" class="block text-sm font-medium text-slate-700">Keterangan gambar atau data (opsional)</label>
            <textarea id="evidence-notes" wire:model="evidenceNotes" rows="2" maxlength="2000" placeholder="Contoh: penanda merah menunjukkan lokasi keramba; stasiun berada 5 km dari lokasi." class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm"></textarea>
            @error('evidenceNotes') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        @if($evidenceField === 'tide')
            <p class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">Pilih gambar, data tabel, atau keduanya. Jika tabel pasut sudah tersimpan, analisis akan mengutamakan tabel terbaru. Gambar tetap tersedia sebagai bukti pendukung.</p>
            @include('livewire.partials.kkprl-tide-data-upload')
        @endif
        <div class="min-w-0 space-y-3 rounded-xl border-2 border-dashed border-blue-300 bg-blue-50 p-4">
            <h4 class="font-semibold text-slate-900">Unggah gambar untuk dianalisis AI</h4>
            <p id="evidence-file-help" class="text-sm text-slate-600">Pilih gambar peta, grafik atau foto dari perangkat Anda, kemudian simpan gambar beserta sumbernya.</p>
            <label for="evidence-file" class="block text-sm font-semibold text-slate-700">Pilih gambar</label>
            <input id="evidence-file" type="file" wire:model="evidenceFile" accept="image/jpeg,image/png,image/webp" aria-describedby="evidence-file-help" wire:loading.attr="disabled" wire:target="uploadEvidence,uploadTideData,analyzeEvidence,analyzeTideData,calculateTideData,generateTideChart,generateSeabedProfile,generateBathymetry" class="block w-full min-w-0 rounded-lg border border-blue-300 bg-white p-2 text-sm text-slate-700 file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:font-semibold file:text-white">
            @if($evidenceFile)
                <p class="break-all text-sm font-medium text-slate-800">File dipilih: {{ $evidenceFile->getClientOriginalName() }}</p>
            @else
                <p class="text-xs text-slate-600">Belum ada gambar dipilih.</p>
            @endif
            <p class="text-xs text-slate-600">JPG, PNG atau WebP, maksimal 5 MB per gambar. Semua lampiran Bab 3 berbagi batas 10 file dan total 15 MB.</p>
            @error('evidenceFile') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
            <p wire:loading wire:target="evidenceFile" role="status" class="text-sm text-blue-700">Mengunggah gambar, mohon tunggu...</p>
            <button type="button" wire:click="uploadEvidence" wire:loading.attr="disabled" wire:target="evidenceFile,tideFile,uploadEvidence,uploadTideData,analyzeEvidence,analyzeTideData,calculateTideData,generateTideChart,sendMessage,confirmAndSave,deleteEvidence,deleteTideData,generateSeabedProfile,generateBathymetry" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">Simpan gambar &amp; sumber</button>
        </div>
        @if($evidenceField === 'bathymetry')
                </div>
            </details>
        @endif
    </fieldset>

    @if($evidenceMessage)
        <p role="status" class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ $evidenceMessage }}</p>
    @endif
    @if($evidenceAttachments->isNotEmpty())
        <div class="mt-6 space-y-3">
            <h4 class="font-semibold text-slate-800">3. Analisis bukti lalu tinjau narasi</h4>
            @php($hasTideData = $evidenceAttachments->contains('attachment_role', 'ai_tide_data'))
            @foreach($evidenceAttachments as $attachment)
                @if($attachment->attachment_role === 'ai_tide_data')
                    @include('livewire.partials.kkprl-tide-data-attachment')
                    @continue
                @endif
                <article wire:key="evidence-{{ $attachment->id }}" class="min-w-0 rounded-xl border border-slate-200 p-3 sm:p-4">
                    <div class="flex flex-col gap-3 {{ (isset($attachment->source_metadata['profile']) || isset($attachment->source_metadata['bathymetry']) || isset($attachment->source_metadata['tide_chart'])) ? '' : 'sm:flex-row' }}">
                        <img src="{{ route('kkprl.proposal.attachment.download', $attachment) }}" alt="Gambar pendukung {{ $evidenceFields[$attachment->field]['label'] ?? 'Bab 3' }}" loading="lazy" class="{{ (isset($attachment->source_metadata['profile']) || isset($attachment->source_metadata['bathymetry']) || isset($attachment->source_metadata['tide_chart'])) ? 'h-auto w-full sm:max-w-xl' : 'h-32 w-full sm:w-40' }} rounded-lg bg-slate-100 object-contain">
                        <div class="min-w-0 flex-1 space-y-2 text-sm">
                            <p class="font-semibold text-slate-800">{{ $evidenceFields[$attachment->field]['label'] ?? 'Gambar Bab 3' }}</p>
                            @if(isset($attachment->source_metadata['tide_chart']))
                                <p class="text-sm font-semibold text-slate-700">Grafik pasut dibuat backend dari {{ $attachment->source_metadata['tide_chart']['source_file_name'] }}.</p>
                                <p class="text-xs text-slate-600">Periksa nama file dan periode grafik jika ada beberapa tabel. Grafik yang sudah dibuat tetap tersedia jika data sumber dihapus.</p>
                                <details>
                                    <summary class="cursor-pointer text-xs font-semibold text-blue-700">Lihat sumber dan keterangan grafik</summary>
                                    <p class="mt-2 break-words text-xs leading-relaxed text-slate-600">{{ $attachment->caption }}</p>
                                </details>
                            @elseif(isset($attachment->source_metadata['bathymetry']))
                                <p class="text-xs text-slate-600">GMRT · Diolah aplikasi · CC BY 4.0</p>
                                <details>
                                    <summary class="cursor-pointer text-xs font-semibold text-blue-700">Lihat sumber dan keterangan peta</summary>
                                    <p class="mt-2 break-words text-xs leading-relaxed text-slate-600">{{ $attachment->caption }}</p>
                                </details>
                            @else
                                <p class="break-words text-xs leading-relaxed text-slate-600">{{ $attachment->caption }}</p>
                            @endif
                            <a href="{{ route('kkprl.proposal.attachment.download', $attachment) }}" target="_blank" rel="noopener" class="inline-block text-blue-700 underline">{{ isset($attachment->source_metadata['tide_chart']) ? 'Lihat / unduh grafik PNG' : 'Unduh gambar' }}</a>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" wire:click="analyzeEvidence({{ $attachment->id }})" wire:loading.attr="disabled" wire:target="analyzeEvidence,analyzeTideData,calculateTideData,generateTideChart,sendMessage,confirmAndSave,uploadEvidence,uploadTideData,deleteEvidence,deleteTideData,evidenceFile,tideFile,generateSeabedProfile,generateBathymetry" @disabled($locked) class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50">{{ $attachment->field === 'tide' && $hasTideData ? 'Rapikan narasi pasut dengan AI' : 'Analisis dengan AI' }}</button>
                        <button type="button" wire:click="deleteEvidence({{ $attachment->id }})" wire:confirm="Hapus gambar ini? Narasi tetap dapat Anda tinjau dan edit." wire:loading.attr="disabled" wire:target="analyzeEvidence,analyzeTideData,calculateTideData,generateTideChart,sendMessage,confirmAndSave,uploadEvidence,uploadTideData,deleteEvidence,deleteTideData,generateSeabedProfile,generateBathymetry" @disabled($locked) class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700 disabled:opacity-50">Hapus gambar</button>
                    </div>
                </article>
            @endforeach
            <p wire:loading wire:target="generateTideChart" role="status" class="text-sm text-blue-700">Backend sedang membuat grafik dari data pasut. Narasi Anda tetap tersedia.</p>
            <p wire:loading wire:target="calculateTideData" role="status" class="text-sm text-blue-700">Sistem sedang menghitung data pasut dan menyiapkan narasi awal tanpa AI.</p>
            <p wire:loading wire:target="analyzeEvidence,analyzeTideData" role="status" class="text-sm text-blue-700">AI sedang menganalisis bukti. Hasilnya akan muncul di Pratinjau Draft di bawah.</p>
        </div>
    @endif
</section>
