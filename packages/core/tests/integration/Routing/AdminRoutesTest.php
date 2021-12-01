<?php

namespace Tests\Core\integration\Routing;

use Mockery;
use Snicco\Http\Psr7\Response;
use Snicco\Http\ResponseEmitter;
use Snicco\Http\ResponsePreparation;
use Snicco\EventDispatcher\Events\AdminInit;
use Tests\Codeception\shared\FrameworkTestCase;

class AdminRoutesTest extends FrameworkTestCase
{
    
    /** @test */
    public function a_response_is_not_sent_directly_but_is_delayed_until_the_all_admin_notices_hook()
    {
        $this->withoutHooks();
        $this->withRequest($this->adminRequest('GET', 'foo'));
        $this->bootApp();
        
        $level_before = ob_get_level();
        do_action('admin_init');
        do_action("load-toplevel_page_foo");
        
        $this->assertNoResponse();
        $this->assertSame('', ob_get_contents());
        
        do_action('all_admin_notices');
        
        $this->sentResponse()->assertSee('FOO_ADMIN');
        $level_after = ob_get_level();
        
        $this->assertSame($level_before, $level_after);
    }
    
    /** @test */
    public function regular_admin_content_is_buffered()
    {
        $this->withRequest($this->adminRequest('GET', 'foo'));
        $this->bootApp();
        
        $emitter = Mockery::mock(ResponseEmitter::class);
        $emitter->shouldReceive('prepare')->once()->andReturnArg(0);
        $emitter->shouldReceive('emit')
                ->once()
                ->andReturnUsing(function (Response $response) {
                    echo $response->getBody();
                });
        
        $this->swap(ResponseEmitter::class, $emitter);
        
        $level_before = ob_get_level();
        $this->dispatcher->dispatch(new AdminInit($this->request));
        
        // Our menu page gets rendered in the correct spot
        $this->expectOutputString('Topmenu-SidebarFOO_ADMIN');
        
        // On a normal WordPress installation no output should ever be sent before the load-xxx hook.
        do_action('load-toplevel_page_foo');
        echo "Topmenu-";
        echo "Sidebar";
        
        $this->assertSame(
            $level_before + 1,
            ob_get_level(),
            "Output buffer was not turned on for a matching route."
        );
        
        // our response is not sent yet.
        $this->assertSame('Topmenu-Sidebar', ob_get_contents());
        
        // Here responses will be released.
        do_action('all_admin_notices');
        
        // Our own output buffers are flushed again.
        $level_after = ob_get_level();
        $this->assertSame(
            $level_before,
            $level_after,
            "Output buffers not reset to original state."
        );
    }
    
    /** @test */
    public function no_content_length_header_is_added_if_no_route_matches()
    {
        $this->withRequest($this->adminRequest('GET', 'bogus'));
        $this->bootApp();
        
        $this->dispatcher->dispatch(new AdminInit($this->request));
        
        // On a normal WordPress installation no output should ever be sent before the load-xxx hook.
        do_action('load-toplevel_page_bogus');
        
        // Here is our response released.
        do_action('all_admin_notices');
        
        $this->sentResponse()
             ->assertDelegatedToWordPress()
             ->assertHeaderMissing('content-length');
    }
    
    /** @test */
    public function no_output_buffers_are_started_if_we_dont_have_a_matching_admin_route()
    {
        $this->withRequest($this->adminRequest('GET', 'bogus'));
        $this->bootApp();
        $this->swap(
            ResponseEmitter::class,
            new ResponseEmitter($this->app[ResponsePreparation::class])
        );
        
        $this->expectOutputString('TopmenuSidebar');
        
        $level_before = ob_get_level();
        $this->dispatcher->dispatch(new AdminInit($this->request));
        
        // On a normal WordPress installation no output should ever be sent before the load-xxx hook.
        do_action('load-toplevel_page_bogus');
        echo 'Topmenu';
        echo 'Sidebar';
        
        // Here responses would be released.
        do_action('all_admin_notices');
        
        $this->assertSame(
            $level_before,
            ob_get_level(),
            'Output buffering was turned on for non matching admin route.'
        );
    }
    
    /** @test */
    public function response_are_returned_immediately_if_its_a_redirect()
    {
        $this->withRequest($this->adminRequest('GET', 'redirect'));
        $this->bootApp();
        
        $level_before = ob_get_level();
        $this->dispatcher->dispatch(new AdminInit($this->request));
        
        do_action('load-toplevel_page_redirect');
        
        $this->sentResponse()->assertRedirect();
        
        $level_after = ob_get_level();
        $this->assertSame($level_before, $level_after);
    }
    
    /** @test */
    public function responses_are_returned_immediately_if_its_a_client_error()
    {
        $this->withRequest($this->adminRequest('GET', 'client-error'));
        $this->bootApp();
        
        $level_before = ob_get_level();
        $this->dispatcher->dispatch(new AdminInit($this->request));
        
        do_action('load-toplevel_page_client-error');
        
        $this->sentResponse()->assertStatus(403);
        
        $level_after = ob_get_level();
        $this->assertSame($level_before, $level_after);
    }
    
    /** @test */
    public function responses_are_returned_immediately_if_its_a_server_error()
    {
        $this->withRequest($this->adminRequest('GET', 'server-error'));
        $this->bootApp();
        
        $level_before = ob_get_level();
        $this->dispatcher->dispatch(new AdminInit($this->request));
        
        do_action('load-toplevel_page_server-error');
        
        $this->sentResponse()->assertStatus(500);
        
        $level_after = ob_get_level();
        $this->assertSame($level_before, $level_after);
    }
    
}