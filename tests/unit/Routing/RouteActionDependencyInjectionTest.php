<?php

declare(strict_types=1);

namespace Tests\unit\Routing;

use Mockery;
use Tests\UnitTest;
use Snicco\Support\WP;
use Snicco\Events\Event;
use Snicco\Routing\Router;
use Snicco\Http\Psr7\Request;
use Contracts\ContainerAdapter;
use Tests\helpers\CreateTestSubjects;
use Tests\fixtures\TestDependencies\Bar;
use Tests\fixtures\TestDependencies\Foo;
use Tests\helpers\CreateDefaultWpApiMocks;
use Tests\fixtures\Controllers\Web\TeamsController;
use Tests\fixtures\Controllers\Web\ControllerWithDependencies;

class RouteActionDependencyInjectionTest extends UnitTest
{
    
    use CreateTestSubjects;
    use CreateDefaultWpApiMocks;
    
    private ContainerAdapter $container;
    private Router           $router;
    
    /** @test */
    public function dependencies_for_controller_actions_are_resolved()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/foo', ControllerWithDependencies::class.'@handle');
            
        });
        
        $request = $this->webRequest('GET', 'foo');
        $this->runAndAssertOutput('foo_controller', $request);
        
    }
    
    /** @test */
    public function method_dependencies_for_controller_actions_are_resolved()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/foo', ControllerWithDependencies::class.'@withMethodDependency');
            
        });
        
        $request = $this->webRequest('GET', 'foo');
        $this->runAndAssertOutput('foobar_controller', $request);
        
    }
    
    /** @test */
    public function route_segment_values_are_passed_to_the_controller_method()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('teams/{team}/{player}', TeamsController::class.'@handle');
            
        });
        
        $request = $this->webRequest('GET', '/teams/dortmund/calvin');
        $this->runAndAssertOutput('dortmund:calvin', $request);
        
    }
    
    /** @test */
    public function additional_dependencies_are_passed_to_the_controller_method_after_route_segments()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('teams/{team}/{player}', TeamsController::class.'@withDependencies');
            
        });
        
        $request = $this->webRequest('GET', '/teams/dortmund/calvin');
        $this->runAndAssertOutput('dortmund:calvin:foo:bar', $request);
        
    }
    
    /** @test */
    public function arguments_from_conditions_are_passed_after_route_segments_and_before_dependencies()
    {
        
        $this->createRoutes(function () {
            
            $this->router
                ->get('teams/{team}/{player}', TeamsController::class.'@withConditions')
                ->where(function ($baz, $biz) {
                    
                    return $baz === 'baz' && $biz === 'biz';
                    
                }, 'baz', 'biz');
            
        });
        
        $request = $this->webRequest('GET', '/teams/dortmund/calvin');
        $this->runAndAssertOutput('dortmund:calvin:baz:biz:foo:bar', $request);
        
    }
    
    /** @test */
    public function closure_actions_also_get_all_dependencies_injected_in_the_correct_order()
    {
        
        $this->createRoutes(function () {
            
            $this->router
                ->get('teams/{team}/{player}')
                ->where(function ($baz, $biz) {
                    
                    return $baz === 'baz' && $biz === 'biz';
                    
                }, 'baz', 'biz')
                ->handle(
                    function (Request $request, $team, $player, $baz, $biz, Foo $foo, Bar $bar) {
                        
                        return $team.':'.$player.':'.$baz.':'.$biz.':'.$foo->foo.':'.$bar->bar;
                        
                    }
                );
            
        });
        
        $request = $this->webRequest('GET', '/teams/dortmund/calvin');
        $this->runAndAssertOutput('dortmund:calvin:baz:biz:foo:bar', $request);
        
    }
    
    protected function beforeTestRun()
    {
        
        $this->container = $this->createContainer();
        $this->routes = $this->newCachedRouteCollection();
        Event::make($this->container);
        Event::fake();
        WP::setFacadeContainer($this->container);
        
    }
    
    protected function beforeTearDown()
    {
        
        Event::setInstance(null);
        Mockery::close();
        WP::reset();
        
    }
    
}