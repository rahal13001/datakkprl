<main class="mx-auto min-h-screen w-full max-w-xl bg-white px-4 py-8 text-slate-900 sm:px-6">
    <header class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-wide text-amber-700">Proposal KKPRL</p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight">
            {{ $mode === 'resume' ? 'Lanjutkan Proposal' : 'Buat Proposal Baru' }}
        </h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">
            Sistem ini membantu penyusunan dan review dokumen. Sistem bukan OSS, e-Sea, atau penerbit izin resmi.
        </p>
    </header>

    @if ($mode === 'resume')
        <form wire:submit="submit" class="space-y-5" aria-label="Lanjutkan proposal">
            <div>
                <label for="ticket-number" class="block text-sm font-medium">Nomor tiket</label>
                <input id="ticket-number" wire:model="ticketNumber" type="text" autocomplete="off" required
                    class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm">
            </div>
            <div>
                <label for="resume-phone" class="block text-sm font-medium">Nomor HP</label>
                <input id="resume-phone" wire:model="phone" type="tel" inputmode="tel" autocomplete="tel" required
                    class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm">
            </div>
            @error('credentials')
                <p role="alert" class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="w-full rounded-md bg-amber-700 px-4 py-3 text-sm font-semibold text-white disabled:cursor-wait disabled:opacity-60">
                <span wire:loading.remove wire:target="submit">Lanjutkan</span>
                <span wire:loading wire:target="submit">Memverifikasi...</span>
            </button>
            @if ($accessGranted)
                <p role="status" class="text-sm text-green-700">Akses proposal berhasil diverifikasi.</p>
                <a href="{{ route('kkprl.proposal.wizard') }}" class="block text-sm font-semibold text-amber-800 underline">Buka proposal</a>
            @endif
        </form>
    @else
        <form wire:submit="submit" class="space-y-5" aria-label="Buat proposal baru">
            <div>
                <label for="start-phone" class="block text-sm font-medium">Nomor HP</label>
                <input id="start-phone" wire:model="phone" type="tel" inputmode="tel" autocomplete="tel" required
                    class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm">
                @error('phone')
                    <p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="w-full rounded-md bg-amber-700 px-4 py-3 text-sm font-semibold text-white disabled:cursor-wait disabled:opacity-60">
                <span wire:loading.remove wire:target="submit">Buat Draft</span>
                <span wire:loading wire:target="submit">Membuat draft...</span>
            </button>
        </form>

        @if ($createdTicket)
            <section class="mt-6 rounded-md border border-green-200 bg-green-50 p-4" aria-live="polite">
                <h2 class="font-semibold text-green-900">Draft berhasil dibuat</h2>
                <p class="mt-2 text-sm text-green-800">Simpan nomor tiket ini untuk melanjutkan proposal:</p>
                <p class="mt-2 break-all font-mono text-lg font-bold text-green-950">{{ $createdTicket }}</p>
                <p class="mt-2 text-sm text-green-800">Nomor tiket saja tidak cukup untuk membuka proposal.</p>
                <a href="{{ route('kkprl.proposal.wizard') }}" class="mt-3 inline-block text-sm font-semibold text-green-900 underline">Isi proposal sekarang</a>
            </section>
        @endif
    @endif
</main>
