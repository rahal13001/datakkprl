<div style="display: flex; flex-direction: column; gap: 1rem; width: 100%;">
    @if($beritaAcara->isSigningExpired())
        <div style="padding: 1rem; background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 0.5rem; color: #b45309; font-size: 0.875rem;">
            <div style="font-weight: bold; margin-bottom: 0.25rem;">Batas waktu telah terlewati</div>
            <div>Batas konfirmasi 3 hari kerja telah lewat. Seluruh peserta dianggap telah menyetujui.</div>
        </div>
    @endif

    @forelse($attendees as $attendee)
        <div style="padding: 1.25rem; background-color: rgba(128, 128, 128, 0.05); border: 1px solid rgba(128, 128, 128, 0.15); border-radius: 0.75rem; width: 100%; box-sizing: border-box;">
            <div>
                <div style="font-weight: 700; font-size: 1.05rem; margin-bottom: 0.25rem;">{{ $attendee->nama }}</div>
                
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span style="font-size: 0.85rem; color: rgba(130, 130, 130, 1);">{{ $attendee->getJabatanInstansiLabel() }}</span>
                    
                    @if($attendee->is_signatory)
                        <span style="background-color: rgba(34, 197, 94, 0.15); color: #16a34a; padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">Penanda Tangan</span>
                    @endif
                    @if($attendee->confirmed_at)
                        <span style="background-color: rgba(16, 185, 129, 0.15); color: #059669; padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">Sudah Masuk</span>
                    @endif
                </div>
            </div>

            @if($attendee->token)
                @php
                    $url = $attendee->getSigningUrl();
                @endphp
                <div style="margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem; width: 100%;" x-data="{ copied: false }">
                    <input 
                        x-ref="urlInput"
                        type="text" 
                        readonly 
                        value="{{ $url }}" 
                        style="flex-grow: 1; min-width: 0; padding: 0.6rem 0.75rem; background-color: rgba(0, 0, 0, 0.1); border: 1px solid rgba(128, 128, 128, 0.3); border-radius: 0.4rem; font-size: 0.875rem; color: inherit; outline: none; box-sizing: border-box;"
                        onclick="this.select()"
                    >
                    <button 
                        type="button"
                        x-on:click="
                            $refs.urlInput.select();
                            document.execCommand('copy');
                            copied = true; 
                            setTimeout(() => copied = false, 2000);
                        "
                        style="display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.6rem 1.25rem; font-weight: 600; font-size: 0.875rem; color: white; border: none; border-radius: 0.4rem; cursor: pointer; transition: all 0.2s; white-space: nowrap; flex-shrink: 0;"
                        :style="copied ? 'background-color: #10b981;' : 'background-color: #3b82f6; hover: background-color: #2563eb;'"
                    >
                        <svg x-show="!copied" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <svg x-show="copied" style="width: 1rem; height: 1rem; display: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span x-text="copied ? 'Tersalin!' : 'Salin'"></span>
                    </button>
                </div>
            @else
                <div style="font-size: 0.875rem; color: rgba(128, 128, 128, 0.8); font-style: italic; margin-top: 0.75rem;">Belum ada link, harap simpan Berita Acara terlebih dahulu.</div>
            @endif
        </div>
    @empty
        <div style="text-align: center; padding: 2rem; color: rgba(128, 128, 128, 0.8);">
            Belum ada peserta yang terdaftar.
        </div>
    @endforelse
</div>
