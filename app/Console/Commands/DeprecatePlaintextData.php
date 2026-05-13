<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeprecatePlaintextData extends Command
{
    protected $signature = 'data-protection:deprecate-plaintext
        {--dry-run : Count redactable legacy plaintext fields without writing}
        {--force : Allow writes in production}';

    protected $description = 'Redact legacy sensitive plaintext columns after encrypted backfill is verified.';

    /** @var array<string, int> */
    private array $summary = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && app()->isProduction() && ! $this->option('force')) {
            $this->error('Refusing to redact plaintext in production without --force. Confirm backups first.');

            return self::FAILURE;
        }

        $this->line($dryRun ? 'Plaintext deprecation dry run.' : 'Plaintext deprecation started.');

        foreach ($this->tables() as $table) {
            $this->redactTable($table, $dryRun);
        }

        foreach ($this->summary as $key => $value) {
            $this->line(str_replace('_', ' ', $key).": {$value}");
        }

        Log::info('Data protection plaintext deprecation '.($dryRun ? 'dry_run' : 'completed'), $this->summary);

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{table: string, key: string, fields: array<int, array{legacy: string, encrypted?: string, hash?: string, replacement?: mixed}>}>
     */
    private function tables(): array
    {
        return [
            [
                'table' => 'clients',
                'key' => 'id',
                'fields' => [
                    ['legacy' => 'contact_details', 'replacement' => json_encode([])],
                    ['legacy' => 'access_token', 'encrypted' => 'access_token_encrypted', 'hash' => 'access_token_hash'],
                    ['legacy' => 'name', 'encrypted' => 'name_encrypted'],
                    ['legacy' => 'email', 'encrypted' => 'email_encrypted', 'hash' => 'email_hash'],
                    ['legacy' => 'whatsapp', 'encrypted' => 'whatsapp_encrypted', 'hash' => 'whatsapp_hash'],
                    ['legacy' => 'instance', 'encrypted' => 'instance_encrypted'],
                    ['legacy' => 'address', 'encrypted' => 'address_encrypted'],
                    ['legacy' => 'metadata', 'encrypted' => 'metadata_encrypted'],
                    ['legacy' => 'supporting_documents', 'encrypted' => 'supporting_documents_encrypted'],
                    ['legacy' => 'supporting_document_links', 'encrypted' => 'supporting_document_links_encrypted'],
                    ['legacy' => 'coordinate_file', 'encrypted' => 'coordinate_file_encrypted'],
                ],
            ],
            [
                'table' => 'schedules',
                'key' => 'id',
                'fields' => [
                    ['legacy' => 'meeting_link', 'encrypted' => 'meeting_link_encrypted'],
                ],
            ],
            [
                'table' => 'berita_acara',
                'key' => 'id',
                'fields' => [
                    ['legacy' => 'attendance_url_token', 'encrypted' => 'attendance_url_token_encrypted', 'hash' => 'attendance_url_token_hash'],
                    ['legacy' => 'lokasi_permohonan', 'encrypted' => 'lokasi_permohonan_encrypted'],
                    ['legacy' => 'hasil_pendampingan', 'encrypted' => 'hasil_pendampingan_encrypted'],
                    ['legacy' => 'tanda_tangan_pemohon', 'encrypted' => 'tanda_tangan_pemohon_encrypted'],
                    ['legacy' => 'lampiran_peta', 'encrypted' => 'lampiran_peta_encrypted'],
                    ['legacy' => 'lampiran_dokumentasi', 'encrypted' => 'lampiran_dokumentasi_encrypted'],
                    ['legacy' => 'lampiran_lainnya', 'encrypted' => 'lampiran_lainnya_encrypted'],
                ],
            ],
            [
                'table' => 'berita_acara_attendees',
                'key' => 'id',
                'fields' => [
                    ['legacy' => 'token', 'encrypted' => 'token_encrypted', 'hash' => 'token_hash'],
                    ['legacy' => 'nama', 'encrypted' => 'nama_encrypted'],
                    ['legacy' => 'jabatan', 'encrypted' => 'jabatan_encrypted'],
                    ['legacy' => 'instansi', 'encrypted' => 'instansi_encrypted'],
                    ['legacy' => 'email', 'encrypted' => 'email_encrypted', 'hash' => 'email_hash'],
                    ['legacy' => 'no_hp', 'encrypted' => 'no_hp_encrypted'],
                    ['legacy' => 'tanda_tangan', 'encrypted' => 'tanda_tangan_encrypted'],
                ],
            ],
            [
                'table' => 'consultation_reports',
                'key' => 'id',
                'fields' => [
                    ['legacy' => 'content', 'encrypted' => 'content_encrypted'],
                    ['legacy' => 'feedback', 'encrypted' => 'feedback_encrypted'],
                    ['legacy' => 'documentation', 'encrypted' => 'documentation_encrypted'],
                ],
            ],
            [
                'table' => 'satisfaction_surveys',
                'key' => 'id',
                'fields' => [
                    ['legacy' => 'criticism', 'encrypted' => 'criticism_encrypted'],
                    ['legacy' => 'suggestion', 'encrypted' => 'suggestion_encrypted'],
                ],
            ],
            [
                'table' => 'notification_logs',
                'key' => 'id',
                'fields' => [
                    ['legacy' => 'destination', 'encrypted' => 'destination_encrypted'],
                    ['legacy' => 'message_body', 'encrypted' => 'message_body_encrypted'],
                ],
            ],
        ];
    }

    /**
     * @param array{table: string, key: string, fields: array<int, array{legacy: string, encrypted?: string, hash?: string, replacement?: mixed}>} $table
     */
    private function redactTable(array $table, bool $dryRun): void
    {
        DB::table($table['table'])
            ->orderBy($table['key'])
            ->cursor()
            ->each(function (object $row) use ($table, $dryRun): void {
                $updates = [];

                foreach ($table['fields'] as $field) {
                    if (! $this->hasLegacyValue($row->{$field['legacy']} ?? null, $field['replacement'] ?? null)) {
                        continue;
                    }

                    if (! $this->canRedact($row, $field)) {
                        $this->increment($table['table'].'_'.$field['legacy'].'_blocked');

                        continue;
                    }

                    $updates[$field['legacy']] = $field['replacement'] ?? null;
                    $this->increment($table['table'].'_'.$field['legacy'].'_redactable');
                }

                if ($updates === [] || $dryRun) {
                    return;
                }

                DB::table($table['table'])
                    ->where($table['key'], $row->{$table['key']})
                    ->update($updates);

                $this->increment($table['table'].'_rows_updated');
            });
    }

    /**
     * @param array{legacy: string, encrypted?: string, hash?: string, replacement?: mixed} $field
     */
    private function canRedact(object $row, array $field): bool
    {
        if (isset($field['hash']) && blank($row->{$field['hash']} ?? null)) {
            return false;
        }

        if (! isset($field['encrypted'])) {
            return true;
        }

        $encrypted = $row->{$field['encrypted']} ?? null;

        if (blank($encrypted)) {
            return false;
        }

        try {
            return filled(Crypt::decryptString($encrypted));
        } catch (\Throwable) {
            return false;
        }
    }

    private function hasLegacyValue(mixed $value, mixed $replacement): bool
    {
        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '' || $trimmed === $replacement) {
                return false;
            }

            $decoded = json_decode($trimmed, true);

            if (($decoded === [] || $decoded === null) && json_last_error() === JSON_ERROR_NONE) {
                return false;
            }

            return true;
        }

        return filled($value);
    }

    private function increment(string $key): void
    {
        $this->summary[$key] = ($this->summary[$key] ?? 0) + 1;
    }
}
