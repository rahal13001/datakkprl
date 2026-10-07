<section class="min-w-0 space-y-4 rounded-2xl border border-blue-200 bg-white p-4 shadow-sm sm:p-6" aria-labelledby="chapter-five-options-heading">
    <h3 id="chapter-five-options-heading" class="text-lg font-bold text-slate-900">Ketersediaan izin pendukung</h3>
    <p class="text-sm leading-relaxed text-slate-600">Pilih sesuai kondisi sebenarnya. Jika belum memiliki izin, Anda tidak perlu mengisi nomor, tahun, masa berlaku atau dokumen izin.</p>
    <div>
        <label for="existing-permits-choice" class="block text-sm font-medium text-slate-700">Apakah Anda sudah memiliki izin pendukung?</label>
        <select id="existing-permits-choice" wire:model.live="existingPermitsChoice" wire:loading.attr="disabled" wire:target="sendMessage,confirmAndSave" @disabled($locked) class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm">
            <option value="">Pilih kondisi izin</option>
            <option value="1">Sudah memiliki izin pendukung</option>
            <option value="0">Belum memiliki izin pendukung</option>
        </select>
        @error('existingPermitsChoice') <p role="alert" class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    @if($existingPermitsChoice === '0')
        <p class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">Bagian perizinan tidak perlu diisi. Pilihan ini memperbarui jawaban Bab 1 saat Anda menekan Konfirmasi &amp; Simpan Draft.</p>
        @if($chapterFiveHasLand)
            <p class="text-sm text-slate-600">Lokasi masih tercatat berhimpitan dengan daratan. Lengkapi informasi bukti lahan di pratinjau; dokumen dan catatan lahan opsional. Jika hubungan dengan daratan keliru, perbaiki pilihan Bab 1.</p>
        @else
            <p class="text-sm text-slate-600">Bab 5 dapat dilewati karena tidak ada kebutuhan bukti lahan darat maupun izin pendukung. Konfirmasi pilihan untuk melanjutkan proposal.</p>
        @endif
    @elseif($existingPermitsChoice === '1')
        <p class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">Lengkapi jenis izin, tahun perolehan dan status izin. Nomor, instansi penerbit, masa berlaku, dokumen dan catatan diisi jika tersedia. Izin yang ada tetap dapat dicatat meskipun dokumennya belum dapat diunggah.</p>
    @endif
    <p class="text-xs leading-relaxed text-slate-500">Data dan koreksi izin yang sudah diisi tetap tersimpan jika Anda mengganti pilihan. Ini pencatatan kondisi usaha; kelengkapan draft bukan persetujuan atau penerbitan izin.</p>
</section>
