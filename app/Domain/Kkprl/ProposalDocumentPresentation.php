<?php

namespace App\Domain\Kkprl;

use DOMDocument;
use DOMNode;

final class ProposalDocumentPresentation
{
    public static function text(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        if (is_array($value)) {
            $lines = [];
            foreach ($value as $key => $item) {
                $lines[] = (is_string($key) ? ucwords(str_replace('_', ' ', $key)).': ' : '').self::text($item);
            }

            return implode("\n", $lines);
        }

        return is_scalar($value) ? (string) $value : '';
    }

    /** @return list<array{text: string, runs: list<array{text: string, bold: bool, italic: bool}>}> */
    public static function paragraphs(mixed $value): array
    {
        $text = self::text($value);
        if (! preg_match('/<\/?[a-z][^>]*>/i', $text)) {
            return array_map(fn ($part) => ['text' => $part, 'runs' => [['text' => $part, 'bold' => false, 'italic' => false]]],
                array_values(array_filter(preg_split('/\R\s*\R/u', trim($text)) ?: [], fn ($part) => trim($part) !== '')));
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><html><body>'.$text.'</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $blocks = [];
        $runs = [];
        $flush = function () use (&$blocks, &$runs): void {
            $plain = implode('', array_column($runs, 'text'));
            if (trim($plain) !== '') {
                $blocks[] = ['text' => $plain, 'runs' => $runs];
            }
            $runs = [];
        };
        $visit = function (DOMNode $node, bool $bold = false, bool $italic = false) use (&$visit, &$runs, $flush): void {
            if ($node->nodeType === XML_TEXT_NODE) {
                $runs[] = ['text' => $node->nodeValue ?? '', 'bold' => $bold, 'italic' => $italic];

                return;
            }
            $tag = strtolower($node->nodeName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'img', 'head'], true)) {
                return;
            }
            if ($tag === 'br') {
                $runs[] = ['text' => "\n", 'bold' => $bold, 'italic' => $italic];

                return;
            }
            $block = in_array($tag, ['p', 'div', 'li', 'h1', 'h2', 'h3', 'h4', 'blockquote', 'tr'], true);
            if ($block) {
                $flush();
            }
            if ($tag === 'li') {
                $runs[] = ['text' => '• ', 'bold' => false, 'italic' => false];
            }
            foreach ($node->childNodes as $child) {
                $visit($child, $bold || in_array($tag, ['b', 'strong'], true), $italic || in_array($tag, ['i', 'em'], true));
            }
            if ($block) {
                $flush();
            }
        };
        $visit($document->getElementsByTagName('body')->item(0) ?? $document);
        $flush();

        return $blocks;
    }

    public static function isTable(mixed $value): bool
    {
        return is_array($value) && array_is_list($value) && is_array($value[0] ?? null) && ! array_is_list($value[0]);
    }

    /** @return list<string> */
    public static function columns(array $rows): array
    {
        return array_values(array_unique(array_merge(...array_map(fn ($row) => array_keys($row), $rows))));
    }

    public static function pdfTableNeedsFlow(array $rows, array $columns, bool $landscape): bool
    {
        // Dompdf cannot paginate inside one table row. Use a conservative line
        // estimate, accounting for explicit breaks and narrower landscape columns.
        $charactersPerLine = max(1, (int) floor(($landscape ? 700 : 453) / max(1, count($columns)) / 6));
        $maximumLines = $landscape ? 22 : 36;
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                $lines = 0;
                foreach (preg_split('/\R/u', self::text($cell)) ?: [] as $line) {
                    $lines += max(1, (int) ceil(mb_strlen($line) / $charactersPerLine));
                }
                if ($lines > $maximumLines) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<array<string, mixed>> */
    public static function blocks(array $data): array
    {
        $blocks = [];
        $details = [];
        $flush = function () use (&$blocks, &$details): void {
            if ($details !== []) {
                $blocks[] = ['type' => 'details', 'rows' => $details];
                $details = [];
            }
        };
        $lastSection = null;
        $narratives = ['site_plan_description', 'coordinates', 'marine_space_use_narrative', 'mangrove', 'seagrass', 'coral_reef', 'current', 'waves', 'tide', 'bathymetry', 'seabed_profile', 'socio_economic', 'accessibility', 'material_method', 'reclaimed_land_plan', 'reclamation_method', 'reclamation_schedule', 'land_notes', 'permit_notes'];
        foreach ($data['values'] as $field => $value) {
            $section = $data['sectionLabels'][$field] ?? null;
            if ($section !== null && $section !== $lastSection) {
                $flush();
                $blocks[] = ['type' => 'heading', 'text' => $section];
                $lastSection = $section;
            }
            $label = ($data['fieldLabels'][$field] ?? ucwords(str_replace('_', ' ', $field))).':';
            $text = self::text($value);
            if ($field === 'shape_type') {
                $text = ['point' => 'Titik', 'line' => 'Garis', 'polygon' => 'Area (polygon)'][$text] ?? $text;
            }
            if (in_array($field, ['includes_reclamation', 'has_existing_permits'], true) && in_array($value, ['0', '1', 0, 1], true)) {
                $text = (bool) $value ? 'Ya' : 'Tidak';
            }
            if ($field === 'coordinates') {
                $text = preg_replace('/(?<!^)(?<!\n)(?=Titik \d+:)/u', "\n", $text) ?? $text;
            }
            if (self::isTable($value)) {
                $flush();
                $blocks[] = ['type' => 'table', 'label' => $label, 'rows' => $value, 'columns' => self::columns($value)];
            } elseif (in_array($field, $narratives, true) || mb_strlen($text) > 220 || str_contains($text, "\n") || preg_match('/<\/?[a-z][^>]*>/i', $text)) {
                $flush();
                $blocks[] = ['type' => 'prose', 'label' => $label, 'paragraphs' => self::paragraphs($text)];
            } else {
                $details[] = ['label' => $label, 'text' => $text];
            }
            foreach ($data['inlineAttachments'][$field] ?? [] as $attachment) {
                $flush();
                $blocks[] = ['type' => 'attachment', 'attachment' => $attachment];
            }
        }
        $flush();
        if ($data['appendixAttachments'] !== []) {
            $blocks[] = ['type' => 'appendix', 'text' => 'LAMPIRAN BAB'];
            foreach ($data['appendixAttachments'] as $attachment) {
                $blocks[] = ['type' => 'attachment', 'attachment' => $attachment];
            }
        }

        return $blocks;
    }
}
