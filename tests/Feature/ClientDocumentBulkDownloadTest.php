<?php

namespace Tests\Feature;

use App\Filament\Layanankkprl\Resources\Clients\Pages\ListClients;
use App\Models\BeritaAcara;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use App\Services\ClientDocumentArchiveService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Smalot\PdfParser\Parser;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use ZipArchive;

class ClientDocumentBulkDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 10, 16)->setTime(10, 0));
        $user = User::factory()->create();
        foreach (['ViewAny:Client', 'View:Client'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('layanankkprl'));
        Filament::bootCurrentPanel();
    }

    public static function documentChoices(): array
    {
        return [
            'reports' => ['downloadReports', 'Laporan-Konsultasi', 2, 0],
            'minutes' => ['downloadBeritaAcara', 'Berita-Acara', 0, 2],
            'both' => ['downloadClientDocuments', 'Dokumen-Klien', 2, 2],
        ];
    }

    public static function actionNames(): array
    {
        return array_map(fn (array $choice): array => [$choice[0]], self::documentChoices());
    }

    #[DataProvider('documentChoices')]
    public function test_selected_document_types_are_downloaded_and_missing_or_unselected_documents_are_skipped(string $action, string $archivePrefix, int $reportCount, int $minutesCount): void
    {
        $both = $this->client('Kedua Dokumen');
        $both->consultationReports()->create(['content' => '<p>LAPORAN LAMA</p>', 'status' => 'draft']);
        $both->consultationReports()->create(['content' => '<p>LAPORAN TERBARU</p>', 'status' => 'draft']);
        $this->minutes($both);
        $reportOnly = $this->client('Hanya Laporan');
        $reportOnly->consultationReports()->create(['content' => '<p>LAPORAN KEDUA</p>', 'status' => 'draft']);
        $minutesOnly = $this->client('Hanya Berita Acara');
        $this->minutes($minutesOnly);
        $empty = $this->client('Tanpa Dokumen');
        $unselected = $this->client('Tidak Dipilih');
        $unselected->consultationReports()->create(['content' => '<p>JANGAN DIUNDUH</p>', 'status' => 'draft']);
        $this->minutes($unselected);

        $component = Livewire::test(ListClients::class)
            ->callTableBulkAction($action, [$both, $reportOnly, $minutesOnly, $empty])
            ->assertFileDownloaded($archivePrefix.'-20261016-100000.zip');

        $entries = $this->archiveEntries(base64_decode($component->effects['download']['content'], true));
        $reports = array_filter($entries, fn ($name) => str_starts_with($name, 'Laporan/'), ARRAY_FILTER_USE_KEY);
        $minutes = array_filter($entries, fn ($name) => str_starts_with($name, 'Berita-Acara/'), ARRAY_FILTER_USE_KEY);
        $this->assertCount($reportCount, $reports);
        $this->assertCount($minutesCount, $minutes);
        $this->assertCount($reportCount + $minutesCount, $entries);
        foreach ($entries as $name => $pdf) {
            $this->assertStringNotContainsString('..', $name);
            $this->assertStringNotContainsString('\\', $name);
            $this->assertStringStartsWith('%PDF-', $pdf);
        }
        if ($reportCount > 0) {
            $text = implode('\n', array_map(fn ($pdf) => (new Parser)->parseContent($pdf)->getText(), $reports));
            $this->assertStringContainsString('LAPORAN TERBARU', $text);
            $this->assertStringContainsString('LAPORAN KEDUA', $text);
            $this->assertStringNotContainsString('LAPORAN LAMA', $text);
            $this->assertStringNotContainsString('JANGAN DIUNDUH', $text);
        }
        $this->assertSame([], Storage::disk('local')->allFiles('client-document-downloads'));
        $this->assertSame([], Storage::disk('local')->directories('client-document-downloads'));
        $this->assertSame('draft', $both->latestConsultationReport->status);
        Mail::assertNothingSent();
    }

    #[DataProvider('actionNames')]
    public function test_no_download_is_created_when_the_requested_documents_are_missing(string $action): void
    {
        $client = $this->client('Tanpa Dokumen');
        if ($action === 'downloadReports') {
            $this->minutes($client);
        } elseif ($action === 'downloadBeritaAcara') {
            $client->consultationReports()->create(['content' => '<p>BUKAN PILIHAN UNDUHAN</p>', 'status' => 'draft']);
        }

        Livewire::test(ListClients::class)
            ->callTableBulkAction($action, [$client])
            ->assertNoFileDownloaded()
            ->assertNotified('Tidak ada dokumen yang tersedia');

        $this->assertSame([], Storage::disk('local')->allFiles('client-document-downloads'));
        $this->assertSame([], Storage::disk('local')->directories('client-document-downloads'));
    }

    public function test_soft_deleted_documents_are_not_exported(): void
    {
        $client = $this->client('Dokumen Dihapus');
        $client->consultationReports()->create(['content' => '<p>DIHAPUS</p>', 'status' => 'draft'])->delete();
        $this->minutes($client)->delete();

        Livewire::test(ListClients::class)
            ->callTableBulkAction('downloadClientDocuments', [$client])
            ->assertNoFileDownloaded()
            ->assertNotified('Tidak ada dokumen yang tersedia');
    }

    public function test_client_view_permission_is_required_to_export_selected_documents(): void
    {
        $client = $this->client('Terbatas');
        $client->consultationReports()->create(['content' => '<p>RAHASIA</p>', 'status' => 'draft']);
        $this->minutes($client);
        auth()->user()->revokePermissionTo('View:Client');

        Livewire::test(ListClients::class)
            ->callTableBulkAction('downloadClientDocuments', [$client])
            ->assertNoFileDownloaded();

        $this->assertSame([], Storage::disk('local')->allFiles('client-document-downloads'));
    }

    public function test_archive_service_rejects_unauthorized_access_and_cleans_up_after_render_failure(): void
    {
        $client = $this->client('Gagal Render');
        $client->consultationReports()->create(['content' => '<p>LAPORAN</p>', 'status' => 'draft']);
        Pdf::shouldReceive('loadView')->once()->andThrow(new \RuntimeException('PDF render failed'));

        try {
            app(ClientDocumentArchiveService::class)->download(Client::whereKey($client->id)->get(), true, false);
            $this->fail('Expected a PDF rendering failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('PDF render failed', $exception->getMessage());
        }
        $this->assertSame([], Storage::disk('local')->allFiles('client-document-downloads'));
        $this->assertSame([], Storage::disk('local')->directories('client-document-downloads'));
        auth()->user()->revokePermissionTo('View:Client');
        $this->expectException(AuthorizationException::class);
        app(ClientDocumentArchiveService::class)->download(Client::whereKey($client->id)->get(), true, false);
    }

    private function client(string $name): Client
    {
        return Client::create([
            'service_id' => Service::firstOrCreate(['name' => 'Konsultasi Bulk'])->id,
            'contact_details' => ['name' => $name],
        ]);
    }

    private function minutes(Client $client): BeritaAcara
    {
        return $client->beritaAcara()->create([
            'nomor_berita_acara' => 'BA/2026\\BULK/../1',
            'tanggal_pelaksanaan' => today(),
            'hasil_pendampingan' => '<p>HASIL PENDAMPINGAN</p>',
            'status' => 'draft',
        ]);
    }

    private function archiveEntries(string $binary): array
    {
        $path = tempnam(sys_get_temp_dir(), 'client-bulk-test-');
        file_put_contents($path, $binary);
        $archive = new ZipArchive;
        try {
            $this->assertTrue($archive->open($path));
            $entries = [];
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entries[$archive->getNameIndex($index)] = $archive->getFromIndex($index);
            }

            return $entries;
        } finally {
            $archive->close();
            unlink($path);
        }
    }
}
