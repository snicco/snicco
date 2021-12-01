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
            $this->router->view('/foo', 'welcome.wordpress');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->assertResponse('VIEW:welcome.wordpress,CONTEXT:[]', $request);
        
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
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->assertResponse('VIEW:welcome.wordpress,CONTEXT:[foo=>bar,bar=>baz]', $request);
        
        HeaderStack::assertHas('Referer', 'foobar');
        HeaderStack::assertHasStatusCode(201);
    }
    
}

