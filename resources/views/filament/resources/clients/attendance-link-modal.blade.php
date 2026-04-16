<div style="display: flex; flex-direction: column; gap: 1rem; width: 100%;">
    <div style="padding: 1.25rem; background-color: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 0.75rem; width: 100%; box-sizing: border-box;">
        <div style="font-weight: 700; font-size: 1.05rem; margin-bottom: 0.5rem; color: #b45309;">
            <i class="fa-solid fa-users" style="margin-right: 0.5rem;"></i> Master Link Daftar Hadir
        </div>
        <p style="font-size: 0.875rem; color: rgba(130, 130, 130, 1); margin-bottom: 1rem;">
            Link ini bersifat publik. Siapapun yang mengisi formulir melalui link ini akan otomatis terekam sebagai peserta pada Berita Acara ini, beserta tanda tangannya.
        </p>

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
                :style="copied ? 'background-color: #10b981;' : 'background-color: #d97706; hover: background-color: #b45309;'"
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
    </div>
</div>
