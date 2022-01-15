<?php

declare(strict_types=1);

namespace Tests\SessionBundle\unit\Middleware;

use RuntimeException;
use DateTimeImmutable;
use Snicco\Session\Session;
use Snicco\SessionBundle\Keys;
use Snicco\Session\ImmutableSession;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\Psr7\Response;
use Snicco\Session\ValueObjects\SessionId;
use Snicco\PimpleContainer\PimpleDIContainer;
use Snicco\Session\Drivers\ArraySessionDriver;
use Snicco\Session\ValueObjects\SessionConfig;
use Snicco\HttpRouting\Middleware\ShareCookies;
use Tests\HttpRouting\InternalMiddlewareTestCase;
use Snicco\SessionBundle\ImmutableSessionWrapper;
use Tests\Codeception\shared\helpers\SessionHelpers;
use Snicco\Session\Contracts\MutableSessionInterface;
use Snicco\SessionBundle\Middleware\HandleStatefulRequest;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Snicco\HttpRouting\Middleware\Internal\MiddlewareFactory;
use Snicco\HttpRouting\Middleware\Internal\MiddlewarePipeline;
use Snicco\Component\Core\ExceptionHandling\NullExceptionHandler;

final class HandleStatefulRequestTestInternal extends InternalMiddlewareTestCase
{
    
    use CreatePsr17Factories;
    use SessionHelpers;
    
    /** @test */
    public function a_dirty_request_is_saved()
    {
        $id = SessionId::createFresh();
        $driver = new ArraySessionDriver();
        
        $manager = $this->getSessionManager(SessionConfig::fromDefaults('test_cookie'), $driver);
        
        $middleware = new HandleStatefulRequest($manager);
        
        $this->withNextMiddlewareResponse(function (Response $response, Request $request) {
            /** @var MutableSessionInterface $session */
            $session = $request->getAttribute(Keys::WRITE_SESSION);
            $session->put('foo', 'baz');
            return $response;
        });
        
        $session = new Session($id, ['foo' => 'bar'], new DateTimeImmutable());
        
        $request = $this->frontendRequest()
                        ->withAddedHeader('Cookie', "test_cookie={$id->asString()}")
                        ->withAttribute(
                            Keys::WRITE_SESSION,
                            $session
                        )
                        ->withAttribute(
                            Keys::READ_SESSION,
                            ImmutableSession::fromSession($session)
                        );
        
        $response = $this->runMiddleware($middleware, $request);
        $response->assertNextMiddlewareCalled();
        
        $data = $driver->read($id->asHash());
        $this->assertSame('baz', $data->asArray()['foo']);
    }
    
    /** @test */
    public function the_session_cookie_is_always_added_to_the_response()
    {
        $c = new PimpleDIContainer();
        
        $manager = $this->getSessionManager(SessionConfig::fromDefaults('test_cookie'));
        
        $middleware = new HandleStatefulRequest($manager);
        
        $session = ImmutableSession::fromSession(
            new Session(
                SessionId::createFresh(),
                ['foo' => 'bar'],
                new DateTimeImmutable()
            )
        );
        
        $request = $this->frontendRequest()
                        ->withAttribute(Keys::READ_SESSION, $session);
        
        $pipeline = new MiddlewarePipeline(
            new MiddlewareFactory($c),
            new NullExceptionHandler(),
            $f = $this->createResponseFactory(),
        );
        
        $response = $pipeline->send($request)
                             ->through([
                                 new ShareCookies(),
                                 $middleware,
                             ])
                             ->then(function () use ($f) {
                                 return $f->make();
                             });
        
        $cookie_header = $response->getHeaderLine('Set-Cookie');
        $this->assertNotEmpty($cookie_header);
        
        $this->assertStringStartsWith("test_cookie={$session->id()->asString()}", $cookie_header);
        $this->assertStringContainsString('path=/', $cookie_header);
        $this->assertStringContainsString('SameSite=Lax', $cookie_header);
        $this->assertStringContainsString('HttpOnly', $cookie_header);
        $this->assertStringContainsString('secure', $cookie_header);
        
        $this->assertStringNotContainsString('domain', $cookie_header);
        // No expiry because the default config has none
        $this->assertStringNotContainsString('expires', $cookie_header);
    }
    
    /** @test */
    public function an_exception_is_thrown_if_no_read_session_has_been_set()
    {
        $manager = $this->getSessionManager();
        $middleware = new HandleStatefulRequest($manager);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("A read-only session has not been shared with the request.");
        
        $this->runMiddleware($middleware, $this->frontendRequest());
    }
    
}