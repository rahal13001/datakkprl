<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 1.6cm; }
        @page landscape { size: A4 landscape; margin: 1.2cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.45; color: #1f2937; }
        h1 { font-size: 16pt; text-align: center; margin: 0 0 18pt; }
        .document-subtitle { text-align: center; font-weight: bold; margin: -8pt 0 14pt; }
        .cover-page { box-sizing: border-box; height: 20cm; page-break-after: always; padding-top: 5cm; text-align: center; }
        .cover-page h1 { margin-bottom: 22pt; }
        .cover-institution { color: #b91c1c; font-weight: bold; font-style: italic; margin-bottom: 28pt; }
        .cover-year { font-weight: bold; margin-top: 14pt; }
        .cover-ticket { margin-top: 22pt; }
        h2 { font-size: 12pt; border-bottom: 1px solid #374151; padding-bottom: 4pt; margin-top: 16pt; }
        .field { page-break-inside: avoid; margin-bottom: 9pt; }
        .label { font-weight: bold; }
        .attachment { page-break-inside: avoid; margin: 8pt 0 12pt 18pt; }
        .attachment img { max-width: 100%; max-height: 22cm; height: auto; width: auto; object-fit: contain; }
        .caption { font-size: 8pt; color: #4b5563; }
        .appendix { page-break-before: always; }
        .pdf-text { white-space: pre-wrap; border: 1px solid #d1d5db; padding: 6pt; font-size: 8pt; }
        .pdf-page { page-break-after: always; white-space: pre-wrap; border: 1px solid #d1d5db; padding: 6pt; font-size: 8pt; }
        .pdf-page:last-child { page-break-after: auto; }
        .pdf-page img { max-width: 100%; max-height: 24cm; height: auto; width: auto; object-fit: contain; }
        .table-wrap { page-break-inside: avoid; overflow: visible; }
        .table-wrap.landscape-table { page: landscape; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8pt; }
        th, td { border: 1px solid #9ca3af; padding: 4pt; vertical-align: top; overflow-wrap: anywhere; word-wrap: break-word; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <div class="cover-page">
        <h1>PROPOSAL PERMOHONAN “{{ $chapterTitle }}”</h1>
        <div class="cover-institution">{{ $values['institution_name'] ?? 'NAMA PERUSAHAAN/PERORANGAN/INSTANSI' }}</div>
        <div class="document-subtitle">PROPOSAL KKPRL — {{ strtoupper(str_replace('bag-', 'BAB ', $chapter)) }}<br>PERSETUJUAN KEGIATAN KESESUAIAN PEMANFAATAN RUANG LAUT (PKKPRL)</div>
        <div class="cover-year">TAHUN {{ $year }}</div>
        <p class="cover-ticket"><strong>Nomor tiket:</strong> {{ $ticket }}</p>
    </div>

    @php($lastSection = null)
    @foreach ($values as $field => $value)
        @php($isTable = is_array($value) && array_is_list($value) && is_array($value[0] ?? null) && ! array_is_list($value[0]))
        @php($text = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE))
        @if (($sectionLabels[$field] ?? null) !== null && ($sectionLabels[$field] ?? null) !== $lastSection)
            <h2>{{ $sectionLabels[$field] }}</h2>
            @php($lastSection = $sectionLabels[$field])
        @endif
        <div class="field">
            <div class="label">{{ ($fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field))) }}</div>
            @if ($isTable)
                @php($columns = array_keys($value[0]))
                <div class="table-wrap {{ count($columns) > 4 ? 'landscape-table' : '' }}">
                    <table>
                        <thead><tr>@foreach ($columns as $column)<th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>@endforeach</tr></thead>
                        <tbody>@foreach ($value as $row)<tr>@foreach ($columns as $column)<td>{!! nl2br(e((string) ($row[$column] ?? ''))) !!}</td>@endforeach</tr>@endforeach</tbody>
                    </table>
                </div>
            @else
                <div>{!! nl2br(e($text ?: '')) !!}</div>
            @endif
            @foreach ($inlineAttachments[$field] ?? [] as $attachment)
                <div class="attachment">
                    <strong>{{ $attachment['number'] }} — {{ $attachment['original_name'] }}</strong>
                    @if ($attachment['is_image'])
                        <img src="{{ $attachment['data_uri'] }}" alt="{{ $attachment['caption'] ?? $attachment['original_name'] }}">
                    @elseif (($attachment['rendered_pages'] ?? []) !== [])
                        @foreach ($attachment['rendered_pages'] as $pageIndex => $page)
                            <div class="pdf-page"><strong>Halaman PDF {{ $pageIndex + 1 }}</strong><br><img src="{{ $page['data_uri'] }}" alt="Halaman PDF {{ $pageIndex + 1 }}"></div>
                        @endforeach
                    @else
                        @foreach ($attachment['pages'] as $pageIndex => $page)
                            <div class="pdf-page"><strong>Halaman PDF {{ $pageIndex + 1 }}</strong><br>{{ $page }}</div>
                        @endforeach
                    @endif
                    <div class="caption">{{ $attachment['caption'] }}</div>
                </div>
            @endforeach
        </div>
    @endforeach

    @if ($appendixAttachments !== [])
        <div class="appendix">
            <h2>Lampiran Bab</h2>
            @foreach ($appendixAttachments as $attachment)
                <div class="attachment">
                    <strong>{{ $attachment['number'] }} — {{ $attachment['original_name'] }}</strong>
                    @if ($attachment['is_image'])
                        <div><img src="{{ $attachment['data_uri'] }}" alt="{{ $attachment['caption'] ?? $attachment['original_name'] }}"></div>
                    @elseif (($attachment['rendered_pages'] ?? []) !== [])
                        @foreach ($attachment['rendered_pages'] as $pageIndex => $page)
                            <div class="pdf-page"><strong>Halaman PDF {{ $pageIndex + 1 }}</strong><br><img src="{{ $page['data_uri'] }}" alt="Halaman PDF {{ $pageIndex + 1 }}"></div>
                        @endforeach
                    @else
                        @foreach ($attachment['pages'] as $pageIndex => $page)
                            <div class="pdf-page"><strong>Halaman PDF {{ $pageIndex + 1 }}</strong><br>{{ $page }}</div>
                        @endforeach
                    @endif
                    <div class="caption">{{ $attachment['caption'] }}</div>
                </div>
            @endforeach
        </div>
    @endif
</body>
</html>
