<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPCap\Tests;

use Closure;
use RuntimeException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\ScopableWP\ScopableWP;
use Snicco\Middleware\WPCap\Authorize;

use function array_merge;
use function call_user_func_array;

class AuthorizeTest extends MiddlewareTestCase
{

    private Request $request;

    /**
     * @test
     */
    public function a_user_with_given_capabilities_can_access_the_route(): void
    {
        $wp = new AuthorizeTestScopableWp(function (string $cap) {
            if ($cap !== 'manage_options') {
                throw new RuntimeException('Wrong cap passed');
            }
            return true;
        });

        $m = $this->newMiddleware($wp, 'manage_options');

        $response = $this->runMiddleware($m, $this->request);

        $response->assertNextMiddlewareCalled();
    }

    private function newMiddleware(ScopableWP $wp, string $cap, $id = null): Authorize
    {
        return new Authorize($wp, $cap, $id);
    }

    /**
     * @test
     */
    public function a_user_without_authorisation_to_the_route_will_throw_an_exception(): void
    {
        $wp = new AuthorizeTestScopableWp(function (string $cap) {
            if ($cap !== 'manage_options') {
                throw new RuntimeException('Wrong cap passed');
            }
            return false;
        });

        $m = $this->newMiddleware($wp, 'manage_options');

        try {
            $response = $this->runMiddleware($m, $this->request);
            $this->fail('An Exception should have been thrown.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->statusCode());
            $this->assertSame(
                'Authorization failed for path [/foo] with required capability [manage_options].',
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_user_can_be_authorized_against_a_resource(): void
    {
        $wp = new AuthorizeTestScopableWp(function (string $cap, int $resource_id) {
            if ($cap !== 'manage_options') {
                throw new RuntimeException('Wrong cap passed');
            }
            return $resource_id === 1;
        });

        $m = $this->newMiddleware($wp, 'manage_options', 1);

        $response = $this->runMiddleware($m, $this->request);
        $response->assertNextMiddlewareCalled();

        $m = $this->newMiddleware($wp, 'manage_options', 10);

        try {
            $response = $this->runMiddleware($m, $this->request);
            $this->fail('An Exception should have been thrown.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->statusCode());
            $this->assertSame(
                'Authorization failed for path [/foo] with required capability [manage_options].',
                $e->getMessage()
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = $this->frontendRequest('/foo');
    }

}

class AuthorizeTestScopableWp extends ScopableWP
{

    private Closure $user_can;

    public function __construct(Closure $user_can)
    {
        $this->user_can = $user_can;
    }

    public function currentUserCan(string $capability, ...$args): bool
    {
        return call_user_func_array($this->user_can, array_merge([$capability], $args));
    }

}


