<?php

namespace Spatie\Macroable;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use BadMethodCallException;
use InvalidArgumentException;

trait Macroable
{
    protected static array $macros = [];

    public static function macro(string $name, object | callable $macro, bool $replace = true): void
    {
        if (! $replace) {
            if (method_exists(static::class, $name)) {
                throw new InvalidArgumentException("Method `{$name}` already exists.");
            }
    
            if (static::hasMacro($name)) {
                throw new InvalidArgumentException("Macro `{$name}` already exists.");
            }
        }

        static::$macros[$name] = $macro;
    }

    public static function mixin(object | string $mixin, bool $replace = true): void
    {
        $methods = (new ReflectionClass($mixin))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            $method->setAccessible(true);

            static::macro($method->name, $method->invoke($mixin), $replace);
        }
    }

    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    public static function flushMacros(): void
    {
        static::$macros = [];
    }

    public static function __callStatic($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException("Method `{$method}` does not exist.");
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
            throw new BadMethodCallException("Method `{$method}` does not exist.");
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            return call_user_func_array($macro->bindTo($this, static::class), $parameters);
        }

        return call_user_func_array($macro, $parameters);
    }
}
