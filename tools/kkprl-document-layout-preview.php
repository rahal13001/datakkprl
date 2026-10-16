<?php

// Offline preview only: reads the supplied legacy samples, never imports them into a database.
use App\Domain\Kkprl\ProposalDocumentPresentation as Presentation;
use App\Domain\Kkprl\ProposalDocumentSectionCatalog;
use App\Domain\Kkprl\ProposalFieldCatalog;
use App\Domain\Kkprl\ProposalProgress;
use App\Services\KkprlProposalDocxRenderer;
use App\Services\KkprlProposalPdfRenderer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

require __DIR__.'/../vendor/autoload.php';
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
DB::connection()->beforeExecuting(function (): never {
    throw new RuntimeException('Database queries are forbidden in this offline preview.');
});
Http::preventStrayRequests();

$input = base_path('docs/export_sample');
$output = $input.'/rapi';
if (! is_dir($output) && ! mkdir($output, 0700, true)) {
    throw new RuntimeException('Cannot create preview output directory.');
}
$fixtureOutput = $output.'/QA';
if (! is_dir($fixtureOutput) && ! mkdir($fixtureOutput, 0700, true)) {
    throw new RuntimeException('Cannot create synthetic fixture directory.');
}
$coverName = '';
foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
    $source = $input.'/proposal-'.$chapter.'.docx';
    $hash = hash_file('sha256', $source);
    $zip = new ZipArchive;
    if ($zip->open($source) !== true) {
        throw new RuntimeException('Cannot open source sample.');
    }
    $document = new DOMDocument;
    $document->loadXML($zip->getFromName('word/document.xml'), LIBXML_NONET);
    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
    $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $rels = new DOMDocument;
    $rels->loadXML($zip->getFromName('word/_rels/document.xml.rels'), LIBXML_NONET);
    $targets = [];
    foreach ($rels->documentElement->childNodes as $rel) {
        if ($rel instanceof DOMElement && $rel->getAttribute('TargetMode') !== 'External') {
            $target = $rel->getAttribute('Target');
            if (preg_match('#^media/[a-zA-Z0-9_.-]+$#', $target)) {
                $targets[$rel->getAttribute('Id')] = 'word/'.$target;
            }
        }
    }
    $labels = [];
    foreach (ProposalFieldCatalog::fields($chapter) as $field) {
        $labels[ProposalFieldCatalog::label($field).':'] = $field;
    }
    // Include optional fields found in the source, without importing any ownership metadata.
    foreach (ProposalProgress::requiredFields('bag-1') as $field) {
        $labels[ProposalFieldCatalog::label($field).':'] = $field;
    }
    $values = [];
    $inline = [];
    $currentField = null;
    $attachment = null;
    $paras = $xpath->query('/w:document/w:body/w:p');
    $title = '';
    $ticket = '';
    $year = '';
    $finishAttachment = function () use (&$attachment, &$inline, &$currentField): void {
        if ($attachment !== null && $currentField !== null) {
            $inline[$currentField][] = $attachment;
        }
        $attachment = null;
    };
    foreach ($paras as $index => $paragraph) {
        $text = '';
        foreach ($xpath->query('.//w:t | .//w:br', $paragraph) as $node) {
            $text .= $node->localName === 'br' ? "\n" : $node->textContent;
        }
        $text = trim($text);
        if ($index === 0) {
            preg_match('/“(.*?)”/u', $text, $match);
            $title = $match[1] ?? '';
        }
        if ($index === 1 && $chapter === 'bag-1') {
            $coverName = $text;
        }
        if (str_starts_with($text, 'TAHUN ')) {
            $year = substr($text, 6);
        }
        if (str_starts_with($text, 'Nomor tiket: ')) {
            $ticket = substr($text, 13);
        }
        if ($index < 7) {
            continue;
        }
        if (isset($labels[$text])) {
            $finishAttachment();
            $currentField = $labels[$text];
            $values[$currentField] = '';

            continue;
        }
        if (preg_match('/^(?:[IVX]+\. )?(?:INFORMASI|RENCANA|JENIS|PETA|Ekosistem Sekitar|Hidro-Oseanografi|Karakteristik Sosial Ekonomi Masyarakat|Aksesibilitas Lokasi)/u', $text) && mb_strlen($text) < 90) {
            $finishAttachment();

            continue;
        }
        if (preg_match('/^(Lampiran Bab \d+-\d+) — (.+)$/u', $text, $match)) {
            $finishAttachment();
            $attachment = ['number' => $match[1], 'original_name' => $match[2], 'caption' => '', 'is_image' => false, 'rendered_pages' => [], 'pages' => [], 'attachment_role' => str_ends_with($match[2], '.csv') ? 'ai_tide_data' : null];

            continue;
        }
        if ($attachment !== null) {
            $embed = $xpath->query('.//a:blip/@r:embed', $paragraph)->item(0)?->nodeValue;
            if ($embed !== null && isset($targets[$embed])) {
                $binary = $zip->getFromName($targets[$embed]);
                $size = getimagesizefromstring($binary);
                if ($size === false || ! in_array($size['mime'], ['image/png', 'image/jpeg'], true)) {
                    throw new RuntimeException('Unsupported sample image.');
                }
                $attachment += ['binary' => $binary, 'mime' => $size['mime'], 'dimensions' => [$size[0], $size[1]], 'data_uri' => 'data:'.$size['mime'].';base64,'.base64_encode($binary)];
                $attachment['is_image'] = true;
            } elseif (str_starts_with($text, 'Keterangan: ')) {
                $attachment['caption'] = substr($text, 12);
            } elseif ($text !== '' && $text !== 'Ringkasan data pasut') {
                $attachment['pages'][] = $text;
            }

            continue;
        }
        if ($currentField !== null && $text !== '') {
            $values[$currentField] .= ($values[$currentField] !== '' ? "\n\n" : '').$text;
        }
    }
    $finishAttachment();
    $zip->close();
    if ($chapter === 'bag-1' && trim($values['institution_name'] ?? '') !== '') {
        $coverName = $values['institution_name'];
    }
    $data = ['chapter' => $chapter, 'chapterTitle' => $title, 'coverName' => $coverName, 'ticket' => $ticket, 'year' => $year, 'values' => $values, 'fieldLabels' => [], 'sectionLabels' => [], 'inlineAttachments' => $inline, 'appendixAttachments' => [], 'landscape' => false];
    foreach (array_keys($values) as $field) {
        $data['fieldLabels'][$field] = ProposalFieldCatalog::label($field);
        $data['sectionLabels'][$field] = ProposalDocumentSectionCatalog::heading($chapter, $field);
    }
    $data['blocks'] = Presentation::blocks($data);
    foreach (['docx' => KkprlProposalDocxRenderer::class, 'pdf' => KkprlProposalPdfRenderer::class] as $extension => $renderer) {
        file_put_contents($output.'/proposal-'.$chapter.'.'.$extension, app($renderer)->render($data));
    }
    // Browser checks use wholly synthetic content. Original names, coordinates,
    // source text and images never enter the connector.
    $visual = $data;
    $visual['coverName'] = 'INSTITUSI CONTOH';
    $visual['ticket'] = 'FIXTURE-LAYOUT';
    foreach ($visual['values'] as $field => $value) {
        $parts = Presentation::paragraphs($value);
        $visual['values'][$field] = implode("\n\n", array_map(fn ($part) => substr(str_repeat('Uraian contoh kegiatan dan kondisi perairan. ', max(1, (int) ceil(mb_strlen($part['text']) / 44))), 0, max(8, mb_strlen($part['text']))), $parts));
    }
    $imageNumber = 0;
    foreach ($visual['inlineAttachments'] as &$items) {
        foreach ($items as &$item) {
            $item['original_name'] = 'gambar-contoh-'.(++$imageNumber).'.png';
            $item['caption'] = 'Gambar pendukung contoh. Sumber: https://example.test/sumber/'.str_repeat('contoh-', 18).' — Informasi sumber dipertahankan dalam caption dan terbungkus mengikuti lebar halaman.';
            $item['pages'] = array_map(fn ($page) => str_repeat('Ringkasan data contoh. ', max(1, (int) ceil(mb_strlen($page) / 22))), $item['pages']);
            if ($item['is_image']) {
                [$width, $height] = $item['dimensions'];
                $image = imagecreatetruecolor($width, $height);
                imagefill($image, 0, 0, imagecolorallocate($image, 226, 232, 240));
                imagerectangle($image, 5, 5, $width - 6, $height - 6, imagecolorallocate($image, 71, 85, 105));
                imagestring($image, 5, 15, 15, 'GAMBAR CONTOH '.$imageNumber, imagecolorallocate($image, 30, 41, 59));
                ob_start();
                imagepng($image);
                $item['binary'] = ob_get_clean();
                imagedestroy($image);
                $item['mime'] = 'image/png';
                $item['data_uri'] = 'data:image/png;base64,'.base64_encode($item['binary']);
            }
        }
        unset($item);
    }
    unset($items);
    $visual['blocks'] = Presentation::blocks($visual);
    foreach (['docx' => KkprlProposalDocxRenderer::class, 'pdf' => KkprlProposalPdfRenderer::class] as $extension => $renderer) {
        file_put_contents($fixtureOutput.'/fixture-'.$chapter.'-visual.'.$extension, app($renderer)->render($visual));
    }
    if (! hash_equals($hash, hash_file('sha256', $source))) {
        throw new RuntimeException('Source sample changed.');
    }
    echo $chapter.': '.count($values).' fields, '.array_sum(array_map('count', $inline))." attachments; source unchanged.\n";
}

