<?php

declare(strict_types=1);

namespace Tests\Auth\unit\Middleware;

use Mockery as m;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Request;
use Tests\Core\MiddlewareTestCase;
use Snicco\Auth\AuthSessionManager;
use Snicco\Testing\Concerns\TravelsTime;
use Snicco\Session\Drivers\ArraySessionDriver;
use Snicco\Auth\Middleware\AuthenticateSession;
use Tests\Codeception\shared\helpers\HashesSessionIds;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;

class AuthenticateSessionTest extends MiddlewareTestCase
{
    
    use TravelsTime;
    use HashesSessionIds;
    
    private AuthSessionManager $session_manager;
    private Request            $request;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->backToPresent();
        $this->session_manager = m::mock(AuthSessionManager::class);
        $this->request = $this->frontendRequest();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        m::close();
    }
    
    /** @test */
    public function auth_confirmation_session_data_is_forgotten_for_idle_sessions()
    {
        $session = $this->newSession();
        $session->put('auth.confirm.foo', 'bar');
        
        $this->session_manager->shouldReceive('idleTimeout')->andReturn(300);
        $middleware = new AuthenticateSession($this->session_manager, new EventDispatcher());
        
        $response = $this->runMiddleware($middleware, $this->request->withSession($session));
        
        $this->assertFalse($session->has('auth.confirm'));
        
        $response->assertOk();
        $response->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function auth_confirmation_session_data_is_not_forgotten_for_active_sessions()
    {
        $session = $this->newSession();
        $session->put('auth.confirm.foo', 'bar');
        $session->setLastActivity(time());
        
        $this->session_manager->shouldReceive('idleTimeout')->andReturn(300);
        $middleware = new AuthenticateSession($this->session_manager, new EventDispatcher());
        
        $response = $this->runMiddleware($middleware, $this->request->withSession($session));
        
        $this->assertTrue($session->has('auth.confirm'));
        $response->assertOk();
        $response->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function custom_keys_can_be_removed_from_the_session_on_idle_timeout()
    {
        $session = $this->newSession();
        $session->put('auth.confirm.foo', 'bar');
        $session->put('foo.bar.baz', 'biz');
        $session->put('foo.biz.bam', 'boo');
        $session->put('biz', 'boo');
        
        $this->session_manager->shouldReceive('idleTimeout')->andReturn(300);
        $middleware = new AuthenticateSession(
            $this->session_manager,
            new EventDispatcher(),
            ['foo.bar', 'biz']
        );
        
        $response = $this->runMiddleware($middleware, $this->request->withSession($session));
        
        $this->assertFalse($session->has('auth.confirm'));
        $this->assertFalse($session->has('biz'));
        $this->assertFalse($session->has('foo.bar'));
        $this->assertTrue($session->has('foo.biz'));
        
        $response->assertOk();
        $response->assertNextMiddlewareCalled();
    }
    
    private function newSession() :Session
    {
        return new Session(new ArraySessionDriver(10));
    }
    
}