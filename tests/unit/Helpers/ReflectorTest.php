<?php


    declare(strict_types = 1);


    namespace Tests\unit\Helpers;

    use Tests\stubs\Bar;
    use Tests\stubs\Foo;
    use WPEmerge\Support\ConstructorReflector;
    use PHPUnit\Framework\TestCase;

    class ReflectorTest extends TestCase
    {

        /** @test */
        public function only_values_that_match_by_type_hint_are_included () {

            $result = ConstructorReflector::namedConstructorArgs(TrailingPrimitive::class, ['foo']);
            $this->assertSame(['param3' => 'foo'], $result);

            $result = ConstructorReflector::namedConstructorArgs(TrailingPrimitive::class, [ $foo = new Foo(), 'foo' ]);
            $this->assertSame(['param1' => $foo, 'param3' => 'foo'], $result);

            $result = ConstructorReflector::namedConstructorArgs(TrailingPrimitive::class, [ $foo = new Foo(), $bar = new Bar(),  'foo' ]);
            $this->assertSame(['param1' => $foo, 'param2' => $bar, 'param3' => 'foo'], $result);


        }

        /** @test */
        public function the_first_matching_typehint_gets_the_first_value_of_that_type () {

            $result = ConstructorReflector::namedConstructorArgs(TrailingPrimitive::class, ['foo', 'bar']);
            $this->assertSame(['param3' => 'foo'], $result);

        }

        /** @test */
        public function a_value_is_included_in_the_payload_if_its_optional_in_the_constructor () {

            $result = ConstructorReflector::namedConstructorArgs(OptionalValue::class, ['foo']);
            $this->assertSame(['param1' => 'foo', 'param2' => 'bar'], $result);


        }

        /** @test */
        public function it_works_with_several_params_with_the_same_typehint () {

            $result = ConstructorReflector::namedConstructorArgs(TwoStrings::class, ['foo']);
            $this->assertSame(['param1' => 'foo', 'param2' => 'bar'], $result);


        }

        // /** @test */
        public function a_value_is_not_included_twice () {

            $result = ConstructorReflector::namedConstructorArgs(TwoStrings::class, ['foo', 'bar']);
            $this->assertSame(['param1' => 'foo', 'param2' => 'bar'], $result);


        }

    }



    class TrailingPrimitive {

        public function __construct(Foo $param1, Bar $param2, string $param3, $param4)
        {

        }

    }

    class OptionalValue {

        public function __construct(string $param1, $param2 = 'bar')
        {

        }

    }

    class TwoStrings  {

        public function __construct( string $param1, string $param2 )
        {

        }

    }

