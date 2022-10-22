<?php

namespace App\Utilities;

use Clockwork\Request\Timeline\Event;
use Exception;

class ClockworkUtility
{
    public static function reset(): void
    {
        clock()->reset();
    }

    public static function start(string $title, string $color = 'purple'): Event
    {
        return clock()->event($title)->color($color)->begin();
    }

    public static function event(string $title, callable $callable): void
    {
        if (! is_callable($callable)) {
            throw new Exception('Expected a callable function as the 2nd param');
        }

        $event = self::start($title);
        $callable();
        $event->end();
    }

    public static function log(string $title): void
    {
        clock()->info($title);
    }

    public static function performance(string $title): void
    {
        clock()->info($title, [
            'trace' => true,
            'performance' => true,
        ]);
    }
}
