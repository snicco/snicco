<?php

declare(strict_types=1);

namespace Tests\unit\Routing;

use Mockery;
use Tests\UnitTest;
use Snicco\Support\WP;
use Snicco\Events\Event;
use Snicco\Routing\Router;
use Snicco\View\ViewFactory;
use Tests\stubs\HeaderStack;
use Contracts\ContainerAdapter;
use Snicco\Http\ResponseFactory;
use Snicco\Routing\UrlGenerator;
use Tests\stubs\TestViewFactory;
use Tests\helpers\CreateTestSubjects;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreateDefaultWpApiMocks;

class ViewRoutesTest extends UnitTest
{
    
    use CreateTestSubjects;
    use CreateDefaultWpApiMocks;
    use CreateUrlGenerator;
    
    private ContainerAdapter $container;
    private Router           $router;
    
    /** @test */
    public function view_routes_work()
    {
        
        $this->createRoutes(function () {
            
            $this->router->view('/foo', 'welcome.wordpress');
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        
        $this->runAndAssertOutput('VIEW:welcome.wordpress,CONTEXT:[]', $request);
        
        HeaderStack::assertHas('Content-Type', 'text/html; charset=UTF-8');
        HeaderStack::assertHasStatusCode(200);
        
    }
    
    /** @test */
    public function the_default_values_can_be_customized_for_view_routes()
    {
        
        $this->createRoutes(function () {
            
            $this->router->view('/foo', 'welcome.wordpress', [
                'foo' => 'bar',
                'bar' => 'baz',
            ], 201, ['Referer' => 'foobar']);
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        
        $this->runAndAssertOutput('VIEW:welcome.wordpress,CONTEXT:[foo=>bar,bar=>baz]', $request);
        
        HeaderStack::assertHas('Referer', 'foobar');
        HeaderStack::assertHasStatusCode(201);
        
    }
    
    protected function beforeTestRun()
    {
        
        $this->container = $this->createContainer();
        $this->routes = $this->newRouteCollection();
        $this->container->instance(UrlGenerator::class, $this->newUrlGenerator());
        $this->container->instance(ViewFactory::class, new TestViewFactory());
        $this->container->instance(ResponseFactory::class, $this->createResponseFactory());
        Event::make($this->container);
        Event::fake();
        WP::setFacadeContainer($this->container);
        HeaderStack::reset();
        $this->createBindingsForViewController();
        
    }
    
    private function createBindingsForViewController()
    {
        
        $this->container->instance(HttpResponseFactory::class, $this->createResponseFactory());
        
    }
    
    protected function beforeTearDown()
    {
        
        Event::setInstance(null);
        Mockery::close();
        WP::reset();
        HeaderStack::reset();
        
    }
    
}

