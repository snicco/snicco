<?php

declare(strict_types=1);

namespace Tests\Session\integration;

use Snicco\Session\SessionManager;
use Snicco\Session\SessionServiceProvider;
use Tests\Codeception\shared\FrameworkTestCase;

class SessionSavingTest extends FrameworkTestCase
{
    
    private SessionManager $manager;
    
    public function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->withSessionCookie();
            $this->bootApp();
            $this->withRequest($this->frontendRequest('GET', '/delegate'));
        });
        $this->afterApplicationBooted(function () {
            $this->manager = $this->app->resolve(SessionManager::class);
        });
        
        parent::setUp();
        
        remove_action('shutdown', 'wp_ob_end_flush_all', 1);
    }
    
    /** @test */
    public function a_session_is_saved_on_the_shutdown_hook_for_delegated_responses()
    {
        $this->withSessionCookie();
        $this->get('delegate');
        $this->assertDriverNotHas('foo');
        
        $this->session->put('foo', 'bar');
        
        do_action('shutdown');
        
        $this->assertDriverHas('bar', 'foo', $this->session->getId());
    }
    
    /** @test */
    public function a_session_is_not_saved_on_shutdown_if_already_saved()
    {
        $this->withSessionCookie();
        
        $this->assertDriverNotHas('foo');
        
        $this->session->put('foo', 'bar');
        $response = $this->get('/foo');
        $response->assertOk();
        
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