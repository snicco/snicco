<?php


    declare(strict_types = 1);


    namespace Tests\unit\Support;

    use Tests\fixtures\TestDependencies\Bar;
    use Tests\fixtures\TestDependencies\Foo;
    use Tests\stubs\TestRequest;
    use Tests\helpers\CreateContainer;
    use WPEmerge\Support\ReflectionPayload;
    use PHPUnit\Framework\TestCase;

    class ReflectionPayloadTest extends TestCase
    {

        use CreateContainer;

        /**
         *
         *
         *
         *
         *
         *
         *
         * ALL PARAMS WITH TYPE HINTS
         *
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public function a_class_without_constructor_returns_an_empty_array () {

            $payload = new ReflectionPayload(NoDependencies::class, ['foo', 'bar']);

            $this->assertSame([], $payload->build());

        }

        /** @test */
        public function only_matching_typehints_are_included () {

            $payload = new ReflectionPayload(AllTypeHints::class, ['foo']);
            $this->assertSame(['param3' => 'foo'], $payload->build());

            $payload = new ReflectionPayload(AllTypeHints::class, [ $foo = new Foo(), 'foo' ]);
            $this->assertSame(['param1' => $foo, 'param3' => 'foo'], $payload->build());

            $payload = new ReflectionPayload(AllTypeHints::class, [ $foo = new Foo(), $bar = new Bar(),  'foo' ]);

            $this->assertSame(['param1' => $foo, 'param2' => $bar, 'param3' => 'foo'], $payload->build());

        }

        /** @test */
        public function the_order_does_not_matter_for_matching_type_hints () {

            $payload = new ReflectionPayload(AllTypeHints::class, [ 'foo', $foo = new Foo(), 'bar' , $bar = new Bar() ]);

            $result = $payload->build();
            $this->assertSame(['param3' => 'foo', 'param1' => $foo, 'param4' => 'bar', 'param2' => $bar], $result);

            $c = $this->createContainer();

            $instance = $c->make(AllTypeHints::class, $result);
            $this->assertInstanceOf(AllTypeHints::class, $instance);

        }

        /** @test */
        public function optional_values_are_included_if_not_present_in_the_payload () {

            $payload = new ReflectionPayload(TypeHintsLastTwoOptional::class,
                [ $foo = new Foo(), $bar = new Bar() ]
            );

            $result = $payload->build();
            $this->assertSame(['param1' => $foo, 'param2' => $bar, 'param3' => 'foo', 'param4' => 'bar'], $result);

        }

        /** @test */
        public function optional_params_get_overwritten_by_order_if_the_type_is_correct () {

            $payload = new ReflectionPayload(TypeHintsLastTwoOptional::class,
                [ $foo = new Foo(), $bar = new Bar(), 'FOO' ]
            );

            $result = $payload->build();
            $this->assertSame(['param1' => $foo, 'param2' => $bar, 'param3' => 'FOO', 'param4' => 'bar'], $result);

             $payload = new ReflectionPayload(TypeHintsLastTwoOptional::class,
                [ $foo = new Foo(), $bar = new Bar(), 'FOO', 'BAR' ]
            );

            $result = $payload->build();
            $this->assertSame(['param1' => $foo, 'param2' => $bar, 'param3' => 'FOO', 'param4' => 'BAR'], $result);

            // type not matching
            $payload = new ReflectionPayload(TypeHintsLastTwoOptional::class,
                [ $foo = new Foo(), $bar = new Bar(), 'FOO', 12 ]
            );

            $result = $payload->build();
            $this->assertSame(['param1' => $foo, 'param2' => $bar, 'param3' => 'FOO', 'param4' => 'bar'], $result);


        }

        /** @test */
        public function the_first_matching_typehint_gets_assigned_the_first_value_of_that_type () {

            $payload = new ReflectionPayload(TrailingPrimitiveWithoutTypeHint::class, ['foo', 'bar'] );

            $this->assertSame(['param3' => 'foo', 'param4' => 'bar'], $payload->build());


        }

        /** @test */
        public function the_order_of_the_payload_does_not_matter () {


            $payload = new ReflectionPayload(TrailingPrimitiveWithoutTypeHint::class, [
                'foo', $bar = new Bar(), $foo = new Foo()
            ]);
            $this->assertSame(['param3' => 'foo', 'param2' => $bar, 'param1' => $foo], $payload->build());


        }

        /** @test */
        public function it_works_with_several_params_with_the_same_typehint () {

            $payload = new ReflectionPayload(TwoStrings::class, ['foo', 'bar']);
            $this->assertSame(['param1' => 'foo', 'param2' => 'bar'], $payload->build());


        }

        /** @test */
        public function a_value_is_not_included_twice () {

            $payload = new ReflectionPayload(TwoStrings::class, ['foo']);
            $this->assertSame(['param1' => 'foo'], $payload->build());


        }

        /** @test */
        public function it_works_with_boolean_types () {

            $payload = new ReflectionPayload(WithBool::class, [true]);
            $this->assertSame(['param1' => true], $payload->build());

        }

        /** @test */
        public function it_works_with_boolean_false () {

            $payload = new ReflectionPayload(WithBool::class, [false]);
            $this->assertSame(['param1' => false], $payload->build());


        }

        /** @test */
        public function passing_no_payload_retains_default_constructor_args () {

            $payload = new ReflectionPayload(WithDefaultValue::class, []);
            $this->assertSame(['param1' => 'foo'], $payload->build());


        }

        /**
         * @test
         *
         * This should only be used as a fallback instantiating the class by typehint payload
         * was not possible.
         */
        public function the_payload_can_also_be_built_by_order_of_parameters () {

            $payload = new ReflectionPayload(
                TrailingPrimitiveWithoutTypeHint::class,
                ['foo', 'bar', 'baz', 'biz']
            );

            $this->assertSame(['param1' => 'foo', 'param2' => 'bar', 'param3' => 'baz', 'param4'=>'biz'], $payload->byOrder());


        }

        /** @test */
        public function passing_more_arguments_to_the_payload_has_no_effect () {

            $payload = new ReflectionPayload(AllTypeHints::class, ['foo', 'bar', 'baz', 'biz']);

            $this->assertSame(['param3'=> 'foo', 'param4' => 'bar'], $payload->build());

        }

        /** @test */
        public function the_used_container_can_build_classes_based_on_the_payloads () {

            $payload = new ReflectionPayload(TrailingPrimitiveWithoutTypeHint::class, [
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
         *
         *
         *
         * SOME PARAMS WITH TYPE HINTS AND NONE WITHOUT IN BETWEEN
         *
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public function until_the_first_parameter_without_typehint_all_params_are_created_by_typehint_instead_of_order () {

            $payload = new ReflectionPayload(FirstTwoTypeHinted::class, [ $foo = new Foo(), 'foo', 'bar' ]);

            $this->assertSame(['param1' => $foo, 'param3' => 'foo', 'param4' => 'bar'], $payload->build());

        }

        /** @test */
        public function default_values_are_included () {

            $payload = new ReflectionPayload(FirstTwoTypeHintedWithOptional::class, [ $foo = new Foo()]);

            $this->assertSame(['param1' => $foo, 'param3' => 'foo', 'param4' => 'bar'], $payload->build());

        }

        /** @test */
        public function default_values_can_be_overwritten () {

            $payload = new ReflectionPayload(FirstTwoTypeHintedWithOptional::class, [ $foo = new Foo(), 'FOO']);
            $this->assertSame(['param1' => $foo, 'param3' => 'FOO', 'param4' => 'bar'], $payload->build());

            $payload = new ReflectionPayload(FirstTwoTypeHintedWithOptional::class, [ $foo = new Foo(), 'FOO', 'BAR']);
            $this->assertSame(['param1' => $foo, 'param3' => 'FOO', 'param4' => 'BAR'], $payload->build());


        }


        /**
         *
         *
         *
         *
         *
         *
         *
         * SOME PARAMS WITH TYPE HINTS MIXED WITHOUT TYPE HINTS
         *
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public function as_soon_as_one_constructor_param_doesnt_have_typehints_we_default_to_building_by_order () {

            $payload = new ReflectionPayload(MixedTypeHints::class, [$foo = new Foo , 'BAR', $bar = new Bar()]);

            $this->assertSame(['param1' => $foo, 'param2' => 'BAR', 'param3' => $bar], $payload->build());


        }

        /** @test */
        public function no_typehints_payload_leads_to_just_building_by_order () {

            $payload = new ReflectionPayload(NoTypeHints::class, [$foo = new Foo , 'foo', $bar = new Bar(), 'bar']);

            $this->assertSame(['param1' => $foo, 'param2' => 'foo', 'param3' => $bar], $payload->build());

        }


        /**
         *
         *
         *
         *
         *
         *
         *
         * VARIADIC PARAMETERS
         *
         *
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

            $payload = new ReflectionPayload(CompleteVariadic::class, [
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

            $payload = new ReflectionPayload(SomeVariadic::class, [
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


        /**
         *
         *
         *
         *
         *
         *
         *
         * CLASS METHOD AND CLOSURES
         *
         *
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public function it_works_for_class_methods () {

            $payload = new ReflectionPayload( [Method::class, 'trailingPrimitive'], [
                $foo = new Foo(), $bar = new Bar(), 'foo', 'BAR'
            ]);

            $result = $payload->build();

            $this->assertSame(['param1'=>$foo, 'param2' => $bar, 'param3'=> 'foo', 'param4' => 'BAR'] , $result);

        }


        /** @test */
        public function it_works_for_closures () {

            $function = function ( Foo $param1, Bar $param2, string $param3, $param4 = 'bar', $param5 = 'baz' ) {


            };

            $payload = new ReflectionPayload( $function , [
                $foo = new Foo(), $bar = new Bar(), 'foo', 'BAR'
            ]);

            $result = $payload->build();

            $this->assertSame(['param1'=>$foo, 'param2' => $bar, 'param3'=> 'foo', 'param4' => 'BAR', 'param5' => 'baz'] , $result);

        }

        /** @test */
        public function it_doesnt_break_when_a_closure_has_no_parameters () {

            $function = function () {


            };

            $payload = new ReflectionPayload( $function , [
                $foo = new Foo(), $bar = new Bar(), 'foo', 'BAR'
            ]);

            $result = $payload->build();

            $this->assertSame([] , $result);

        }

    }

    class NoTypeHints {
        public function __construct( $param1, $param2,  $param3 )
        {

        }
    }

    class MixedTypeHints {


        public function __construct(Foo $param1, $param2, Bar $param3 )
        {

        }


    }

    class AllTypeHints {

        public function __construct(Foo $param1, Bar $param2, string $param3, string $param4)
        {

        }

    }

    class TypeHintsLastTwoOptional {

        public function __construct(Foo $param1, Bar $param2, string $param3 = 'foo', string $param4 = 'bar')
        {

        }

    }

    class FirstTwoTypeHinted {

        public function __construct(Foo $param1, Bar $param2, $param3 , $param4)
        {
        }

    }

    class FirstTwoTypeHintedWithOptional {

        public function __construct(Foo $param1, Bar $param2, $param3 = 'foo' , $param4 = 'bar')
        {
        }

    }

    class Method {

        public function trailingPrimitive (Foo $param1, Bar $param2, string $param3, $param4 = 'bar') {



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

    class NoDependencies {

    }