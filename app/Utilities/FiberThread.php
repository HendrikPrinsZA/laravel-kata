<?php

declare(ticks=1);

namespace App\Utilities;

use App\Console\Commands\KataCommand;
use Exception;
use Fiber;
use Throwable;

class FiberThread
{
    protected static $items = [];

    public static function callable(
        KataCommand $instance,
        string $function,
        array $params = []
    ): mixed {
        return $instance->{$function}(...$params);
    }

    public static function register(
        KataCommand $instance,
        string $function,
        array $params = []
    ): array {
        $callable = sprintf('%s::%s', self::class, 'callable');
        return self::registerOG($function, $callable, [
            $instance,
            $function,
            $params
        ]);
    }

    public static function registerOG(string|int $name, callable|string $callback, array $params = []): array
    {
        // Static stuff...
        if (is_string($callback)) {
            $parts = explode('::', $callback);
            $methodName = count($parts) > 0
                ? array_pop($parts)
                : null;
            $className = count($parts) > 0
                ? array_pop($parts)
                : null;

            if (is_null($methodName)) {
                throw new Exception(sprintf('No method found from "%s"', $name));
            }

            if (!is_null($className)) {
                if (!class_exists($className)) {
                    throw new Exception(sprintf('Class doesn\'t exist: %s', $className));
                }

                if (!method_exists($className, $methodName)) {
                    throw new Exception(sprintf(
                        'Class method doesn\'t exist: %s::%s',
                        $className,
                        $methodName
                    ));
                }
            }
        }

        // $uniqId = md5(serialize($callback) . serialize($params));
        $uniqId = uniqid('thread-');

        if (!isset(self::$items[$uniqId])) {
            self::$items[$uniqId] = [
                'name' => $name,
                'fiber' => new Fiber($callback),
                'params' => $params,
            ];
        }

        return self::$items[$uniqId];
    }

    public static function run()
    {
        $output = [];

        while (self::$items) {
            foreach (self::$items as $uniqId => $item) {
                $name = $item['name'];
                $fiber = $item['fiber'];
                $params = $item['params'];

                try {
                    if (!$fiber->isStarted()) {
                        // Register a new tick function for scheduling this fiber
                        register_tick_function(sprintf(
                            '%s::%s',
                            self::class,
                            'scheduler'
                        ));

                        $fiber->start(...$params);
                    } elseif ($fiber->isTerminated()) {
                        $output[$name] = $fiber->getReturn();
                        unset(self::$items[$uniqId]);
                    } elseif ($fiber->isSuspended()) {
                        $fiber->resume();
                    }
                } catch (Throwable $e) {
                    $output[$name] = $e;
                }
            }
        }

        return $output;
    }

    public static function scheduler()
    {
        if (Fiber::getCurrent() === null) {
            return;
        }

        // running Fiber::suspend() in this if condition will prevent an infinite loop!
        if (count(self::$items) <= 1) {
            return;
        }

        Fiber::suspend();
    }
}
