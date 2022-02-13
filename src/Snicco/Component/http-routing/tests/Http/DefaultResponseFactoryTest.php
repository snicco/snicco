<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Responsable;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\RuntimeRouteCollection;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;
use Snicco\Component\HttpRouting\Tests\helpers\CreateUrlGenerator;
use stdClass;

use function dirname;
use function fopen;

class DefaultResponseFactoryTest extends TestCase
{

    use CreateTestPsr17Factories;
    use CreateUrlGenerator;

    private ResponseFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app_domain = 'foo.com';
        $this->routes = new RuntimeRouteCollection([]);
        $this->factory = $this->createResponseFactory(
            $this->createUrlGenerator(
                UrlGenerationContext::forConsole($this->app_domain),
                $this->routes
            ),
        );
    }

    public function test_make(): void
    {
        $response = $this->factory->createResponse(204, 'Hello');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('Hello', $response->getReasonPhrase());
    }

    public function test_json(): void
    {
        $response = $this->factory->json(['foo' => 'bar'], 401);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame(json_encode(['foo' => 'bar']), (string)$response->getBody());
    }

    /**
     * @test
     */
    public function test_toResponse_for_response(): void
    {
        $response = $this->factory->createResponse();
        $result = $this->factory->toResponse($response);
        $this->assertSame($result, $response);
    }

    /**
     * @test
     */
    public function test_toResponse_for_psr7_response(): void
    {
        $response = $this->psrResponseFactory()->createResponse();
        $result = $this->factory->toResponse($response);
        $this->assertNotSame($result, $response);
        $this->assertInstanceOf(Response::class, $result);
    }

    /**
     * @test
     */
    public function test_toResponse_for_string(): void
    {
        $response = $this->factory->toResponse('foo');
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
        $this->assertSame('foo', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function test_toResponse_for_array(): void
    {
        $input = ['foo' => 'bar', 'bar' => 'baz'];

        $response = $this->factory->toResponse($input);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));

        $this->assertSame(json_encode($input), (string)$response->getBody());
    }

    /**
     * @test
     */
    public function test_toResponse_for_stdclass(): void
    {
        $input = new stdClass();
        $input->foo = 'bar';

        $response = $this->factory->toResponse($input);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame(json_encode(['foo' => 'bar']), $response->getBody()->__toString());
    }

    /**
     * @test
     */
    public function test_toResponse_for_responseable(): void
    {
        $class = new class implements Responsable {

            public function toResponsable()
            {
                return 'foo';
            }

        };

        $response = $this->factory->toResponse($class);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
        $this->assertSame('foo', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function toResponse_throws_an_exception_if_no_response_can_be_created(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->toResponse(1);
    }

    /**
     * @test
     */
    public function test_noContent(): void
    {
        $response = $this->factory->noContent();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_redirect(): void
    {
        $response = $this->factory->redirect('/foo', 307);

        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame('/foo', $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_exception_for_status_code_that_is_to_low(): void
    {
        $this->assertInstanceOf(Response::class, $this->factory->createResponse(100));
        $this->expectException(InvalidArgumentException::class);
        $this->factory->createResponse(99);
    }

    /**
     * @test
     */
    public function test_exception_for_status_code_that_is_to_high(): void
    {
        $this->assertInstanceOf(Response::class, $this->factory->createResponse(599));
        $this->expectException(InvalidArgumentException::class);
        $this->factory->createResponse(600);
    }

    /**
     * @test
     */
    public function test_home_with_no_home_route_defaults_to_the_base_path(): void
    {
        $response = $this->factory->home(['foo' => 'bar'], 307);

        $this->assertSame('/?foo=bar', $response->getHeaderLine('location'));
        $this->assertSame(307, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_home_goes_to_the_home_route_if_it_exists(): void
    {
        $home_route = Route::create('/home/{user_id}', Route::DELEGATE, 'home');

        $routes = new RuntimeRouteCollection([$home_route]);

        $factory = $this->createResponseFactory(
            $this->createUrlGenerator(null, $routes)
        );

        $response = $factory->home(['user_id' => 1, 'foo' => 'bar'], 307);

        $this->assertSame('/home/1?foo=bar', $response->getHeaderLine('location'));
        $this->assertSame(307, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_toRoute(): void
    {
        $route = Route::create('/foo/{param}', Route::DELEGATE, 'r1');

        $routes = new RuntimeRouteCollection([$route]);

        $factory = $this->createResponseFactory(
            $this->createUrlGenerator(null, $routes)
        );

        $response = $factory->toRoute('r1', ['param' => 1, 'foo' => 'bar'], 307);

        $this->assertSame('/foo/1?foo=bar', $response->getHeaderLine('location'));
        $this->assertSame(307, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_refresh(): void
    {
        $request = $this->psrServerRequestFactory()
            ->createServerRequest(
                'GET',
                $url = 'https://foobar.com/foo?bar=baz#section1'
            );

        $factory = new ResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $this->createUrlGenerator(UrlGenerationContext::fromRequest($request))
        );

        $response = $factory->refresh();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame($url, $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_back_with_referer_header_present(): void
    {
        $request = $this->psrServerRequestFactory()
            ->createServerRequest(
                'GET',
                'https://foobar.com/foo?bar=baz#section1'
            )->withAddedHeader('referer', '/foo/bar');

        $factory = new ResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $this->createUrlGenerator(UrlGenerationContext::fromRequest($request))
        );

        $response = $factory->back('/', 307);

        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame('/foo/bar', $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_back_with_referer_header_missing(): void
    {
        $request = $this->psrServerRequestFactory()
            ->createServerRequest(
                'GET',
                $url = 'https://foobar.com/foo?bar=baz#section1'
            );

        $factory = new ResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $this->createUrlGenerator(UrlGenerationContext::fromRequest($request))
        );

        $response = $factory->back('/foobar_fallback', 307);

        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame(
            'https://foobar.com/foobar_fallback',
            $response->getHeaderLine('location')
        );
    }

    /**
     * @test
     */
    public function test_to(): void
    {
        $response = $this->factory->to('foo', 307, ['bar' => 'baz']);

        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame('/foo?bar=baz', $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_secure(): void
    {
        $request = $this->psrServerRequestFactory()
            ->createServerRequest(
                'GET',
                $url = 'http://foobar.com/'
            );

        $factory = new ResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $this->createUrlGenerator(UrlGenerationContext::fromRequest($request))
        );

        $response = $factory->secure('/foo', 307);

        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame('https://foobar.com/foo', $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_away_allows_validation_bypass(): void
    {
        $normal_response = $this->factory->to('/foo');
        $this->assertFalse($normal_response->isExternalRedirectAllowed());

        $external = $this->factory->away('https://external.com/foo', 307);
        $this->assertTrue($external->isExternalRedirectAllowed());

        $this->assertSame(307, $external->getStatusCode());
        $this->assertSame('https://external.com/foo', $external->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_deny_throws_exception_if_query_contains_intended(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->deny('/foo', 302, ['intended' => 'bar']);
    }

    /**
     * @test
     */
    public function test_deny(): void
    {
        $request = $this->psrServerRequestFactory()
            ->createServerRequest(
                'GET',
                $current = 'https://foobar.com/foo?bar=baz#section1'
            );

        $factory = new ResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $this->createUrlGenerator(UrlGenerationContext::fromRequest($request))
        );

        $response = $factory->deny('login', 307, ['foo' => 'bar']);

        $this->assertSame(307, $response->getStatusCode());

        $expected = '/login?foo=bar&intended=https://foobar.com/foo?bar%3Dbaz%23section1';

        $this->assertSame($expected, $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_intended_with_intended_query_param_present(): void
    {
        $request = $this->psrServerRequestFactory()
            ->createServerRequest(
                'GET',
                $original = 'https://foobar.com/foo?bar=baz#section1'
            );

        $factory = new ResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $this->createUrlGenerator(UrlGenerationContext::fromRequest($request))
        );

        $response = $factory->deny('login', 307, ['foo' => 'bar']);
        $redirected_to = $response->getHeaderLine('location');

        $request = $this->psrServerRequestFactory()
            ->createServerRequest(
                'GET',
                'https://foobar.com' . $redirected_to
            );

        $factory = new ResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $this->createUrlGenerator(UrlGenerationContext::fromRequest($request))
        );

        $response = $factory->intended('/home', 307);

        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame($original, $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_intended_with_missing_query_param_goes_to_fallback(): void
    {
        $response = $this->factory->intended('/home', 307);
        $this->assertSame(307, $response->getStatusCode());
        $this->assertSame('/home', $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_delegate(): void
    {
        $this->assertTrue($this->factory->delegate()->shouldHeadersBeSent());
        $this->assertFalse($this->factory->delegate(false)->shouldHeadersBeSent());
    }

    /**
     * @test
     */
    public function test_createStreamFromFile(): void
    {
        $stream = $this->factory->createStreamFromFile(dirname(__DIR__) . '/fixtures/stream/foo.txt');
        $this->assertSame(3, $stream->getSize());
        $this->assertSame('foo', $stream->getContents());
    }

    /**
     * @test
     */
    public function test_createStreamFromResource(): void
    {
        $file = dirname(__DIR__) . '/fixtures/stream/foo.txt';
        /** @psalm-suppress PossiblyFalseArgument */
        $stream = $this->factory->createStreamFromResource(fopen($file, 'r'));
        $this->assertSame(3, $stream->getSize());
        $this->assertSame('foo', $stream->getContents());
    }

}