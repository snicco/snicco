<?php


    declare(strict_types = 1);


    namespace Tests\unit\Helpers;

    use Tests\stubs\Bar;
    use Tests\stubs\Foo;
    use WPEmerge\Support\ConstructorPayload;
    use PHPUnit\Framework\TestCase;

    class ConstructorReflectorTest extends TestCase
    {

        /** @test */
        public function only_values_that_match_by_type_hint_are_included () {

            $result = ConstructorPayload::byTypeHint(TrailingPrimitive::class, ['foo']);
            $this->assertSame(['param3' => 'foo'], $result);

            $result = ConstructorPayload::byTypeHint(TrailingPrimitive::class, [ $foo = new Foo(), 'foo' ]);
            $this->assertSame(['param1' => $foo, 'param3' => 'foo'], $result);

            $result = ConstructorPayload::byTypeHint(TrailingPrimitive::class, [ $foo = new Foo(), $bar = new Bar(),  'foo' ]);
            $this->assertSame(['param1' => $foo, 'param2' => $bar, 'param3' => 'foo'], $result);


        }

        /** @test */
        public function the_first_matching_typehint_gets_the_first_value_of_that_type () {

            $result = ConstructorPayload::byTypeHint(TrailingPrimitive::class, ['foo', 'bar']);
            $this->assertSame(['param3' => 'foo'], $result);

        }

        /** @test */
        public function a_value_is_included_in_the_payload_if_its_optional_in_the_constructor () {

            $result = ConstructorPayload::byTypeHint(OptionalValue::class, ['foo']);
            $this->assertSame(['param1' => 'foo', 'param2' => 'bar'], $result);


        }

        /** @test */
        public function it_works_with_several_params_with_the_same_typehint () {

            $result = ConstructorPayload::byTypeHint(TwoStrings::class, ['foo', 'bar']);
            $this->assertSame(['param1' => 'foo', 'param2' => 'bar'], $result);


        }

        /** @test */
        public function a_value_is_not_included_twice () {

            $result = ConstructorPayload::byTypeHint(TwoStrings::class, ['foo']);
            $this->assertSame(['param1' => 'foo'], $result);


        }

        /** @test */
        public function it_works_with_boolean_types () {

            $result = ConstructorPayload::byTypeHint(WithBool::class, [true]);
            $this->assertSame(['param1' => true], $result);

        }

        /** @test */
        public function it_works_with_boolean_false () {

            $result = ConstructorPayload::byTypeHint(WithBool::class, [false]);
            $this->assertSame(['param1' => false], $result);

        }

        /** @test */
        public function passing_no_payload_retains_default_constructor_args () {

            $result = ConstructorPayload::byTypeHint(WithDefaultValue::class, []);
            $this->assertSame(['param1' => 'foo'], $result);



        }

        /** @test */
        public function payload_values_have_priority_over_constructor_defaults () {

            $result = ConstructorPayload::byTypeHint(WithDefaultValue::class, ['bar']);
            $this->assertSame(['param1' => 'bar'], $result);

        }

        /**
         * @test
         *
         * This should only be used as a fallback instantiating the class by typehint payload
         * was not possible.
         */
        public function the_payload_can_also_be_built_by_order_of_parameters () {

            $result = ConstructorPayload::byOrder(
                TrailingPrimitive::class,
                ['foo', 'bar', 'baz', 'biz']
            );
            $this->assertSame(['param1' => 'foo', 'param2' => 'bar', 'param3' => 'baz', 'param4'=>'biz'], $result);

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

    class WithBool {

        public function __construct( bool $param1)
        {
        }

    }

    class WithDefaultValue {

        public function __construct( string $param1 = 'foo')
        {
        }

    }

