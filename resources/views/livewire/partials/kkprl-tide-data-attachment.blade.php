<article wire:key="tide-data-{{ $attachment->id }}" class="min-w-0 space-y-3 rounded-xl border border-blue-200 bg-blue-50 p-3 sm:p-4">
    <h4 class="text-sm font-semibold text-slate-900">Data tabel pasang surut — {{ $attachment->original_name }}</h4>
    <p class="break-words text-xs text-slate-600">{{ $attachment->caption }}</p>
    @if(isset($attachment->source_metadata['tide_summary']))
        @php($tideSummary = $attachment->source_metadata['tide_summary'])
        <p class="text-sm leading-relaxed text-slate-700">{{ number_format($tideSummary['count'], 0, ',', '.') }} sampel; {{ $tideSummary['start'] }} sampai {{ $tideSummary['end'] }} ({{ $tideSummary['timezone'] }}). Acuan tinggi: {{ $tideSummary['datum'] }}.</p>
        @if(($tideSummary['data_kind'] ?? '') === 'prediction')
            <p class="text-sm font-medium text-slate-700">Sumber berisi prediksi pasang surut, bukan pengukuran langsung.</p>
        @endif
        @if(isset($tideSummary['harmonic']))
            @include('livewire.partials.kkprl-tide-harmonic-summary', ['harmonic' => $tideSummary['harmonic']])
        @else
            <p class="text-sm text-slate-600">Klik Hitung data pasut untuk memeriksa estimasi Formzahl dan membuat narasi awal. Proses ini tidak memerlukan AI.</p>
        @endif
        <details class="rounded-lg border border-blue-200 bg-white p-3">
            <summary class="cursor-pointer text-sm font-semibold text-slate-700">Lihat ringkasan lengkap dan keterbatasan data</summary>
            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ app(\App\Services\KkprlTideDataAnalyzer::class)->report($tideSummary) }}</p>
        </details>
    @endif
    <a href="{{ route('kkprl.proposal.attachment.download', $attachment) }}" class="inline-block text-sm text-blue-700 underline">Unduh data pasut</a>
    <p class="text-xs text-slate-600">Jika ada beberapa tabel, analisis menggunakan tabel yang terakhir disimpan. Hapus tabel yang tidak diperlukan.</p>
    <div class="flex flex-wrap gap-2">
        <button type="button" wire:click="generateTideChart({{ $attachment->id }})" wire:loading.attr="disabled" wire:target="generateTideChart,calculateTideData,analyzeTideData,analyzeEvidence,uploadTideData,uploadEvidence,tideFile,evidenceFile,sendMessage,confirmAndSave,deleteTideData,deleteEvidence,generateSeabedProfile,generateBathymetry" @disabled($locked) class="rounded-lg border border-blue-300 bg-white px-3 py-2 text-sm font-semibold text-blue-700 disabled:opacity-50">Buat grafik pasut</button>
        <button type="button" wire:click="calculateTideData({{ $attachment->id }})" @if(trim($previewData['tide'] ?? '') !== '') wire:confirm="Ganti narasi pasut di pratinjau dengan narasi awal dari hasil perhitungan terbaru?" @endif wire:loading.attr="disabled" wire:target="calculateTideData,generateTideChart,analyzeTideData,analyzeEvidence,uploadTideData,uploadEvidence,tideFile,evidenceFile,sendMessage,confirmAndSave,deleteTideData,deleteEvidence,generateSeabedProfile,generateBathymetry" @disabled($locked) class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50">Hitung data pasut</button>
        <button type="button" wire:click="analyzeTideData({{ $attachment->id }})" wire:loading.attr="disabled" wire:target="calculateTideData,generateTideChart,analyzeTideData,analyzeEvidence,uploadTideData,uploadEvidence,tideFile,evidenceFile,sendMessage,confirmAndSave,deleteTideData,deleteEvidence,generateSeabedProfile,generateBathymetry" @disabled($locked) class="rounded-lg border border-blue-300 bg-white px-3 py-2 text-sm font-semibold text-blue-700 disabled:opacity-50">Rapikan narasi dengan AI</button>
        <button type="button" wire:click="deleteTideData({{ $attachment->id }})" wire:confirm="Hapus data pasut ini? Narasi tetap dapat Anda tinjau dan edit." wire:loading.attr="disabled" wire:target="analyzeTideData,calculateTideData,generateTideChart,analyzeEvidence,uploadTideData,uploadEvidence,sendMessage,confirmAndSave,deleteTideData,deleteEvidence,generateSeabedProfile,generateBathymetry" @disabled($locked) class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm text-red-700 disabled:opacity-50">Hapus data pasut</button>
    </div>
    @error('tideChart') <p class="text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
    <p class="text-xs leading-relaxed text-slate-600">Buat grafik pasut menghasilkan gambar PNG dari data tabel terbaru tanpa AI. Zona waktu, satuan meter, acuan tinggi dan sumber dicantumkan; celah data memutus garis. Grafik tampil di daftar bukti dan ikut ekspor Bab 3. Narasi yang sudah Anda koreksi tetap tersedia.</p>
    <p class="text-xs leading-relaxed text-slate-600">Hitung data pasut membuat narasi awal di pratinjau. Narasi AI opsional; jika AI tidak tersedia, hasil perhitungan tetap dapat ditinjau dan disimpan.</p>
</article>
