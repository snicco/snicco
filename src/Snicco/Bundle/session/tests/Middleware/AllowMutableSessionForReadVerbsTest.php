<?php

declare(strict_types=1);


namespace Snicco\Bundle\Session\Tests\Middleware;

use LogicException;
use Snicco\Bundle\Session\Middleware\AllowMutableSessionForReadVerbs;
use Snicco\Bundle\Session\Middleware\StatefulRequest;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Session\ImmutableSession;

final class AllowMutableSessionForReadVerbsTest extends MiddlewareTestCase
{
    /**
     * @test
     */
    public function the_request_is_marked_as_being_allowed_a_write_session_for_read_requests(): void
    {
        $middleware = new AllowMutableSessionForReadVerbs();

        $request = $this->frontendRequest();

        $response = $this->runMiddleware($middleware, $request)->assertNextMiddlewareCalled();
        $response->assertNextMiddlewareCalled();

        $this->assertTrue($this->receivedRequest()->getAttribute(StatefulRequest::ALLOW_WRITE_SESSION_FOR_READ_VERBS));
    }

    /**
     * @test
     */
    public function test_exception_if_session_already_set(): void
    {
        $middleware = new AllowMutableSessionForReadVerbs();

        $request = $this->frontendRequest()->withAttribute(ImmutableSession::class, 'irrelevant');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("A session has already been set on the request.\nMake sure that ");

        $this->runMiddleware($middleware, $request)->assertNextMiddlewareCalled();
    }
}
