<main class="mx-auto min-h-screen w-full max-w-3xl bg-white px-4 py-8 text-slate-900 sm:px-6">
    <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Dokumen proposal KKPRL</h1>
        <button type="button" wire:click="logout" class="rounded-md border border-slate-300 px-4 py-2 text-sm">Keluar</button>
    </header>
    @if ($documentMessage !== '')
        <p role="status" class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ $documentMessage }}</p>
    @endif
    @error('documents')
        <p role="alert" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $message }}</p>
    @enderror
    <section aria-label="Dokumen hasil generate" class="space-y-3">
        <h2 class="sr-only">Dokumen hasil generate</h2>
        <x-kkprl.generated-document-list :documents="$documents" />
        {{ $documents->links() }}
    </section>
    @if ($approved)
        <section aria-label="Proposal milik Anda" class="mt-8 space-y-4">
            <h2 class="text-lg font-semibold">Proposal milik Anda</h2>
            <button type="button" wire:click="createProposal" wire:loading.attr="disabled" class="rounded-md bg-amber-700 px-4 py-3 font-semibold text-white">Buat proposal baru</button>
            @foreach ($proposals as $proposal)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-4">
                    <span class="break-all">{{ $proposal->ticket_number }} {{ $proposal->revision_label }} · {{ $proposal->status }}</span>
                    <button type="button" wire:click="openProposal({{ $proposal->id }})" class="font-semibold text-amber-800 underline">Buka proposal</button>
                </div>
            @endforeach
            {{ $proposals->links() }}
        </section>
    @endif
</main>
