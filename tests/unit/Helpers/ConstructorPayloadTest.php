<?php


    declare(strict_types = 1);


    namespace Tests\unit\Helpers;

    use Tests\stubs\Bar;
    use Tests\stubs\Foo;
    use Tests\traits\CreateContainer;
    use WPEmerge\Support\ConstructorPayload;
    use PHPUnit\Framework\TestCase;

    class ConstructorPayloadTest extends TestCase
    {

        use CreateContainer;


        /** @test */
        public function a_class_without_constructor_returns_an_empty_array () {

            $payload = new ConstructorPayload(NoDependencies::class, ['foo', 'bar']);

            $this->assertSame([], $payload->build());

        }

        /** @test */
        public function only_values_that_match_by_type_hint_are_included () {

            $payload = new ConstructorPayload(TrailingPrimitiveWithoutTypeHint::class, ['foo']);
            $this->assertSame(['param3' => 'foo'], $payload->build());

            $payload = new ConstructorPayload(TrailingPrimitiveWithoutTypeHint::class, [ $foo = new Foo(), 'foo' ]);
            $this->assertSame(['param1' => $foo, 'param3' => 'foo'], $payload->build());

            $payload = new ConstructorPayload(TrailingPrimitiveWithoutTypeHint::class, [ $foo = new Foo(), $bar = new Bar(),  'foo' ]);

            $this->assertSame(['param1' => $foo, 'param2' => $bar, 'param3' => 'foo'], $payload->build());


        }

        /** @test */
        public function the_first_matching_typehint_gets_assigned_the_first_value_of_that_type () {

            $payload = new ConstructorPayload(TrailingPrimitiveWithoutTypeHint::class, ['foo', 'bar'] );

            $this->assertSame(['param3' => 'foo', 'param4' => 'bar'], $payload->build());


        }

        /** @test */
        public function the_order_of_the_payload_does_not_matter () {


            $payload = new ConstructorPayload(TrailingPrimitiveWithoutTypeHint::class, [
                'foo', $bar = new Bar(), $foo = new Foo()
            ]);
            $this->assertSame(['param3' => 'foo', 'param2' => $bar, 'param1' => $foo], $payload->build());


        }

        /** @test */
        public function if_the_payload_has_more_arguments_than_constructor_arguments_with_typehints_we_try_to_append_the_missing_ones () {

            $payload = new ConstructorPayload(TrailingPrimitiveWithoutTypeHint::class, [
                $foo = new Foo(), $bar = new Bar(), 'foo', 'bar'
            ]);

            $this->assertSame(['param1' => $foo, 'param2' => $bar, 'param3' => 'foo', 'param4' => 'bar'], $payload->build());


        }

        /** @test */
        public function optional_parameters_have_priority_over_additional_passed_payload () {

            $payload = new ConstructorPayload(TrailingPrimitiveOptional::class, [
                $foo = new Foo(), $bar = new Bar(), 'foo', 'bar'
            ]);

            $this->assertSame(['param1' => $foo, 'param2' => $bar, 'param3' => 'foo', 'param4' => 'BAR'], $payload->build());


        }

        /** @test */
        public function additional_payload_is_only_appended_for_trailing_parameters () {

            $payload = new ConstructorPayload(TrailingPrimitiveWithoutTypeHint::class, [
                $foo = new Foo(), 'foo', 'bar', 'biz', 'baz'
            ]);

            $this->assertSame(['param1' => $foo, 'param3' => 'foo', 'param4' => 'bar'], $payload->build());


        }

        /** @test */
        public function a_value_is_included_in_the_payload_if_its_optional_in_the_constructor () {

            $payload = new ConstructorPayload(OptionalValue::class, ['foo']);
            $this->assertSame(['param1' => 'foo', 'param2' => 'bar'], $payload->build());

        }

        /** @test */
        public function it_works_with_several_params_with_the_same_typehint () {

            $payload = new ConstructorPayload(TwoStrings::class, ['foo', 'bar']);
            $this->assertSame(['param1' => 'foo', 'param2' => 'bar'], $payload->build());


        }

        /** @test */
        public function a_value_is_not_included_twice () {

            $payload = new ConstructorPayload(TwoStrings::class, ['foo']);
            $this->assertSame(['param1' => 'foo'], $payload->build());


        }

        /** @test */
        public function it_works_with_boolean_types () {

            $payload = new ConstructorPayload(WithBool::class, [true]);
            $this->assertSame(['param1' => true], $payload->build());

        }

        /** @test */
        public function it_works_with_boolean_false () {

            $payload = new ConstructorPayload(WithBool::class, [false]);
            $this->assertSame(['param1' => false], $payload->build());


        }

        /** @test */
        public function passing_no_payload_retains_default_constructor_args () {

            $payload = new ConstructorPayload(WithDefaultValue::class, []);
            $this->assertSame(['param1' => 'foo'], $payload->build());


        }

        /** @test */
        public function payload_values_have_priority_over_constructor_defaults () {

            $payload = new ConstructorPayload(WithDefaultValue::class, ['bar']);
            $this->assertSame(['param1' => 'bar'], $payload->build());

        }

        /**
         * @test
         *
         * This should only be used as a fallback instantiating the class by typehint payload
         * was not possible.
         */
        public function the_payload_can_also_be_built_by_order_of_parameters () {

            $payload = new ConstructorPayload(
                TrailingPrimitiveWithoutTypeHint::class,
                ['foo', 'bar', 'baz', 'biz']
            );

            $this->assertSame(['param1' => 'foo', 'param2' => 'bar', 'param3' => 'baz', 'param4'=>'biz'], $payload->byOrder());


        }

        /** @test */
        public function the_payload_is_created_by_order_if_no_typehints_are_provided () {

            $payload = new ConstructorPayload(WithoutTypeHints::class, ['foo', 'bar']);
            $this->assertSame(['param1' => 'foo', 'param2' => 'bar'], $payload->build());

        }

        /** @test */
        public function passing_more_arguments_to_the_payload_has_no_effect () {

            $payload = new ConstructorPayload(TrailingPrimitiveWithoutTypeHint::class, ['foo', 'bar', 'baz', 'biz']);

            $this->assertSame(['param3'=> 'foo', 'param4' => 'bar'], $payload->build());

        }

        /** @test */
        public function the_used_container_can_build_classes_based_on_the_payloads () {

            $payload = new ConstructorPayload(TrailingPrimitiveWithoutTypeHint::class, [
                $foo = new Foo(), $bar = new Bar(), 'foo', 'bar'
            ]);

            $instance = $this->createContainer()->make(TrailingPrimitiveWithoutTypeHint::class, $payload->build());

            $this->assertInstanceOf(TrailingPrimitiveWithoutTypeHint::class, $instance);

        }

        /**
         *
         *
         *
         *
         * VARIADIC CONSTRUCTOR
         *
         *
         *
         *
         *
         */

        /**
         *
         * @test
         *
         * As soon as variadic runtime parameters are needed users have to use a bound closure
         * in the container. Everything else would be hacky and dependent on the implementation
         * of the concrete container.
         */
        public function when_a_constructor_is_completely_variadic_it_works () {

            $payload = new ConstructorPayload(CompleteVariadic::class, [
                $foo = new Foo(), $bar = new Bar(), 'foo', 'bar'
            ]);

            $this->assertSame([$foo, $bar, 'foo', 'bar'], $result = $payload->build());

            $c = $this->createContainer();

            $c->singleton(CompleteVariadic::class, function ($c, $args ) {

                return new CompleteVariadic(...$args);

            });

            $instance = $c->make(CompleteVariadic::class, $result);

            $this->assertInstanceOf(CompleteVariadic::class, $instance);
            $this->assertSame($result, $instance->args);


        }

        /** @test */
        public function it_works_when_some_values_are_required_and_then_a_variadic () {

            $payload = new ConstructorPayload(SomeVariadic::class, [
               'foo', 'bar', 'baz', 'biz'
            ]);

            $result = $payload->build();

            $this->assertSame(['param1'=>'foo', 'param2' => 'bar', 'args'=>['baz', 'biz']] , $result);

            $c = $this->createContainer();

            $c->singleton(SomeVariadic::class, function ($c, $args ) {

                return new SomeVariadic($args['param1'], $args['param2'], ...$args['args']);

            });

            $instance = $c->make(SomeVariadic::class, $result);
            $this->assertInstanceOf(SomeVariadic::class, $instance);

            $this->assertSame(['baz', 'biz'], $instance->args);

        }

    }


    class CompleteVariadic {

        public $args;

        public function __construct(...$args)
        {
            $this->args = $args;
        }

    }

    class SomeVariadic {

        public $args;

        public function __construct( string $param1, string $param2, ...$args)
        {

            $this->args = $args;
        }

    }

    class TrailingPrimitiveWithoutTypeHint {

        public function __construct(Foo $param1, Bar $param2, string $param3, $param4)
        {

        }

    }

    class TrailingPrimitiveOptional {

        public function __construct(Foo $param1, Bar $param2, string $param3, $param4 = 'BAR')
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

    class WithoutTypeHints {

        public function __construct($param1, $param2)
        {
        }

    }

    class NoDependencies {

    }