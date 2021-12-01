<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Mockery;
use Snicco\Support\WP;
use Snicco\Support\Str;
use Snicco\Support\Arr;
use Snicco\Support\Carbon;
use Snicco\Session\Session;
use Snicco\Routing\Pipeline;
use Snicco\Contracts\MagicLink;
use Snicco\Routing\UrlGenerator;
use Tests\Core\MiddlewareTestCase;
use Snicco\Factories\MiddlewareFactory;
use Snicco\Middleware\Core\ShareCookies;
use Snicco\Middleware\ValidateSignature;
use Snicco\Testing\TestDoubles\TestMagicLink;
use Snicco\Session\Drivers\ArraySessionDriver;
use Snicco\ExceptionHandling\NullExceptionHandler;
use Tests\Codeception\shared\helpers\CreateContainer;
use Tests\Codeception\shared\helpers\CreateDefaultWpApiMocks;
use Snicco\ExceptionHandling\Exceptions\InvalidSignatureException;

class ValidateSignatureTest extends MiddlewareTestCase
{
    
    use CreateDefaultWpApiMocks;
    use CreateContainer;
    
    private UrlGenerator  $url;
    private TestMagicLink $magic_link;
    
    protected function setUp() :void
    {
        WP::shouldReceive('userId')->andReturn(0)->byDefault();
        parent::setUp();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        WP::reset();
        Mockery::close();
        Carbon::setTestNow();
    }
    
