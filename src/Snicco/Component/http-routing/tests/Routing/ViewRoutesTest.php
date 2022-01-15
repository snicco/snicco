<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use Snicco\Component\HttpRouting\Tests\RoutingTestCase;

class ViewRoutesTest extends RoutingTestCase
{
    
    /** @test */
    public function view_routes_work()
    {
        $this->routeConfigurator()->view(
            '/foo',
            SHARED_FIXTURES_DIR.'/views/welcome.wordpress.php'
        );
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $response = $this->runKernel($request);
        $response->assertSee('Welcome to Wordpress');
        $response->assertIsHtml();
        $response->assertOk();
        
        $this->assertSame('/foo', $this->generator->toRoute('view:welcome.wordpress.php'));
    }
    
    /** @test */
    public function the_default_values_can_be_customized_for_view_routes()
    {
        $this->routeConfigurator()->view(
            '/foo',
            SHARED_FIXTURES_DIR.'/views/view-with-context.php',
            [
                'world' => 'WORLD',
            ],
            201,
            ['Referer' => 'foobar']
        );
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $response = $this->runKernel($request);
        $response->assertSee('Hello WORLD');
        $response->assertIsHtml();
        $response->assertStatus(201);
        $response->assertHeader('referer', 'foobar');
        
        $this->assertSame('/foo', $this->generator->toRoute('view:view-with-context.php'));
    }
    
}

