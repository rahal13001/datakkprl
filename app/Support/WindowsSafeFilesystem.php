<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class WindowsSafeFilesystem extends Filesystem
{
    public function replace($path, $content, $mode = null)
    {
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;
        $tempPath = tempnam(dirname($path), basename($path));

        if (! is_null($mode)) {
            @chmod($tempPath, $mode);
        } else {
            @chmod($tempPath, 0777 - umask());
        }

        file_put_contents($tempPath, $content);

        if ($this->renameWithRetries($tempPath, $path, $content)) {
            return;
        }

        @unlink($tempPath);

        $error = error_get_last()['message'] ?? 'unknown filesystem error';

        throw new RuntimeException("Unable to replace [{$path}]: {$error}");
    }

    private function renameWithRetries(string $tempPath, string $path, string $content): bool
    {
        $attempts = DIRECTORY_SEPARATOR === '\\' ? 10 : 1;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            clearstatcache(true, $path);

            if (@rename($tempPath, $path)) {
                return true;
            }

            if (is_file($path) && @file_get_contents($path) === $content) {
                @unlink($tempPath);

                return true;
            }

            if (DIRECTORY_SEPARATOR === '\\' && is_file($path)) {
                @unlink($path);

                if (@rename($tempPath, $path)) {
                    return true;
                }
            }

            usleep(50_000);
        }

        return false;
    }
}
