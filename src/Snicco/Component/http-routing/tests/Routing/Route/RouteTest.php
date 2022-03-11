<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\Route;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Routing\Condition\ConditionBlueprint;
use Snicco\Component\HttpRouting\Routing\Condition\RouteCondition;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Tests\fixtures\Conditions\MaybeRouteCondition;
use Snicco\Component\HttpRouting\Tests\fixtures\Conditions\TrueRouteCondition;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;
use stdClass;
use TypeError;

/**
 * @internal
 */
final class RouteTest extends TestCase
{
    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_exception_if_path_does_not_start_with_forward_slash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected route pattern to start with /.');
        Route::create('foobar', []);
    }

    /**
     * @test
     */
    public function test_exception_if_name_contains_whitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route name for route [my route] should not contain whitespaces.');
        Route::create('/foobar', RoutingTestController::class, 'my route');
    }

    /**
     * @test
     */
    public function test_exception_for_bad_methods(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('bogus');

        Route::create('/foo', Route::DELEGATE, 'foo_route', ['GET', 'bogus']);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_exception_if_controller_array_is_missing_method(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Controller class [foo] does not exist.');
        Route::create('/foo', ['foo']);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_exception_controller_class_is_not_a_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected controller class to be a string.');
        Route::create('/foo', [new stdClass(), 'foo']);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_exception_bad_controller_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected controller array to have a class and a method.');
        Route::create('/foo', [
            'class' => RoutingTestController::class,
        ]);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_exception_controller_method_is_not_a_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected controller method to be a string.');
        Route::create('/foo', [RoutingTestController::class, new stdClass()]);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_exception_if_controller_class_does_not_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Controller class [Bogus] does not exist.');
        Route::create('/foo', ['Bogus', 'foo']);
    }

    /**
     * @test
     */
    public function test_exception_if_controller_method_does_not_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $c = RoutingTestController::class;
        $this->expectExceptionMessage('The method [' . $c . '::bogus] is not callable.');
        Route::create('/foo', [RoutingTestController::class, 'bogus']);
    }

    /**
     * @test
     */
    public function a_controller_shorthand_with_a_namespace_works(): void
    {
        $route = Route::create(
            '/foo',
            'RoutingTestController@static',
            null,
            ['GET'],
            HttpRunnerTestCase::CONTROLLER_NAMESPACE
        );

        $this->assertSame([RoutingTestController::class, 'static'], $route->getController());

        $route = Route::create(
            '/foo',
            'RoutingTestController@static',
            null,
            ['GET'],
            HttpRunnerTestCase::CONTROLLER_NAMESPACE . '\\'
        );

        $this->assertSame([RoutingTestController::class, 'static'], $route->getController());
    }

    /**
     * @test
     */
    public function test_exception_if_name_starts_with_dot(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Route::create('/foo', Route::DELEGATE, '.foo');
    }

    /**
     * @test
     */
    public function an_invalid_route_shorthand_still_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $c = RoutingTestController::class;
        $this->expectExceptionMessage('The method [' . $c . '::bogus] is not callable.');

        Route::create(
            '/foo',
            'RoutingTestController@bogus',
            null,
            ['GET'],
            HttpRunnerTestCase::CONTROLLER_NAMESPACE
        );
    }

    /**
     * @test
     */
    public function invokable_controllers_can_be_passed_with_only_the_class_name(): void
    {
        $route = Route::create('/foo', RoutingTestController::class);
        $this->assertSame([RoutingTestController::class, '__invoke'], $route->getController());
    }

    /**
     * @test
     */
    public function a_route_name_will_be_generated_if_not_passed_explicitly(): void
    {
        $route = Route::create('/foo', $arr = [RoutingTestController::class, 'static']);

        $e = implode('@', $arr);

        $this->assertSame('/foo:' . $e, $route->getName());

        $route = Route::create('/foo', $arr, 'foo_route');
        $this->assertSame('foo_route', $route->getName());
    }

    /**
     * @test
     */
    public function test_exception_if_duplicate_required_segment_names(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route segment names have to be unique but 1 of them is duplicated.');

        Route::create('/foo/{bar}/{bar}', Route::DELEGATE);
    }

    /**
     * @test
     */
    public function test_exception_if_duplicate_optional_segment_names(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route segment names have to be unique but 1 of them is duplicated.');

        Route::create('/foo/{bar?}/{bar?}', Route::DELEGATE);
    }

    /**
     * @test
     */
    public function test_exception_if_duplicate_required_and_optional_segment_names(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route segment names have to be unique but 1 of them is duplicated.');

        Route::create('/foo/{bar}/{bar?}', Route::DELEGATE);
    }

    /**
     * @test
     */
    public function test_exception_if_requirements_are_added_for_missing_segment(): void
    {
        $route = $this->newRoute('/foo/{bar}');

        $route->requirements([
            'bar' => '\d+',
        ]);

        $this->expectExceptionMessage('Expected one of the valid segment names: ["bar"]. Got: ["bogus"].');
        $route->requirements([
            'bogus' => '\d+',
        ]);
    }

    /**
     * @test
     */
    public function test_exception_if_requirements_are_overwritten(): void
    {
        $route = $this->newRoute('/foo/{bar}');

        $route->requirements([
            'bar' => '\d+',
        ]);
        $this->expectExceptionMessage('Requirement for segment [bar] can not be overwritten.');
        $route->requirements([
            'bar' => '\w+',
        ]);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_defaults_throws_exception_for_non_primitives(): void
    {
        $route = $this->newRoute();
        $route->defaults([
            'foo' => 'bar',
        ]);

        $this->expectExceptionMessage('A route default value has to be a scalar or an array of scalars.');

        $route->defaults([
            'foo' => new stdClass(),
        ]);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_conditions_throws_exceptions_for_bad_class(): void
    {
        $route = $this->newRoute();

        $route->condition(TrueRouteCondition::class);

        try {
            $route->condition(stdClass::class);
            $this->fail('No exception thrown for bad route condition class.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith(
                sprintf('A condition has to be an instance of [%s].', RouteCondition::class),
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function test_condition_throws_exception_for_duplicate_condition(): void
    {
        $route = $this->newRoute();

        $route->condition(TrueRouteCondition::class);

        try {
            $route->condition(TrueRouteCondition::class);
            $this->fail('Duplicate condition added.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith(
                sprintf(
                    'Condition [%s] was added twice to route [%s].',
                    TrueRouteCondition::class,
                    $route->getName()
                ),
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function test_get_conditions(): void
    {
        $route = $this->newRoute();

        $route->condition(MaybeRouteCondition::class, true);

        $expected = new ConditionBlueprint(MaybeRouteCondition::class, [true]);

        $this->assertEquals([
            MaybeRouteCondition::class => $expected,
        ], $route->getConditions());
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_middleware_throws_exceptions_for_non_strings(): void
    {
        $route = $this->newRoute();

        $this->expectException(TypeError::class);

        $route->middleware(['foo', new FooMiddleware()]);
    }

    /**
     * @test
     */
    public function test_exception_if_duplicate_middleware_is_set(): void
    {
        $route = Route::create('/foo', Route::DELEGATE, 'foo_route');

        $route->middleware('foo');
        $route->middleware('bar');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware [foo] added twice to route [foo_route].');
        $route->middleware('foo');
    }

    /**
     * @test
     */
    public function test_exception_if_duplicate_middleware_is_set_with_arguments(): void
    {
        $route = Route::create('/foo', Route::DELEGATE, 'foo_route');

        $route->middleware('foo:arg1');
        $route->middleware('bar');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware [foo] added twice to route [foo_route].');
        $route->middleware('foo:arg2');
    }

    /**
     * @test
     */
    public function test_middleware_returns_array_of_string(): void
    {
        $route = Route::create('/foo', Route::DELEGATE, 'foo_route');

        $route->middleware('foo:arg1');
        $route->middleware('bar');

        $this->assertSame(['foo:arg1', 'bar'], $route->getMiddleware());
    }

    /**
     * @test
     */
    public function test_serialize(): void
    {
        $route = $this->newRoute();

        $array = serialize($route);

        $new_route = unserialize($array);

        $this->assertInstanceOf(Route::class, $new_route);

        $this->assertEquals($route, $new_route);
    }

    /**
     * @test
     */
    public function test_matches_only_trailing(): void
    {
        $route = $this->newRoute('/foo');
        $this->assertFalse($route->matchesOnlyWithTrailingSlash());

        $route = $this->newRoute('/foo/');
        $this->assertTrue($route->matchesOnlyWithTrailingSlash());

        $route = $this->newRoute('/foo/{bar}');
        $this->assertFalse($route->matchesOnlyWithTrailingSlash());

        $route = $this->newRoute('/foo/{bar}/');
        $this->assertTrue($route->matchesOnlyWithTrailingSlash());

        $route = $this->newRoute('/foo/{bar?}');
        $this->assertFalse($route->matchesOnlyWithTrailingSlash());

        $route = $this->newRoute('/foo/{bar?}/');
        $this->assertTrue($route->matchesOnlyWithTrailingSlash());
    }

    private function newRoute(string $path = '/foo'): Route
    {
        return Route::create($path, Route::DELEGATE);
    }
}
