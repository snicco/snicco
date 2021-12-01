<?php

declare(strict_types=1);

namespace Tests\Session\unit\Middleware;

use Mockery;
use Snicco\Support\WP;
use Snicco\Http\Cookies;
use Snicco\Support\Carbon;
use Snicco\Session\Session;
use Snicco\Routing\Pipeline;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Support\Repository;
use Tests\Core\MiddlewareTestCase;
use Snicco\Session\SessionManager;
use Snicco\Factories\MiddlewareFactory;
use Snicco\Middleware\Core\ShareCookies;
use Snicco\Session\Drivers\ArraySessionDriver;
use Snicco\ExceptionHandling\NullExceptionHandler;
use Tests\Codeception\shared\helpers\CreateContainer;
use Snicco\Session\Middleware\StartSessionMiddleware;
use Snicco\EventDispatcher\Dispatcher\FakeDispatcher;
use Tests\Codeception\shared\helpers\HashesSessionIds;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Tests\Codeception\shared\helpers\CreateDefaultWpApiMocks;

class StartSessionMiddlewareTest extends MiddlewareTestCase
{
    
    use HashesSessionIds;
    use CreateDefaultWpApiMocks;
    use CreateContainer;
    
    private Cookies $cookies;
    
    private array   $config = [
        'lifetime' => 1,
        'lottery' => [0, 100],
        'cookie' => 'test_session',
        'domain' => null,
        'same_site' => 'lax',
        'http_only' => true,
        'secure' => true,
        'path' => '/',
        'rotate' => 3600,
    ];
    private Request $request;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        WP::shouldReceive('userId')->andReturn(1)->byDefault();
        
        $this->request = $this->frontendRequest('GET', '/foo')
                              ->withAttribute(
                                  'cookies',
                                  new Repository([
                                      'test_session' => $this->getSessionId(),
                                  ])
                              );
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        WP::reset();
        Mockery::close();
    }
    
    /** @test */
    public function the_request_has_access_to_the_session()
    {
        $response = $this->runMiddleware($this->newMiddleware(), $this->request);
        $response->assertNextMiddlewareCalled();
        
        $this->assertInstanceOf(Session::class, $this->receivedRequest()->session());
    }
    
    /** @test */
    public function the_correct_session_gets_created_from_the_cookie()
    {
        $handler = new ArraySessionDriver(10);
        $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
        $handler->write($this->anotherSessionId(), serialize(['foo' => 'baz']));
        
        $session = $this->newSession($handler);
        
        $middleware = $this->newMiddleware($session);
        $this->runMiddleware($middleware, $this->request);
        
        $this->assertSame('bar', $this->receivedRequest()->session()->get('foo'));
    }
    
    /** @test */
    public function a_session_without_matching_session_cookie_in_the_driver_will_create_a_new_session()
    {
        $handler = new ArraySessionDriver(10);
        $handler->write($this->hash($this->anotherSessionId()), serialize(['foo' => 'bar']));
        
        $session = $this->newSession($handler);
        
        $middleware = $this->newMiddleware($session);
        $this->runMiddleware($middleware, $this->request);
        
        $this->assertArrayNotHasKey('foo', $this->receivedRequest()->session()->all());
    }
    
    /** @test */
    public function the_previous_url_is_saved_to_the_session_after_creating_the_response()
    {
        $handler = new ArraySessionDriver(10);
        $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
        
        $store = $this->newSession($handler);
        
        $middleware = $this->newMiddleware($store);
        
        $response = $this->runMiddleware($middleware, $this->request);
        
        $persisted_url = unserialize($handler->read($this->hashedSessionId()))['_url']['previous'];
        
        $this->assertSame('https://example.com/foo', $persisted_url);
    }
    
    /** @test */
    public function values_added_to_the_session_are_saved()
    {
        $this->setNextMiddlewareResponse(function (Response $response, Request $request) {
            $request->session()->put('name', 'calvin');
            return $response;
        });
        
        $handler = new ArraySessionDriver(10);
        $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
        
        $store = $this->newSession($handler);
        $middleware = $this->newMiddleware($store);
        $response = $this->runMiddleware($middleware, $this->request);
        
        $persisted_data = unserialize($handler->read($this->hashedSessionId()));
        
        $this->assertSame('calvin', $persisted_data['name']);
    }
    
    /** @test */
    public function garbage_collection_works()
    {
        $handler = new ArraySessionDriver(10);
        $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
        $this->assertNotSame('', unserialize($handler->read($this->hashedSessionId())));
        
        Carbon::setTestNow(Carbon::now()->addSeconds(120));
        
        $store = $this->newSession($handler);
        
        $middleware = $this->newMiddleware($store, [100, 100]);
        $response = $this->runMiddleware($middleware, $this->request);
        
        $this->assertSame('', $handler->read($this->hashedSessionId()));
        
        Carbon::setTestNow();
    }
    
    /** @test */
    public function the_session_cookie_is_added_to_the_response()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(0));
        $c = $this->createContainer();
        
        $pipeline = new Pipeline(
            new MiddlewareFactory($c),
            new NullExceptionHandler(),
            $this->response_factory
        );
        
        $request = $this->frontendRequest('GET', 'foo');
        
        $session = $this->newSession();
        
        $response = $pipeline
            ->send($request)
            ->through([ShareCookies::class, $this->newMiddleware($session)])
            ->then(function () {
                return $this->response_factory->make();
            });
        
        $cookies = $response->getHeaderLine('Set-Cookie');
        
        $this->assertStringStartsWith("test_session={$session->getId()}", $cookies);
        $this->assertStringContainsString('path=/', $cookies);
        $this->assertStringContainsString('SameSite=Lax', $cookies);
        $this->assertStringContainsString('expires=Thu, 01-Jan-1970 00:00:01 UTC', $cookies);
        $this->assertStringContainsString('HttpOnly', $cookies);
        $this->assertStringContainsString('secure', $cookies);
        $this->assertStringNotContainsString('domain', $cookies);
        
        Carbon::setTestNow();
    }
    
    /** @test */
    public function providing_a_cookie_that_does_not_have_an_active_session_regenerates_the_id()
    {
        // This works because the session driver has an active session for the provided cookie value.
        $driver = new ArraySessionDriver(10);
        $driver->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
        $session = $this->newSession($driver);
        
        $middleware = $this->newMiddleware($session);
        $this->runMiddleware($middleware, $this->request);
        
        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame($session->getId(), $this->getSessionId());
        
        // Now we reject the session id.
        $driver->destroy($this->hashedSessionId());
        $session = $this->newSession($driver);
        
        $middleware = $this->newMiddleware($session);
        $this->runMiddleware($middleware, $this->request);
        
        $this->assertNotSame($session->getId(), $this->getSessionId());
    }
    
    /** @test */
    public function the_user_id_is_set_on_the_session()
    {
        $response = $this->runMiddleware($this->newMiddleware(), $this->request->withUserId(99));
        
        $session = $this->receivedRequest()->session();
        
        $this->assertSame(99, $session->userId());
    }
    
    private function newMiddleware(
        Session $session = null, $gc_collection = [
        0,
        100,
    ]
    ) :StartSessionMiddleware {
        $session = $session ?? $this->newSession();
        
        $config = $this->config;
        
        $config['lottery'] = $gc_collection;
        
        return new StartSessionMiddleware(
            new SessionManager($config, $session, new FakeDispatcher(new EventDispatcher()))
        );
    }
    
    private function newSession($handler = null) :Session
    {
        $handler = $handler ?? new ArraySessionDriver(10);
        
        return new Session($handler);
    }
    
}