<?php

namespace Uwakmfon1\LaravelLogsCleanup\Services;

use Carbon\Carbon;
use RuntimeException;

class LogCleaner
{
    public function __construct(
        protected LogParser $parser,
        protected TempFileManager $tempFileManager
    ) {}

    public function clearAll(bool $dryRun = false): array
    {
        $logFile = config('logs-cleanup.log_file');

        if (! file_exists($logFile)) {
            throw new \RuntimeException('laravel.log does not exist.');
        }

        $createBackup = config('logs-cleanup.create_backup')
            ?? config('logs-cleanup.backup')
            ?? false;

        $originalSize = filesize($logFile);
        $entryCount = 0;

        foreach ($this->parser->parse($logFile) as $entry) {
            $entryCount++;
        }

        if ($dryRun) {
            return [
                'removed_entries' => $entryCount,
                'preserved_entries' => 0,
                'original_size_mb' => round($originalSize / 1024 / 1024, 2),
                'new_size_mb' => 0,
            ];
        }

        if ($createBackup && is_file($logFile) && $originalSize > 0) {
            copy($logFile, "{$logFile}.bak");
        }

        file_put_contents($logFile, '');

        return [
            'removed_entries' => $entryCount,
            'preserved_entries' => 0,
            'original_size_mb' => round($originalSize / 1024 / 1024, 2),
            'new_size_mb' => 0,
        ];
    }

    public function clean(
        Carbon $cutoffDate,
        bool $dryRun = false
    ): array {
        $logFile = config('logs-cleanup.log_file');

        $tempFile = config('logs-cleanup.temp_file');

        $createBackup = config('logs-cleanup.create_backup')
            ?? config('logs-cleanup.backup')
            ?? false;

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        if (! file_exists($logFile)) {
            throw new \RuntimeException('laravel.log does not exist.');
        }

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $removedEntries = 0;
        $preservedEntries = 0;

        $originalSize = filesize($logFile);

        /*
        |--------------------------------------------------------------------------
        | Dry Run
        |--------------------------------------------------------------------------
        */

        if ($dryRun) {

            foreach ($this->parser->parse($logFile) as $entry) {

                $entryDate = $this->extractDate($entry);

                if (! $entryDate) {
                    $preservedEntries++;

                    continue;
                }

                if ($entryDate->lt($cutoffDate)) {
                    $removedEntries++;
                } else {
                    $preservedEntries++;
                }
            }

            return [
                'removed_entries' => $removedEntries,
                'preserved_entries' => $preservedEntries,
                'original_size_mb' => round($originalSize / 1024 / 1024, 2),
                'new_size_mb' => round($originalSize / 1024 / 1024, 2),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Create Temp File
        |--------------------------------------------------------------------------
        */

        $tempHandle = $this->tempFileManager->create($tempFile);

        foreach ($this->parser->parse($logFile) as $entry) {
            $entryDate = $this->extractDate($entry);

            if (! $entryDate || $entryDate->gte($cutoffDate)) {
                fwrite($tempHandle, $entry);
                $preservedEntries++;

                continue;
            }

            $removedEntries++;
        }

        fclose($tempHandle);

        if ($removedEntries === 0) {
            @unlink($tempFile);

            return [
                'removed_entries' => 0,
                'preserved_entries' => $preservedEntries,
                'original_size_mb' => round($originalSize / 1024 / 1024, 2),
                'new_size_mb' => round($originalSize / 1024 / 1024, 2),
            ];
        }

        if ($preservedEntries > 0 && (! is_file($tempFile) || filesize($tempFile) === 0)) {
            @unlink($tempFile);

            throw new RuntimeException(
                'Cleanup produced an empty temp file; original log was not modified.'
            );
        }

        $this->tempFileManager->replace(
            tempFile: $tempFile,
            originalFile: $logFile,
            backup: $createBackup
        );

        $newSize = filesize($logFile);

        return [
            'removed_entries' => $removedEntries,
            'preserved_entries' => $preservedEntries,
            'original_size_mb' => round($originalSize / 1024 / 1024, 2),
            'new_size_mb' => round($newSize / 1024 / 1024, 2),
        ];
    }

    protected function extractDate(string $entry): ?Carbon
    {
        preg_match(
            '/^\[(.*?)\]/',
            $entry,
            $matches
        );

        if (! isset($matches[1])) {
            return null;
        }

        try {

            return Carbon::parse($matches[1]);

        } catch (\Throwable $e) {

            return null;
        }
    }

    public function getLatestDate(string $file): ?Carbon
    {
        $latestDate = null;
        $hasAnyValid = false;
        foreach ($this->parser->parse($file) as $entry) {

            $entryDate = $this->extractDate($entry);

            if (! $entryDate) {
                continue;
            }
            $hasAnyValid = true;

            if (! $latestDate || $entryDate->gt($latestDate)) {
                $latestDate = $entryDate;
            }
        }



        return $hasAnyValid ? $latestDate : null;
    }
}
