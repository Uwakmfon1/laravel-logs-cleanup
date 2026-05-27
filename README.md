# Laravel Logs Cleanup

[![Latest Version on Packagist](https://img.shields.io/packagist/v/uwakmfon1/laravel-logs-cleanup.svg?style=flat-square)](https://packagist.org/packages/uwakmfon1/laravel-logs-cleanup)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/uwakmfon1/laravel-logs-cleanup/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/uwakmfon1/laravel-logs-cleanup/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/uwakmfon1/laravel-logs-cleanup/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/uwakmfon1/laravel-logs-cleanup/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/uwakmfon1/laravel-logs-cleanup.svg?style=flat-square)](https://packagist.org/packages/uwakmfon1/laravel-logs-cleanup)

A lightweight Laravel package for cleaning old entries from storage/logs/laravel.log based on a specified number of days to preserve.

Ideal for large log files containing thousands of lines.

## Features
- Cleans only storage/logs/laravel.log
- Preserves recent logs
- Supports dry-run mode
- Creates backup files
- Memory-efficient stream processing
- Safe for large log files


## Installation

You can install the package via composer:

```bash
composer require uwakmfon1/laravel-logs-cleanup
```

## Publish Configuration
You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-logs-cleanup-config"
```
## Configuration
File:
```
config/logs-cleanup.php
```
Example:

```php
return [

    'log_file' => storage_path('logs/laravel.log'),

    'temp_file' => storage_path('logs/laravel-temp.log'),

    'create_backup' => true,
];
```

## Usage
### Clear entire log file

```bash
php artisan logs:clear
```

### Clear old logs, keep recent days

Keep logs from the last 3 days:

```bash
php artisan logs:clear --except=3
```

## Dry Run
Preview cleanup without deleting logs:

```bash
php artisan logs:clear --dry-run
php artisan logs:clear --except=3 --dry-run
```

## How it Works
The package:

1. Reads laravel.log line-by-line
2. Detects log timestamps
3. Removes entries older than the cutoff date
4. Writes valid logs into a temporary file
5. Replaces the original log safely

This prevents memory exhaustion on large log files.

## Supported Log Format
```
[2026-05-23 10:30:22] local.ERROR: Something happened
```

## Example Output
```
Cleaning logs older than 3 days...

Backup created successfully.

Removed: 85,421 lines
Kept: 14,579 lines

Log cleanup completed successfully.
```

## Local Package Development
Inside another Laravel app:
```
composer config repositories.logs-cleaner path ../laravel-logs-cleanup
```
Install Locally: 
```
composer require uwakmfon1/laravel-logs-cleanup:dev-main
```


## Credits

- [Uwakmfon1](https://github.com/Uwakmfon1)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
