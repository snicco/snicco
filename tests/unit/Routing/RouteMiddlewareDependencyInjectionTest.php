<?php

declare(strict_types=1);

namespace Tests\unit\Routing;

use Mockery;
use Tests\UnitTest;
use Snicco\Support\WP;
use Snicco\Events\Event;
use Snicco\Routing\Router;
use Snicco\View\ViewFactory;
use Snicco\Http\Psr7\Request;
use Contracts\ContainerAdapter;
use Snicco\Contracts\MagicLink;
use Snicco\Routing\UrlGenerator;
use Tests\stubs\TestViewFactory;
use Tests\helpers\CreateTestSubjects;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreateDefaultWpApiMocks;
use Snicco\Testing\TestDoubles\TestMagicLink;
use Tests\fixtures\Middleware\MiddlewareWithDependencies;
use Tests\fixtures\Controllers\Admin\AdminControllerWithMiddleware;

class RouteMiddlewareDependencyInjectionTest extends UnitTest
{
    
    use CreateTestSubjects;
    use CreateDefaultWpApiMocks;
    use CreateUrlGenerator;
    
    private ContainerAdapter $container;
    private Router           $router;
    
    /** @test */
    public function middleware_is_resolved_from_the_service_container()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/foo', function (Request $request) {
                
                return $request->body;
                
            })->middleware(MiddlewareWithDependencies::class);
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foobar', $request);
        
    }
    
    /** @test */
    public function controller_middleware_is_resolved_from_the_service_container()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/foo', AdminControllerWithMiddleware::class.'@handle');
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foobarbaz:controller_with_middleware', $request);
        
    }
    
    /** @test */
    public function after_controller_middleware_got_resolved_the_controller_is_not_instantiated_again_when_handling_the_request()
    {
        
        $GLOBALS['test'][AdminControllerWithMiddleware::constructed_times] = 0;
        
        $this->createRoutes(function () {
            
            $this->router->get('/foo', AdminControllerWithMiddleware::class.'@handle');
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foobarbaz:controller_with_middleware', $request);
        
        $this->assertRouteActionConstructedTimes(1, AdminControllerWithMiddleware::class);
        
    }
    
    protected function beforeTestRun()
    {
        
        $this->container = $this->createContainer();
        $this->routes = $this->newCachedRouteCollection();
        $this->container->instance(UrlGenerator::class, $this->newUrlGenerator());
        $this->container->instance(MagicLink::class, new TestMagicLink());
        $this->container->instance(ViewFactory::class, new TestViewFactory());
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
    
    private function assertRouteActionConstructedTimes(int $times, $class)
    {
        
        $actual = $GLOBALS['test'][$class::constructed_times] ?? 0;
        
        $this->assertSame(
            $times,
            $actual,
            'RouteAction ['
            .$class
            .'] was supposed to run: '
            .$times
            .' times. Actual: '
            .$GLOBALS['test'][$class::constructed_times]
        );
        
    }
    
}