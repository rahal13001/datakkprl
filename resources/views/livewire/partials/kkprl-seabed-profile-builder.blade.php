<div class="min-w-0 space-y-4 rounded-xl border border-blue-200 bg-slate-50 p-4" wire:loading.class="pointer-events-none" wire:target="generateSeabedProfile,analyzeEvidence,analyzeTideData,calculateTideData,generateTideChart,sendMessage,confirmAndSave" x-data="kkprlProfileMap($wire, @js($profileReferencePoints), @js($locked))">
    <h4 class="font-semibold text-slate-900">Buat penampang dasar laut langsung di sini</h4>
    <p class="text-sm text-slate-600">Titik abu-abu adalah referensi lokasi Bab 1. Pilih A sebagai awal dan B sebagai akhir garis yang melewati area usaha, kemudian klik <strong>Buat grafik &amp; narasi awal</strong>. Koordinat Bab 1 tidak berubah.</p>
    <div class="flex flex-wrap gap-2">
        <button type="button" @click="mode = 'A'" :aria-pressed="mode === 'A'" :class="mode === 'A' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700'" class="rounded-lg border border-blue-300 px-3 py-2 text-sm font-semibold">Pilih titik A di peta</button>
        <button type="button" @click="mode = 'B'" :aria-pressed="mode === 'B'" :class="mode === 'B' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700'" class="rounded-lg border border-blue-300 px-3 py-2 text-sm font-semibold">Pilih titik B di peta</button>
        <button type="button" @click="clear()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">Bersihkan lintasan</button>
    </div>
    <p role="status" aria-live="polite" class="text-sm text-slate-600" x-text="mapMessage"></p>
    <div wire:ignore class="relative z-0 overflow-hidden rounded-lg border border-slate-300">
        <div x-ref="map" class="h-72 w-full" role="region" aria-label="Peta pemilihan lintasan profil dasar laut A dan B"></div>
    </div>
    <p class="text-xs text-slate-600">Perbesar peta atau seret penanda A/B untuk menyesuaikan garis. Jika peta tidak tampil, isi koordinat desimal di bawah. Lintang selatan memakai tanda minus; bujur timur bernilai positif. Gunakan titik sebagai pemisah desimal.</p>
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach(['Start' => 'A — Awal lintasan', 'End' => 'B — Akhir lintasan'] as $part => $label)
            <fieldset class="min-w-0 space-y-2 rounded-lg border border-slate-200 bg-white p-3">
                <legend class="px-1 text-sm font-semibold text-slate-800">{{ $label }}</legend>
                @foreach(['Lat' => ['Lintang', '-85', '85', '-0.480000'], 'Lng' => ['Bujur', '-180', '180', '130.880000']] as $axis => $definition)
                    @php($property = 'profile'.$part.$axis)
                    <div>
                        <label for="{{ $property }}" class="block text-sm text-slate-700">{{ $definition[0] }} titik {{ $part === 'Start' ? 'A' : 'B' }} (derajat)</label>
                        <input id="{{ $property }}" wire:model="{{ $property }}" type="number" step="any" min="{{ $definition[1] }}" max="{{ $definition[2] }}" placeholder="{{ $definition[3] }}" wire:loading.attr="disabled" wire:target="generateSeabedProfile" class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm">
                        @error($property) <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </fieldset>
        @endforeach
    </div>
    <p class="rounded-lg bg-amber-50 p-3 text-sm leading-relaxed text-amber-900">Hasil merupakan gambaran dari grid sekunder GMRT. Lintasan pendek atau dekat pantai bisa terlalu kasar untuk lokasi keramba. Banyaknya titik grafik tidak berarti datanya lebih rinci. Jenis pasir, lumpur atau batuan tetap perlu pengamatan atau bukti lain.</p>
    <p class="text-xs text-slate-600">Panjang lintasan: 10 m–100 km. Pembuatan grafik memakai GMRT gratis dan tidak meminta Gemini. Grafik mengikuti batas 10 lampiran / 15 MB Bab 3.</p>
    @error('profileTransect') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
    <button type="button" wire:click="generateSeabedProfile" @if(trim($previewData['seabed_profile'] ?? '') !== '') wire:confirm="Buat penampang baru dan ganti pratinjau Profil dasar laut? Grafik lama tetap tersimpan." @endif wire:loading.attr="disabled" wire:target="generateSeabedProfile,analyzeEvidence,analyzeTideData,calculateTideData,generateTideChart,sendMessage,confirmAndSave,uploadEvidence,uploadTideData,deleteEvidence,deleteTideData,evidenceFile,tideFile" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
        <span wire:loading.remove wire:target="generateSeabedProfile">Buat grafik &amp; narasi awal</span>
        <span wire:loading wire:target="generateSeabedProfile">Mengambil profil GMRT...</span>
    </button>
    <p wire:loading wire:target="generateSeabedProfile" role="status" class="text-sm text-blue-700">Mengambil sampel lintasan dan membuat grafik. Grafik sebelumnya tetap tersedia.</p>
</div>
