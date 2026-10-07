<div class="min-w-0 space-y-4 rounded-xl border border-blue-200 bg-slate-50 p-4">
    <h4 class="font-semibold text-slate-900">Buat peta batimetri langsung dari GMRT</h4>
    <p class="text-sm leading-relaxed text-slate-600">Peta memakai semua titik lokasi Bab 1. Anda tidak perlu membuka situs lain atau mengambil screenshot. Sumber, legenda dan kredit akan dicatat otomatis.</p>
    @if($bathymetryReferencePoints === [])
        <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Lengkapi dan periksa semua koordinat Bab 1 sebelum membuat peta.</p>
    @else
        <p class="text-sm text-slate-700">{{ count($bathymetryReferencePoints) }} titik Bab 1 akan ditandai pada peta.</p>
    @endif
    <div>
        <label for="bathymetry-buffer" class="block text-sm font-medium text-slate-700">Jarak tambahan di sekitar lokasi</label>
        <select id="bathymetry-buffer" wire:model="bathymetryBufferKm" wire:loading.attr="disabled" wire:target="generateBathymetry" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm">
            <option value="0.5">0,5 km</option>
            <option value="1">1 km</option>
            <option value="2">2 km</option>
            <option value="5">5 km</option>
        </select>
        <p class="mt-1 text-xs text-slate-600">Menentukan area peta di sekeliling titik; batas permohonan tetap mengikuti Bab 1.</p>
        @error('bathymetryBufferKm') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    @error('bathymetryArea') <p role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $message }}</p> @enderror
    <button type="button" wire:click="generateBathymetry" @if(trim($previewData['bathymetry'] ?? '') !== '') wire:confirm="Buat peta baru dan ganti pratinjau Batimetri? Peta lama tetap tersimpan." @endif wire:loading.attr="disabled" wire:target="generateBathymetry,generateSeabedProfile,analyzeEvidence,analyzeTideData,calculateTideData,generateTideChart,sendMessage,confirmAndSave,uploadEvidence,uploadTideData,deleteEvidence,deleteTideData,evidenceFile,tideFile" @disabled($bathymetryReferencePoints === []) class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
        <span wire:loading.remove wire:target="generateBathymetry">Buat peta batimetri &amp; narasi awal</span>
        <span wire:loading wire:target="generateBathymetry">Mengambil grid GMRT...</span>
    </button>
    <p wire:loading wire:target="generateBathymetry" role="status" class="text-sm text-blue-700">Mengambil data dan membuat peta. Peta dan narasi sebelumnya tetap tersedia.</p>
    <p class="text-xs leading-relaxed text-slate-600">Data sekunder gratis dengan kredit CC BY 4.0. Angka berlaku untuk seluruh area peta. Ketelitian asli setempat dapat lebih rendah daripada jarak antar sel; perlu verifikasi lapangan. Peta tidak digunakan untuk navigasi.</p>
</div>
