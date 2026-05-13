<?php

namespace App\Console\Commands;

use App\Models\BeritaAcara;
use App\Models\BeritaAcaraAttendee;
use App\Models\Client;
use App\Models\ConsultationReport;
use App\Models\NotificationLog;
use App\Models\SatisfactionSurvey;
use App\Models\Schedule;
use App\Services\DataHashService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class BackfillDataProtection extends Command
{
    protected $signature = 'data-protection:backfill
        {--dry-run : Count rows and files without writing}
        {--files-only : Only copy legacy public files to private storage}
        {--chunk=100 : Number of rows to process per transaction}
        {--force : Allow writes in production}';

    protected $description = 'Backfill encrypted data-protection columns, HMAC lookup hashes, and private file copies.';

    private DataHashService $hashes;

    /** @var array<string, int> */
    private array $summary = [];

    public function handle(DataHashService $hashes): int
    {
        $this->hashes = $hashes;

        $dryRun = (bool) $this->option('dry-run');
        $filesOnly = (bool) $this->option('files-only');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if (! $dryRun && app()->isProduction() && ! $this->option('force')) {
            $this->error('Refusing to write in production without --force. Confirm backups and maintenance mode first.');

            return self::FAILURE;
        }

        $this->line($dryRun ? 'Data protection backfill dry run.' : 'Data protection backfill started.');

        $this->reportDryRunCounts();

        if ($dryRun) {
            $this->logSummary('dry_run');

            return self::SUCCESS;
        }

        if (! $filesOnly) {
            Model::withoutEvents(function () use ($chunkSize): void {
                $this->backfillClients($chunkSize);
                $this->backfillSchedules($chunkSize);
                $this->backfillBeritaAcara($chunkSize);
                $this->backfillAttendees($chunkSize);
                $this->backfillConsultationReports($chunkSize);
                $this->backfillSatisfactionSurveys($chunkSize);
                $this->backfillNotificationLogs($chunkSize);
            });
        }

        $this->copySensitivePublicFiles();
        $this->logSummary('completed');

        $this->info('Data protection backfill completed.');

        return self::SUCCESS;
    }

    private function reportDryRunCounts(): void
    {
        $counts = [
            'total_clients' => Client::withTrashed()->count(),
            'clients_missing_access_token_hash' => Client::withTrashed()
                ->whereNotNull('access_token')
                ->whereNull('access_token_hash')
                ->count(),
            'clients_missing_encrypted_identity_fields' => $this->countClientsMissingEncryptedIdentityFields(),
            'berita_acara_missing_attendance_token_hash' => BeritaAcara::withTrashed()
                ->whereNotNull('attendance_url_token')
                ->whereNull('attendance_url_token_hash')
                ->count(),
            'attendees_missing_token_hash' => BeritaAcaraAttendee::query()
                ->whereNotNull('token')
                ->whereNull('token_hash')
                ->count(),
            'sensitive_public_files_needing_migration' => $this->countSensitivePublicFilesNeedingMigration(),
        ];

        foreach ($counts as $key => $value) {
            $this->summary[$key] = $value;
            $this->line(str_replace('_', ' ', $key).": {$value}");
        }
    }

    private function backfillClients(int $chunkSize): void
    {
        Client::withTrashed()->chunkById($chunkSize, function ($clients): void {
            DB::transaction(function () use ($clients): void {
                foreach ($clients as $client) {
                    $updates = $this->encryptedUpdates($client, [
                        ['access_token', 'access_token_encrypted', 'string'],
                        ['name', 'name_encrypted', 'string'],
                        ['email', 'email_encrypted', 'string'],
                        ['whatsapp', 'whatsapp_encrypted', 'string'],
                        ['instance', 'instance_encrypted', 'string'],
                        ['address', 'address_encrypted', 'string'],
                        ['metadata', 'metadata_encrypted', 'array'],
                        ['supporting_documents', 'supporting_documents_encrypted', 'array'],
                        ['supporting_document_links', 'supporting_document_links_encrypted', 'array'],
                        ['coordinate_file', 'coordinate_file_encrypted', 'string'],
                    ]);

                    $this->putHashIfBlank($updates, $client, 'access_token_hash', 'access_token', 'token');
                    $this->putHashIfBlank($updates, $client, 'email_hash', 'email', 'email');
                    $this->putHashIfBlank($updates, $client, 'whatsapp_hash', 'whatsapp', 'phone');

                    $this->saveUpdates($client, $updates, 'clients_updated');
                }
            });
        });
    }

    private function countClientsMissingEncryptedIdentityFields(): int
    {
        $count = 0;

        Client::withTrashed()->cursor()->each(function (Client $client) use (&$count): void {
            $updates = $this->encryptedUpdates($client, [
                ['access_token', 'access_token_encrypted', 'string'],
                ['name', 'name_encrypted', 'string'],
                ['email', 'email_encrypted', 'string'],
                ['whatsapp', 'whatsapp_encrypted', 'string'],
                ['instance', 'instance_encrypted', 'string'],
                ['address', 'address_encrypted', 'string'],
                ['metadata', 'metadata_encrypted', 'array'],
                ['supporting_documents', 'supporting_documents_encrypted', 'array'],
                ['supporting_document_links', 'supporting_document_links_encrypted', 'array'],
                ['coordinate_file', 'coordinate_file_encrypted', 'string'],
            ]);

            if ($updates !== []) {
                $count++;
            }
        });

        return $count;
    }

    private function backfillSchedules(int $chunkSize): void
    {
        Schedule::withTrashed()->chunkById($chunkSize, function ($schedules): void {
            DB::transaction(function () use ($schedules): void {
                foreach ($schedules as $schedule) {
                    $updates = $this->encryptedUpdates($schedule, [
                        ['meeting_link', 'meeting_link_encrypted', 'string'],
                    ]);

                    $this->saveUpdates($schedule, $updates, 'schedules_updated');
                }
            });
        });
    }

    private function backfillBeritaAcara(int $chunkSize): void
    {
        BeritaAcara::withTrashed()->chunkById($chunkSize, function ($rows): void {
            DB::transaction(function () use ($rows): void {
                foreach ($rows as $row) {
                    $updates = $this->encryptedUpdates($row, [
                        ['attendance_url_token', 'attendance_url_token_encrypted', 'string'],
                        ['lokasi_permohonan', 'lokasi_permohonan_encrypted', 'string'],
                        ['hasil_pendampingan', 'hasil_pendampingan_encrypted', 'string'],
                        ['tanda_tangan_pemohon', 'tanda_tangan_pemohon_encrypted', 'string'],
                        ['lampiran_peta', 'lampiran_peta_encrypted', 'string'],
                        ['lampiran_dokumentasi', 'lampiran_dokumentasi_encrypted', 'array'],
                        ['lampiran_lainnya', 'lampiran_lainnya_encrypted', 'array'],
                    ]);

                    $this->putHashIfBlank($updates, $row, 'attendance_url_token_hash', 'attendance_url_token', 'token');

                    $this->saveUpdates($row, $updates, 'berita_acara_updated');
                }
            });
        });
    }

    private function backfillAttendees(int $chunkSize): void
    {
        BeritaAcaraAttendee::query()->chunkById($chunkSize, function ($attendees): void {
            DB::transaction(function () use ($attendees): void {
                foreach ($attendees as $attendee) {
                    $updates = $this->encryptedUpdates($attendee, [
                        ['token', 'token_encrypted', 'string'],
                        ['nama', 'nama_encrypted', 'string'],
                        ['jabatan', 'jabatan_encrypted', 'string'],
                        ['instansi', 'instansi_encrypted', 'string'],
                        ['email', 'email_encrypted', 'string'],
                        ['no_hp', 'no_hp_encrypted', 'string'],
                        ['tanda_tangan', 'tanda_tangan_encrypted', 'string'],
                    ]);

                    $this->putHashIfBlank($updates, $attendee, 'token_hash', 'token', 'token');
                    $this->putHashIfBlank($updates, $attendee, 'email_hash', 'email', 'email');

                    $this->saveUpdates($attendee, $updates, 'attendees_updated');
                }
            });
        });
    }

    private function backfillConsultationReports(int $chunkSize): void
    {
        ConsultationReport::withTrashed()->chunkById($chunkSize, function ($reports): void {
            DB::transaction(function () use ($reports): void {
                foreach ($reports as $report) {
                    $updates = $this->encryptedUpdates($report, [
                        ['content', 'content_encrypted', 'string'],
                        ['feedback', 'feedback_encrypted', 'string'],
                        ['documentation', 'documentation_encrypted', 'array'],
                    ]);

                    $this->saveUpdates($report, $updates, 'consultation_reports_updated');
                }
            });
        });
    }

    private function backfillSatisfactionSurveys(int $chunkSize): void
    {
        SatisfactionSurvey::query()->chunkById($chunkSize, function ($surveys): void {
            DB::transaction(function () use ($surveys): void {
                foreach ($surveys as $survey) {
                    $updates = $this->encryptedUpdates($survey, [
                        ['criticism', 'criticism_encrypted', 'string'],
                        ['suggestion', 'suggestion_encrypted', 'string'],
                    ]);

                    $this->saveUpdates($survey, $updates, 'satisfaction_surveys_updated');
                }
            });
        });
    }

    private function backfillNotificationLogs(int $chunkSize): void
    {
        NotificationLog::query()->chunkById($chunkSize, function ($logs): void {
            DB::transaction(function () use ($logs): void {
                foreach ($logs as $log) {
                    $updates = $this->encryptedUpdates($log, [
                        ['destination', 'destination_encrypted', 'string'],
                        ['message_body', 'message_body_encrypted', 'string'],
                    ]);

                    $this->saveUpdates($log, $updates, 'notification_logs_updated');
                }
            });
        });
    }

    /**
     * @param array<int, array{0: string, 1: string, 2: string}> $fields
     * @return array<string, mixed>
     */
    private function encryptedUpdates(Model $model, array $fields): array
    {
        $updates = [];

        foreach ($fields as [$legacyColumn, $encryptedColumn, $type]) {
            if ($this->encryptedColumnIsBackfilled($model, $encryptedColumn)) {
                continue;
            }

            $value = $this->plainLegacyValue($model, $legacyColumn, $type);

            if (! $this->hasValue($value)) {
                continue;
            }

            $updates[$encryptedColumn] = $value;
        }

        return $updates;
    }

    private function encryptedColumnIsBackfilled(Model $model, string $encryptedColumn): bool
    {
        if (! $this->hasValue($model->getRawOriginal($encryptedColumn))) {
            return false;
        }

        try {
            return $this->hasValue($model->getAttributeValue($encryptedColumn));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $updates
     */
    private function putHashIfBlank(array &$updates, Model $model, string $hashColumn, string $legacyColumn, string $kind): void
    {
        if ($this->hasValue($model->getRawOriginal($hashColumn))) {
            return;
        }

        $value = $this->plainLegacyValue($model, $legacyColumn, 'string');

        $hash = match ($kind) {
            'email' => $this->hashes->email($value),
            'phone' => $this->hashes->phone($value),
            default => $this->hashes->token($value),
        };

        if ($hash !== null) {
            $updates[$hashColumn] = $hash;
        }
    }

    /**
     * @param array<string, mixed> $updates
     */
    private function saveUpdates(Model $model, array $updates, string $counter): void
    {
        if ($updates === []) {
            return;
        }

        DB::table($model->getTable())
            ->where($model->getKeyName(), $model->getKey())
            ->update($this->databaseUpdates($updates));

        $this->increment($counter);
    }

    /**
     * @param array<string, mixed> $updates
     * @return array<string, mixed>
     */
    private function databaseUpdates(array $updates): array
    {
        return collect($updates)
            ->mapWithKeys(function (mixed $value, string $column): array {
                if (! str_ends_with($column, '_encrypted') || $value === null) {
                    return [$column => $value];
                }

                return [$column => Crypt::encryptString(is_array($value) ? json_encode($value) : $value)];
            })
            ->all();
    }

    private function plainLegacyValue(Model $model, string $column, string $type): mixed
    {
        $rawValue = $model->getRawOriginal($column);

        if ($type === 'array') {
            return $this->arrayValue($rawValue);
        }

        return is_string($rawValue) ? trim($rawValue) : $rawValue;
    }

    private function arrayValue(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value === [] ? null : $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) && $decoded !== [] ? $decoded : null;
    }

    private function hasValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return filled($value);
    }

    private function countSensitivePublicFilesNeedingMigration(): int
    {
        $count = 0;

        $this->forEachSensitivePath(function (string $path) use (&$count): void {
            if (! Storage::disk('local')->exists($path) && Storage::disk('public')->exists($path)) {
                $count++;
            }
        });

        return $count;
    }

    private function copySensitivePublicFiles(): void
    {
        $this->forEachSensitivePath(function (string $path): void {
            if (Storage::disk('local')->exists($path) || ! Storage::disk('public')->exists($path)) {
                return;
            }

            try {
                $readStream = Storage::disk('public')->readStream($path);

                if ($readStream === false) {
                    $this->increment('file_copy_failures');

                    return;
                }

                Storage::disk('local')->put($path, $readStream);
                $this->increment('files_copied_to_private');
            } catch (Throwable $exception) {
                report($exception);
                $this->increment('file_copy_failures');
            } finally {
                if (isset($readStream) && is_resource($readStream)) {
                    fclose($readStream);
                }
            }
        });
    }

    private function forEachSensitivePath(callable $callback): void
    {
        $seen = [];

        $emit = function (mixed $value) use (&$seen, $callback): void {
            foreach ($this->filePathsFromValue($value) as $path) {
                if (isset($seen[$path])) {
                    continue;
                }

                $seen[$path] = true;
                $callback($path);
            }
        };

        Client::withTrashed()
            ->select(['supporting_documents', 'coordinate_file'])
            ->cursor()
            ->each(function (Client $client) use ($emit): void {
                $emit($this->arrayValue($client->getRawOriginal('supporting_documents')));
                $emit($client->getRawOriginal('coordinate_file'));
            });

        ConsultationReport::withTrashed()
            ->select(['documentation'])
            ->cursor()
            ->each(fn (ConsultationReport $report) => $emit($this->arrayValue($report->getRawOriginal('documentation'))));

        BeritaAcara::withTrashed()
            ->select(['lampiran_peta', 'lampiran_dokumentasi', 'lampiran_lainnya', 'tanda_tangan_pemohon'])
            ->cursor()
            ->each(function (BeritaAcara $row) use ($emit): void {
                $emit($row->getRawOriginal('lampiran_peta'));
                $emit($this->arrayValue($row->getRawOriginal('lampiran_dokumentasi')));
                $emit($this->arrayValue($row->getRawOriginal('lampiran_lainnya')));
                $emit($row->getRawOriginal('tanda_tangan_pemohon'));
            });

        BeritaAcaraAttendee::query()
            ->select(['tanda_tangan'])
            ->cursor()
            ->each(fn (BeritaAcaraAttendee $attendee) => $emit($attendee->getRawOriginal('tanda_tangan')));
    }

    /**
     * @return array<int, string>
     */
    private function filePathsFromValue(mixed $value): array
    {
        if (! $this->hasValue($value)) {
            return [];
        }

        if (is_array($value)) {
            return collect($value)
                ->flatMap(fn ($item) => $this->filePathsFromValue($item))
                ->values()
                ->all();
        }

        if (! is_string($value)) {
            return [];
        }

        $path = str_replace('\\', '/', trim($value));

        if (
            $path === ''
            || str_contains($path, '..')
            || str_starts_with($path, '/')
            || preg_match('/^[a-zA-Z]:\//', $path)
            || str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, 'data:')
        ) {
            return [];
        }

        return [$path];
    }

    private function increment(string $key, int $amount = 1): void
    {
        $this->summary[$key] = ($this->summary[$key] ?? 0) + $amount;
    }

    private function logSummary(string $stage): void
    {
        Log::info('Data protection backfill '.$stage, $this->summary);

        foreach ($this->summary as $key => $value) {
            if (str_starts_with($key, 'total_') || str_contains($key, 'missing') || str_contains($key, 'updated') || str_contains($key, 'files_') || str_contains($key, 'failures')) {
                $this->line(str_replace('_', ' ', $key).": {$value}");
            }
        }
    }
}
