<?php

namespace App\Services;

use App\Domain\Kkprl\InvalidTideData;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use ZipArchive;

final class KkprlTideDataReader
{
    public const MAX_ROWS = 50000;

    public const MAX_COLUMNS = 20;

    public const MAX_BYTES = 5242880;

    public const MAX_CELLS = 200000;

    private const EXCEL_CHUNK_CELLS = 60000;

    /** @return array{columns: array, rows: array, calendar: int, metadata: array, header_line: int} */
    public function read(string $path, string $format): array
    {
        if (! is_file($path) || filesize($path) > self::MAX_BYTES || ! in_array($format, ['csv', 'xls', 'xlsx'], true)) {
            throw new InvalidTideData('Gunakan CSV, XLS atau XLSX maksimal 5 MB.');
        }
        try {
            $metadata = [];
            $headerLine = 1;
            if ($format === 'csv') {
                [$rows, $metadata, $headerLine] = $this->csv($path);
                $calendar = Date::CALENDAR_WINDOWS_1900;
            } else {
                [$rows, $calendar] = $this->excel($path, $format);
            }
        } catch (InvalidTideData $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new InvalidTideData('File data pasut tidak dapat dibaca. Gunakan format asli CSV/XLS/XLSX tanpa kata sandi.', previous: $exception);
        }
        $columns = array_shift($rows) ?? [];
        if (count($columns) < 2 || count($columns) > self::MAX_COLUMNS || $rows === []) {
            throw new InvalidTideData('Baris pertama harus memuat judul kolom waktu dan tinggi, diikuti data. Maksimal '.number_format(self::MAX_COLUMNS, 0, ',', '.').' kolom dan '.number_format(self::MAX_ROWS, 0, ',', '.').' baris data.');
        }
        foreach ($columns as &$label) {
            $label = trim((string) $label);
            if (is_numeric($label) || preg_match('~^(?:\d{4}-\d{2}-\d{2}|\d{2}[/-]\d{2}[/-]\d{4})(?:\s|T|$)~', $label)) {
                throw new InvalidTideData('Baris pertama harus berisi judul kolom, bukan sampel data. Tambahkan judul seperti waktu dan tinggi.');
            }
            if (mb_strlen($label) > 120) {
                throw new InvalidTideData('Judul kolom terlalu panjang; gunakan maksimal 120 karakter.');
            }
        }
        unset($label);

        return ['columns' => $columns, 'rows' => $rows, 'calendar' => $calendar, 'metadata' => $metadata, 'header_line' => $headerLine];
    }

