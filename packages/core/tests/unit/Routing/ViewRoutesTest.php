<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\TestDoubles\HeaderStack;

class ViewRoutesTest extends RoutingTestCase
{
    
    /** @test */
    public function view_routes_work()
    {
        $this->createRoutes(function () {
            $this->router->view('/foo', SHARED_FIXTURES_DIR.'/views/welcome.wordpress.php');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->assertResponse('Welcome to Wordpress', $request);
        
        HeaderStack::assertHas('Content-Type', 'text/html; charset=UTF-8');
        HeaderStack::assertHasStatusCode(200);
    }
    
    /** @test */
    public function the_default_values_can_be_customized_for_view_routes()
    {
        $this->createRoutes(function () {
            $this->router->view('/foo', SHARED_FIXTURES_DIR.'/views/view-with-context.php', [
                'world' => 'WORLD',
            ], 201, ['Referer' => 'foobar']);
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->assertResponse('Hello WORLD', $request);
        
        HeaderStack::assertHas('Referer', 'foobar');
        HeaderStack::assertHasStatusCode(201);
    }
    
}

