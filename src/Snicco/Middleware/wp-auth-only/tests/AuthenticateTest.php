<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPAuth\Tests;

use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\ScopableWP\ScopableWP;
use Snicco\Middleware\WPAuth\Authenticate;

class AuthenticateTest extends MiddlewareTestCase
{

    /**
     * @test
     */
    public function logged_in_users_can_access_the_route(): void
    {
        $middleware = new Authenticate(new WPTestDouble(true));

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->assertNextMiddlewareCalled();
    }

    /**
     * @test
     */
    public function the_user_id_is_added_to_the_request(): void
    {
        $middleware = new Authenticate(new WPTestDouble(true));

        $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertSame(1, $this->receivedRequest()->getAttribute('_user_id'));
    }

    /**
     * @test
     */
    public function logged_out_users_cant_access_the_route(): void
    {
        $request = $this->frontendRequest('https://mysite.com/foo');

        $middleware = new Authenticate(new WPTestDouble(false));

        try {
            $this->runMiddleware(
                $middleware,
                $request
            );
            $this->fail('An exception should have been thrown');
        } catch (HttpException $e) {
            $this->assertSame(401, $e->statusCode());
            $this->assertSame('Missing authentication for request path [/foo].', $e->getMessage());
        }
    }

}

class WPTestDouble extends ScopableWP
{

    private bool $is_logged_in;

    public function __construct(bool $is_logged_in)
    {
        $this->is_logged_in = $is_logged_in;
    }

    public function isUserLoggedIn(): bool
    {
        return $this->is_logged_in;
    }

    public function getCurrentUserId(): int
    {
        return $this->is_logged_in ? 1 : 0;
    }

}
