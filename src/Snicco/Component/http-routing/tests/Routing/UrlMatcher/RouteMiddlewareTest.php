<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use InvalidArgumentException;
use Snicco\Component\HttpRouting\Exception\InvalidMiddleware;
use Snicco\Component\HttpRouting\Exception\MiddlewareRecursion;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\BazMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\BooleanMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\ControllerWithBarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\FoobarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\IntegerMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;
use stdClass;

/**
 * @internal
 */
final class RouteMiddlewareTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function an_exception_is_thrown_if_alias_and_middleware_group_have_the_same_name(): void
    {
        $this->withMiddlewareAlias([
            'alias' => FooMiddleware::class,
        ]);
        $this->withMiddlewareGroups([
            'alias' => [BarMiddleware::class],
        ]);

        $this->expectException(InvalidMiddleware::class);
        $this->expectExceptionMessage('Middleware group and alias have the same name [alias].');
        $this->runNewPipeline($this->frontendRequest());
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function an_exception_is_thrown_if_a_middleware_alias_does_not_resolve_to_a_valid_middleware_class(): void
    {
        $this->withMiddlewareAlias([
            'alias1' => stdClass::class,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Alias [alias1] resolves to invalid middleware class-string [stdClass].\nExpected: [Psr\\Http\\Server\\MiddlewareInterface]"
        );
        $this->runNewPipeline($this->frontendRequest());
    }

    /**
     * @test
     */
    public function applying_a_route_group_to_a_route_applies_all_middleware_in_the_group(): void
    {
        $this->withMiddlewareGroups([
            'group1' => [FooMiddleware::class, BarMiddleware::class],
        ]);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)->middleware('group1');
        });

        $request = $this->frontendRequest('/foo');

        // Foo middleware is run first, so it appends last to the response body
        $this->assertResponseBody(RoutingTestController::static . ':bar_middleware:foo_middleware', $request);
    }

    /**
     * @test
     */
    public function duplicate_middleware_is_filtered_out(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)->middleware('group1');
        });

        $this->withMiddlewareGroups(
            [
                'global' => [FooMiddleware::class, BarMiddleware::class],
                'group1' => [FooMiddleware::class, BarMiddleware::class],
            ]
        );

        $request = $this->frontendRequest('/foo');

        // The middleware is not run twice.
        $this->assertResponseBody(RoutingTestController::static . ':bar_middleware:foo_middleware', $request);
    }

    /**
     * @test
     */
    public function duplicate_middleware_does_not_throw_an_exception(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->middleware(FooMiddleware::class)->group(
                function (WebRoutingConfigurator $router): void {
                    $router->middleware([FooMiddleware::class, BarMiddleware::class])->group(
                        function (WebRoutingConfigurator $router): void {
                            $router->get('r1', '/foo', RoutingTestController::class)->middleware(
                                BazMiddleware::class
                            );
                        }
                    );
                }
            );
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runNewPipeline($request);
        $response->assertOk()
            ->assertBodyExact(RoutingTestController::static . ':baz_middleware:bar_middleware:foo_middleware');
    }

    /**
     * @test
     */
    public function duplicate_middleware_is_filtered_out_when_passing_the_same_middleware_arguments(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)->middleware(['all', 'foo:FOO']);
        });

        $this->withMiddlewareGroups([
            'all' => [FooMiddleware::class . ':FOO', BarMiddleware::class],
        ]);

        $request = $this->frontendRequest('foo');
        $this->assertResponseBody(RoutingTestController::static . ':bar_middleware:FOO', $request);
    }

    /**
     * @test
     */
    public function duplicate_middleware_is_not_filtered_out_when_passing_different_arguments(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)->middleware(['all', 'foo:FOO1']);
        });

        $this->withMiddlewareGroups([
            'all' => [FooMiddleware::class . ':FOO2', BarMiddleware::class],
        ]);

        $request = $this->frontendRequest('foo');

        // The middleware on the route is run last which is why is output is appended first to the response body.
        $this->assertResponseBody(RoutingTestController::static . ':FOO1:bar_middleware:FOO2', $request);
    }

    /**
     * @test
     */
    public function multiple_middleware_groups_can_be_applied(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)
                ->middleware(['group1', 'group2']);
        });
        $this->withMiddlewareGroups([
            'group1' => [FooMiddleware::class],
            'group2' => [BarMiddleware::class],
        ]);

        $request = $this->frontendRequest('/foo');

        $this->assertResponseBody(RoutingTestController::static . ':bar_middleware:foo_middleware', $request);
    }

    /**
     * @test
     */
    public function middleware_can_be_added_as_a_full_class_name(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)
                ->middleware([FooMiddleware::class, BarMiddleware::class]);
        });

        $request = $this->frontendRequest('/foo');

        $this->assertResponseBody(RoutingTestController::static . ':bar_middleware:foo_middleware', $request);
    }

    /**
     * @test
     */
    public function unknown_middleware_aliases_throw_an_exception(): void
    {
        $this->expectExceptionMessage('The middleware [abc] is not an alias or group name.');

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)
                ->middleware('abc');
        });

        $this->runNewPipeline($this->frontendRequest('foo'));
    }

    /**
     * @test
     */
    public function multiple_middleware_arguments_can_be_passed(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)
                ->middleware('foobar');

            $configurator->post('r2', '/foo', RoutingTestController::class)
                ->middleware('foobar:FOO');

            $configurator->patch('r3', '/foo', RoutingTestController::class)
                ->middleware('foobar:FOO,BAR');
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':foobar_middleware', $request);

        $request = $this->frontendRequest('/foo', [], 'POST');
        $this->assertResponseBody(RoutingTestController::static . ':FOO_foobar_middleware', $request);

        $request = $this->frontendRequest('/foo', [], 'PATCH');
        $this->assertResponseBody(RoutingTestController::static . ':FOO_BAR', $request);
    }

    /**
     * @test
     */
    public function boolean_true_false_is_converted(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)
                ->middleware(BooleanMiddleware::class . ':true');

            $configurator->post('r2', '/foo', RoutingTestController::class)
                ->middleware(BooleanMiddleware::class . ':false');
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':boolean_true', $request);

        $request = $this->frontendRequest('/foo', [], 'POST');
        $this->assertResponseBody(RoutingTestController::static . ':boolean_false', $request);
    }

    /**
     * @test
     */
    public function numeric_values_are_converted(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)
                ->middleware(IntegerMiddleware::class . ':1');
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':integer_1', $request);
    }

    /**
     * @test
     */
    public function a_middleware_group_can_point_to_a_middleware_alias(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)->middleware('foogroup');
        });

        $this->withMiddlewareGroups([
            'foogroup' => ['foo'],
        ]);

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':foo_middleware', $request);
    }

    /**
     * @test
     */
    public function group_and_route_middleware_can_be_combined(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)
                ->middleware(['baz', 'group1']);
        });

        $this->withMiddlewareGroups([
            'group1' => [FooMiddleware::class, BarMiddleware::class],
        ]);

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(
            RoutingTestController::static . ':bar_middleware:foo_middleware:baz_middleware',
            $request
        );
    }

    /**
     * @test
     */
    public function a_middleware_group_can_contain_another_middleware_group(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)->middleware('baz_group');
        });

        $this->withMiddlewareGroups([
            'baz_group' => [BazMiddleware::class, 'bar_group'],
            'bar_group' => [BarMiddleware::class, 'foo_group'],
            'foo_group' => [FooMiddleware::class],
        ]);

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(
            RoutingTestController::static . ':foo_middleware:bar_middleware:baz_middleware',
            $request
        );
    }

    /**
     * @test
     */
    public function middleware_can_be_applied_without_an_alias_and_arguments(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)->middleware(
                FooMiddleware::class . ':FOO'
            );
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':FOO', $request);
    }

    /**
     * @test
     */
    public function middleware_is_sorted(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)
                ->middleware(['barbaz', FooMiddleware::class]);
        });

        // The global middleware will be run last even tho it has no priority.
        $this->withGlobalMiddleware([FoobarMiddleware::class]);

        $this->withMiddlewareGroups([
            'barbaz' => [BazMiddleware::class, BarMiddleware::class],
        ]);

        $this->withMiddlewarePriority([FooMiddleware::class, BarMiddleware::class, BazMiddleware::class]);

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(
            RoutingTestController::static
            . ':baz_middleware:bar_middleware:foo_middleware:foobar_middleware',
            $request
        );
    }

    /**
     * @test
     */
    public function global_middleware_will_be_run_first_even_if_set_on_route(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)
                ->middleware([FooMiddleware::class, 'global']);
        });

        // The global middleware will be run last even tho it has no priority.
        $this->withGlobalMiddleware([BarMiddleware::class]);

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':foo_middleware:bar_middleware', $request);
    }

    /**
     * @test
     */
    public function middleware_keeps_its_relative_position_if_its_has_no_priority_defined(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)->middleware('all');
        });

        $this->withMiddlewareGroups([
            'all' => [FoobarMiddleware::class, BarMiddleware::class, BazMiddleware::class, FooMiddleware::class],
        ]);

        $this->withMiddlewarePriority([FooMiddleware::class, BarMiddleware::class]);

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(
            RoutingTestController::static
            . ':baz_middleware:foobar_middleware:bar_middleware:foo_middleware',
            $request
        );
    }

    /**
     * @test
     */
    public function middleware_in_the_global_group_is_always_applied_if_a_route_matches(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo');

        $this->withMiddlewareGroups([
            'global' => [FooMiddleware::class, BarMiddleware::class],
        ]);

        $this->assertResponseBody(RoutingTestController::static . ':bar_middleware:foo_middleware', $request);
    }

    /**
     * @test
     */
    public function global_middleware_is_not_run_when_no_route_matches(): void
    {
        $this->withGlobalMiddleware([FooMiddleware::class, BarMiddleware::class]);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class);
        });

        $response = $this->runNewPipeline($this->frontendRequest('/bar'));

        $this->assertSame('', $response->body());
    }

    /**
     * @test
     */
    public function global_middleware_can_be_configured_to_run_for_even_for_non_matching_requests(): void
    {
        $this->alwaysRun([RoutingConfigurator::GLOBAL_MIDDLEWARE]);
        $this->withGlobalMiddleware([FooMiddleware::class, BarMiddleware::class]);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class);
        });

        $response = $this->runNewPipeline($this->frontendRequest('/bar'));

        $this->assertSame(':bar_middleware:foo_middleware', $response->body());
    }

    /**
     * @test
     */
    public function web_middleware_can_be_configured_to_always_run_for_non_matching_requests(): void
    {
        $this->alwaysRun([RoutingConfigurator::FRONTEND_MIDDLEWARE]);
        $this->withMiddlewareGroups(
            [
                RoutingConfigurator::FRONTEND_MIDDLEWARE => [FooMiddleware::class, BarMiddleware::class],
            ]
        );

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class);
        });

        $response = $this->runNewPipeline($this->frontendRequest('/bar'));

        $this->assertSame(':bar_middleware:foo_middleware', $response->body());
    }

    /**
     * @test
     */
    public function running_web_middleware_always_is_has_no_effect_on_admin_requests(): void
    {
        $this->alwaysRun([RoutingConfigurator::FRONTEND_MIDDLEWARE]);
        $this->withMiddlewareGroups(
            [
                RoutingConfigurator::FRONTEND_MIDDLEWARE => [FooMiddleware::class, BarMiddleware::class],
            ]
        );

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('admin1', 'admin.php/foo', RoutingTestController::class, [],);
        });

        $response = $this->runNewPipeline($this->adminRequest('/wp-admin/admin.php?page=foo'));
        $this->assertSame(RoutingTestController::static, $response->body());

        $response = $this->runNewPipeline($this->adminRequest('/bar'));
        $this->assertSame('', $response->body());
    }

    /**
     * @test
     */
    public function admin_middleware_can_be_configured_to_always_run_for_non_admin_matching_requests(): void
    {
        $this->alwaysRun([RoutingConfigurator::ADMIN_MIDDLEWARE]);
        $this->withMiddlewareGroups(
            [
                RoutingConfigurator::ADMIN_MIDDLEWARE => [FooMiddleware::class, BarMiddleware::class],
            ]
        );

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('r1', 'admin.php/foo', RoutingTestController::class,);
        });

        $response = $this->runNewPipeline($this->adminRequest('/bar'));

        $this->assertSame(':bar_middleware:foo_middleware', $response->body());
    }

    /**
     * @test
     */
    public function running_admin_middleware_always_has_no_effect_on_non_matching_web_requests(): void
    {
        $this->alwaysRun([RoutingConfigurator::ADMIN_MIDDLEWARE]);
        $this->withMiddlewareGroups(
            [
                RoutingConfigurator::ADMIN_MIDDLEWARE => [FooMiddleware::class, BarMiddleware::class],
            ]
        );

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('web1', '/foo', RoutingTestController::class);
        });

        $response = $this->runNewPipeline($this->frontendRequest('/bar'));
        $this->assertSame('', $response->body());
    }

    /**
     * @test
     */
    public function api_middleware_can_be_always_run(): void
    {
        $this->alwaysRun([RoutingConfigurator::API_MIDDLEWARE]);
        $this->withMiddlewareGroups(
            [
                RoutingConfigurator::API_MIDDLEWARE => [FooMiddleware::class, BarMiddleware::class],
            ]
        );

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', 'foo', RoutingTestController::class,);
        });

        $response = $this->runNewPipeline($this->apiRequest('/bar'));
        $this->assertSame(':bar_middleware:foo_middleware', $response->body());

        $response = $this->runNewPipeline($this->frontendRequest('/bar'));
        $this->assertSame('', $response->body());
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function adding_one_of_the_non_core_middleware_groups_to_always_run_global_will_thrown_an_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('can not be used as middleware that is always');

        $this->alwaysRun([FooMiddleware::class]);

        $this->runNewPipeline($this->frontendRequest());
    }

    /**
     * @test
     */
    public function recursion_is_detected(): void
    {
        $this->withMiddlewareGroups([
            'group1' => ['group2'],
            'group2' => ['group1'],
        ]);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)
                ->middleware('group1');
        });

        $this->expectException(MiddlewareRecursion::class);
        $this->expectExceptionMessage('Detected middleware recursion: group1->group2->group1');

        $this->runNewPipeline($this->frontendRequest('/foo'));
    }

    /**
     * @test
     */
    public function recursion_is_detected_recursively(): void
    {
        $this->withMiddlewareGroups([
            'group1' => ['group2'],
            'group2' => ['group3'],
            'group3' => ['group1'],
        ]);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)
                ->middleware('group1');
        });

        $this->expectException(MiddlewareRecursion::class);
        $this->expectExceptionMessage('Detected middleware recursion: group1->group2->group3->group1');

        $this->runNewPipeline($this->frontendRequest('/foo'));
    }

    /**
     * @test
     */
    public function recursion_is_detected_for_complex_nesting(): void
    {
        $this->withMiddlewareGroups([
            'correct_1' => [FooMiddleware::class],
            'correct_2' => [BarMiddleware::class],
            'group1' => ['correct_1', 'group2'],
            'group2' => ['correct_2', 'group3'],
            'group3' => [BazMiddleware::class, 'group1'],
        ]);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)
                ->middleware('group1');
        });

        $this->expectException(MiddlewareRecursion::class);
        $this->expectExceptionMessage('Detected middleware recursion: group1->group2->group3->group1');

        $this->runNewPipeline($this->frontendRequest('/foo'));
    }

    /**
     * @test
     */
    public function recursion_is_detected_in_the_special_middleware_groups_before_a_matching_route_is_run(): void
    {
        $this->withMiddlewareGroups([
            'correct_1' => [FooMiddleware::class],
            'correct_2' => [BarMiddleware::class],
            RoutingConfigurator::GLOBAL_MIDDLEWARE => ['correct_1', 'group2'],
            'group2' => ['correct_2', 'group3'],
            'group3' => [BazMiddleware::class, 'group2'],
        ]);

        $this->expectException(MiddlewareRecursion::class);
        $this->expectExceptionMessage('Detected middleware recursion: global->group2->group3->group2');

        $this->runNewPipeline($this->frontendRequest());
    }

    /**
     * @test
     */
    public function controller_middleware_is_after_route_middleware(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', ControllerWithBarMiddleware::class)
                ->middleware(FooMiddleware::class);
        });

        $response = $this->runNewPipeline($this->frontendRequest('/foo'));
        $this->assertSame('controller:bar_middleware:foo_middleware', $response->body());
    }
}
