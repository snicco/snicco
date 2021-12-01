<?php

namespace Tests\Core\integration\Http;

use Snicco\Http\ResponseFactory;
use Snicco\EventDispatcher\Events\DoShutdown;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\EventDispatcher\Events\ResponseSent;

class ResponsePostProcessorTest extends FrameworkTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->bootApp();
        $this->fakeEvents(DoShutdown::class);
    }
    
    /** @test */
    public function the_script_is_always_terminated_after_a_redirect_response()
    {
        $response = $this->responseFactory()->redirect()->to('foo');
        
        $this->dispatcher->dispatch(new ResponseSent($response, $this->request));
        
        $this->dispatcher->assertDispatched(function (DoShutdown $event) {
            return $event->do_shutdown === true;
        });
    }
    
    /** @test */
    public function the_script_is_terminated_after_request_to_the_wp_frontend()
    {
        $response = $this->responseFactory()->html('foo');
        
        $this->dispatcher->dispatch(
            new ResponseSent($response, $this->frontendRequest('GET', 'foo'))
        );
        
        $this->dispatcher->assertDispatched(function (DoShutdown $event) {
            return $event->do_shutdown === true;
        });
    }
    
    /** @test */
    public function the_script_is_terminated_after_request_to_the_wp_ajax_endpoint()
    {
        $response = $this->responseFactory()->json(['foo' => 'bar']);
        
        $this->dispatcher->dispatch(
            new ResponseSent($response, $this->adminAjaxRequest('GET', 'foo'))
        );
        
        $this->dispatcher->assertDispatched(function (DoShutdown $event) {
            return $event->do_shutdown === true;
        });
    }
    
    /** @test */
    public function the_script_is_not_terminated_after_requests_to_the_wp_admin_area()
    {
        $response = $this->responseFactory()->html('foo_admin_page');
        
        $this->dispatcher->dispatch(
            new ResponseSent($response, $this->adminRequest('GET', 'foo_page'))
        );
        
        $this->dispatcher->assertNotDispatched(DoShutdown::class);
    }
    
    /** @test */
    public function the_script_is_shut_down_for_requests_in_wp_admin_if_its_a_client_error()
    {
        $response = $this->responseFactory()->html('No permissions')->withStatus(403);
        
        $this->dispatcher->dispatch(
            new ResponseSent($response, $this->adminRequest('GET', 'foo_page'))
        );
        
        $this->dispatcher->assertDispatched(function (DoShutdown $event) {
            return $event->do_shutdown === true;
        });
    }
    
    /** @test */
    public function the_script_is_shut_down_for_requests_in_wp_admin_if_its_a_server_error()
    {
        $response = $this->responseFactory()->html('Server Error')->withStatus(500);
        
        $this->dispatcher->dispatch(
            new ResponseSent($response, $this->adminRequest('GET', 'foo_page'))
        );
        
        $this->dispatcher->assertDispatched(function (DoShutdown $event) {
            return $event->do_shutdown === true;
        });
    }
    
    private function responseFactory() :ResponseFactory
    {
        return $this->app->resolve(ResponseFactory::class);
    }
    
}