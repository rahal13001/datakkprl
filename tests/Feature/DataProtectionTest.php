<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\BeritaAcara;
use App\Models\BeritaAcaraAttendee;
use App\Models\ConsultationReport;
use App\Models\NotificationLog;
use App\Models\SatisfactionSurvey;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use App\Services\DataHashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DataProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    #[Test]
    public function client_creation_populates_hashes_and_encrypted_columns(): void
    {
        $service = Service::create(['name' => 'Protection Test Service']);

        $client = Client::create([
            'contact_details' => [],
            'name' => 'Privacy Person',
            'email' => 'Privacy.Person@Example.test',
            'whatsapp' => '+62 812-3456-7890',
            'address' => 'Sensitive address',
            'service_id' => $service->id,
        ]);

        $raw = DB::table('clients')->where('id', $client->id)->first();

        $this->assertNotEmpty($raw->access_token_hash);
        $this->assertNotEmpty($raw->email_hash);
        $this->assertNotEmpty($raw->whatsapp_hash);
        $this->assertNotEmpty($raw->name_encrypted);
        $this->assertNotSame('Privacy Person', $raw->name_encrypted);
        $this->assertNull($raw->access_token);
        $this->assertNull($raw->name);
        $this->assertNull($raw->email);
        $this->assertNull($raw->whatsapp);
        $this->assertSame([], json_decode($raw->contact_details, true));
        $this->assertSame(
            app(DataHashService::class)->email('privacy.person@example.test'),
            $raw->email_hash,
        );
        $this->assertSame('Privacy.Person@Example.test', $client->fresh()->email);
    }

    #[Test]
    public function protected_model_writes_do_not_persist_sensitive_legacy_plaintext(): void
    {
        $service = Service::create(['name' => 'Protected Write Service']);
        $client = Client::create([
            'contact_details' => [],
            'name' => 'Protected Client',
            'email' => 'protected@example.test',
            'whatsapp' => '+62 811 1111 1111',
            'address' => 'Protected address',
            'metadata' => ['activity' => 'Protected metadata'],
            'supporting_documents' => ['client-documents/protected.pdf'],
            'supporting_document_links' => ['https://files.example.test/protected'],
            'coordinate_file' => 'client-documents/protected-coordinate.xlsx',
            'service_id' => $service->id,
        ]);

        $schedule = Schedule::create([
            'client_id' => $client->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'is_online' => true,
            'meeting_link' => 'https://meet.example.test/protected-room',
        ]);

        $beritaAcara = BeritaAcara::create([
            'client_id' => $client->id,
            'tanggal_pelaksanaan' => now()->toDateString(),
            'lokasi_permohonan' => 'Protected location',
            'hasil_pendampingan' => 'Protected consultation result',
            'tanda_tangan_pemohon' => 'signatures/protected-applicant.txt',
            'lampiran_peta' => 'attachments/protected-map.pdf',
            'lampiran_dokumentasi' => ['attachments/protected-photo.jpg'],
            'lampiran_lainnya' => ['attachments/protected-extra.pdf'],
            'status' => 'draft',
            'attendance_is_open' => true,
        ]);

        $attendee = BeritaAcaraAttendee::create([
            'berita_acara_id' => $beritaAcara->id,
            'nama' => 'Protected Attendee',
            'jabatan' => 'Protected Role',
            'instansi' => 'Protected Agency',
            'email' => 'attendee@example.test',
            'no_hp' => '+62 812 2222 2222',
            'tanda_tangan' => 'signatures/protected-attendee.txt',
            'is_officer' => false,
            'is_signatory' => true,
        ]);

        $report = ConsultationReport::create([
            'client_id' => $client->id,
            'content' => 'Protected report content',
            'feedback' => 'Protected report feedback',
            'documentation' => ['reports/protected-documentation.pdf'],
            'status' => 'draft',
        ]);

        $survey = SatisfactionSurvey::create([
            'client_id' => $client->id,
            'criticism' => 'Protected criticism',
            'suggestion' => 'Protected suggestion',
        ]);

        $notification = NotificationLog::create([
            'client_id' => $client->id,
            'channel' => 'email',
            'destination' => 'notify@example.test',
            'message_body' => 'Protected notification body',
            'status' => 'sent',
        ]);

        $rawClient = DB::table('clients')->where('id', $client->id)->first();
        $this->assertNull($rawClient->name);
        $this->assertNull($rawClient->email);
        $this->assertNull($rawClient->whatsapp);
        $this->assertNull($rawClient->address);
        $this->assertNull($rawClient->metadata);
        $this->assertNull($rawClient->supporting_documents);
        $this->assertNull($rawClient->supporting_document_links);
        $this->assertNull($rawClient->coordinate_file);
        $this->assertNotEmpty($rawClient->name_encrypted);
        $this->assertNotEmpty($rawClient->email_hash);
        $this->assertSame('protected@example.test', $client->fresh()->email);

        $rawSchedule = DB::table('schedules')->where('id', $schedule->id)->first();
        $this->assertNull($rawSchedule->meeting_link);
        $this->assertNotEmpty($rawSchedule->meeting_link_encrypted);
        $this->assertSame('https://meet.example.test/protected-room', $schedule->fresh()->meeting_link);

        $rawBeritaAcara = DB::table('berita_acara')->where('id', $beritaAcara->id)->first();
        $this->assertNull($rawBeritaAcara->attendance_url_token);
        $this->assertNull($rawBeritaAcara->lokasi_permohonan);
        $this->assertNull($rawBeritaAcara->hasil_pendampingan);
        $this->assertNull($rawBeritaAcara->tanda_tangan_pemohon);
        $this->assertNull($rawBeritaAcara->lampiran_peta);
        $this->assertNull($rawBeritaAcara->lampiran_dokumentasi);
        $this->assertNull($rawBeritaAcara->lampiran_lainnya);
        $this->assertNotEmpty($rawBeritaAcara->attendance_url_token_hash);
        $this->assertNotEmpty($rawBeritaAcara->lokasi_permohonan_encrypted);
        $this->assertSame('Protected location', $beritaAcara->fresh()->lokasi_permohonan);

        $rawAttendee = DB::table('berita_acara_attendees')->where('id', $attendee->id)->first();
        $this->assertNull($rawAttendee->token);
        $this->assertNull($rawAttendee->nama);
        $this->assertNull($rawAttendee->jabatan);
        $this->assertNull($rawAttendee->instansi);
        $this->assertNull($rawAttendee->email);
        $this->assertNull($rawAttendee->no_hp);
        $this->assertNull($rawAttendee->tanda_tangan);
        $this->assertNotEmpty($rawAttendee->token_hash);
        $this->assertNotEmpty($rawAttendee->email_hash);
        $this->assertSame('Protected Attendee', $attendee->fresh()->nama);

        $rawReport = DB::table('consultation_reports')->where('id', $report->id)->first();
        $this->assertNull($rawReport->content);
        $this->assertNull($rawReport->feedback);
        $this->assertNull($rawReport->documentation);
        $this->assertNotEmpty($rawReport->content_encrypted);
        $this->assertSame('Protected report content', $report->fresh()->content);

        $rawSurvey = DB::table('satisfaction_surveys')->where('id', $survey->id)->first();
        $this->assertNull($rawSurvey->criticism);
        $this->assertNull($rawSurvey->suggestion);
        $this->assertNotEmpty($rawSurvey->criticism_encrypted);
        $this->assertSame('Protected suggestion', $survey->fresh()->suggestion);

        $rawNotification = DB::table('notification_logs')->where('id', $notification->id)->first();
        $this->assertNull($rawNotification->destination);
        $this->assertNull($rawNotification->message_body);
        $this->assertNotEmpty($rawNotification->destination_encrypted);
        $this->assertSame('notify@example.test', $notification->fresh()->destination);
    }

    #[Test]
    public function existing_ticket_token_lookup_works_through_hash(): void
    {
        $service = Service::create(['name' => 'Lookup Test Service']);
        $client = Client::create([
            'contact_details' => ['name' => 'Lookup User', 'email' => 'lookup@example.test'],
            'service_id' => $service->id,
        ]);

        $tokenHash = app(DataHashService::class)->token($client->access_token);

        $found = Client::query()
            ->where('ticket_number', $client->ticket_number)
            ->where('access_token_hash', $tokenHash)
            ->first();

        $this->assertTrue($client->is($found));
        $this->assertTrue($client->fresh()->matchesAccessToken($client->access_token));
        $this->assertFalse($client->fresh()->matchesAccessToken('wrong-token'));
    }

    #[Test]
    public function private_client_file_route_requires_matching_token_or_admin(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $service = Service::create(['name' => 'Private File Service']);
        $client = Client::create([
            'contact_details' => ['name' => 'File User', 'email' => 'file@example.test'],
            'supporting_documents' => ['client-documents/proof.pdf'],
            'service_id' => $service->id,
        ]);

        Storage::disk('local')->put('client-documents/proof.pdf', 'private file');

        $this->get(route('client.files.download', [
            'client' => $client,
            'path' => 'client-documents/proof.pdf',
            'token' => 'wrong-token',
        ]))->assertForbidden();

        $this->get(route('client.files.download', [
            'client' => $client,
            'path' => 'client-documents/proof.pdf',
            'token' => $client->access_token,
        ]))->assertOk();

        $this->actingAs(User::factory()->create())
            ->get(route('private-files.admin', ['path' => 'client-documents/proof.pdf']))
            ->assertOk();
    }

    #[Test]
    public function backfill_command_encrypts_legacy_rows_and_copies_public_files(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $service = Service::create(['name' => 'Backfill Test Service']);
        Storage::disk('public')->put('client-documents/legacy.pdf', 'legacy file');

        DB::table('clients')->insert([
            'ticket_number' => 'TICKET-20260513-TEST',
            'access_token' => 'legacy-token',
            'contact_details' => json_encode([]),
            'name' => 'Legacy User',
            'email' => 'legacy@example.test',
            'whatsapp' => '+62 812 0000 0000',
            'status' => 'waiting',
            'metadata' => json_encode(['topic' => 'legacy']),
            'service_id' => $service->id,
            'supporting_documents' => json_encode(['client-documents/legacy.pdf']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('data-protection:backfill')
            ->expectsOutputToContain('Data protection backfill completed.')
            ->assertExitCode(0);

        $raw = DB::table('clients')->where('ticket_number', 'TICKET-20260513-TEST')->first();

        $this->assertNotEmpty($raw->access_token_hash);
        $this->assertNotEmpty($raw->email_hash);
        $this->assertNotEmpty($raw->name_encrypted);
        $this->assertNotSame('Legacy User', $raw->name_encrypted);
        $this->assertTrue(Storage::disk('local')->exists('client-documents/legacy.pdf'));

        $this->artisan('data-protection:deprecate-plaintext')
            ->expectsOutputToContain('Plaintext deprecation started.')
            ->assertExitCode(0);

        $redacted = DB::table('clients')->where('ticket_number', 'TICKET-20260513-TEST')->first();

        $this->assertNull($redacted->access_token);
        $this->assertNull($redacted->name);
        $this->assertNull($redacted->email);
        $this->assertNull($redacted->whatsapp);
        $this->assertSame([], json_decode($redacted->contact_details, true));

        $client = Client::where('ticket_number', 'TICKET-20260513-TEST')->first();

        $this->assertSame('Legacy User', $client->name);
        $this->assertSame('legacy@example.test', $client->email);
        $this->assertTrue($client->matchesAccessToken('legacy-token'));
    }
}
