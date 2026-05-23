<?php

namespace Uwakmfon1\LaravelLogsCleanup\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Uwakmfon1\LaravelLogsCleanup\Services\LogCleaner;


class LaravelLogsCleanupCommand extends Command
{
    protected $signature = '
        logs:clear
        {--except=3: Number of recent days to preserve}
        {--dry-run : Preview without deleting}
        {--force : Skip confirmation prompt}
        ';

    protected $description = 'Cleanup Laravel logs';

    public function handle(LogCleaner $cleaner): int
    {
        $days = (int) $this->option('except');

        $cutoffDate = Carbon::now()->subDays($days)->startOfDay();
        $this->info('Preserving logs from {$cutoffDate} till now.');

        if (! $this->option('force') && ! $this->confirm('Do you want to proceed with log cleanup?')) {
            $this->info('Log cleanup cancelled.');
            return self::SUCCESS;
        }
        
        $result = $cleaner->clean(
            cutoffDate: $cutoffDate,
            dryRun: $this->option('dry-run')
        );

        $this->newline();
        $this->info("Removed Entries: {$result['removed_entries']}");
        $this->info("Preserved Entries: {$result['preserved_entries']}");
        $this->info("Original Size: {$result['original_size_mb']} MB");
        $this->info("New Size: {$result['new_size_mb']} MB");   

        if($this->option('dry-run')){
        $this->warn('Dry run only. No changes applied');
        }

        return self::SUCCESS;
    }   
}