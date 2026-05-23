<?php

namespace Uwakmfon1\LaravelLogsCleanup\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Uwakmfon1\LaravelLogsCleanup\LaravelLogsCleanup
 */
class LaravelLogsCleanup extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Uwakmfon1\LaravelLogsCleanup\LaravelLogsCleanup::class;
    }
}
