<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Conditions\RouteConditionWithArgs;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\ControllerWithDependencies;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies\Foo;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

/**
 * @internal
 */
final class RouteActionDependencyInjectionTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function the_request_does_not_have_to_be_bound_in_the_container(): void
    {
        $this->assertFalse($this->psr_container->has(Request::class));

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('route1', '/foo', [RoutingTestController::class, 'onlyRequest']);
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function its_not_required_to_have_class_dependencies(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', 'teams/{team}/{player}', [RoutingTestController::class, 'twoParams']);
        });

        $request = $this->frontendRequest('/teams/dortmund/calvin');
        $this->assertResponseBody('dortmund:calvin', $request);
    }

    /**
     * @test
     */
    public function the_request_can_be_required_together_with_params(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get(
                'r1',
                'teams/{team}/{player}',
                [RoutingTestController::class, 'twoParamsWithRequest']
            );
        });

        $request = $this->frontendRequest('/teams/dortmund/calvin');
        $this->assertResponseBody('dortmund:calvin', $request);
    }

    /**
     * @test
     */
    public function controllers_are_resolved_from_the_container(): void
    {
        $foo = new Foo();
        $this->pimple[Foo::class] = $foo;
        $foo->value = 'FOO';

        $this->pimple[ControllerWithDependencies::class] = fn (): ControllerWithDependencies => new ControllerWithDependencies(
            $foo
        );

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', ControllerWithDependencies::class);
        });

        $request = $this->frontendRequest('foo');
        $this->assertResponseBody('FOO_controller', $request);
    }

    /**
     * @test
     */
    public function arguments_from_conditions_are_passed_after_class_dependencies(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get(
                'r1',
                'teams/{team}/{player}',
                [RoutingTestController::class, 'requestParamsConditionArgs']
            )->condition(RouteConditionWithArgs::class, 'FOO', true);
        });

        $request = $this->frontendRequest('/teams/dortmund/calvin');
        $this->assertResponseBody('dortmund:calvin:FOO', $request);
    }
}
