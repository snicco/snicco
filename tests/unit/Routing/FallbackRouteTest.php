<?php

declare(strict_types=1);

namespace Tests\unit\Routing;

use Mockery;
use Tests\UnitTest;
use Snicco\Support\WP;
use Snicco\Events\Event;
use Snicco\Routing\Router;
use Snicco\View\ViewFactory;
use Contracts\ContainerAdapter;
use Snicco\Http\ResponseFactory;
use Snicco\Routing\UrlGenerator;
use Tests\stubs\TestViewFactory;
use Tests\fixtures\Conditions\IsPost;
use Tests\helpers\CreateTestSubjects;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreateRouteCollection;
use Tests\helpers\CreateDefaultWpApiMocks;

class FallbackRouteTest extends UnitTest
{
    
    use CreateTestSubjects;
    use CreateDefaultWpApiMocks;
    use CreateUrlGenerator;
    use CreateRouteCollection;
    
    private Router           $router;
    private ContainerAdapter $container;
    
    /** @test */
    public function users_can_create_a_custom_fallback_route_that_gets_run_if_no_route_matched_at_all()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get()->where(IsPost::class, false)
                         ->handle(function () {
                
                             return 'FOO';
                
                         });
            
            $this->router->fallback(function () {
                return 'FOO_FALLBACK';
            });
            
        });
        
        $request = $this->webRequest('GET', 'post1');
        $this->runAndAssertOutput('FOO_FALLBACK', $request);
        
    }
    
    protected function beforeTestRun()
    {
        
        $this->container = $this->createContainer();
        $this->routes = $this->newCachedRouteCollection();
        $this->container->instance(UrlGenerator::class, $this->newUrlGenerator());
        $this->container->instance(ViewFactory::class, new TestViewFactory());
        $this->container->instance(ResponseFactory::class, $this->createResponseFactory());
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