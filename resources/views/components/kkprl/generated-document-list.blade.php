@props(['documents'])

<div class="space-y-3">
    @forelse ($documents as $document)
        <article wire:key="generated-document-{{ $document['id'] }}" class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-4">
            <div class="min-w-0 space-y-1">
                <h3 class="font-semibold text-slate-900">Bab {{ str_replace('bag-', '', $document['chapter']) }} · {{ strtoupper($document['format']) }}</h3>
                @if ($document['proposal_reference'])
                    <p class="break-all text-xs text-slate-600">{{ $document['proposal_reference'] }}</p>
                @endif
                <p class="text-xs text-slate-600">
                    Dibuat {{ $document['generated_at']?->format('d-m-Y H:i') ?? '—' }} · {{ $document['status'] }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @if ($document['can_download'])
                    <a href="{{ route('kkprl.proposal.document.download', $document['id']) }}" class="inline-flex min-h-11 items-center font-semibold text-amber-800 underline">Lihat / unduh {{ strtoupper($document['format']) }}</a>
                @endif
                @if ($document['can_delete'])
                    <button type="button" wire:click="deleteDocument({{ $document['id'] }})"
                        wire:confirm="Hapus berkas {{ strtoupper($document['format']) }} Bab {{ str_replace('bag-', '', $document['chapter']) }} ini secara permanen? Data proposal, lampiran, dan format lain tetap tersimpan."
                        wire:loading.attr="disabled" wire:target="deleteDocument,generateChapterDocuments,submitProposal"
                        class="min-h-11 rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"
                        aria-label="Hapus dokumen {{ strtoupper($document['format']) }} Bab {{ str_replace('bag-', '', $document['chapter']) }} yang dibuat {{ $document['generated_at']?->format('d-m-Y H:i') ?? 'sebelumnya' }}, {{ $document['status'] }}">
                        Hapus dokumen
                    </button>
                @endif
            </div>
        </article>
    @empty
        <p class="rounded-lg border border-slate-200 p-6 text-sm text-slate-600">Belum ada dokumen.</p>
    @endforelse
</div>
