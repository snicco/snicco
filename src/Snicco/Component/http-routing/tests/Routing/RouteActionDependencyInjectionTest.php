<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;
use Snicco\Component\Core\Configuration\WritableConfig;
use Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies\Foo;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\ControllerWithDependencies;
use Snicco\Component\HttpRouting\Tests\fixtures\Conditions\RouteConditionWithDependency;

class RouteActionDependencyInjectionTest extends HttpRunnerTestCase
{
    
    /** @test */
    public function the_request_does_not_have_to_be_bound_in_the_container()
    {
        $this->assertFalse($this->container->has(Request::class));
        
        $this->routeConfigurator()->get(
            'route1',
            '/foo',
            [RoutingTestController::class, 'onlyRequest']
        );
        
        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function its_not_required_to_have_class_dependencies()
    {
        $this->routeConfigurator()->get(
            'r1',
            'teams/{team}/{player}',
            [RoutingTestController::class, 'twoParams']
        );
        
        $request = $this->frontendRequest('/teams/dortmund/calvin');
        $this->assertResponseBody('dortmund:calvin', $request);
    }
    
    /** @test */
    public function the_request_can_be_required_together_with_params()
    {
        $this->routeConfigurator()->get(
            'r1',
            'teams/{team}/{player}',
            [RoutingTestController::class, 'twoParamsWithRequest']
        );
        
        $request = $this->frontendRequest('/teams/dortmund/calvin');
        $this->assertResponseBody('dortmund:calvin', $request);
    }
    
    /** @test */
    public function controllers_are_resolved_from_the_container()
    {
        $foo = new Foo();
        $this->container[Foo::class] = $foo;
        $foo->value = 'FOO';
        
        $this->container[ControllerWithDependencies::class] = new ControllerWithDependencies($foo);
        
        $this->routeConfigurator()->get('r1', '/foo', ControllerWithDependencies::class);
        
        $request = $this->frontendRequest('foo');
        $this->assertResponseBody('FOO_controller', $request);
    }
    
    /** @test */
    public function arguments_from_conditions_are_passed_after_class_dependencies()
    {
        $config = new WritableConfig();
        $this->container[WritableConfig::class] = $config;
        $config->set('foo', 'FOO_CONFIG');
        
        $this->container->instance(RouteConditionWithDependency::class, function (bool $pass) {
            return new RouteConditionWithDependency(
                $this->container[WritableConfig::class],
                $pass
            );
        });
        
        $this->routeConfigurator()->get(
            'r1',
            'teams/{team}/{player}',
            [RoutingTestController::class, 'requestParamsConditionArgs']
        )->condition(RouteConditionWithDependency::class, true);
        
        $request = $this->frontendRequest('/teams/dortmund/calvin');
        $this->assertResponseBody('dortmund:calvin:FOO_CONFIG', $request);
    }
    
}