// Synthetic stress fixture: long narrative and a wide table, no personal data.
$data['chapter'] = 'bag-4';
$data['chapterTitle'] = 'PRATINJAU TATA LETAK';
$data['coverName'] = 'INSTITUSI CONTOH';
$data['ticket'] = 'FIXTURE-LAYOUT';
$data['inlineAttachments'] = [];
$data['landscape'] = true;
$data['values'] = ['material_method' => implode("\n\n", array_map(fn ($n) => '<p>Paragraf '.$n.'. '.str_repeat('Kalimat laporan yang perlu tetap terbaca. ', 22).'</p>', range(1, 22))), 'reclamation_schedule_rows' => array_map(fn ($n) => ['kegiatan' => 'Kegiatan '.$n, 'tahun' => '2026', 'triwulan_1' => 'Persiapan', 'triwulan_2' => 'Pelaksanaan', 'triwulan_3' => 'Pengawasan', 'triwulan_4' => 'Evaluasi'], range(1, 35))];
$data['fieldLabels'] = ['material_method' => 'Metode pelaksanaan', 'reclamation_schedule_rows' => 'Jadwal pelaksanaan'];
$data['sectionLabels'] = [];
$data['blocks'] = Presentation::blocks($data);
foreach (['docx' => KkprlProposalDocxRenderer::class, 'pdf' => KkprlProposalPdfRenderer::class] as $extension => $renderer) {
    file_put_contents($fixtureOutput.'/fixture-layout.'.$extension, app($renderer)->render($data));
}
echo "Synthetic stress fixture generated; no database or external HTTP request.\n";
