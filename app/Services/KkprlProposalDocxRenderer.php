<?php

namespace App\Services;

use App\Domain\Kkprl\ProposalDocumentPresentation;
use ZipArchive;

final class KkprlProposalDocxRenderer
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function render(array $data): string
    {
        $relationships = [];
        $media = [];
        $xml = $this->document($data, $relationships, $media);
        $temporary = tempnam(sys_get_temp_dir(), 'kkprl-docx-');
        if ($temporary === false) {
            throw new \RuntimeException('Unable to create DOCX temporary file.');
        }
        try {
            $zip = new ZipArchive;
            if ($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to create DOCX package.');
            }
            $zip->addFromString('[Content_Types].xml', $this->contentTypes($media));
            $zip->addFromString('_rels/.rels', $this->relationships([['id' => 'rId1', 'target' => 'word/document.xml', 'type' => 'officeDocument']]));
            $zip->addFromString('word/document.xml', $xml);
            $zip->addFromString('word/_rels/document.xml.rels', $this->relationships([
                ['id' => 'rIdStyles', 'target' => 'styles.xml', 'type' => 'styles'],
                ['id' => 'rIdFooter', 'target' => 'footer1.xml', 'type' => 'footer'],
                ['id' => 'rIdSettings', 'target' => 'settings.xml', 'type' => 'settings'],
                ...$relationships,
            ]));
            $zip->addFromString('word/styles.xml', $this->styles($data['landscape']));
            $zip->addFromString('word/settings.xml', '<?xml version="1.0"?><w:settings xmlns:w="'.self::W.'"><w:updateFields w:val="true"/></w:settings>');
            $zip->addFromString('word/footer1.xml', '<?xml version="1.0"?><w:ftr xmlns:w="'.self::W.'"><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="666666"/></w:rPr><w:t xml:space="preserve">Halaman </w:t></w:r><w:fldSimple w:instr=" PAGE "><w:r><w:t>1</w:t></w:r></w:fldSimple></w:p></w:ftr>');
            foreach ($media as $item) {
                $zip->addFromString($item['path'], $item['binary']);
            }
            if (! $zip->close()) {
                throw new \RuntimeException('Unable to finish DOCX package.');
            }
            $binary = file_get_contents($temporary);
            if (! is_string($binary)) {
                throw new \RuntimeException('Unable to read generated DOCX.');
            }

            return $binary;
        } finally {
            @unlink($temporary);
        }
    }

    private function document(array $data, array &$relationships, array &$media): string
    {
        $landscape = $data['landscape'];
        $width = $landscape ? 14004 : 9072;
        $body = $this->paragraph('PROPOSAL PERMOHONAN', 'CoverTitle');
        $body .= $this->paragraph('“'.$data['chapterTitle'].'”', 'CoverChapter');
        $body .= $this->paragraph($data['coverName'], 'CoverName');
        $body .= $this->paragraph('PROPOSAL KKPRL — '.strtoupper(str_replace('bag-', 'BAB ', $data['chapter'])), 'CoverSubtitle');
        $body .= $this->paragraph('PERSETUJUAN KEGIATAN KESESUAIAN PEMANFAATAN RUANG LAUT (PKKPRL)', 'CoverSubtitle');
        $body .= $this->paragraph('TAHUN '.$data['year'], 'CoverMeta');
        $body .= $this->paragraph('Nomor tiket: '.$data['ticket'], 'CoverMeta');
        // sectPr describes the section before it. Cover has no footer; body starts at page 1.
        $body .= '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="20" w:lineRule="exact"/>'.$this->section($landscape, false).'</w:pPr></w:p>';
        foreach ($data['blocks'] as $blockIndex => $block) {
            switch ($block['type']) {
                case 'heading':
                    $body .= $this->paragraph($block['text'], 'Heading1', $blockIndex === 0);
                    break;
                case 'appendix':
                    $body .= $this->pageBreak().$this->paragraph($block['text'], 'Heading1');
                    break;
                case 'details':
                    $rows = array_map(fn ($row) => [$row['label'], $row['text']], $block['rows']);
                    $body .= $this->table($rows, [(int) round($width * .38), $width - (int) round($width * .38)], true);
                    break;
                case 'prose':
                    $body .= $this->paragraph($block['label'], 'Heading2', $blockIndex === 0);
                    foreach ($block['paragraphs'] as $paragraph) {
                        $body .= $this->paragraph($paragraph['runs']);
                    }
                    break;
                case 'table':
                    $body .= $this->paragraph($block['label'], 'Heading2', $blockIndex === 0);
                    $columns = $block['columns'];
                    $rows = [array_map(fn ($column) => ucwords(str_replace('_', ' ', $column)), $columns)];
                    foreach ($block['rows'] as $row) {
                        $rows[] = array_map(fn ($column) => ProposalDocumentPresentation::text($row[$column] ?? ''), $columns);
                    }
                    $widths = array_fill(0, count($columns), intdiv($width, max(1, count($columns))));
                    $widths[count($widths) - 1] += $width - array_sum($widths);
                    $body .= $this->table($rows, $widths);
                    break;
                case 'attachment':
                    $attachment = $block['attachment'];
                    $body .= $this->paragraph($attachment['number'], 'FigureTitle', $blockIndex === 0);
                    if ($attachment['is_image']) {
                        $body .= $this->image($attachment, $width, $relationships, $media);
                    } elseif ($attachment['rendered_pages'] !== []) {
                        foreach ($attachment['rendered_pages'] as $index => $page) {
                            $body .= $this->paragraph('Halaman PDF '.($index + 1), 'FigureTitle');
                            $body .= $this->image($page, $width, $relationships, $media);
                            if ($index < count($attachment['rendered_pages']) - 1) {
                                $body .= $this->pageBreak();
                            }
                        }
                    } else {
                        foreach ($attachment['pages'] as $index => $page) {
                            $body .= $this->paragraph(($attachment['attachment_role'] ?? null) === 'ai_tide_data' ? 'Ringkasan data pasut' : 'Halaman PDF '.($index + 1), 'Heading2');
                            foreach (ProposalDocumentPresentation::paragraphs($page) as $paragraph) {
                                $body .= $this->paragraph($paragraph['runs'], 'ReportText');
                            }
                        }
                    }
                    $caption = trim($attachment['caption'] ?? '');
                    if ($caption !== '') {
                        foreach (ProposalDocumentPresentation::paragraphs('Keterangan: '.$caption) as $paragraph) {
                            $body .= $this->paragraph($paragraph['runs'], 'Caption');
                        }
                    } else {
                        $body .= $this->paragraph('Berkas: '.$attachment['original_name'], 'Caption');
                    }
                    break;
            }
        }

        $finalSection = $this->section($landscape, true);
        if (isset($data['locationMap'])) {
            $body .= '<w:p><w:pPr>'.$finalSection.'</w:pPr></w:p>';
            $body .= $this->paragraph('PETA LOKASI KEGIATAN', isset($data['locationMap']['print_width_points']) ? 'MapTitle' : 'FigureTitle');
            $mapWidth = isset($data['locationMap']['print_width_points']) ? $data['locationMap']['print_width_points'] * 20 : 14004;
            $mapHeight = isset($data['locationMap']['print_width_points']) ? 8600 : 8400;
            $body .= $this->image($data['locationMap'], $mapWidth, $relationships, $media, $mapHeight);
            $finalSection = $this->section(true, true, false);
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="'.self::W.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><w:body>'
            .$body.$finalSection.'</w:body></w:document>';
    }

    private function section(bool $landscape, bool $footer, bool $restart = true): string
    {
        return '<w:sectPr>'.($footer ? '<w:footerReference w:type="default" r:id="rIdFooter"/>' : '')
            .'<w:type w:val="nextPage"/><w:pgSz w:w="'.($landscape ? 16838 : 11906).'" w:h="'.($landscape ? 11906 : 16838).'"'.($landscape ? ' w:orient="landscape"' : '').'/>'
            .'<w:pgMar w:top="1417" w:right="1417" w:bottom="1417" w:left="1417" w:header="708" w:footer="708" w:gutter="0"/>'
            .($footer && $restart ? '<w:pgNumType w:start="1"/>' : '').'</w:sectPr>';
    }

    private function paragraph(string|array $value, string $style = 'BodyText', bool $newPage = false): string
    {
        $runs = is_string($value) ? [['text' => $value]] : $value;
        $xml = '<w:p><w:pPr><w:pStyle w:val="'.$style.'"/>'.($newPage ? '<w:pageBreakBefore/>' : '').'</w:pPr>';
        foreach ($runs as $run) {
            $properties = (($run['bold'] ?? false) ? '<w:b/>' : '').(($run['italic'] ?? false) ? '<w:i/>' : '');
            $escaped = $this->escape($run['text']);
            $escaped = str_replace(["\r\n", "\r"], "\n", $escaped);
            $escaped = str_replace("\n", '</w:t><w:br/><w:t xml:space="preserve">', $escaped);
            $xml .= '<w:r>'.($properties ? '<w:rPr>'.$properties.'</w:rPr>' : '').'<w:t xml:space="preserve">'.$escaped.'</w:t></w:r>';
        }

        return $xml.'</w:p>';
    }

    private function table(array $rows, array $widths, bool $details = false): string
    {
        $border = $details ? 'nil' : 'single';
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="'.array_sum($widths).'" w:type="dxa"/><w:tblBorders>';
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $side) {
            $xml .= '<w:'.$side.' w:val="'.$border.'" w:sz="4" w:color="CBD5E1"/>';
        }
        $xml .= '</w:tblBorders><w:tblLayout w:type="fixed"/><w:tblCellMar><w:top w:w="85" w:type="dxa"/><w:left w:w="110" w:type="dxa"/><w:bottom w:w="85" w:type="dxa"/><w:right w:w="110" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid>';
        foreach ($widths as $width) {
            $xml .= '<w:gridCol w:w="'.$width.'"/>';
        }
        $xml .= '</w:tblGrid>';
        foreach ($rows as $index => $row) {
            $header = ! $details && $index === 0;
            $xml .= '<w:tr>'.($header ? '<w:trPr><w:cantSplit/><w:tblHeader/></w:trPr>' : '');
            foreach ($row as $column => $value) {
                $shade = ($details && $column === 0) || $header;
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="'.$widths[$column].'" w:type="dxa"/>'.($shade ? '<w:shd w:val="clear" w:color="auto" w:fill="F1F5F9"/>' : '').'<w:vAlign w:val="top"/></w:tcPr>';
                $xml .= $this->paragraph($value, $details ? ($column === 0 ? 'DetailLabel' : 'DetailValue') : ($header ? 'TableHeader' : 'TableText')).'</w:tc>';
            }
            $xml .= '</w:tr>';
        }

        return $xml.'</w:tbl>'.$this->paragraph('', 'TableSpacer');
    }

    private function image(array $attachment, int $width, array &$relationships, array &$media, ?int $height = null): string
    {
        $index = count($media) + 1;
        $id = 'rIdImage'.$index;
        $extension = $attachment['mime'] === 'image/png' ? 'png' : 'jpg';
        $relationships[] = ['id' => $id, 'target' => 'media/image'.$index.'.'.$extension, 'type' => 'image'];
        $media[] = ['path' => 'word/media/image'.$index.'.'.$extension, 'binary' => $attachment['binary'], 'content_type' => $attachment['mime']];
        [$sourceWidth, $sourceHeight] = $attachment['dimensions'] ?? [600, 400];
        $maxWidth = $width * 635;
        $maxHeight = ($height ?? ($width > 9072 ? 6800 : 10400)) * 635;
        $scale = min($maxWidth / max(1, $sourceWidth), $maxHeight / max(1, $sourceHeight), 9525);
        $cx = max(1, (int) round($sourceWidth * $scale));
        $cy = max(1, (int) round($sourceHeight * $scale));

        return '<w:p><w:pPr><w:keepNext/><w:spacing w:before="100" w:after="100"/><w:jc w:val="center"/></w:pPr><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="'.$cx.'" cy="'.$cy.'"/><wp:docPr id="'.$index.'" name="Lampiran '.$index.'"/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="'.$index.'" name="image'.$index.'"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="'.$id.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
    }

    private function styles(bool $landscape): string
    {
        $body = '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:pPr><w:widowControl/><w:spacing w:after="120" w:line="310" w:lineRule="auto"/></w:pPr><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="22"/><w:szCs w:val="22"/><w:color w:val="1F2937"/></w:rPr></w:style>';
        $definitions = [
            'BodyText' => ['<w:jc w:val="both"/>', ''],
            'Heading1' => ['<w:keepNext/><w:spacing w:before="300" w:after="140"/><w:outlineLvl w:val="0"/>', '<w:b/><w:sz w:val="25"/>'],
            'Heading2' => ['<w:keepNext/><w:spacing w:before="160" w:after="70"/><w:outlineLvl w:val="1"/>', '<w:b/>'],
            'CoverTitle' => ['<w:spacing w:before="'.($landscape ? '850' : '2200').'" w:after="240"/><w:jc w:val="center"/>', '<w:b/><w:sz w:val="30"/>'],
            'CoverChapter' => ['<w:spacing w:after="520"/><w:jc w:val="center"/>', '<w:b/><w:sz w:val="36"/>'],
            'CoverName' => ['<w:spacing w:before="420" w:after="340"/><w:jc w:val="center"/>', '<w:b/><w:sz w:val="28"/>'],
            'CoverSubtitle' => ['<w:spacing w:after="120"/><w:jc w:val="center"/>', '<w:sz w:val="21"/>'],
            'CoverMeta' => ['<w:spacing w:before="220" w:after="100"/><w:jc w:val="center"/>', '<w:sz w:val="21"/>'],
            'DetailLabel' => ['<w:spacing w:after="0" w:line="270"/>', '<w:b/><w:sz w:val="21"/>'],
            'DetailValue' => ['<w:spacing w:after="0" w:line="270"/>', '<w:sz w:val="21"/>'],
            'TableHeader' => ['<w:keepNext/><w:spacing w:after="0" w:line="250"/>', '<w:b/><w:sz w:val="19"/>'],
            'TableText' => ['<w:spacing w:after="0" w:line="250"/>', '<w:sz w:val="19"/>'],
            'TableSpacer' => ['<w:spacing w:before="0" w:after="80" w:line="20" w:lineRule="exact"/>', '<w:sz w:val="2"/>'],
            'FigureTitle' => ['<w:keepNext/><w:spacing w:before="180" w:after="80"/><w:jc w:val="center"/>', '<w:b/><w:sz w:val="20"/>'],
            'MapTitle' => ['<w:keepNext/><w:spacing w:before="0" w:after="0" w:line="180"/><w:jc w:val="center"/>', '<w:b/><w:sz w:val="16"/>'],
            'Caption' => ['<w:spacing w:after="80" w:line="240"/>', '<w:sz w:val="18"/><w:color w:val="475569"/>'],
            'ReportText' => ['<w:spacing w:after="100" w:line="260"/>', '<w:sz w:val="20"/>'],
        ];
        foreach ($definitions as $name => [$paragraph, $run]) {
            $body .= '<w:style w:type="paragraph" w:styleId="'.$name.'"><w:name w:val="'.$name.'"/><w:basedOn w:val="Normal"/><w:next w:val="BodyText"/><w:pPr>'.$paragraph.'</w:pPr><w:rPr>'.$run.'</w:rPr></w:style>';
        }

        return '<?xml version="1.0"?><w:styles xmlns:w="'.self::W.'">'.$body.'</w:styles>';
    }

    private function relationships(array $items): string
    {
        $xml = '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($items as $item) {
            $xml .= '<Relationship Id="'.$item['id'].'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/'.$item['type'].'" Target="'.$item['target'].'"/>';
        }

        return $xml.'</Relationships>';
    }

    private function contentTypes(array $media): string
    {
        $xml = '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>';
        foreach (['document' => 'document.main', 'styles' => 'styles', 'settings' => 'settings', 'footer1' => 'footer'] as $name => $type) {
            $xml .= '<Override PartName="/word/'.$name.'.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.'.$type.'+xml"/>';
        }
        $types = [];
        foreach ($media as $item) {
            $types[pathinfo($item['path'], PATHINFO_EXTENSION)] = $item['content_type'];
        }
        foreach ($types as $extension => $type) {
            $xml .= '<Default Extension="'.$extension.'" ContentType="'.$type.'"/>';
        }

        return $xml.'</Types>';
    }

    private function escape(string $text): string
    {
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? '', ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function pageBreak(): string
    {
        return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    }
}
