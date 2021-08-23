<?php

namespace Tests\integration\Http;

use Snicco\Events\Event;
use Tests\FrameworkTestCase;
use Snicco\Events\DoShutdown;
use Snicco\Events\ResponseSent;
use Snicco\Http\ResponseFactory;
use Snicco\Http\ResponsePostProcessor;

class ResponsePostProcessorTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_script_is_always_terminated_after_a_redirect_response()
    {
        
        $response = $this->responseFactory()->redirect()->to('foo');
        
        $did_shutdown = false;
        Event::listen(DoShutdown::class, function () use (&$did_shutdown) {
            $did_shutdown = true;
        });
        
        ResponseSent::dispatch([$response, $this->request]);
        
        $this->assertTrue($did_shutdown);
        
    }
    
    /** @test */
    public function the_script_is_terminated_after_request_to_the_wp_frontend()
    {
        
        $response = $this->responseFactory()->html('foo');
        
        $did_shutdown = false;
        Event::listen(DoShutdown::class, function () use (&$did_shutdown) {
            $did_shutdown = true;
        });
        
        ResponseSent::dispatch([$response, $this->frontendRequest('GET', 'foo')]);
        
        $this->assertTrue($did_shutdown);
        
    }
    
    /** @test */
    public function the_script_is_terminated_after_request_to_the_wp_ajax_endpoint()
    {
        
        $response = $this->responseFactory()->json(['foo' => 'bar']);
        
        $did_shutdown = false;
        Event::listen(DoShutdown::class, function () use (&$did_shutdown) {
            $did_shutdown = true;
        });
        
        ResponseSent::dispatch([$response, $this->adminAjaxRequest('GET', 'foo')]);
        
        $this->assertTrue($did_shutdown);
        
    }
    
    /** @test */
    public function the_script_is_not_terminated_after_requests_to_the_wp_admin_area()
    {
        
        $response = $this->responseFactory()->html('foo_admin_page');
        
        $did_shutdown = false;
        Event::listen(DoShutdown::class, function () use (&$did_shutdown) {
            $did_shutdown = true;
        });
        
        ResponseSent::dispatch([$response, $this->adminRequest('GET', 'foo_page')]);
        
        $this->assertFalse($did_shutdown);
        
    }
    
    /** @test */
    public function the_script_is_shut_down_for_requests_in_wp_admin_if_its_a_client_error()
    {
        
        $response = $this->responseFactory()->html('No permissions')->withStatus(403);
        
        $did_shutdown = false;
        Event::listen(DoShutdown::class, function () use (&$did_shutdown) {
            $did_shutdown = true;
        });
        
        ResponseSent::dispatch([$response, $this->adminRequest('GET', 'foo_page')]);
        
        $this->assertTrue($did_shutdown);
        
    }
    
    /** @test */
    public function the_script_is_shut_down_for_requests_in_wp_admin_if_its_a_server_error()
    {
        
        $response = $this->responseFactory()->html('Server Error')->withStatus(500);
        
        $did_shutdown = false;
        Event::listen(DoShutdown::class, function () use (&$did_shutdown) {
            $did_shutdown = true;
        });
        
        ResponseSent::dispatch([$response, $this->adminRequest('GET', 'foo_page')]);
        
        $this->assertTrue($did_shutdown);
        
    }
    
    /** @test */
    public function script_termination_can_be_disabled_with_a_custom_filter()
    {
        
        $response = $this->responseFactory()->html('foo');
        
        $did_shutdown = false;
        
        // some user land filter
        add_filter(DoShutdown::class, function (DoShutdown $event) {
            
            return false;
            
        }, 5, 1);
        
        Event::listen(DoShutdown::class, function (bool $do_shutdown) use (&$did_shutdown) {
            
            if ($do_shutdown === false) {
                $did_shutdown = false;
            }
            else {
                $did_shutdown = true;
            }
            
        });
        
        ResponseSent::dispatch([$response, $this->frontendRequest('GET', 'foo')]);
        
        $this->assertFalse($did_shutdown);
        
    }
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->bootApp();
    }
    
    private function getPostProcessor()
    {
        return new ResponsePostProcessor(true);
    }
    
    private function responseFactory() :ResponseFactory
    {
        return $this->app->resolve(ResponseFactory::class);
    }
    
}