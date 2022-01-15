<?php

declare(strict_types=1);

namespace Tests\SessionBundle\unit\Middleware;

use Mockery as m;
use RuntimeException;
use DateTimeImmutable;
use Snicco\Session\Session;
use Snicco\SessionBundle\Keys;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\Psr7\Response;
use Snicco\Session\ValueObjects\SessionId;
use Snicco\Session\ValueObjects\SessionConfig;
use Snicco\Session\Drivers\ArraySessionDriver;
use Snicco\Session\Contracts\SessionInterface;
use Tests\HttpRouting\InternalMiddlewareTestCase;
use Snicco\SessionBundle\Middleware\StartSession;
use Tests\Codeception\shared\helpers\SessionHelpers;
use Snicco\Session\Contracts\MutableSessionInterface;
use Snicco\Session\Contracts\SessionManagerInterface;
use Snicco\Session\ValueObjects\SerializedSessionData;
use Snicco\Session\Contracts\ImmutableSessionInterface;

final class StartSessionTestInternal extends InternalMiddlewareTestCase
{
    
    use SessionHelpers;
    
    protected function setUp() :void
    {
        parent::setUp();
        m::getConfiguration()->allowMockingNonExistentMethods(false);
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        m::close();
    }
    
    /** @test */
    public function an_exception_is_thrown_if_the_request_path_does_not_match_the_cookie_path_in_the_config()
    {
        $mock = m::mock(SessionManagerInterface::class);
        $mock->shouldReceive('start')->andReturn(
            new Session(SessionId::createFresh(), [], new DateTimeImmutable())
        )->byDefault();
        
        try {
            $this->runMiddleware(
                new StartSession('/bar', $mock),
                $this->frontendRequest('GET', '/foo')
            );
            $this->fail('The session middleware should have thrown an exception.');
        } catch (RuntimeException $e) {
            $this->assertStringStartsWith(
                'The request path [/foo] is not compatible with your cookie path [/bar].',
                $e->getMessage()
            );
        }
        
        try {
            $this->runMiddleware(
                new StartSession('/foo/baz/', $mock),
                $this->frontendRequest('GET', '/foo/boom/bang')
            );
            $this->fail('The session middleware should have thrown an exception.');
        } catch (RuntimeException $e) {
            $this->assertStringStartsWith(
                'The request path [/foo/boom/bang] is not compatible with your cookie path [/foo/baz/].',
                $e->getMessage()
            );
        }
        
        try {
            $this->runMiddleware(
                new StartSession('/foo/', $mock),
                $this->frontendRequest('GET', '/foo')
            );
            $this->fail('The session middleware should have thrown an exception.');
        } catch (RuntimeException $e) {
            $this->assertStringStartsWith(
                'The request path [/foo] is not compatible with your cookie path [/foo/].',
                $e->getMessage()
            );
        }
        
        $this->runMiddleware(
            new StartSession('/foo', $mock),
            $this->frontendRequest('GET', '/foo/bar')
        )->assertNextMiddlewareCalled();
        
        $this->runMiddleware(
            new StartSession('/foo/baz/', $mock),
            $this->frontendRequest('GET', '/foo/baz/biz')
        )->assertNextMiddlewareCalled();
        
        $this->runMiddleware(
            new StartSession('/foo', $mock),
            $this->frontendRequest('GET', '/foo')
        )->assertNextMiddlewareCalled();
        
        $this->runMiddleware(
            new StartSession('/foo', $mock),
            $this->frontendRequest('GET', '/foo/')
        )->assertNextMiddlewareCalled();
        
        $this->runMiddleware(
            new StartSession('/foo/', $mock),
            $this->frontendRequest('GET', '/foo/')
        )->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function a_session_is_started_without_existing_cookie()
    {
        $middleware = $this->newMiddleware();
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->withNextMiddlewareResponse(function (Response $response, Request $request) {
            $session = $request->getAttribute(Keys::READ_SESSION);
            $this->assertInstanceOf(ImmutableSessionInterface::class, $session);
            return $response->withAddedHeader('X-Called', '1');
        });
        
        $response = $this->runMiddleware($middleware, $request);
        $response->assertNextMiddlewareCalled();
        $response->assertHeader('X-Called', '1');
    }
    
    /** @test */
    public function a_session_is_started_from_an_existing_cookie_id()
    {
        $id = SessionId::createFresh();
        $driver = new ArraySessionDriver();
        $driver->write($id->asHash(), SerializedSessionData::fromArray(['foo' => 'bar'], time()));
        
        $request = $this->frontendRequest('GET', '/foo')
                        ->withAddedHeader("Cookie", "my_cookie={$id->asString()}");
        
        $middleware = $this->newMiddleware('/foo', 'my_cookie', $driver);
        
        $this->withNextMiddlewareResponse(
            function (Response $response, Request $request) use ($id) {
                $session = $request->getAttribute(Keys::READ_SESSION);
                $this->assertInstanceOf(ImmutableSessionInterface::class, $session);
                $this->assertNotInstanceOf(Session::class, $session);
                $this->assertTrue($id->sameAs($session->id()));
                return $response->withAddedHeader('X-Called', '1');
            }
        );
        $response = $this->runMiddleware($middleware, $request);
        $response->assertNextMiddlewareCalled();
        $response->assertHeader('X-Called', '1');
    }
    
    /** @test */
    public function read_requests_only_have_read_access_to_the_session()
    {
        $middleware = $this->newMiddleware();
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->withNextMiddlewareResponse(function (Response $response, Request $request) {
            $write_session = $request->getAttribute(Keys::WRITE_SESSION);
            $this->assertNull($write_session);
            
            $read_session = $request->getAttribute(Keys::READ_SESSION);
            $this->assertInstanceOf(ImmutableSessionInterface::class, $read_session);
            
            return $response->withAddedHeader('X-Called', '1');
        });
        
        $response = $this->runMiddleware($middleware, $request);
        $response->assertNextMiddlewareCalled();
        $response->assertHeader('X-Called', '1');
    }
    
    /** @test */
    public function non_read_request_do_have_write_access_to_the_session()
    {
        $middleware = $this->newMiddleware();
        $request = $this->frontendRequest('POST', '/foo');
        
        $this->withNextMiddlewareResponse(function (Response $response, Request $request) {
            $write_session = $request->getAttribute(Keys::WRITE_SESSION);
            $this->assertInstanceOf(MutableSessionInterface::class, $write_session);
            $this->assertInstanceOf(SessionInterface::class, $write_session);
            
            $read_session = $request->getAttribute(Keys::READ_SESSION);
            $this->assertInstanceOf(ImmutableSessionInterface::class, $read_session);
            
            return $response->withAddedHeader('X-Called', '1');
        });
        
        $response = $this->runMiddleware($middleware, $request);
        $response->assertNextMiddlewareCalled();
        $response->assertHeader('X-Called', '1');
    }
    
    private function newMiddleware(string $cookie_path = '/', string $cookie_name = 'test_cookie', $driver = null) :StartSession
    {
        return new StartSession(
            $cookie_path, $this->getSessionManager(
            SessionConfig::fromDefaults($cookie_name),
            $driver
        )
        );
    }
    
}