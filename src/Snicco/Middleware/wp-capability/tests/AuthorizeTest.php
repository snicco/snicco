<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPCap\Tests;

use Closure;
use RuntimeException;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Middleware\WPCap\Authorize;

use function array_values;

class AuthorizeTest extends MiddlewareTestCase
{
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = $this->frontendRequest('/foo');
    }

    /**
     * @test
     */
    public function a_user_with_given_capabilities_can_access_the_route(): void
    {
        $wp = new AuthorizeTestBetterWPAPI(function (string $cap) {
            if ($cap !== 'manage_options') {
                throw new RuntimeException('Wrong cap passed');
            }
            return true;
        });

        $m = $this->newMiddleware($wp, 'manage_options');

        $response = $this->runMiddleware($m, $this->request);

        $response->assertNextMiddlewareCalled();
    }

    /**
     * @test
     */
    public function a_user_without_authorisation_to_the_route_will_throw_an_exception(): void
    {
        $wp = new AuthorizeTestBetterWPAPI(function (string $cap) {
            if ($cap !== 'manage_options') {
                throw new RuntimeException('Wrong cap passed');
            }
            return false;
        });

        $m = $this->newMiddleware($wp, 'manage_options');

        try {
            $this->runMiddleware($m, $this->request);
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
        $wp = new AuthorizeTestBetterWPAPI(function (string $cap, int $resource_id) {
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
            $this->runMiddleware($m, $this->request);
            $this->fail('An Exception should have been thrown.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->statusCode());
            $this->assertSame(
                'Authorization failed for path [/foo] with required capability [manage_options].',
                $e->getMessage()
            );
        }
    }

    private function newMiddleware(BetterWPAPI $wp, string $cap, ?int $id = null): Authorize
    {
        return new Authorize($cap, $id, $wp);
    }
}

class AuthorizeTestBetterWPAPI extends BetterWPAPI
{
    /**
     * @var Closure(string, mixed...):bool
     */
    private Closure $user_can;

    /**
     * @param Closure(string, mixed...):bool $user_can
     */
    public function __construct(Closure $user_can)
    {
        $this->user_can = $user_can;
    }

    public function currentUserCan(string $capability, ...$args): bool
    {
        return call_user_func($this->user_can, $capability, ...array_values($args));
    }
}
