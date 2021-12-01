<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Snicco\Http\Psr7\Request;
use Tests\Core\RoutingTestCase;
use Tests\Codeception\shared\TestDependencies\Bar;
use Tests\Codeception\shared\TestDependencies\Foo;
use Tests\Core\fixtures\Controllers\Web\TeamsController;
use Tests\Core\fixtures\Controllers\Web\ControllerWithDependencies;

class RouteActionDependencyInjectionTest extends RoutingTestCase
{
    
    /** @test */
    public function its_not_required_to_have_class_dependencies()
    {
        $this->createRoutes(function () {
            $this->router->get('teams/{team}/{player}', TeamsController::class.'@withoutClassDeps');
        });
        
        $request = $this->frontendRequest('GET', '/teams/dortmund/calvin');
        $this->assertResponse('dortmund:calvin', $request);
    }
    
    /** @test */
    public function its_possible_to_require_a_class_but_not_the_request()
    {
        $this->createRoutes(function () {
            $this->router->get('teams/{team}/{player}', function (Foo $foo, $team, $player) {
                return $foo->foo.':'.$team.':'.$player;
            });
        });
        
        $request = $this->frontendRequest('GET', '/teams/dortmund/calvin');
        $this->assertResponse('foo:dortmund:calvin', $request);
    }
    
    /** @test */
    public function dependencies_for_controller_actions_are_resolved()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', ControllerWithDependencies::class.'@handle');
        });
        
        $request = $this->frontendRequest('GET', 'foo');
        $this->assertResponse('foo_controller', $request);
    }
    
    /** @test */
    public function method_dependencies_for_controller_actions_are_resolved()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', ControllerWithDependencies::class.'@withMethodDependency');
        });
        
        $request = $this->frontendRequest('GET', 'foo');
        $this->assertResponse('foobar_controller', $request);
    }
    
    /** @test */
    public function additional_dependencies_are_passed_to_the_controller_method_before_route_segments()
    {
        $this->createRoutes(function () {
            $this->router->get('teams/{team}/{player}', TeamsController::class.'@withDependencies');
        });
        
        $request = $this->frontendRequest('GET', '/teams/dortmund/calvin');
        $this->assertResponse('foo:bar:dortmund:calvin', $request);
    }
    
    /** @test */
    public function arguments_from_conditions_are_passed_after_class_dependencies()
    {
        $this->createRoutes(function () {
            $this->router
                ->get('*', TeamsController::class.'@withConditions')
                ->where(function ($baz, $biz) {
                    return $baz === 'baz' && $biz === 'biz';
                }, 'baz', 'biz');
        });
        
        $request = $this->frontendRequest('GET', '/teams/dortmund/calvin');
        $this->assertResponse('foo:bar:baz:biz', $request);
    }
    
    /** @test */
    public function closure_actions_also_get_all_dependencies_injected_in_the_correct_order()
    {
        $this->createRoutes(function () {
            $this->router
                ->get()
                ->where(function ($baz, $biz) {
                    return $baz === 'baz' && $biz === 'biz';
                }, 'baz', 'biz')
                ->handle(
                    function (Request $request, Foo $foo, Bar $bar, $baz, $biz) {
                        return $foo->foo.':'.$bar->bar.':'.$baz.':'.$biz;
                    }
                );
        });
        
        $request = $this->frontendRequest('GET', '/teams/dortmund/calvin');
        $this->assertResponse('foo:bar:baz:biz', $request);
    }
    
}