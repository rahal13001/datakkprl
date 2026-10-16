@if (filled($attachment['caption'] ?? null))
    @foreach (\App\Domain\Kkprl\ProposalDocumentPresentation::paragraphs('Keterangan: '.$attachment['caption']) as $paragraph)
        <div class="caption">@include('pdf.partials.kkprl-document-paragraph', ['paragraph' => $paragraph])</div>
    @endforeach
@else
    <p class="caption">Berkas: {{ $attachment['original_name'] }}</p>
@endif
