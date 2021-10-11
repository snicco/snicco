<?php

declare(strict_types=1);

namespace Tests\integration\Session;

use Tests\FrameworkTestCase;
use Snicco\Session\SessionManager;
use Snicco\Session\SessionServiceProvider;

class SessionSavingTest extends FrameworkTestCase
{
    
    private SessionManager $manager;
    
    public function setUp() :void
    {
        
        $this->afterApplicationCreated(function () {
            
            $this->withSessionCookie();
            $this->bootApp();
            $this->withRequest($this->frontendRequest());
            
        });
        $this->afterApplicationBooted(function () {
            $this->manager = $this->app->resolve(SessionManager::class);
        });
        
        parent::setUp();
        
        remove_action('shutdown', 'wp_ob_end_flush_all', 1);
        
    }
    
    /** @test */
    public function a_session_is_saved_on_the_shutdown_hook_if_it_was_not_saved_in_the_request_cycle()
    {
        
        $this->manager->start($this->request, 1);
        
        $this->session->put('foo', 'bar');
        
        $this->assertDriverNotHas('foo');
        
        do_action('shutdown');
        
        $this->assertDriverHas('bar', 'foo', $this->session->getId());
        
    }
    
    /** @test */
    public function a_session_is_not_saved_on_shutdown_if_already_saved()
    {
        
        $this->manager->start($this->request, 1);
        
        $this->session->put('foo', 'bar');
        
        $this->manager->save();
        
        $this->assertDriverHas('bar', 'foo', $this->session->getId());
        
        $this->assertSame(time(), $this->session->lastActivity());
        
        $this->travelIntoFuture(10);
        
        do_action('shutdown');
        
        $this->assertSame(time(), $this->session->lastActivity());
        
    }
    
    protected function packageProviders() :array
    {
        return [
            SessionServiceProvider::class,
        ];
    }
    
}