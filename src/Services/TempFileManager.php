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
        if ($backup) {
            copy(
                $originalFile,
                "{$originalFile}.bak"
            );
        }

        unlink($originalFile);
        rename($tempFile, $originalFile);
    }
}
