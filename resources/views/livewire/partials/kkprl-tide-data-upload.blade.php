<div class="min-w-0 space-y-3 rounded-xl border-2 border-dashed border-blue-300 bg-blue-50 p-4">
    <h4 class="font-semibold text-slate-900">Unggah data tabel pasang surut</h4>
    <p id="tide-file-help" class="text-sm text-slate-700">Aplikasi menghitung angka dan membuat narasi awal tanpa AI. AI dapat dipakai setelahnya untuk merapikan narasi. Gunakan CSV UTF-8 atau workbook Excel asli. Tabel umum memakai judul kolom pada baris pertama, lalu tanggal/waktu dan tinggi muka air. CSV asli SRGI BIG dapat langsung diunggah dengan informasi stasiunnya; kolom waktu UTC dan tinggi dalam meter dipilih otomatis. Excel membaca worksheet pertama.</p>
    <label for="tide-file" class="block text-sm font-semibold text-slate-700">Pilih file data pasut</label>
    <input id="tide-file" type="file" wire:model="tideFile" accept=".csv,.xls,.xlsx" aria-describedby="tide-file-help" wire:loading.attr="disabled" wire:target="uploadEvidence,uploadTideData,analyzeEvidence,analyzeTideData,calculateTideData,generateTideChart,generateSeabedProfile,generateBathymetry" class="block w-full min-w-0 rounded-lg border border-blue-300 bg-white p-2 text-sm text-slate-700 file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:font-semibold file:text-white">
    @if($tideFile)
        <p class="break-all text-sm font-medium text-slate-800">File dipilih: {{ $tideFile->getClientOriginalName() }}</p>
    @else
        <p class="text-xs text-slate-600">Belum ada file data pasut dipilih.</p>
    @endif
    <p class="text-xs text-slate-600">CSV/XLS/XLSX maksimal 5 MB, {{ number_format(\App\Services\KkprlTideDataReader::MAX_ROWS, 0, ',', '.') }} baris data dan {{ \App\Services\KkprlTideDataReader::MAX_COLUMNS }} kolom (area Excel maksimal {{ number_format(\App\Services\KkprlTideDataReader::MAX_CELLS, 0, ',', '.') }} sel). Mendukung data satu bulan dengan interval 1 menit, termasuk 31 hari (44.641 sampel jika kedua batas waktu disertakan). Tidak menerima rumus; salin sebagai nilai. Batas bersama Bab 3: 10 file dan total 15 MB.</p>
    @error('tideFile') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
    <p wire:loading wire:target="tideFile" role="status" class="text-sm text-blue-700">Mengunggah dan membaca kolom data pasut...</p>
    <details class="rounded-lg bg-white p-3 text-sm text-slate-700">
        <summary class="cursor-pointer font-semibold">Lihat contoh susunan CSV</summary>
        <pre class="mt-2 whitespace-pre-wrap break-words text-xs">waktu,tinggi
2026-10-01 00:00,1.20
2026-10-01 01:00,1.45
2026-10-01 02:00,1.10</pre>
        <p class="mt-2 text-xs">Contoh memakai meter. Pemisah CSV boleh koma, titik koma atau tab. Gunakan titik/koma desimal tanpa pemisah ribuan. Pilih satuan sesuai file Anda.</p>
    </details>
    @if($tideColumns !== [])
        <p class="text-sm font-medium text-slate-700">Periksa kolom yang terdeteksi sebelum menyimpan:</p>
        @if($tideLocalTimezone !== null)
            <p class="text-xs text-slate-600">Pilihan zona waktu otomatis menyesuaikan kolom tanggal UTC atau lokal, dan sebaliknya. Kedua kolom sudah berisi tanggal serta jam lengkap; tidak perlu kolom jam terpisah.</p>
        @endif
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach(['tideDateColumn' => 'Kolom tanggal atau tanggal/waktu', 'tideHeightColumn' => 'Kolom tinggi muka air', 'tideTimeColumn' => 'Kolom jam terpisah (opsional)'] as $property => $label)
                <div>
                    <label for="{{ $property }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                    <select id="{{ $property }}" wire:model.live="{{ $property }}" @disabled($property === 'tideTimeColumn' && $tideLocalTimezone !== null) class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm">
                        @if($property === 'tideTimeColumn') <option value="">Jam sudah di kolom tanggal / tidak ada</option> @endif
                        @foreach($tideColumns as $index => $column) <option value="{{ $index }}" @disabled($property === 'tideDateColumn' && $tideLocalTimezone !== null && !in_array(strtolower(trim($column)), ['timestamp_utc', 'timestamp_local'], true))>{{ $index + 1 }}. {{ $column ?: 'Kolom tanpa judul' }}</option> @endforeach
                    </select>
                    @error($property) <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
    @endif
    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label for="tide-unit" class="block text-sm font-medium text-slate-700">Satuan tinggi dalam file</label>
            <select id="tide-unit" wire:model="tideUnit" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm">
                <option value="m">Meter (m)</option><option value="cm">Sentimeter (cm)</option><option value="mm">Milimeter (mm)</option>
            </select>
            @error('tideUnit') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="tide-timezone" class="block text-sm font-medium text-slate-700">Zona waktu dalam file</label>
            <select id="tide-timezone" wire:model.live="tideTimezone" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm">
                <option value="Asia/Jakarta" @disabled($tideLocalTimezone !== null && $tideLocalTimezone !== 'Asia/Jakarta')>WIB (UTC+7)</option><option value="Asia/Makassar" @disabled($tideLocalTimezone !== null && $tideLocalTimezone !== 'Asia/Makassar')>WITA (UTC+8)</option><option value="Asia/Jayapura" @disabled($tideLocalTimezone !== null && $tideLocalTimezone !== 'Asia/Jayapura')>WIT (UTC+9)</option><option value="UTC">UTC</option>
            </select>
            @error('tideTimezone') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="tide-date-format" class="block text-sm font-medium text-slate-700">Format tanggal teks dalam file</label>
            <select id="tide-date-format" wire:model="tideDateFormat" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm">
                <option value="ymd">Tahun-bulan-hari (2026-10-01)</option><option value="dmy">Hari/bulan/tahun (01/10/2026)</option>
            </select>
            <p class="mt-1 text-xs text-slate-600">Jam memakai 00:00 atau 00:00:00. Penanda UTC/WIB/WITA/WIT harus sesuai zona waktu pilihan. Untuk timestamp_utc pilih UTC; timestamp_local memakai zona yang tertera di data. Tanggal numerik Excel dibaca otomatis.</p>
            @error('tideDateFormat') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="tide-datum" class="block text-sm font-medium text-slate-700">Acuan nol tinggi muka air</label>
            <input id="tide-datum" wire:model="tideDatum" maxlength="200" placeholder="Contoh: nol palem stasiun atau Belum diketahui" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm">
            <p class="mt-1 text-xs text-slate-600">Isi sesuai keterangan penyedia data. Jangan menebak jika belum diketahui.</p>
            @error('tideDatum') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <button type="button" wire:click="uploadTideData" wire:loading.attr="disabled" wire:target="tideFile,evidenceFile,uploadTideData,uploadEvidence,analyzeTideData,calculateTideData,generateTideChart,analyzeEvidence,sendMessage,confirmAndSave,deleteTideData,deleteEvidence,generateSeabedProfile,generateBathymetry" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">Simpan data pasut &amp; sumber</button>
</div>
