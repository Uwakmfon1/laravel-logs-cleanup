<?php

namespace Uwakmfon1\LaravelLogsCleanup;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Uwakmfon1\LaravelLogsCleanup\Commands\LaravelLogsCleanupCommand;

class LaravelLogsCleanupServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ ."/../config/logs-cleanup.php",
            'logs-cleaner'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ ."/../config/logs-cleanup.php" => config_path('logs-cleanup.php'),
        ], 'logs-cleanup-config');

        $this->commands([
            LaravelLogsCleanupCommand::class,
        ]);
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-logs-cleanup')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_logs_cleanup_table')
            ->hasCommand(LaravelLogsCleanupCommand::class);
    }
}
