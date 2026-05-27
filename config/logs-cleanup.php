<?php

// config for Uwakmfon1/LaravelLogsCleanup
return [
    'log_file' => storage_path('logs/laravel.log'),

    'temp_file' => storage_path('logs/laravel-cleaner.tmp'),

    'create_backup' => true,

    'chunk_size' => 8192,
];
