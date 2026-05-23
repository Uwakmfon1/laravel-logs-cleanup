<?php 

namespace Uwakmfon1\LaravelLogsCleanup\Services;


class LogParser
{
    protected string $pattern = '/^\[\d{4}-\d{2}-\d{2}/';

    public function parse(string $path): \Generator
    {
        $handle = fopen($path, 'r');

        if (!$handle) {
            throw new \RuntimeException("Unable to open log file: {$path}");
        }

        $currentEntry = '';
        while (($line = fgets($handle)) !== false) {
            if (preg_match($this->pattern, $line)) {
                 if (! empty($currentEntry)) {
                    yield $currentEntry;
                }

                $currentEntry = $line;

                continue;
            }

            $currentEntry .= $line;
        }

        if (! empty($currentEntry)) {
            yield $currentEntry;
        }

        fclose($handle);
    }
}


// WHY THIS IS CORRECT

// This parser:

// ✅ handles stack traces
// ✅ handles multiline logs
// ✅ avoids memory exhaustion
// ✅ streams efficiently
// ✅ scales to GB-sized files