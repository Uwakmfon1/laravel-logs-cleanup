<?php

namespace Uwakmfon1\LaravelLogsCleanup\Commands;

use Illuminate\Console\Command;
use Uwakmfon1\LaravelLogsCleanup\Services\LogCleaner;

class LaravelLogsCleanupCommand extends Command
{
    protected string $logFile;

    public function __construct()
    {
        parent::__construct();
        $this->logFile = config('logs-cleanup.log_file', storage_path('logs/laravel.log'));
    }

    protected $signature = '
        logs:clear
        {--except= : Number of recent days to preserve}
        {--dry-run : Preview without deleting}
        {--force : Skip confirmation prompt}
        ';

    protected $description = 'Cleanup Laravel logs';

    public function handle(LogCleaner $cleaner): int
    {
        $except = $this->option('except');

        if ($except === null) {
            $this->info('Clearing entire log file.');
        } else {
            $days = (int) $except;

            if ($days < 1) {
                $this->error('The --except option must be at least 1.');

                return self::FAILURE;
            }

            $referenceDate = $cleaner->getLatestDate($this->logFile);

            if (! $referenceDate) {
                $this->warn('No valid timestamps found in log file. Nothing to clean.');

                return self::SUCCESS;
            }

            $cutoffDate = $referenceDate
                ->copy()
                ->subDays($days)
                ->startOfDay();

            $this->info("Preserving logs from {$cutoffDate->toDateTimeString()} onward.");
        }

        if (! $this->option('force') && ! $this->confirm('Do you want to proceed with log cleanup?')) {
            $this->info('Log cleanup cancelled.');

            return self::SUCCESS;
        }

        $result = $except === null
            ? $cleaner->clearAll(dryRun: $this->option('dry-run'))
            : $cleaner->clean(
                cutoffDate: $cutoffDate,
                dryRun: $this->option('dry-run')
            );

        $this->newline();
        $this->info("Removed Entries: {$result['removed_entries']}");
        $this->info("Preserved Entries: {$result['preserved_entries']}");
        $this->info("Original Size: {$result['original_size_mb']} MB");
        $this->info("New Size: {$result['new_size_mb']} MB");

        if ($this->option('dry-run')) {
            $this->warn('Dry run only. No changes applied');
        }

        return self::SUCCESS;
    }
}
