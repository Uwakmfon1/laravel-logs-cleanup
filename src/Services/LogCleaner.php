<?php

namespace Uwakmfon1\LaravelLogsCleanup\Services;

use Carbon\Carbon;

class LogCleaner
{
    public function __construct(
        protected LogParser $parser,
        protected TempFileManager $tempFileManager
    ) {}

    public function clean(
        Carbon $cutoffDate,
        bool $dryRun = false
    ): array {
        $logFile = config('logs-cleanup.log_file');

        $tempFile = config('logs-cleanup.temp_file');

        $createBackup = config('logs-cleanup.create_backup') ?? false;

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

        /*
        |--------------------------------------------------------------------------
        | Parse & Filter
        |--------------------------------------------------------------------------
        */

        foreach ($this->parser->parse($logFile) as $entry) {

            $entryDate = $this->extractDate($entry);

            /*
            |--------------------------------------------------------------------------
            | Invalid Entries
            |--------------------------------------------------------------------------
            */

            if (! $entryDate) {

                fwrite($tempHandle, $entry);

                $preservedEntries++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Preserve Recent Logs
            |--------------------------------------------------------------------------
            */

            if ($entryDate->gte($cutoffDate)) {

                fwrite($tempHandle, $entry);

                $preservedEntries++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Remove Old Logs
            |--------------------------------------------------------------------------
            */

            $removedEntries++;
        }

        fclose($tempHandle);

        /*
        |--------------------------------------------------------------------------
        | Replace Original File
        |--------------------------------------------------------------------------
        */

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

        foreach ($this->parser->parse($file) as $entry) {

            $entryDate = $this->extractDate($entry);

            if (! $entryDate) {
                continue;
            }

            if (! $latestDate || $entryDate->gt($latestDate)) {
                $latestDate = $entryDate;
            }
        }

        return $latestDate;
    }
}
