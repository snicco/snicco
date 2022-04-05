<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPAuth\Tests;

use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Middleware\WPAuth\AuthenticateWPUser;

/**
 * @internal
 */
final class AuthenticateTest extends MiddlewareTestCase
{
    /**
     * @test
     */
    public function logged_in_users_can_access_the_route(): void
    {
        $middleware = new AuthenticateWPUser(new WPAPI(true));

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->assertNextMiddlewareCalled();
    }

    /**
     * @test
     */
    public function logged_out_users_cant_access_the_route(): void
    {
        $request = $this->frontendRequest('https://mysite.com/foo');

        $middleware = new AuthenticateWPUser(new WPAPI(false));

        try {
            $this->runMiddleware($middleware, $request);
            $this->fail('An exception should have been thrown');
        } catch (HttpException $e) {
            $this->assertSame(401, $e->statusCode());
            $this->assertSame('Missing authentication for request path [/foo].', $e->getMessage());
        }
    }
}

final class WPAPI extends BetterWPAPI
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

    public function currentUserId(): int
    {
        return $this->is_logged_in ? 1 : 0;
    }
}
