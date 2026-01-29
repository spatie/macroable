<?php

use Spatie\Macroable\Macroable;

uses(PHPUnit\Framework\TestCase::class);

function getMixinClass(): object
{
    return new class()
    {
        private function secretMixinMethod()
        {
            return 'secret';
        }

        public function mixinMethodA()
        {
            return function ($value) {
                return $this->mixinMethodB($value);
            };
        }

        public function mixinMethodB()
        {
            return function ($value) {
                return $this->privateVariable . '-' . $value;
            };
        }
    };
}

beforeEach(function () {
    $this->macroableClass = new class()
    {
        private $privateVariable = 'privateValue';

        use Macroable;

        private static function getPrivateStatic()
        {
            return 'privateStaticValue';
        }
    };
});

test('a new macro can be registered and called')
    ->defer(function () {
        $this->macroableClass::macro('newMethod', function () {
            return 'newValue';
        });
    })
    ->expect(fn() => $this->macroableClass->newMethod())
    ->toEqual('newValue');

test('a new macro can be registered and called statically')
    ->defer(function () {
        $this->macroableClass::macro('newMethod', function () {
            return 'newValue';
        });
    })
    ->expect(fn() => $this->macroableClass::newMethod())
    ->toEqual('newValue');

test('a class can be registered as a new macro an be invoked')
    ->defer(function () {
        $this->macroableClass::macro('newMethod', new class()
        {
            public function __invoke()
            {
                return 'newValue';
            }
        });
    })
    ->expect(fn() => $this->macroableClass->newMethod())->toEqual('newValue')
    ->and(fn() => $this->macroableClass::newMethod())->toEqual('newValue');

it('passes parameters correctly')
    ->defer(function () {
        $this->macroableClass::macro('concatenate', function (...$strings) {
            return implode('-', $strings);
        });
    })
    ->expect(fn() => $this->macroableClass->concatenate('one', 'two', 'three'))
    ->toEqual('one-two-three');

test('registered methods are bound to the class')
    ->defer(function () {
        $this->macroableClass::macro('newMethod', function () {
            return $this->privateVariable;
        });
    })
    ->expect(fn() =>  $this->macroableClass->newMethod())
    ->toEqual('privateValue');

it('can work on static methods')
    ->defer(function () {
        $this->macroableClass::macro('testStatic', function () {
            return $this::getPrivateStatic();
        });
    })
    ->expect(fn() => $this->macroableClass->testStatic())
    ->toEqual('privateStaticValue');

it('can mixin all public methods from another class')
    ->defer(fn() => $this->macroableClass::mixin(getMixinClass()))
    ->expect(fn() => $this->macroableClass->mixinMethodA('test'))
    ->toEqual('privateValue-test');

it('will throw an exception if a method does not exist')
    ->defer(fn() => $this->macroableClass->nonExistingMethod())
    ->throws(BadMethodCallException::class);

it('will throw an exception if a static method does not exist')
    ->defer(fn() => $this->macroableClass::nonExistingMethod())
    ->throws(BadMethodCallException::class);
