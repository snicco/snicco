<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Testing;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\Admin\AdminArea;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;
use Snicco\Component\HttpRouting\Testing\CreatesPsrRequests;

final class CreatesPsrRequestsTests extends TestCase
{

    use CreatesPsrRequests;

    protected string $host = 'foo.com';

    /**
     * @test
     */
    public function the_frontend_request_url_is_correctly_encoded(): void
    {
        $request = $this->frontendRequest('foo?bar=baz#section1');
        $this->assertInstanceOf(Request::class, $request);

        $this->assertEquals('https://foo.com/foo?bar=baz#section1', (string)$request->getUri());
        $this->assertEquals(['bar' => 'baz'], $request->getQueryParams());

        $request = $this->frontendRequest('foo?city=foo bar');
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('https://foo.com/foo?city=foo%20bar', (string)$request->getUri());
        $this->assertEquals(['city' => 'foo bar'], $request->getQueryParams());
    }

    /**
     * @test
     */
    public function a_full_uri_can_be_specified(): void
    {
        $request = $this->frontendRequest('http://foobar.com:8080/foo?bar=baz#section1');
        $this->assertInstanceOf(Request::class, $request);

        $this->assertEquals(
            'http://foobar.com:8080/foo?bar=baz#section1',
            (string)$request->getUri()
        );
        $this->assertEquals(['bar' => 'baz'], $request->getQueryParams());
    }

    /**
     * @test
     */
    public function the_method_is_get_by_default(): void
    {
        $request = $this->frontendRequest('/foo');
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('GET', $request->server('REQUEST_METHOD'));
    }

    /**
     * @test
     */
    public function the_method_can_be_changed(): void
    {
        $request = $this->frontendRequest('/foo', [], 'POST');
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('POST', $request->server('REQUEST_METHOD'));
    }

    /**
     * @test
     */
    public function server_params_can_be_set(): void
    {
        $request = $this->frontendRequest('/foo', ['X-FOO' => 'BAR'], 'POST');
        $this->assertEquals('POST', $request->server('REQUEST_METHOD'));
        $this->assertEquals('BAR', $request->server('X-FOO'));
    }

    /**
     * @test
     */
    public function the_request_type_is_set_to_frontend(): void
    {
        $request = $this->frontendRequest('/foo');
        $this->assertTrue($request->isToFrontend());
    }

    /**
     * @test
     */
    public function test_admin_request(): void
    {
        $request = $this->adminRequest(
            '/wp-admin/admin.php?page=foo&city=foo bar',
            ['X-FOO' => 'BAR']
        );

        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals(
            'https://foo.com/wp-admin/admin.php?page=foo&city=foo%20bar',
            (string)$request->getUri()
        );
        $this->assertEquals(['city' => 'foo bar', 'page' => 'foo'], $request->getQueryParams());
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('GET', $request->server('REQUEST_METHOD'));
        $this->assertSame('BAR', $request->server('X-FOO'));
        $this->assertTrue($request->isToAdminArea());
    }

    public function psrServerRequestFactory(): ServerRequestFactoryInterface
    {
        return new Psr17Factory();
    }

    public function psrUriFactory(): UriFactoryInterface
    {
        return new Psr17Factory();
    }

    protected function host(): string
    {
        return 'foo.com';
    }

    protected function urlGenerator(): UrlGeneratorInterface
    {
        return new UrlGenerator(
            new RouteCollection([]),
            UrlGenerationContext::forConsole($this->host),
            $this->adminArea()
        );
    }

    protected function adminArea(): AdminArea
    {
        return WPAdminArea::fromDefaults();
    }

}