<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Cookie;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final class ResponseTest extends TestCase
{
    use CreateTestPsr17Factories;

    private ResponseFactory $factory;

    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = $this->createResponseFactory();
        $this->response = $this->factory->createResponse();
    }

    /**
     * @test
     */
    public function is_psr_response(): void
    {
        $response = $this->factory->createResponse();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @test
     */
    public function is_immutable(): void
    {
        $response1 = $this->factory->createResponse();
        $response2 = $response1->withHeader('foo', 'bar');

        $this->assertNotSame($response1, $response2);
        $this->assertTrue($response2->hasHeader('foo'));
        $this->assertFalse($response1->hasHeader('foo'));
    }

    /**
     * @test
     */
    public function magic_set_throws_exception(): void
    {
        $response1 = $this->factory->createResponse();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot set undefined property [foo]');

        $response1->foo = 'bar';
    }

    /**
     * @test
     */
    public function html(): void
    {
        $stream = $this->factory->createStream('foo');

        $response = $this->factory->createResponse()
            ->withHtml($stream);

        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
        $this->assertSame('foo', $response->getBody()->__toString());
    }

    /**
     * @test
     */
    public function json(): void
    {
        $stream = $this->factory->createStream(json_encode([
            'foo' => 'bar',
        ], JSON_THROW_ON_ERROR));

        $response = $this->factory->createResponse()
            ->withJson($stream);

        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame([
            'foo' => 'bar',
        ], json_decode($response->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @test
     */
    public function no_index(): void
    {
        $response = $this->response->withNoIndex();
        $this->assertSame('noindex', $response->getHeaderLine('x-robots-tag'));

        $response = $this->response->withNoIndex('googlebot');
        $this->assertSame('googlebot: noindex', $response->getHeaderLine('x-robots-tag'));
    }

    /**
     * @test
     */
    public function no_follow(): void
    {
        $response = $this->response->withNoFollow();
        $this->assertSame('nofollow', $response->getHeaderLine('x-robots-tag'));

        $response = $this->response->withNoFollow('googlebot');
        $this->assertSame('googlebot: nofollow', $response->getHeaderLine('x-robots-tag'));
    }

    /**
     * @test
     */
    public function no_robots(): void
    {
        $response = $this->response->withNoRobots();
        $this->assertSame('none', $response->getHeaderLine('x-robots-tag'));

        $response = $this->response->withNoRobots('googlebot');
        $this->assertSame('googlebot: none', $response->getHeaderLine('x-robots-tag'));
    }

    /**
     * @test
     */
    public function no_archive(): void
    {
        $response = $this->response->withNoArchive();
        $this->assertSame('noarchive', $response->getHeaderLine('x-robots-tag'));

        $response = $this->response->withNoArchive('googlebot');
        $this->assertSame('googlebot: noarchive', $response->getHeaderLine('x-robots-tag'));
    }

    /**
     * @test
     */
    public function is_informational(): void
    {
        $response = $this->response->withStatus(100);
        $this->assertTrue($response->isInformational());
        $this->assertTrue($response->withStatus(199)->isInformational());
        $this->assertFalse($response->withStatus(200)->isInformational());
    }

    /**
     * @test
     */
    public function test_is_redirection(): void
    {
        $response = $this->response->withStatus(299);
        $this->assertFalse($response->isRedirection());
        $this->assertTrue($response->withStatus(300)->isRedirection());
        $this->assertFalse($response->withStatus(400)->isRedirection());
    }

    /**
     * @test
     */
    public function test_is_client_error(): void
    {
        $response = $this->response->withStatus(399);
        $this->assertFalse($response->isClientError());
        $this->assertTrue($response->withStatus(400)->isClientError());
        $this->assertFalse($response->withStatus(500)->isClientError());
    }

    /**
     * @test
     */
    public function test_is_server_error(): void
    {
        $response = $this->response->withStatus(499);
        $this->assertFalse($response->isServerError());
        $this->assertTrue($response->withStatus(500)->isServerError());
        $this->assertTrue($response->withStatus(599)->isServerError());
    }

    /**
     * @test
     */
    public function test_has_empty_body(): void
    {
        $response = $this->factory->createResponse();
        $this->assertTrue($response->hasEmptyBody());

        $response->getBody()
            ->detach();
        $this->assertTrue($response->hasEmptyBody());

        $html_response = $this->factory->html('foobar');
        $this->assertFalse($html_response->hasEmptyBody());
    }

    /**
     * @test
     */
    public function test_is_empty(): void
    {
        $response = $this->response->withStatus(204);
        $this->assertTrue($response->isEmpty());
        $this->assertTrue($response->withStatus(304)->isEmpty());
        $this->assertTrue($this->factory->html('foo')->withStatus(204)->isEmpty());
        $this->assertTrue($this->factory->html('foo')->withStatus(304)->isEmpty());
    }

    /**
     * @test
     */
    public function test_with_cookie(): void
    {
        $response = $this->response->withCookie(new Cookie('foo', 'bar'));

        $cookies = $response->cookies()
            ->toHeaders();
        $this->assertCount(1, $cookies);
        $this->assertCount(0, $this->response->cookies()->toHeaders());
    }

    /**
     * @test
     */
    public function test_without_cookie(): void
    {
        $response = $this->response->withoutCookie('foo');

        $cookies = $response->cookies()
            ->toHeaders();
        $this->assertCount(1, $cookies);
        $this->assertCount(0, $this->response->cookies()->toHeaders());
    }

    /**
     * @test
     * @psalm-suppress PossiblyUndefinedIntArrayOffset
     */
    public function cookies_are_not_reset_in_nested_responses(): void
    {
        $redirect_response = $this->factory->createResponse()
            ->withCookie(new Cookie('foo', 'bar'));

        $response = new Response($redirect_response);

        $response = $response->withCookie(new Cookie('bar', 'baz'));

        $cookies = $response->cookies();

        $headers = $cookies->toHeaders();

        $this->assertCount(2, $headers);
        $this->assertStringStartsWith('foo=bar', $headers[0]);
        $this->assertStringStartsWith('bar=baz', $headers[1]);
    }

    /**
     * @test
     */
    public function test_with_flash_messages(): void
    {
        $response = $this->response->withFlashMessages([
            'foo' => 'bar',
        ]);

        $this->assertSame([
            'foo' => 'bar',
        ], $response->flashMessages());
        $this->assertSame([], $this->response->flashMessages());

        $response = $this->response->withFlashMessages($arr = [
            'foo' => 'bar',
            'bar' => 'baz',
        ]);

        $this->assertSame($arr, $response->flashMessages());
        $this->assertSame([], $this->response->flashMessages());

        $response_new = $response->withFlashMessages([
            'biz' => 'boom',
        ]);

        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
            'biz' => 'boom',
        ], $response_new->flashMessages());
        $this->assertSame($arr, $response->flashMessages());
    }

    /**
     * @test
     */
    public function test_with_input(): void
    {
        $response = $this->response->withOldInput([
            'foo' => 'bar',
        ]);

        $this->assertSame([
            'foo' => 'bar',
        ], $response->oldInput());
        $this->assertSame([], $this->response->oldInput());

        $response = $this->response->withOldInput($arr = [
            'foo' => 'bar',
            'bar' => 'baz',
        ]);

        $this->assertSame($arr, $response->oldInput());
        $this->assertSame([], $this->response->oldInput());

        $response_new = $response->withOldInput([
            'biz' => 'boom',
        ]);

        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
            'biz' => 'boom',
        ], $response_new->oldInput());
        $this->assertSame($arr, $response->oldInput());
    }

    /**
     * @test
     */
    public function test_with_errors(): void
    {
        $response = $this->response->withErrors([
            'foo' => 'bar',
        ]);

        $this->assertSame([
            'default' => [
                'foo' => ['bar'],
            ],
        ], $response->errors());
        $this->assertSame([], $this->response->errors());

        $response = $this->response->withErrors([
            'foo' => ['bar', 'baz'],
            'baz' => 'biz',
        ]);

        $this->assertSame([
            'default' => [
                'foo' => ['bar', 'baz'],
                'baz' => ['biz'],
            ],
        ], $response->errors());
        $this->assertSame([], $this->response->errors());

        $response = $this->response->withErrors([
            'foo' => 'bar',
        ]);

        $response_new = $response->withErrors([
            'bar' => 'baz',
        ]);
        $this->assertSame([
            'default' => [
                'foo' => ['bar'],
            ],
        ], $response->errors());
        $this->assertSame([
            'default' => [
                'foo' => ['bar'],
                'bar' => ['baz'],
            ],
        ], $response_new->errors());

        $response = $this->response->withErrors([
            'foo' => 'bar',
        ], 'namespace1');
        $this->assertSame([
            'namespace1' => [
                'foo' => ['bar'],
            ],
        ], $response->errors());
        $this->assertSame([], $this->response->errors());
    }

    /**
     * @test
     */
    public function test_get_protocol_version(): void
    {
        $this->assertSame('1.1', $this->response->getProtocolVersion());
        $response = $this->response->withProtocolVersion('1.0');
        $this->assertSame('1.0', $response->getProtocolVersion());
    }

    /**
     * @test
     */
    public function test_get_headers(): void
    {
        $response = $this->response
            ->withHeader('foo', 'bar1')
            ->withAddedHeader('foo', 'bar2')
            ->withHeader('baz', 'biz');

        $this->assertSame([
            'foo' => ['bar1', 'bar2'],
            'baz' => ['biz'],
        ], $response->getHeaders());
    }

    /**
     * @test
     */
    public function test_is_redirect(): void
    {
        $response = $this->response->withStatus(301);
        $this->assertTrue($response->isRedirect());

        $response = $this->response->withStatus(302);
        $this->assertTrue($response->isRedirect());

        $response = $this->response->withStatus(303);
        $this->assertTrue($response->isRedirect());

        $response = $this->response->withStatus(307);
        $this->assertTrue($response->isRedirect());

        $response = $this->response->withStatus(308);
        $this->assertTrue($response->isRedirect());

        $response = $this->response->withStatus(301)
            ->withHeader('location', '/foo');
        $this->assertTrue($response->isRedirect('/foo'));
        $this->assertFalse($response->isRedirect('/bar'));

        $response = $this->response->withStatus(200);
        $this->assertFalse($response->isRedirect());
    }

    /**
     * @test
     */
    public function test_is_ok(): void
    {
        $response = $this->response->withStatus(201);
        $this->assertFalse($response->isOk());

        $response = $this->response->withStatus(200);
        $this->assertTrue($response->isOk());
    }

    /**
     * @test
     */
    public function test_is_not_found(): void
    {
        $response = $this->response->withStatus(403);
        $this->assertFalse($response->isNotFound());

        $response = $this->response->withStatus(404);
        $this->assertTrue($response->isNotFound());
    }

    /**
     * @test
     */
    public function test_is_forbidden(): void
    {
        $response = $this->response->withStatus(404);
        $this->assertFalse($response->isForbidden());

        $response = $this->response->withStatus(403);
        $this->assertTrue($response->isForbidden());
    }
}
