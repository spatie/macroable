<?php

namespace Spatie\Macroable;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use BadMethodCallException;

use TypeError;

trait Macroable
{
    protected static array $macros = [];

    public static function macro(string $name, $macro): void
    {
        if (is_object($macro) || is_callable($macro))
            static::$macros[$name] = $macro;
        else
            throw new TypeError("macro must be object or callable.");
    }

    public static function mixin($mixin): void
    {
        if (is_object($mixin) || is_string($mixin)) {
            $methods = (new ReflectionClass($mixin))->getMethods(
                ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
            );

            foreach ($methods as $method) {
                $method->setAccessible(true);

                static::macro($method->name, $method->invoke($mixin));
            }
        }
        else
            throw new TypeError("mixin must be object or string.");
    }

    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    public static function __callStatic($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            return call_user_func_array(Closure::bind($macro, null, static::class), $parameters);
        }

        return call_user_func_array($macro, $parameters);
    }

    public function __call($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            return call_user_func_array($macro->bindTo($this, static::class), $parameters);
        }

        return call_user_func_array($macro, $parameters);
    }
}
