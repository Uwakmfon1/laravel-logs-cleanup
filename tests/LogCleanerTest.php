<?php

use Carbon\Carbon;
use Uwakmfon1\LaravelLogsCleanup\Services\LogCleaner;
use Uwakmfon1\LaravelLogsCleanup\Services\LogParser;
use Uwakmfon1\LaravelLogsCleanup\Services\TempFileManager;

function createLogCleanerTestFiles(): array
{
    $dir = sys_get_temp_dir().'/laravel-logs-cleanup-'.uniqid();
    mkdir($dir);

    return [
        'dir' => $dir,
        'log' => $dir.'/laravel.log',
        'temp' => $dir.'/laravel-cleaner.tmp',
    ];
}

it('removes old entries and preserves recent ones', function () {
    $files = createLogCleanerTestFiles();
    $oldDate = Carbon::now()->subDays(10)->format('Y-m-d H:i:s');
    $recentDate = Carbon::now()->subDay()->format('Y-m-d H:i:s');

    file_put_contents($files['log'], implode("\n", [
        "[{$oldDate}] local.ERROR: Old message",
        "[{$recentDate}] local.INFO: Recent message",
    ]));

    config([
        'logs-cleanup.log_file' => $files['log'],
        'logs-cleanup.temp_file' => $files['temp'],
        'logs-cleanup.create_backup' => false,
    ]);

    $cutoff = Carbon::now()->subDays(5)->startOfDay();
    $cleaner = new LogCleaner(new LogParser, new TempFileManager);

    $result = $cleaner->clean($cutoff);

    expect($result['removed_entries'])->toBeGreaterThanOrEqual(1)
        ->and($result['preserved_entries'])->toBeGreaterThanOrEqual(1);

    $contents = file_get_contents($files['log']);
    expect($contents)->toContain('Recent message')
        ->and($contents)->not->toContain('Old message');
});

it('clears the entire log file', function () {
    $files = createLogCleanerTestFiles();

    file_put_contents($files['log'], "[2026-01-01 10:00:00] local.ERROR: Message\n");

    config([
        'logs-cleanup.log_file' => $files['log'],
        'logs-cleanup.temp_file' => $files['temp'],
        'logs-cleanup.create_backup' => false,
    ]);

    $cleaner = new LogCleaner(new LogParser, new TempFileManager);

    $result = $cleaner->clearAll();

    expect($result['removed_entries'])->toBe(1)
        ->and($result['preserved_entries'])->toBe(0)
        ->and(file_get_contents($files['log']))->toBe('');
});

it('does not modify the log file when nothing would be removed', function () {
    $files = createLogCleanerTestFiles();
    $recentDate = Carbon::now()->subDay()->format('Y-m-d H:i:s');

    $original = "[{$recentDate}] local.INFO: Recent only\n";
    file_put_contents($files['log'], $original);

    config([
        'logs-cleanup.log_file' => $files['log'],
        'logs-cleanup.temp_file' => $files['temp'],
        'logs-cleanup.create_backup' => false,
    ]);

    $cutoff = Carbon::now()->subDays(5)->startOfDay();
    $cleaner = new LogCleaner(new LogParser, new TempFileManager);

    $result = $cleaner->clean($cutoff);

    expect($result['removed_entries'])->toBe(0)
        ->and(file_get_contents($files['log']))->toBe($original);
});
