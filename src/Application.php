<?php

namespace schedule;

use Symfony\Component\Process\PhpExecutableFinder;

class Application
{
    /**
     * Determine the proper PHP executable.
     * @return string
     */
    public static function phpBinary()
    {
        return ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false));
    }

    /**
     * Determine the proper Artisan executable.
     *
     * @return string
     */
    public static function artisanBinary()
    {
        return ProcessUtils::escapeArgument(defined('THINK_BINARY') ? THINK_BINARY : 'think');
    }

    /**
     * Format the given command as a fully-qualified executable command.
     *
     * @param string $string
     * @return string
     */
    public static function formatCommandString($string)
    {
        return sprintf('%s %s %s', static::phpBinary(), static::artisanBinary(), $string);
    }
}