<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Smalot\PdfParser\Parser;
use Symfony\Component\Process\ExecutableFinder;
use ZipArchive;

class KkprlPreflight extends Command
{
    protected $signature = 'kkprl:preflight {--strict : Return failure when any release prerequisite is missing}';

    protected $description = 'Check runtime prerequisites for KKPRL proposal generation and private artifacts';

    public function handle(): int
    {
        $checks = [
            'APP_KEY' => filled(config('app.key')),
            'Private storage' => $this->privateStorageIsConfigured(),
            'Session cookie security' => $this->sessionCookieIsSafe(),
            'DOCX ZIP support' => class_exists(ZipArchive::class),
            'PDF generator' => class_exists(Pdf::class),
            'PDF parser' => class_exists(Parser::class),
            'PDF renderer format' => in_array(config('kkprl.pdf_renderer.format'), ['png', 'bmp'], true),
            'PDF attachment renderer' => $this->rendererIsAvailable(),
            'Image normalization' => $this->imageNormalizationIsAvailable(),
            'KKPRL templates' => $this->templatesAreAvailable(),
        ];

        $failed = false;
        foreach ($checks as $label => $passed) {
            $passed = (bool) $passed;
            $failed = $failed || ! $passed;
            $this->line(($passed ? '<fg=green>PASS</> ' : '<fg=red>FAIL</> ').$label);
        }

        if ($failed && $this->option('strict')) {
            $this->error('KKPRL preflight gagal: runtime belum siap untuk release.');

            return self::FAILURE;
        }

        if ($failed) {
            $this->warn('KKPRL preflight menemukan prerequisite yang belum tersedia. Gunakan --strict pada release gate.');
        }

        return self::SUCCESS;
    }

    private function privateStorageIsConfigured(): bool
    {
        $disk = config('filesystems.disks.'.config('kkprl.storage_disk'), []);
        $root = is_array($disk) ? ($disk['root'] ?? null) : null;
        $publicRoot = realpath(public_path());
        $storageRoot = is_string($root) ? realpath($root) : false;

        if (! is_array($disk)
            || ($disk['driver'] ?? null) !== 'local'
            || ($disk['serve'] ?? true) !== false
            || ! is_string($root)) {
            return false;
        }

        if ($publicRoot === false || $storageRoot === false) {
            return ! str_starts_with($root, public_path().DIRECTORY_SEPARATOR);
        }

        return $storageRoot !== $publicRoot
            && ! str_starts_with($storageRoot, $publicRoot.DIRECTORY_SEPARATOR);
    }

    private function sessionCookieIsSafe(): bool
    {
        if (! (bool) config('session.http_only')
            || ! in_array(config('session.same_site'), ['lax', 'strict', 'none'], true)) {
            return false;
        }

        return ! app()->environment('production') || config('session.secure') === true;
    }

    private function rendererIsAvailable(): bool
    {
        $binary = config('kkprl.pdf_renderer.binary');
        if (! is_string($binary) || trim($binary) === '') {
            return false;
        }

        try {
            return (new ExecutableFinder)->find($binary) !== null
                || is_file($binary);
        } catch (\Throwable) {
            return false;
        }
    }

    private function templatesAreAvailable(): bool
    {
        $templates = glob(base_path('docs/template/*.docx')) ?: [];

        return count($templates) >= 5;
    }

    private function imageNormalizationIsAvailable(): bool
    {
        if (! function_exists('imagepng')) {
            return false;
        }

        if (strtolower((string) config('kkprl.pdf_renderer.format', 'png')) === 'bmp'
            && ! function_exists('imagecreatefrombmp')) {
            return false;
        }

        return function_exists('imagecreatefromwebp');
    }
}
