<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Closure;
use Webmozart\Assert\Assert;
use Tests\Core\MiddlewareTestCase;
use Snicco\Core\Middleware\Authorize;
use Snicco\Core\ExceptionHandling\Exceptions\AuthorizationException;

class AuthorizeTest extends MiddlewareTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->request = $this->frontendRequest('GET', '/foo');
    }
    
    /** @test */
    public function a_user_with_given_capabilities_can_access_the_route()
    {
        $grant_access = function (string $cap, array $args) {
            return true;
        };
        
        $m = $this->newMiddleware($grant_access, 'manage_options');
        
        $response = $this->runMiddleware($m, $this->request);
        
        $response->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function a_user_without_authorisation_to_the_route_will_throw_an_exception()
    {
        $grant_access = function (string $cap, array $args) {
            return false;
        };
        
        $m = $this->newMiddleware($grant_access, 'manage_options');
        
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage(
            "Authorization failed for path [/foo] with required capability [manage_options]."
        );
        $response = $this->runMiddleware($m, $this->request);
        $response->assertNextMiddlewareNotCalled();
    }
    
    /** @test */
    public function the_user_can_be_authorized_against_a_resource()
    {
        $grant_access = function (string $cap, array $args) {
            Assert::same(1, $args[0]);
            return true;
        };
        
        $m = $this->newMiddleware($grant_access, 'manage_options', 1);
        
        $response = $this->runMiddleware($m, $this->request);
        $response->assertNextMiddlewareCalled();
        
        $grant_access = function (string $cap, array $args) {
            Assert::same(10, $args[0]);
            return false;
        };
        
        $m = $this->newMiddleware($grant_access, 'manage_options', 10);
        
        $this->expectException(AuthorizationException::class);
        $response = $this->runMiddleware($m, $this->request);
        $response->assertNextMiddlewareCalled();
    }
    
    private function newMiddleware(Closure $grant_access, string $capability, int $object_id = null) :Authorize
    {
        return new Authorize($grant_access, $capability, $object_id);
    }
    
}
