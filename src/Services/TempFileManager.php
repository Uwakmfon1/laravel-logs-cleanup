<?php

namespace Uwakmfon1\LaravelLogsCleanup\Services;

class TempFileManager
{
    public function create(string $path)
    {
        $handle = fopen($path, 'w');
        if (! $handle) {
            throw new \RuntimeException("Unable to create temp file: {$path}");
        }

        return $handle;
    }

    public function replace(
        string $tempFile,
        string $originalFile,
        bool $backup = true,
    ): void {
        if (! is_file($tempFile)) {
            throw new \RuntimeException("Temp file missing: {$tempFile}");
        }

        if ($backup && is_file($originalFile)) {
            copy($originalFile, "{$originalFile}.bak");
        }

        if (! rename($tempFile, $originalFile)) {
            throw new \RuntimeException('Failed to replace log file.');
        }
    }
}
