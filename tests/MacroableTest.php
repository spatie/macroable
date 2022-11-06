<?php

namespace Spatie\Macroable\Test;

use BadMethodCallException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Spatie\Macroable\Macroable;

class MacroableTest extends TestCase
{
    protected $macroableClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->macroableClass = new class() {
            private $privateVariable = 'privateValue';

            use Macroable;

            private static function getPrivateStatic()
            {
                return 'privateStaticValue';
            }

            public function publicMethod()
            {
                return 'publicMethodValue';
            }
        };
    }
    
    protected function tearDown(): void
    {
        $this->macroableClass::flushMacros();

        parent::tearDown();
    }

    /** @test */
    public function a_new_macro_can_be_registered_and_called()
    {
        $this->macroableClass::macro('newMethod', function () {
            return 'newValue';
        });

        $this->assertEquals('newValue', $this->macroableClass->newMethod());
    }

    /** @test */
    public function a_new_macro_can_be_registered_and_called_statically()
    {
        $this->macroableClass::macro('newMethod', function () {
            return 'newValue';
        });

        $this->assertEquals('newValue', $this->macroableClass::newMethod());
    }

    /** @test */
    public function a_class_can_be_registered_as_a_new_macro_and_be_invoked()
    {
        $this->macroableClass::macro('newMethod', new class() {
            public function __invoke()
            {
                return 'newValue';
            }
        });

        $this->assertEquals('newValue', $this->macroableClass->newMethod());
        $this->assertEquals('newValue', $this->macroableClass::newMethod());
    }

    /** @test */
    public function it_passes_parameters_correctly()
    {
        $this->macroableClass::macro('concatenate', function (...$strings) {
            return implode('-', $strings);
        });

        $this->assertEquals('one-two-three', $this->macroableClass->concatenate('one', 'two', 'three'));
    }

    /** @test */
    public function registered_methods_are_bound_to_the_class()
    {
        $this->macroableClass::macro('newMethod', function () {
            return $this->privateVariable;
        });

        $this->assertEquals('privateValue', $this->macroableClass->newMethod());
    }

    /** @test */
    public function it_can_work_on_static_methods()
    {
        $this->macroableClass::macro('testStatic', function () {
            return $this::getPrivateStatic();
        });

        $this->assertEquals('privateStaticValue', $this->macroableClass->testStatic());
    }

    /** @test */
    public function it_can_mixin_all_public_methods_from_another_class()
    {
        $this->macroableClass::mixin($this->getMixinClass());

        $this->assertEquals('privateValue-test', $this->macroableClass->mixinMethodA('test'));
    }

    /** @test */
    public function it_will_throw_an_exception_if_a_method_does_not_exist()
    {
        $this->expectException(BadMethodCallException::class);

        $this->macroableClass->nonExistingMethod();
    }

    /** @test */
    public function it_will_throw_an_exception_if_a_static_method_does_not_exist()
    {
        $this->expectException(BadMethodCallException::class);

        $this->macroableClass::nonExistingMethod();
    }

    /** @test */
    public function it_can_flush_all_macros()
    {
        $this->macroableClass::macro('newMethod', function () {
            return 'newValue';
        } );

        $this->expectException(BadMethodCallException::class);

        $this->macroableClass::flushMacros();

        $this->macroableClass::newMethod();
    }

    /** @test */
    public function it_can_add_a_mixin_if_replace_is_false_and_method_doesnt_exist()
    {
        $this->macroableClass::macro('newMethod', function () {
            return 'newValue';
        }, false );

        $this->assertEquals('newValue', $this->macroableClass->newMethod());
    }

    /** @test */
    public function it_will_throw_an_exception_if_replace_is_false_and_method_already_exists()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->macroableClass::macro('publicMethod', function () {
            return 'newPublicMethodValue';
        }, false );
    }

    /** @test */
    public function it_will_throw_an_exception_if_replace_is_false_and_macro_already_exists()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->macroableClass::macro('newMacro', function () {
            return 'macroValue';
        } );

        $this->macroableClass::macro('newMacro', function () {
            return 'macroValue';
        }, false );
    }

    protected function getMixinClass()
    {
        return new class() {
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
                    return $this->privateVariable.'-'.$value;
                };
            }
        };
    }
}
