<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Configuration\WritableConfig;
use Tests\Codeception\shared\TestDependencies\Foo;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
use Tests\Core\fixtures\Conditions\RouteConditionWithDependency;
use Tests\Core\fixtures\Controllers\Web\ControllerWithDependencies;

class RouteActionDependencyInjectionTest extends RoutingTestCase
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
        
        $request = $this->frontendRequest('GET', '/foo');
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
        
        $request = $this->frontendRequest('GET', '/teams/dortmund/calvin');
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
        
        $request = $this->frontendRequest('GET', '/teams/dortmund/calvin');
        $this->assertResponseBody('dortmund:calvin', $request);
    }
    
    /** @test */
    public function its_possible_to_require_a_class_but_not_the_request()
    {
        $foo = new Foo();
        $this->container[Foo::class] = $foo;
        $foo->foo = 'FOO';
        
        $this->routeConfigurator()->get(
            'r1',
            'teams/{team}/{player}',
            [RoutingTestController::class, 'twoParamsWithDependency']
        );
        
        $request = $this->frontendRequest('GET', '/teams/dortmund/calvin');
        $this->assertResponseBody('FOO:dortmund:calvin', $request);
    }
    
    /** @test */
    public function its_possible_to_require_a_class_and_the_request_plus_params()
    {
        $foo = new Foo();
        $this->container[Foo::class] = $foo;
        $foo->foo = 'FOO';
        
        $this->routeConfigurator()->get(
            'r1',
            'teams/{team}/{player}',
            [RoutingTestController::class, 'twoParamsWithDependencyAndRequest']
        );
        
        $request = $this->frontendRequest('GET', '/teams/dortmund/calvin');
        $this->assertResponseBody('FOO:dortmund:calvin', $request);
    }
    
    /** @test */
    public function controllers_are_resolved_from_the_container()
    {
        $foo = new Foo();
        $this->container[Foo::class] = $foo;
        $foo->foo = 'FOO';
        
        $this->container[ControllerWithDependencies::class] = new ControllerWithDependencies($foo);
        
        $this->routeConfigurator()->get('r1', '/foo', ControllerWithDependencies::class);
        
        $request = $this->frontendRequest('GET', 'foo');
        $this->assertResponseBody('FOO_controller', $request);
    }
    
    /** @test */
    public function arguments_from_conditions_are_passed_after_class_dependencies()
    {
        $foo = new Foo();
        $this->container[Foo::class] = $foo;
        $foo->foo = 'FOO';
        
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
            [RoutingTestController::class, 'requestDependencyParamsCondition']
        )->condition(RouteConditionWithDependency::class, true);
        
        $request = $this->frontendRequest('GET', '/teams/dortmund/calvin');
        $this->assertResponseBody('FOO:dortmund:calvin:FOO_CONFIG', $request);
    }
    
}