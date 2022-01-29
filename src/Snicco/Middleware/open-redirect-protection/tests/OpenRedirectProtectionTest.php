<?php

declare(strict_types=1);

namespace Snicco\Middleware\OpenRedirectProtection\Tests;

use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Testing\AssertableResponse;
use Snicco\Component\HttpRouting\Tests\InternalMiddlewareTestCase;
use Snicco\Middleware\OpenRedirectProtection\OpenRedirectProtection;
use Snicco\Component\HttpRouting\Routing\Controller\RedirectController;

class OpenRedirectProtectionTest extends InternalMiddlewareTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $route = Route::create(
            '/redirect/exit',
            [RedirectController::class, 'exit'],
            'redirect.protection',
            ['GET']
        );
        $this->withRoutes([$route]);
    }
    
    /** @test */
    public function non_redirect_responses_are_always_allowed()
    {
        $request = $this->frontendRequest();
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertOk();
    }
    
    /** @test */
    public function a_redirect_response_is_allowed_if_its_relative()
    {
        $this->withNextMiddlewareResponse(function (Response $response) {
            return $this->getRedirector()->to('foo');
        });
        
        $request = $this->frontendRequest('/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertRedirect('/foo', 302);
    }
    
    /** @test */
    public function a_redirect_response_is_allowed_if_its_absolute_and_to_the_same_host()
    {
        $this->withNextMiddlewareResponse(function (Response $response) {
            return $this->getResponseFactory()->redirect('https://foo.com/bar');
        });
        
        $request = $this->frontendRequest('https://foo.com/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertRedirect('https://foo.com/bar', 302);
    }
    
    /** @test */
    public function absolute_redirects_to_other_hosts_are_not_allowed()
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->redirect('https://bar.com/foo');
        });
        
        $request = $this->frontendRequest('https://foo.com/foo');
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response->psr(), 'https://bar.com/foo');
    }
    
    /** @test */
    public function a_network_path_url_is_not_allowed()
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->redirect('//bar.com:80/path/info');
        });
        
        $request = $this->frontendRequest('https://foo.com/foo');
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response->psr(), '//bar.com:80/path/info');
    }
    
    /** @test */
    public function hosts_can_be_whitelisted_if_the_referer_is_the_same_site()
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->redirect('https://stripe.com/foo');
        });
        
        $request = $this->frontendRequest('https://foo.com/foo');
        $response = $this->runMiddleware($this->newMiddleware(['stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertRedirect('https://stripe.com/foo');
    }
    
    /** @test */
    public function a_redirect_response_is_forbidden_if_its_to_a_non_white_listed_host()
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->redirect('https://paypal.com/pay');
        });
        
        $request = $this->frontendRequest('https://foo.com/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(['stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response->psr(), 'https://paypal.com/pay');
    }
    
    /** @test */
    public function subdomains_can_be_whitelisted_with_regex()
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->redirect(
                'https://payments.stripe.com/foo'
            );
        });
        
        $request = $this->frontendRequest('/foo');
        $response = $this->runMiddleware($this->newMiddleware(['*.stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertRedirect('https://payments.stripe.com/foo');
        
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->redirect(
                'https://accounts.stripe.com/foo'
            );
        });
        
        $request = $this->frontendRequest('/foo');
        $response = $this->runMiddleware($this->newMiddleware(['*.stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertRedirect('https://accounts.stripe.com/foo');
    }
    
    /** @test */
    public function redirects_to_same_site_subdomains_are_allowed()
    {
        $this->withNextMiddlewareResponse(function () {
            $target = 'https://accounts.foo.com/foo';
            
            return $this->getResponseFactory()->redirect($target);
        });
        
        $request = $this->frontendRequest('https://foo.com/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertRedirect('https://accounts.foo.com/foo');
    }
    
    /** @test */
    public function all_protection_can_be_bypassed_if_using_the_away_method()
    {
        $this->withNextMiddlewareResponse(function () {
            $target = 'https://external-site.com';
            
            return $this->getRedirector()->away($target);
        });
        
        $request = $this->frontendRequest('/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->psr()->assertRedirect('https://external-site.com');
    }
    
    /** @test */
    public function if_the_route_does_not_exist_the_user_is_redirect_to_the_homepage()
    {
        $this->withRoutes([]);
        
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->redirect('https://paypal.com/pay');
        });
        
        $request = $this->frontendRequest('https://foo.com/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(['stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertRedirect('/?intended_redirect=https://paypal.com/pay');
    }
    
    private function newMiddleware($whitelist = []) :OpenRedirectProtection
    {
        return new OpenRedirectProtection('https://foo.com', $whitelist);
    }
    
    private function assertForbiddenRedirect(AssertableResponse $response, string $intended)
    {
        $this->assertStringStartsWith(
            '/redirect/exit',
            $response->getPsrResponse()->getHeaderLine('Location')
        );
        $this->assertStringContainsString(
            '?intended_redirect='.$intended,
            $response->getPsrResponse()->getHeaderLine('Location')
        );
    }
    
}

