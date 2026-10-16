<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 2.5cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; line-height: 1.4; color: #1f2937; }
        .cover { text-align: center; page-break-after: always; padding-top: {{ $landscape ? '1.5cm' : '4cm' }}; }
        .cover-kicker { font-size: 15pt; font-weight: bold; margin: 0 0 14pt; }
        .cover h1 { font-size: 18pt; line-height: 1.3; margin: 0 0 38pt; }
        .cover-name { font-size: 14pt; font-weight: bold; margin: 22pt 0; }
        .cover-subtitle { font-size: 10.5pt; margin: 8pt 0; }
        .cover-meta { font-size: 10.5pt; margin: 18pt 0 0; }
        h2 { font-size: 12.5pt; line-height: 1.3; margin: 18pt 0 9pt; padding-bottom: 5pt; border-bottom: .5pt solid #cbd5e1; page-break-after: avoid; }
        h3 { font-size: 11pt; margin: 11pt 0 5pt; page-break-after: avoid; }
        p { margin: 0 0 8pt; orphans: 3; widows: 3; }
        .prose p { text-align: justify; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 8pt; }
        td, th { padding: 5pt 6pt; vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
        .details { font-size: 10.5pt; }
        .details .key { width: 38%; background: #f1f5f9; font-weight: bold; }
        .details td { border-bottom: .4pt solid #e2e8f0; }
        .data-table { font-size: 9.5pt; line-height: 1.3; }
        .data-table td, .data-table th { border: .5pt solid #cbd5e1; }
        .data-table th { background: #f1f5f9; font-weight: bold; text-align: left; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .figure { page-break-inside: avoid; margin: 12pt 0 14pt; }
        .figure-title { text-align: center; font-size: 10pt; font-weight: bold; margin-bottom: 5pt; }
        .image { text-align: center; margin: 6pt 0; }
        .image img { max-width: 100%; max-height: {{ $landscape ? '10cm' : '17cm' }}; height: auto; width: auto; }
        .caption { font-size: 9pt; color: #475569; line-height: 1.35; margin: 4pt 0; word-wrap: break-word; }
        .caption p { margin: 0 0 4pt; }
        .appendix { page-break-before: always; }
        .pdf-page { page-break-after: always; }
        .pdf-page:last-child { page-break-after: auto; }
        .report p { font-size: 10pt; text-align: left; }
        .row-card { margin-bottom: 12pt; }
    </style>
</head>
<body>
    <div class="cover">
        <p class="cover-kicker">PROPOSAL PERMOHONAN</p>
        <h1>“{{ $chapterTitle }}”</h1>
        <p class="cover-name">{{ $coverName }}</p>
        <p class="cover-subtitle">PROPOSAL KKPRL — {{ strtoupper(str_replace('bag-', 'BAB ', $chapter)) }}</p>
        <p class="cover-subtitle">PERSETUJUAN KEGIATAN KESESUAIAN PEMANFAATAN RUANG LAUT (PKKPRL)</p>
        <p class="cover-meta">TAHUN {{ $year }}</p>
        <p class="cover-meta">Nomor tiket: {{ $ticket }}</p>
    </div>
    @foreach ($blocks as $block)
        @switch($block['type'])
            @case('heading')
                <h2>{{ $block['text'] }}</h2>
                @break
            @case('appendix')
                <h2 class="appendix">{{ $block['text'] }}</h2>
                @break
            @case('details')
                <table class="details"><tbody>
                    @foreach ($block['rows'] as $row)
                        <tr><td class="key">{{ $row['label'] }}</td><td>{!! nl2br(e($row['text'])) !!}</td></tr>
                    @endforeach
                </tbody></table>
                @break
            @case('prose')
                <div class="prose">
                    <h3>{{ $block['label'] }}</h3>
                    @foreach ($block['paragraphs'] as $paragraph)
                        @include('pdf.partials.kkprl-document-paragraph', ['paragraph' => $paragraph])
                    @endforeach
                </div>
                @break
            @case('table')
                <h3>{{ $block['label'] }}</h3>
                @php($largeRows = \App\Domain\Kkprl\ProposalDocumentPresentation::pdfTableNeedsFlow($block['rows'], $block['columns'], $landscape))
                @if ($largeRows)
                    @foreach ($block['rows'] as $row)
                        <div class="row-card">
                            @foreach ($block['columns'] as $column)
                                <h3>{{ ucwords(str_replace('_', ' ', $column)) }}</h3>
                                @foreach (\App\Domain\Kkprl\ProposalDocumentPresentation::paragraphs($row[$column] ?? '') as $paragraph)
                                    @include('pdf.partials.kkprl-document-paragraph', ['paragraph' => $paragraph])
                                @endforeach
                            @endforeach
                        </div>
                    @endforeach
                @else
                    <table class="data-table">
                        <thead><tr>@foreach ($block['columns'] as $column)<th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>@endforeach</tr></thead>
                        <tbody>@foreach ($block['rows'] as $row)<tr>@foreach ($block['columns'] as $column)<td>{!! nl2br(e(\App\Domain\Kkprl\ProposalDocumentPresentation::text($row[$column] ?? ''))) !!}</td>@endforeach</tr>@endforeach</tbody>
                    </table>
                @endif
                @break
            @case('attachment')
                @php($attachment = $block['attachment'])
                @if ($attachment['is_image'])
                    <div class="figure">
                        <div class="figure-title">{{ $attachment['number'] }}</div>
                        <div class="image"><img src="{{ $attachment['data_uri'] }}" alt="{{ $attachment['caption'] ?? $attachment['original_name'] }}"></div>
                        @include('pdf.partials.kkprl-document-caption', ['attachment' => $attachment])
                    </div>
                @elseif ($attachment['rendered_pages'] !== [])
                    @foreach ($attachment['rendered_pages'] as $index => $page)
                        <div class="figure pdf-page">
                            <div class="figure-title">{{ $attachment['number'] }} — Halaman PDF {{ $index + 1 }}</div>
                            <div class="image"><img src="{{ $page['data_uri'] }}" alt="Halaman PDF {{ $index + 1 }}"></div>
                            @if ($loop->last)
                                @include('pdf.partials.kkprl-document-caption', ['attachment' => $attachment])
                            @endif
                        </div>
                    @endforeach
                @else
                    <h3>{{ $attachment['number'] }} — {{ ($attachment['attachment_role'] ?? null) === 'ai_tide_data' ? 'Ringkasan data pasut' : 'Dokumen pendukung' }}</h3>
                    <div class="report">
                        @foreach ($attachment['pages'] as $index => $page)
                            @if (($attachment['attachment_role'] ?? null) !== 'ai_tide_data')<h3>Halaman PDF {{ $index + 1 }}</h3>@endif
                            @foreach (\App\Domain\Kkprl\ProposalDocumentPresentation::paragraphs($page) as $paragraph)
                                @include('pdf.partials.kkprl-document-paragraph', ['paragraph' => $paragraph])
                            @endforeach
                        @endforeach
                    </div>
                    @include('pdf.partials.kkprl-document-caption', ['attachment' => $attachment])
                @endif
                @break
        @endswitch
    @endforeach
</body>
</html>