    /** @test */
    public function a_valid_signature_grants_access_to_the_route()
    {
        $url = $this->url->signed('foo');
        $request = $this->frontendRequest('GET', $url);
        
        $response = $this->runMiddleware($this->newMiddleware($this->magic_link), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertOk();
    }
    
    /** @test */
    public function signatures_that_were_created_from_absolute_urls_can_be_validated()
    {
        $url = $this->url->signed('foo', 10, true);
        $request = $this->frontendRequest('GET', $url);
        
        $response = $this->runMiddleware(
            $this->newMiddleware($this->magic_link, 'absolute'),
            $request
        );
        
        $response->assertNextMiddlewareCalled();
        $response->assertOk();
    }
    
    /** @test */
    public function an_exception_is_thrown_for_invalid_signatures()
    {
        $url = $this->url->signed('foo', 10, true);
        $request = $this->frontendRequest('GET', $url.'changed');
        
        $this->expectException(InvalidSignatureException::class);
        
        $response = $this->runMiddleware(
            $this->newMiddleware($this->magic_link, 'absolute'),
            $request
        );
    }
    
    /** @test */
    public function the_magic_links_is_invalidated_after_the_first_access()
    {
        $url = $this->url->signed('foo');
        $request = $this->frontendRequest('GET', $url);
        
        $this->assertArrayHasKey($request->query('signature'), $this->magic_link->getStored());
        
        $response = $this->runMiddleware(
            $this->newMiddleware($this->magic_link),
            $request
        );
        
        $response->assertNextMiddlewareCalled();
        $response->assertOk();
        
        $this->assertArrayNotHasKey($request->query('signature'), $this->magic_link->getStored());
    }
    
    /** @test */
    public function if_sessions_are_used_the_user_with_the_session_can_access_the_route_several_times()
    {
        $url = $this->url->signed('foo');
        $request = $this->frontendRequest('GET', $url)
                        ->withSession($session = new Session(new ArraySessionDriver(10)));
        
        $this->assertArrayHasKey($request->query('signature'), $this->magic_link->getStored());
        
        $response = $this->runMiddleware(
            $this->newMiddleware($this->magic_link),
            $request
        );
        
        $this->assertArrayNotHasKey($request->query('signature'), $this->magic_link->getStored());
        $response->assertNextMiddlewareCalled()->assertOk();
        
        $response = $this->runMiddleware(
            $this->newMiddleware($this->magic_link),
            $request
        );
        $response->assertNextMiddlewareCalled()->assertOk();
    }
    
    /** @test */
    public function session_based_access_is_not_possible_after_expiration_time()
    {
        $url = $this->url->signed('foo', 500);
        $request = $this->frontendRequest('GET', $url)
                        ->withSession($session = new Session(new ArraySessionDriver(10)));
        
        $this->assertArrayHasKey($request->query('signature'), $this->magic_link->getStored());
        
        $response = $this->runMiddleware(
            $this->newMiddleware($this->magic_link),
            $request
        );
        
        $this->assertArrayNotHasKey($request->query('signature'), $this->magic_link->getStored());
        $response->assertNextMiddlewareCalled()->assertOk();
        
        Carbon::setTestNow(Carbon::now()->addSeconds(501));
        
        $this->expectException(InvalidSignatureException::class);
        $this->runMiddleware(
            $this->newMiddleware($this->magic_link),
            $request
        );
    }
    
    /** @test */
    public function if_sessions_are_not_used_a_cookie_is_used_to_allow_access_to_the_route()
    {
        $url = $this->url->signed('foo');
        $request = $this->frontendRequest('GET', $url);
        $c = $this->createContainer();
        
        $pipeline = new Pipeline(
            new MiddlewareFactory($c),
            new NullExceptionHandler(),
            $this->response_factory
        );
        $response = $pipeline->send($request)
                             ->through([
                                 ShareCookies::class,
                                 $this->newMiddleware($this->magic_link),
                             ])
                             ->then(fn() => $this->response_factory->make());
        
        $cookie_header = $response->getHeaderLine('Set-Cookie');
        
        $cookie = [];
        
        foreach (explode(";", $cookie_header) as $part) {
            $cookie[trim(Str::before($part, '='))] = trim(Str::after($part, '='));
        }
        
        $this->assertSame('/foo', $cookie['path']);
        $this->assertSame('secure', $cookie['secure']);
        $this->assertSame('HostOnly', $cookie['HostOnly']);
        $this->assertSame('HttpOnly', $cookie['HttpOnly']);
        $this->assertSame('Lax', $cookie['SameSite']);
        
        $c = Arr::firstKey($cookie).'='.Arr::firstEl($cookie).';';
        $request_with_access_cookie = $request->withAddedHeader('Cookie', $c);
        
        $response = $pipeline->send($request_with_access_cookie)
                             ->through([
                                 ShareCookies::class,
                                 $this->newMiddleware($this->magic_link),
                             ])
                             ->then(function () {
                                 return $this->response_factory->make();
                             });
        
        $this->assertSame(200, $response->getStatusCode());
    }
    
    /** @test */
    public function cookie_based_route_access_is_not_possible_after_the_expiration_time()
    {
        $url = $this->url->signed('foo', 500);
        $request = $this->frontendRequest('GET', $url);
        $c = $this->createContainer();
        
        $pipeline = new Pipeline(
            new MiddlewareFactory($c),
            new NullExceptionHandler(),
            $this->response_factory
        );
        $response = $pipeline->send($request)
                             ->through([
                                 ShareCookies::class,
                                 $this->newMiddleware($this->magic_link),
                             ])
                             ->then(fn() => $this->response_factory->make());
        
        $cookie_header = $response->getHeaderLine('Set-Cookie');
        
        $cookie = [];
        
        foreach (explode(';', $cookie_header) as $part) {
            $cookie[trim(Str::before($part, '='))] = trim(Str::after($part, '='));
        }
        
        $this->assertSame('/foo', $cookie['path']);
        $this->assertSame('secure', $cookie['secure']);
        $this->assertSame('HostOnly', $cookie['HostOnly']);
        $this->assertSame('HttpOnly', $cookie['HttpOnly']);
        $this->assertSame('Lax', $cookie['SameSite']);
        
        $c = Arr::firstKey($cookie).'='.Arr::firstEl($cookie).';';
        $request_with_access_cookie = $request->withAddedHeader('Cookie', $c);
        
        Carbon::setTestNow(Carbon::now()->addSeconds(501));
        
        $this->expectException(InvalidSignatureException::class);
        
        $pipeline->send($request_with_access_cookie)
                 ->through([
                     ShareCookies::class,
                     $this->newMiddleware($this->magic_link),
                 ])
                 ->then(function () {
                     return $this->response_factory->make();
                 });
    }
    
    protected function urlGenerator() :UrlGenerator
    {
        $this->url = new UrlGenerator(
            $this->routeUrlGenerator(),
            $this->magic_link = new TestMagicLink(),
            false
        );
        
        $this->url->setRequestResolver(function () {
            return $this->frontendRequest('GET', 'https://example.com');
        });
        
        return $this->url;
    }
    
    private function newMiddleware(MagicLink $magic_link, string $type = 'relative') :ValidateSignature
    {
        return new ValidateSignature($magic_link, $type);
    }
    
}
