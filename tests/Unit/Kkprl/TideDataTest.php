<?php

namespace Tests\Unit\Kkprl;

use App\Domain\Kkprl\InvalidTideData;
use App\Services\KkprlTideDataAnalyzer;
use App\Services\KkprlTideDataReader;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TideDataTest extends TestCase
{
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            @unlink($path);
        }
        parent::tearDown();
        // Workbook writers retain cyclic references to their writer parts and caches.
        gc_collect_cycles();
    }

    private function path(string $content = ''): string
    {
        $path = tempnam(sys_get_temp_dir(), 'kkprl-tide-test-');
        $this->paths[] = $path;
        file_put_contents($path, $content);

        return $path;
    }

    private function options(array $changes = []): array
    {
        return array_replace(['date_column' => 0, 'height_column' => 1, 'time_column' => null,
            'date_format' => 'ymd', 'unit' => 'cm', 'timezone' => 'Asia/Jayapura', 'datum' => 'Nol palem stasiun'], $changes);
    }

    #[Test]
    public function csv_computes_real_statistics_in_metres_and_keeps_sampling_limits_explicit(): void
    {
        $table = (new KkprlTideDataReader)->read($this->path("\xEF\xBB\xBFwaktu;tinggi\n2026-10-01 02:00;-50,0\n2026-10-01 00:00;100\n2026-10-01 01:00;150\n"), 'csv');
        $summary = (new KkprlTideDataAnalyzer)->analyze($table, $this->options());
        $this->assertSame(['waktu', 'tinggi'], $table['columns']);
        $this->assertSame(3, $summary['count']);
        $this->assertEquals(-0.5, $summary['minimum_m']);
        $this->assertEquals(1.5, $summary['maximum_m']);
        $this->assertEqualsWithDelta(2 / 3, $summary['mean_m'], 0.00001);
        $this->assertEquals(2, $summary['range_m']);
        $this->assertSame('2026-10-01 01:00:00', $summary['maximum_at']);
        $this->assertSame(3600, $summary['interval_min_seconds']);
        $this->assertSame(3600, $summary['interval_max_seconds']);
        $report = (new KkprlTideDataAnalyzer)->report($summary);
        $this->assertStringContainsString('rata-rata aritmetika', $report);
        $this->assertStringContainsString('bukan MSL', $report);
        $this->assertStringContainsString('bukan HHWL/LLWL/HAT/LAT', $report);
    }

    #[Test]
    public function srgi_preamble_and_station_metadata_are_read_without_losing_samples(): void
    {
        $table = (new KkprlTideDataReader)->read(dirname(__DIR__, 2).'/Fixtures/kkprl/srgi-tide.csv', 'csv');
        $this->assertSame(['timestamp_utc', 'timestamp_local', 'predicted_height_m'], $table['columns']);
        $this->assertCount(4, $table['rows']);
        $this->assertSame(10, $table['header_line']);
        $this->assertSame('srgi_big', $table['metadata']['format']);
        $this->assertSame('PAAM', $table['metadata']['station_name']);
        $this->assertEquals(130.803, $table['metadata']['longitude']);
        $this->assertEquals(-0.43258, $table['metadata']['latitude']);
        $this->assertSame('MSL', $table['metadata']['datum']);
        $analyzer = new KkprlTideDataAnalyzer;
        $utc = $analyzer->analyze($table, $this->options(['height_column' => 2, 'unit' => 'm', 'timezone' => 'UTC', 'datum' => 'MSL']));
        $local = $analyzer->analyze($table, $this->options(['date_column' => 1, 'height_column' => 2, 'unit' => 'm', 'datum' => 'MSL']));
        $this->assertSame(4, $utc['count']);
        $this->assertSame(60, $utc['interval_min_seconds']);
        $this->assertSame('2026-09-01 00:00:00', $utc['start']);
        $this->assertSame('2026-09-01 09:00:00', $local['start']);
        $this->assertEquals(0.64275, $utc['mean_m']);
        $this->assertEquals($utc['mean_m'], $local['mean_m']);
        $this->assertSame('prediction', $utc['data_kind']);
        $this->assertStringContainsString('prediksi', $analyzer->report($utc));
        $this->assertStringContainsString('bukan pengukuran langsung', $analyzer->report($utc));
    }

    #[Test]
    public function a_local_timestamp_suffix_cannot_be_interpreted_in_the_wrong_timezone(): void
    {
        $table = (new KkprlTideDataReader)->read($this->path("waktu,tinggi\n2026-09-01 09:00:00 WIT,1\n2026-09-01 09:01:00 WIT,2\n"), 'csv');
        $this->expectException(InvalidTideData::class);
        (new KkprlTideDataAnalyzer)->analyze($table, $this->options(['timezone' => 'Asia/Jakarta']));
    }

    public static function timestampZones(): array
    {
        return [['UTC', 'UTC'], ['WIB', 'Asia/Jakarta'], ['WITA', 'Asia/Makassar'], ['WIT', 'Asia/Jayapura']];
    }

    #[Test]
    #[DataProvider('timestampZones')]
    public function timestamp_suffix_and_iso_separator_keep_the_selected_timezone(string $suffix, string $timezone): void
    {
        $table = (new KkprlTideDataReader)->read($this->path("waktu,tinggi\n2026-09-01T09:00:00 {$suffix},1\n2026-09-01 09:01:00 {$suffix},2\n"), 'csv');
        $summary = (new KkprlTideDataAnalyzer)->analyze($table, $this->options(['timezone' => $timezone]));
        $this->assertSame('2026-09-01 09:00:00', $summary['start']);
        $this->assertSame(60, $summary['interval_min_seconds']);
    }

    #[Test]
    public function srgi_column_units_and_utc_timezone_cannot_be_misinterpreted(): void
    {
        $table = (new KkprlTideDataReader)->read(dirname(__DIR__, 2).'/Fixtures/kkprl/srgi-tide.csv', 'csv');
        foreach ([['unit' => 'cm', 'timezone' => 'UTC'], ['unit' => 'm', 'timezone' => 'Asia/Jayapura']] as $changes) {
            try {
                (new KkprlTideDataAnalyzer)->analyze($table, $this->options(['height_column' => 2] + $changes));
                $this->fail('Explicit column units or timezone were ignored');
            } catch (InvalidTideData $exception) {
                $this->assertStringContainsString('Kolom', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function srgi_preamble_cannot_hide_missing_headers_formulae_or_invalid_data(): void
    {
        $csv = file_get_contents(dirname(__DIR__, 2).'/Fixtures/kkprl/srgi-tide.csv');
        foreach ([str_replace('timestamp_utc,timestamp_local,predicted_height_m', '', $csv),
            str_replace('Datum : MSL', 'Datum : =1+2', $csv),
            str_replace('Lat : -0.43258', 'Lat : 100', $csv),
            str_replace('0.643', 'rusak', $csv)] as $content) {
            try {
                $table = (new KkprlTideDataReader)->read($this->path($content), 'csv');
                (new KkprlTideDataAnalyzer)->analyze($table, $this->options(['height_column' => 2, 'unit' => 'm', 'timezone' => 'UTC']));
                $this->fail('Invalid SRGI input was accepted');
            } catch (InvalidTideData $exception) {
                $this->assertNotEmpty($exception->getMessage());
                if (str_contains($content, 'rusak')) {
                    $this->assertStringContainsString('baris 11', $exception->getMessage());
                }
            }
        }
    }

    public static function formats(): array
    {
        return [['xls'], ['xlsx']];
    }

    public static function monthlyFormats(): array
    {
        return [[30, 'csv'], [31, 'csv'], [31, 'xls'], [31, 'xlsx']];
    }

    #[Test]
    #[DataProvider('monthlyFormats')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function a_full_month_of_minute_samples_is_read_without_truncation(int $days, string $format): void
    {
        $count = $days * 1440 + 1;
        $time = new \DateTimeImmutable('2026-10-01 00:00:00');
        $rows = [['Tanggal', 'Jam', 'Tinggi', 'Keterangan']];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [$time->format('Y-m-d'), $time->format('H:i'), ($i % 3 + 1) * 100, 'Sampel'];
            $time = $time->modify('+1 minute');
        }
        $path = $this->path();
        if ($format === 'csv') {
            $handle = fopen($path, 'wb');
            foreach ($rows as $row) {
                fputcsv($handle, $row, ',', '"', '');
            }
            fclose($handle);
        } else {
            $book = new Spreadsheet;
            $book->getActiveSheet()->fromArray($rows, null, 'A1', true);
            ($format === 'xls' ? new Xls($book) : new Xlsx($book))->save($path);
            $book->disconnectWorksheets();
            unset($book);
        }
        unset($rows);
        $table = (new KkprlTideDataReader)->read($path, $format);
        $summary = (new KkprlTideDataAnalyzer)->analyze($table, $this->options(['time_column' => 1, 'height_column' => 2]));
        $this->assertSame($count, $summary['count']);
        $this->assertSame(60, $summary['interval_min_seconds']);
        $this->assertSame(60, $summary['interval_max_seconds']);
        $this->assertEquals($days * 24, $summary['duration_hours']);
        $this->assertSame('2026-10-01 00:00:00', $summary['start']);
        $this->assertSame($time->modify('-1 minute')->format('Y-m-d H:i:s'), $summary['end']);
        $this->assertEquals(1, $summary['minimum_m']);
        $this->assertEquals(3, $summary['maximum_m']);
        $remainder = $count % 3;
        $expectedMean = (intdiv($count, 3) * 6 + $remainder * ($remainder + 1) / 2) / $count;
        $this->assertEqualsWithDelta($expectedMean, $summary['mean_m'], 0.00000001);
    }

    #[Test]
    #[DataProvider('formats')]
    public function reads_genuine_excel_files_and_serial_dates_without_evaluating_formulae(string $format): void
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([
            ['waktu', 'tinggi'],
            [Date::PHPToExcel(new \DateTimeImmutable('2026-10-01 00:00:00')), 100],
            [Date::PHPToExcel(new \DateTimeImmutable('2026-10-01 01:00:00')), 200],
        ]);
        $path = $this->path();
        ($format === 'xls' ? new Xls($book) : new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        $table = (new KkprlTideDataReader)->read($path, $format);
        $summary = (new KkprlTideDataAnalyzer)->analyze($table, $this->options());
        $this->assertSame(2, $summary['count']);
        $this->assertEquals(1.5, $summary['mean_m']);
        $this->assertSame('2026-10-01 00:00:00', $summary['start']);
    }

    #[Test]
    public function separate_date_and_time_columns_and_millimetres_are_supported(): void
    {
        $table = (new KkprlTideDataReader)->read($this->path("Tanggal,Jam,Elevasi\n01/10/2026,00:00,500\n01/10/2026,02:00,1500\n01/10/2026,03:00,1000\n"), 'csv');
        $summary = (new KkprlTideDataAnalyzer)->analyze($table, $this->options(['time_column' => 1, 'height_column' => 2, 'date_format' => 'dmy', 'unit' => 'mm']));
        $this->assertEquals(1, $summary['mean_m']);
        $this->assertSame(3600, $summary['interval_min_seconds']);
        $this->assertSame(7200, $summary['interval_max_seconds']);
    }

    public static function invalidRows(): array
    {
        return [
            ["waktu,tinggi\n2026-10-01 00:00,1\n2026-10-01 01:00,rusak\n"],
            ["waktu,tinggi\n2026-10-01 00:00,1\n2026-10-01 00:00,2\n"],
            ["waktu,tinggi\n2026-02-30 00:00,1\n2026-10-01 01:00,2\n"],
            ["waktu,tinggi\n2026-10-01 00:00,=WEBSERVICE(\"https://example.test\")\n2026-10-01 01:00,2\n"],
            ["waktu,tinggi\n2026-10-01 00:00,1\n"],
        ];
    }

    #[Test]
    #[DataProvider('invalidRows')]
    public function malformed_duplicate_or_formula_rows_are_rejected_without_silent_skips(string $content): void
    {
        $this->expectException(InvalidTideData::class);
        $table = (new KkprlTideDataReader)->read($this->path($content), 'csv');
        (new KkprlTideDataAnalyzer)->analyze($table, $this->options());
    }

    #[Test]
    public function html_renamed_as_excel_and_oversized_tables_are_rejected(): void
    {
        foreach ([['xls', '<html><table><tr><td>1</td></tr></table></html>'], ['csv', "waktu,tinggi\n".str_repeat("2026-10-01 00:00,1\n", KkprlTideDataReader::MAX_ROWS + 1)]] as [$format, $content]) {
            try {
                (new KkprlTideDataReader)->read($this->path($content), $format);
                $this->fail('Invalid input was accepted');
            } catch (InvalidTideData $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    #[Test]
    public function the_row_limit_excludes_the_header(): void
    {
        $path = $this->path("waktu,tinggi\n".str_repeat("2026-10-01 00:00,1\n", 50000));
        $table = (new KkprlTideDataReader)->read($path, 'csv');
        $this->assertCount(50000, $table['rows']);
        $this->assertSame(['waktu', 'tinggi'], $table['columns']);
        file_put_contents($path, "2026-10-01 00:01,2\n", FILE_APPEND);
        $this->expectException(InvalidTideData::class);
        (new KkprlTideDataReader)->read($path, 'csv');
    }

    #[Test]
    #[DataProvider('formats')]
    public function formulae_in_later_excel_chunks_are_rejected(string $format): void
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([['waktu', 'tinggi'], ['2026-10-01 00:00', 1]]);
        $book->getActiveSheet()->setCellValue('B35000', '=1+2');
        $path = $this->path();
        ($format === 'xls' ? new Xls($book) : new Xlsx($book))->setPreCalculateFormulas(false)->save($path);
        $book->disconnectWorksheets();
        $this->expectException(InvalidTideData::class);
        $this->expectExceptionMessage('rumus Excel');
        (new KkprlTideDataReader)->read($path, $format);
    }

    #[Test]
    public function excel_formula_and_external_links_are_rejected(): void
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([['waktu', 'tinggi'], ['2026-10-01 00:00', '=1+2'], ['2026-10-01 01:00', 2]]);
        $path = $this->path();
        (new Xlsx($book))->setPreCalculateFormulas(false)->save($path);
        $book->disconnectWorksheets();
        try {
            (new KkprlTideDataReader)->read($path, 'xlsx');
            $this->fail('Formula accepted');
        } catch (InvalidTideData $exception) {
            $this->assertStringContainsString('rumus', $exception->getMessage());
        }
        $zip = new \ZipArchive;
        $zip->open($path);
        $zip->addFromString('xl/externalLinks/externalLink1.xml', '<externalLink/>');
        $zip->close();
        $this->expectException(InvalidTideData::class);
        (new KkprlTideDataReader)->read($path, 'xlsx');
    }

    #[Test]
    public function ambiguous_mapping_and_unknown_units_are_rejected(): void
    {
        $table = (new KkprlTideDataReader)->read($this->path("waktu,tinggi\n2026-10-01 00:00,1\n2026-10-01 01:00,2\n"), 'csv');
        foreach ([['height_column' => 0], ['unit' => 'feet'], ['timezone' => 'Unknown'], ['time_column' => 1]] as $changes) {
            try {
                (new KkprlTideDataAnalyzer)->analyze($table, $this->options($changes));
                $this->fail('Invalid mapping accepted');
            } catch (InvalidTideData $exception) {
                $this->assertStringContainsString('Periksa pilihan', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function workbook_1904_calendar_and_separate_numeric_clock_are_preserved(): void
    {
        $old = Date::getExcelCalendar();
        Date::setExcelCalendar(Date::CALENDAR_MAC_1904);
        try {
            $serial = Date::PHPToExcel(new \DateTimeImmutable('2026-10-01 00:00:00'));
            $book = new Spreadsheet;
            $book->getActiveSheet()->fromArray([['Tanggal', 'Jam', 'Tinggi'], [$serial, 0, 100], [$serial, 0.5, 200]], null, 'A1', true);
            $path = $this->path();
            (new Xlsx($book))->save($path);
            $book->disconnectWorksheets();
        } finally {
            Date::setExcelCalendar($old);
        }
        $table = (new KkprlTideDataReader)->read($path, 'xlsx');
        $this->assertSame(Date::CALENDAR_MAC_1904, $table['calendar']);
        $summary = (new KkprlTideDataAnalyzer)->analyze($table, $this->options(['time_column' => 1, 'height_column' => 2]));
        $this->assertSame('2026-10-01 00:00:00', $summary['start']);
        $this->assertSame('2026-10-01 12:00:00', $summary['end']);
        $this->assertSame($old, Date::getExcelCalendar());
    }

    #[Test]
    public function compressed_workbook_expansion_is_bounded_before_parsing(): void
    {
        $path = $this->path();
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('xl/workbook.xml', '<workbook/>');
        $zip->addFromString('xl/sharedStrings.xml', str_repeat('x', 33 * 1024 * 1024));
        $zip->close();
        $this->expectException(InvalidTideData::class);
        (new KkprlTideDataReader)->read($path, 'xlsx');
    }

    #[Test]
    public function worksheet_cell_area_is_bounded_for_shared_hosting_memory(): void
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray(array_fill(0, 20, 'kolom'));
        $lastRow = intdiv(KkprlTideDataReader::MAX_CELLS, 20) + 2;
        $book->getActiveSheet()->setCellValue('T'.$lastRow, 1);
        $path = $this->path();
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        $this->expectException(InvalidTideData::class);
        (new KkprlTideDataReader)->read($path, 'xlsx');
    }

    #[Test]
    public function a_file_without_headers_cannot_silently_lose_its_first_measurement(): void
    {
        $this->expectException(InvalidTideData::class);
        (new KkprlTideDataReader)->read($this->path("2026-10-01 00:00,1\n2026-10-01 01:00,2\n2026-10-01 02:00,3\n"), 'csv');
    }
}
