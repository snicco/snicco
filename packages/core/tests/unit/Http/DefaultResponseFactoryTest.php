<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use Mockery;
use stdClass;
use Snicco\Core\Support\WP;
use InvalidArgumentException;
use Snicco\Core\Routing\Route;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Contracts\Responsable;
use Tests\Codeception\shared\UnitTest;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Routing\RouteCollection;
use Snicco\Core\Http\Responses\NullResponse;
use Snicco\Core\Http\DefaultResponseFactory;
use Snicco\Core\Routing\UrlGenerationContext;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

class DefaultResponseFactoryTest extends UnitTest
{
    
    use CreatePsr17Factories;
    
    /**
     * @var DefaultResponseFactory
     */
    private $factory;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->app_domain = 'foo.com';
        $this->routes = new RouteCollection();
        $this->factory = $this->createResponseFactory();
    }
    
    public function test_make()
    {
        $response = $this->factory->make(204, 'Hello');
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('Hello', $response->getReasonPhrase());
    }
    
    public function test_json()
    {
        $response = $this->factory->json(['foo' => 'bar'], 401);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame(json_encode(['foo' => 'bar']), (string) $response->getBody());
    }
    
    /** @test */
    public function test_null()
    {
        $response = $this->factory->null();
        
        $this->assertInstanceOf(NullResponse::class, $response);
    }
    
    /** @test */
    public function test_toResponse_for_response()
    {
        $response = $this->factory->make();
        $result = $this->factory->toResponse($response);
        $this->assertSame($result, $response);
    }
    
    /** @test */
    public function test_toResponse_for_psr7_response()
    {
        $response = $this->psrResponseFactory()->createResponse();
        $result = $this->factory->toResponse($response);
        $this->assertNotSame($result, $response);
        $this->assertInstanceOf(Response::class, $result);
    }
    
    /** @test */
    public function test_toResponse_for_string()
    {
        $response = $this->factory->toResponse('foo');
        $this->assertInstanceOf(Response::class, $response);
        
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
        $this->assertSame('foo', (string) $response->getBody());
    }
    
    /** @test */
    public function test_toResponse_for_array()
    {
        $input = ['foo' => 'bar', 'bar' => 'baz'];
        
        $response = $this->factory->toResponse($input);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        
        $this->assertSame(json_encode($input), (string) $response->getBody());
    }
    
    /** @test */
    public function test_toResponse_for_stdclass()
    {
        $input = new stdClass();
        $input->foo = 'bar';
        
        $response = $this->factory->toResponse($input);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame(json_encode(['foo' => 'bar']), $response->getBody()->__toString());
    }
    
    /** @test */
    public function test_toResponse_for_responseable()
    {
        $class = new class implements Responsable
        {
            
            public function toResponsable()
            {
                return 'foo';
            }
            
        };
        
        $response = $this->factory->toResponse($class);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
        $this->assertSame('foo', (string) $response->getBody());
    }
    
    /** @test */
    public function toResponse_throws_an_exception_if_no_response_can_be_created()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->toResponse(1);
    }
    
    /** @test */
    public function test_noContent()
    {
        $response = $this->factory->noContent();
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }
    
    /** @test */
    public function test_redirect()
    {
        $response = $this->factory->redirect('/foo', 307);
        
        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame('/foo', $response->getHeaderLine('location'));
    }
    
    /** @test */
    public function test_exception_for_status_code_that_is_to_low()
    {
        $this->assertInstanceOf(Response::class, $this->factory->make(100));
        $this->expectException(InvalidArgumentException::class);
        $this->factory->make(99);
    }
    
    /** @test */
    public function test_exception_for_status_code_that_is_to_high()
    {
        $this->assertInstanceOf(Response::class, $this->factory->make(599));
        $this->expectException(InvalidArgumentException::class);
        $this->factory->make(600);
    }
    
    /** @test */
    public function test_home_with_no_home_route_defaults_to_the_base_path()
    {
        $response = $this->factory->home(['foo' => 'bar'], 307);
        
        $this->assertSame('/?foo=bar', $response->getHeaderLine('location'));
        $this->assertSame(307, $response->getStatusCode());
    }
    
    /** @test */
    public function test_home_goes_to_the_home_route_if_it_exists()
    {
        WP::shouldReceive('adminUrl')->andReturn('');
        WP::shouldReceive('wpAdminFolder')->andReturn('');
        
        $home_route = new Route(['GET'], '/home/{user_id}');
        $home_route->noAction()->name('home');
        $this->routes->add($home_route);
        $this->routes->addToUrlMatcher();
        
        $response = $this->factory->home(['user_id' => 1, 'foo' => 'bar'], 307);
        
        $this->assertSame('/home/1?foo=bar', $response->getHeaderLine('location'));
        $this->assertSame(307, $response->getStatusCode());
        
        WP::reset();
        Mockery::close();
    }
    
    /** @test */
    public function test_toRoute()
    {
        WP::shouldReceive('adminUrl')->andReturn('');
        WP::shouldReceive('wpAdminFolder')->andReturn('');
        
        $home_route = new Route(['GET'], '/home/{user_id}');
        $home_route->noAction()->name('home');
        $this->routes->add($home_route);
        $this->routes->addToUrlMatcher();
        
        $response = $this->factory->toRoute('home', ['user_id' => 1, 'foo' => 'bar'], 307);
        
        $this->assertSame('/home/1?foo=bar', $response->getHeaderLine('location'));
        $this->assertSame(307, $response->getStatusCode());
        
        WP::reset();
        Mockery::close();
    }
    
    /** @test */
    public function test_refresh()
    {
        $request = $this->psrServerRequestFactory()
                        ->createServerRequest(
                            'GET',
                            $url = 'https://foobar.com/foo?bar=baz#section1'
                        );
        
        $request = new Request($request);
        $context = new UrlGenerationContext($request);
        $g = $this->newUrlGenerator($context);
        
        $factory = new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $g
        );
        
        $response = $factory->refresh();
        
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame($url, $response->getHeaderLine('location'));
    }
    
    /** @test */
    public function test_back_with_referer_header_present()
    {
        $request = $this->psrServerRequestFactory()
                        ->createServerRequest(
                            'GET',
                            $url = 'https://foobar.com/foo?bar=baz#section1'
                        )->withAddedHeader('referer', '/foo/bar');
        
        $request = new Request($request);
        $context = new UrlGenerationContext($request);
        $g = $this->newUrlGenerator($context);
        
        $factory = new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $g
        );
        
        $response = $factory->back('/', 307);
        
        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame('/foo/bar', $response->getHeaderLine('location'));
    }
    
    /** @test */
    public function test_back_with_referer_header_missing()
    {
        $request = $this->psrServerRequestFactory()
                        ->createServerRequest(
                            'GET',
                            $url = 'https://foobar.com/foo?bar=baz#section1'
                        );
        
        $request = new Request($request);
        $context = new UrlGenerationContext($request);
        $g = $this->newUrlGenerator($context);
        
        $factory = new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $g
        );
        
        $response = $factory->back('/foobar_fallback', 307);
        
        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame(
            'https://foobar.com/foobar_fallback',
            $response->getHeaderLine('location')
        );
    }
    
    /** @test */
    public function test_to()
    {
        $response = $this->factory->to('foo', 307, ['bar' => 'baz']);
        
        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame('/foo?bar=baz', $response->getHeaderLine('location'));
    }
    
    /** @test */
    public function test_secure()
    {
        $request = $this->psrServerRequestFactory()
                        ->createServerRequest(
                            'GET',
                            $url = 'http://foobar.com/'
                        );
        
        $request = new Request($request);
        $context = new UrlGenerationContext($request);
        $g = $this->newUrlGenerator($context);
        
        $factory = new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $g
        );
        
        $response = $factory->secure('/foo', 307);
        
        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame('https://foobar.com/foo', $response->getHeaderLine('location'));
    }
    
    /** @test */
    public function test_away_allows_validation_bypass()
    {
        $normal_response = $this->factory->to('/foo');
        $this->assertFalse($normal_response->externalRedirectAllowed());
        
        $external = $this->factory->away('https://external.com/foo', 307);
        $this->assertTrue($external->externalRedirectAllowed());
        
        $this->assertSame(307, $external->getStatusCode());
        $this->assertSame('https://external.com/foo', $external->getHeaderLine('location'));
    }
    
    /** @test */
    public function test_deny_throws_exception_if_query_contains_intended()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->deny('/foo', 302, ['intended' => 'bar']);
    }
    
    /** @test */
    public function test_deny()
    {
        $request = $this->psrServerRequestFactory()
                        ->createServerRequest(
                            'GET',
                            $current = 'https://foobar.com/foo?bar=baz#section1'
                        );
        
        $request = new Request($request);
        $context = new UrlGenerationContext($request);
        $g = $this->newUrlGenerator($context);
        
        $factory = new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $g
        );
        
        $response = $factory->deny('login', 307, ['foo' => 'bar']);
        
        $this->assertSame(307, $response->getStatusCode());
        
        $expected = '/login?foo=bar&intended=https://foobar.com/foo?bar%3Dbaz%23section1';
        
        $this->assertSame($expected, $response->getHeaderLine('location'));
    }
    
    /** @test */
    public function test_intended_with_intended_query_param_present()
    {
        $request = $this->psrServerRequestFactory()
                        ->createServerRequest(
                            'GET',
                            $original = 'https://foobar.com/foo?bar=baz#section1'
                        );
        
        $request = new Request($request);
        $context = new UrlGenerationContext($request);
        $g = $this->newUrlGenerator($context);
        
        $factory = new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $g
        );
        
        $response = $factory->deny('login', 307, ['foo' => 'bar']);
        $redirected_to = $response->getHeaderLine('location');
        
        $request = $this->psrServerRequestFactory()
                        ->createServerRequest(
                            'GET',
                            $redirected_to
                        );
        
        $request = new Request($request);
        $context = new UrlGenerationContext($request);
        $g = $this->newUrlGenerator($context);
        
        $factory = new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $g
        );
        
        $response = $factory->intended('/home', 307);
        
        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame($original, $response->getHeaderLine('location'));
    }
    
    /** @test */
    public function test_intended_with_missing_query_param_goes_to_fallback()
    {
        $response = $this->factory->intended('/home', 307);
        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame('/home', $response->getHeaderLine('location'));
    }
    
}