<?php

declare(strict_types=1);

namespace Snicco\Bundle\Session\Tests\Middleware;

use LogicException;
use Snicco\Bundle\Session\Middleware\ShareSessionWithViews;
use Snicco\Bundle\Session\ValueObject\SessionErrors;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\MutableSession;
use Snicco\Component\Session\Serializer\JsonSerializer;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\SessionManager\FactorySessionManager;
use Snicco\Component\Session\ValueObject\CookiePool;
use Snicco\Component\Session\ValueObject\ReadOnlySession;
use Snicco\Component\Session\ValueObject\SessionConfig;

/**
 * @internal
 */
final class ShareSessionWithViewsTest extends MiddlewareTestCase
{
    private Session $session;

    private Request $request_with_session;

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
        $this->request_with_session = $this->frontendRequest()
            ->withAttribute(
                MutableSession::class,
                $this->session = $session_manager->start(new CookiePool([]))
            )->withAttribute(ImmutableSession::class, ReadOnlySession::fromSession($this->session));
    }

    /**
     * @test
     */
    public function session_utils_are_shared_with_view_responses(): void
    {
        $middleware = new ShareSessionWithViews();

        $this->session->flash(SessionErrors::class, [
            'default' => [
                'key1' => ['error1', 'error2'],
            ],
        ]);

        $this->withNextMiddlewareResponse(fn (Response $response) => (new ViewResponse('foo_view', $response))
            ->withViewData([
                'foo' => 'bar',
            ]));

        $response = $this->runMiddleware($middleware, $this->request_with_session);
        $response->assertNextMiddlewareCalled();

        $view_response = $response->assertableResponse()
            ->getPsrResponse();
        $this->assertInstanceOf(ViewResponse::class, $view_response);

        $this->assertEquals([
            'foo' => 'bar',
            'session' => ReadOnlySession::fromSession($this->session),
            'errors' => new SessionErrors([
                'default' => [
                    'key1' => ['error1', 'error2'],
                ],
            ]),
        ], $view_response->viewData());
    }

    /**
     * @test
     */
    public function test_does_nothing_for_non_view_response(): void
    {
        $middleware = new ShareSessionWithViews();
        $response = $this->runMiddleware($middleware, $this->request_with_session);
        $response->assertNextMiddlewareCalled();
    }

    /**
     * @test
     */
    public function test_exception_for_view_response_if_session_not_set(): void
    {
        $middleware = new ShareSessionWithViews();

        $this->withNextMiddlewareResponse(fn (Response $response) => (new ViewResponse('foo_view', $response))
            ->withViewData([
                'foo' => 'bar',
            ]));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No session has been set on the request.');
        $this->runMiddleware($middleware, $this->frontendRequest());
    }
}