    private function csv(string $path): array
    {
        $binary = file_get_contents($path);
        if (! mb_check_encoding($binary, 'UTF-8') || str_contains($binary, "\0") || preg_match('/^\s*</', $binary)) {
            throw new InvalidTideData('CSV harus berupa teks UTF-8. Ekspor ulang sebagai CSV UTF-8 dari Excel.');
        }
        unset($binary);
        $handle = fopen($path, 'rb');
        try {
            $first = fgets($handle);
            $first = preg_replace('/^\xEF\xBB\xBF/', '', $first ?: '');
            $delimiter = ',';
            $most = 0;
            foreach ([',', ';', "\t"] as $candidate) {
                $count = count(str_getcsv($first, $candidate, '"', ''));
                if ($count > $most) {
                    $delimiter = $candidate;
                    $most = $count;
                }
            }
            rewind($handle);
            $rows = [];
            $metadata = [];
            $headerLine = 1;
            if (trim($first) === 'Direktorat Sistem Referensi Geospasial') {
                [$columns, $metadata, $headerLine, $delimiter] = $this->srgiPreamble($handle);
                $rows[] = $columns;
            }
            while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
                if (count($row) === 1 && trim((string) $row[0]) === '') {
                    continue;
                }
                if (count($rows) > self::MAX_ROWS || count($row) > self::MAX_COLUMNS) {
                    throw new InvalidTideData('Data pasut maksimal '.number_format(self::MAX_ROWS, 0, ',', '.').' baris dan '.number_format(self::MAX_COLUMNS, 0, ',', '.').' kolom.');
                }
                if ($rows === []) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0] ?? '');
                }
                $this->assertNoFormulas($row);
                $rows[] = $row;
            }

            return [$rows, $metadata, $headerLine];
        } finally {
            fclose($handle);
        }
    }

    private function srgiPreamble($handle): array
    {
        $metadata = ['format' => 'srgi_big'];
        $keys = ['station name' => 'station_name', 'lon' => 'longitude', 'lat' => 'latitude', 'datum' => 'datum'];
        for ($line = 1; $line <= 32 && ($content = fgets($handle)) !== false; $line++) {
            $content = trim(preg_replace('/^\xEF\xBB\xBF/', '', $content));
            if (mb_strlen($content) > 512) {
                break;
            }
            $this->assertNoFormulas([$content]);
            if ($line === 1 || ($line === 2 && $content === 'Badan Informasi Geospasial') || $content === '') {
                continue;
            }
            if ($line === 2) {
                break;
            }
            foreach ([',', ';', "\t"] as $delimiter) {
                $columns = str_getcsv($content, $delimiter, '"', '');
                if (array_map(fn ($column) => strtolower(trim($column)), $columns) === ['timestamp_utc', 'timestamp_local', 'predicted_height_m']) {
                    return [$columns, $metadata, $line, $delimiter];
                }
            }
            if (! preg_match('/^(Station Name|Email|Lon|Lat|Datum)\s*:\s*(.*)$/i', $content, $matches)) {
                break;
            }
            $key = $keys[strtolower($matches[1])] ?? null;
            if ($key === null) {
                continue; // Contact information is not needed for proposal analysis.
            }
            $value = trim($matches[2]);
            $this->assertNoFormulas([$value]);
            if (in_array($key, ['longitude', 'latitude'], true)) {
                if (! is_numeric($value) || ! is_finite((float) $value) || abs((float) $value) > ($key === 'longitude' ? 180 : 90)) {
                    throw new InvalidTideData('Koordinat stasiun pada informasi awal CSV SRGI BIG tidak valid.');
                }
                $value = (float) $value;
            } elseif (mb_strlen($value) > 200) {
                throw new InvalidTideData('Nama stasiun atau acuan tinggi pada CSV SRGI BIG terlalu panjang.');
            }
            $metadata[$key] = $value;
        }
        throw new InvalidTideData('Judul tabel CSV SRGI BIG tidak ditemukan. Gunakan file asli dengan kolom timestamp_utc, timestamp_local dan predicted_height_m setelah informasi stasiun.');
    }

    private function excel(string $path, string $format): array
    {
        if ($format === 'xlsx') {
            $this->assertArchive($path);
        } elseif (file_get_contents($path, false, null, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            throw new InvalidTideData('XLS harus berupa workbook Excel asli, bukan HTML atau CSV yang diganti ekstensi.');
        }
        $reader = IOFactory::createReader($format === 'xls' ? 'Xls' : 'Xlsx');
        if (! $reader->canRead($path)) {
            throw new InvalidTideData('Format internal Excel tidak sesuai. Ekspor ulang sebagai XLSX atau CSV.');
        }
        $sheet = $reader->listWorksheetInfo($path)[0] ?? null;
        if (! $sheet || $sheet['totalRows'] > self::MAX_ROWS + 1 || $sheet['totalColumns'] > self::MAX_COLUMNS) {
            throw new InvalidTideData('Worksheet pertama maksimal '.number_format(self::MAX_ROWS, 0, ',', '.').' baris data dan '.number_format(self::MAX_COLUMNS, 0, ',', '.').' kolom. Hapus sel kosong berformat di luar tabel.');
        }
        if (max(0, $sheet['totalRows'] - 1) * $sheet['totalColumns'] > self::MAX_CELLS) {
            throw new InvalidTideData('Area data Excel maksimal '.number_format(self::MAX_CELLS, 0, ',', '.').' sel. Sisakan kolom waktu dan tinggi yang diperlukan, atau bagi periode data.');
        }
        $reader->setReadDataOnly(true)->setReadEmptyCells(false)->setLoadSheetsOnly([$sheet['worksheetName']]);
        $chunkRows = max(1, intdiv(self::EXCEL_CHUNK_CELLS, max(1, $sheet['totalColumns'])));
        $previousCalendar = Date::getExcelCalendar();
        try {
            $rows = [];
            // Bound workbook cell objects while preserving every original sample.
            for ($firstRow = 1; $firstRow <= $sheet['totalRows']; $firstRow += $chunkRows) {
                $lastRow = min($firstRow + $chunkRows - 1, $sheet['totalRows']);
                $reader->setReadFilter(new class($firstRow, $lastRow) implements IReadFilter
                {
                    public function __construct(private int $firstRow, private int $lastRow) {}

                    public function readCell($columnAddress, $row, $worksheetName = ''): bool
                    {
                        return $row >= $this->firstRow && $row <= $this->lastRow;
                    }
                });
                $book = $reader->load($path);
                $calendar = Date::getExcelCalendar();
                $active = $book->getSheet(0);
                try {
                    for ($r = $firstRow; $r <= $lastRow; $r++) {
                        $row = [];
                        for ($c = 1; $c <= $sheet['totalColumns']; $c++) {
                            $address = Coordinate::stringFromColumnIndex($c).$r;
                            $cell = $active->cellExists($address) ? $active->getCell($address) : null;
                            if ($cell?->getDataType() === DataType::TYPE_FORMULA) {
                                throw new InvalidTideData('Data pasut tidak boleh memuat rumus Excel. Salin lalu tempel sebagai nilai sebelum mengunggah.');
                            }
                            $row[] = $cell?->getValue(); // Never calculate formulas or fetch linked workbooks.
                        }
                        $this->assertNoFormulas($row);
                        $rows[] = $row;
                    }
                } finally {
                    $book->disconnectWorksheets();
                    unset($cell, $active, $book);
                    // PhpSpreadsheet objects contain cycles; release them before the next batch.
                    gc_collect_cycles();
                }
            }

            return [$rows, $calendar];
        } finally {
            Date::setExcelCalendar($previousCalendar);
        }
    }

    private function assertNoFormulas(array $row): void
    {
        foreach ($row as $value) {
            if (is_string($value) && str_starts_with(ltrim($value), '=')) {
                throw new InvalidTideData('Data pasut tidak boleh memuat rumus. Ekspor atau tempel sebagai nilai.');
            }
        }
    }

    private function assertArchive(string $path): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new InvalidTideData('XLSX tidak dapat dibaca. Ekspor ulang workbook tanpa kata sandi.');
        }
        try {
            if ($zip->numFiles > 1000 || $zip->locateName('xl/workbook.xml') === false) {
                throw new InvalidTideData('File XLSX bukan workbook yang didukung.');
            }
            $size = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $size += $entry['size'];
                if ($size > 32 * 1024 * 1024 || preg_match('~externalLinks/|embeddings/|vbaProject|connections\.xml~i', $entry['name'])) {
                    throw new InvalidTideData('Excel terlalu besar setelah dibuka atau memuat macro/tautan data eksternal. Ekspor tabel saja sebagai CSV.');
                }
            }
        } finally {
            $zip->close();
        }
    }
}
