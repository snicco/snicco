<?php

declare(strict_types=1);

namespace Snicco\Bundle\Session\Tests\Middleware;

use LogicException;
use Snicco\Bundle\Session\Middleware\SaveResponseAttributes;
use Snicco\Bundle\Session\ValueObject\SessionErrors;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\MutableSession;
use Snicco\Component\Session\Serializer\JsonSerializer;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\SessionManager\FactorySessionManager;
use Snicco\Component\Session\ValueObject\CookiePool;
use Snicco\Component\Session\ValueObject\SessionConfig;

/**
 * @internal
 */
final class SaveResponseAttributesTest extends MiddlewareTestCase
{
    private Request $request;

    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();
        $session_manager = new FactorySessionManager(
            SessionConfig::mergeDefaults('test_cookie', [
                'garbage_collection_percentage' => 0,
            ]),
            new InMemoryDriver(),
            new JsonSerializer()
        );
        $this->request = $this->frontendRequest()
            ->withAttribute(MutableSession::class, $this->session = $session_manager->start(new CookiePool([])));
    }

    /**
     * @test
     */
    public function errors_are_added_to_the_session(): void
    {
        $middleware = new SaveResponseAttributes();

        $this->withNextMiddlewareResponse(fn (Response $response) => $response->withErrors([
            'foo' => ['bar', 'baz'],
        ], 'name1'));

        $response = $this->runMiddleware($middleware, $this->request);
        $response->assertNextMiddlewareCalled();

        $errors = $this->session->get(SessionErrors::class);

        $this->assertSame([
            'name1' => [
                'foo' => ['bar', 'baz'],
            ],
        ], $errors);
    }

    /**
     * @test
     */
    public function flash_messages_are_added(): void
    {
        $middleware = new SaveResponseAttributes();

        $this->withNextMiddlewareResponse(fn (Response $response) => $response->withFlashMessages([
            'foo' => 'bar',
            'baz' => 'biz',
        ]));

        $response = $this->runMiddleware($middleware, $this->request);
        $response->assertNextMiddlewareCalled();

        $this->assertSame('bar', $this->session->get('foo'));
        $this->assertSame('biz', $this->session->get('baz'));
    }

    /**
     * @test
     */
    public function old_input_is_added(): void
    {
        $middleware = new SaveResponseAttributes();

        $this->withNextMiddlewareResponse(fn (Response $response) => $response->withOldInput([
            'foo' => 'bar',
            'baz' => 'biz',
        ]));

        $response = $this->runMiddleware($middleware, $this->request);
        $response->assertNextMiddlewareCalled();

        $this->assertSame('bar', $this->session->oldInput('foo'));
        $this->assertSame('biz', $this->session->oldInput('baz'));
    }

    /**
     * @test
     */
    public function test_exception_if_session_not_set(): void
    {
        $middleware = new SaveResponseAttributes();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No mutable session');
        $this->runMiddleware($middleware, $this->frontendRequest());
    }
}
