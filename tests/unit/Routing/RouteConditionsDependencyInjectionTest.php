<?php

declare(strict_types=1);

namespace Tests\unit\Routing;

use Mockery;
use Tests\UnitTest;
use Snicco\Support\WP;
use Snicco\Events\Event;
use Snicco\Routing\Router;
use Contracts\ContainerAdapter;
use Tests\helpers\CreatesWpUrls;
use Tests\helpers\CreateTestSubjects;
use Tests\helpers\CreateDefaultWpApiMocks;
use Tests\fixtures\Conditions\ConditionWithDependency;

class RouteConditionsDependencyInjectionTest extends UnitTest
{
    
    use CreateTestSubjects;
    use CreatesWpUrls;
    use CreateDefaultWpApiMocks;
    
    private Router           $router;
    private ContainerAdapter $container;
    
    /** @test */
    public function a_condition_gets_dependencies_injected_after_the_passed_arguments()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/foo', function () {
                
                return 'foo';
                
            })->where(ConditionWithDependency::class, true);
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
    }
    
    protected function beforeTestRun()
    {
        
        $this->container = $this->createContainer();
        $this->routes = $this->newRouteCollection();
        Event::make($this->container);
        Event::fake();
        WP::setFacadeContainer($this->container);
        
    }
    
    protected function beforeTearDown()
    {
        
        Mockery::close();
        Event::setInstance(null);
        WP::reset();
        
    }
    
}

