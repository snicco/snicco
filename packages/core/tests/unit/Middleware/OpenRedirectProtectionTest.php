<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Mockery;
use Snicco\Support\WP;
use Snicco\Routing\Route;
use Snicco\Support\Carbon;
use Snicco\Http\Psr7\Response;
use Snicco\Testing\TestResponse;
use Tests\Core\MiddlewareTestCase;
use Snicco\Controllers\RedirectController;
use Snicco\Middleware\Core\OpenRedirectProtection;
use Tests\Codeception\shared\helpers\CreateDefaultWpApiMocks;

class OpenRedirectProtectionTest extends MiddlewareTestCase
{
    
    use CreateDefaultWpApiMocks;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->createDefaultWpApiMocks();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        
        WP::reset();
        Mockery::close();
    }
    
    /** @test */
    public function non_redirect_responses_are_always_allowed()
    {
        $request = $this->frontendRequest();
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertOk();
    }
    
    /** @test */
    public function a_redirect_response_is_allowed_if_its_relative()
    {
        $this->setNextMiddlewareResponse(function (Response $response) {
            return $this->response_factory->redirect()->to('foo');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertRedirect('/foo', 302);
    }
    
    /** @test */
    public function a_redirect_response_is_allowed_if_its_absolute_and_to_the_same_host()
    {
        $this->setNextMiddlewareResponse(function (Response $response) {
            return $this->response_factory->redirect()->absoluteRedirect('/bar');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertRedirect('https://example.com/bar', 302);
    }
    
    /** @test */
    public function a_redirect_response_is_forbidden_if_its_to_a_non_white_listed_host()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect()->absoluteRedirect('https://paypal.com');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(['stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response, 'https://paypal.com');
    }
    
    /** @test */
    public function an_url_with_double_leading_slash_is_not_allowed()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect()->absoluteRedirect('//foo.com:80/path/info');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response, 'foo.com:80/path/info');
    }
    
    /** @test */
    public function absolute_redirects_to_other_hosts_are_not_allowed()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect()->absoluteRedirect('https://foo.com/foo');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response, 'https://foo.com/foo');
    }
    
    /** @test */
    public function hosts_can_be_whitelisted()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect()->absoluteRedirect('https://stripe.com/foo');
        });
        
        $request =
            $this->frontendRequest('GET', '/foo')->withHeader('referer', 'https://example.com');
        $response = $this->runMiddleware($this->newMiddleware(['stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertRedirect('https://stripe.com/foo');
    }
    
    /** @test */
    public function subdomains_can_be_whitelisted_with_regex()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect()->absoluteRedirect(
                'https://payments.stripe.com/foo'
            );
        });
        
        $request =
            $this->frontendRequest('GET', '/foo')->withHeader('referer', 'https://example.com');
        $response = $this->runMiddleware($this->newMiddleware(['*.stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertRedirect('https://payments.stripe.com/foo');
        
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect()->absoluteRedirect(
                'https://accounts.stripe.com/foo'
            );
        });
        
        $request =
            $this->frontendRequest('GET', '/foo')->withHeader('referer', 'https://example.com');
        $response = $this->runMiddleware($this->newMiddleware(['*.stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertRedirect('https://accounts.stripe.com/foo');
    }
    
    /** @test */
    public function redirects_to_external_domains_are_not_allowed_if_not_coming_from_the_same_referer()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect()->absoluteRedirect('https://stripe.com/foo');
        });
        
        $request =
            $this->frontendRequest('GET', '/foo')->withHeader('referer', 'https://evil.com');
        $response = $this->runMiddleware($this->newMiddleware(['stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response, 'https://stripe.com/foo');
    }
    
    /** @test */
    public function redirects_to_same_domain_paths_are_allowed_from_external_referer()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect()->to('/foo');
        });
        
        $request =
            $this->frontendRequest('GET', '/foo')->withHeader('referer', 'https://stripe.com');
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertRedirect('/foo');
    }
    
    /** @test */
    public function redirects_to_same_site_subdomains_are_allowed()
    {
        $this->setNextMiddlewareResponse(function () {
            $target = 'https://accounts.example.com/foo';
            
            return $this->response_factory->redirect()
                                          ->absoluteRedirect($target);
        });
        
        $request = $this->frontendRequest('GET', '/foo')
                        ->withHeader('referer', 'https://example.com');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertRedirect('https://accounts.example.com/foo');
    }
    
    /** @test */
    public function redirects_to_same_site_subdomains_are_forbidden_for_different_refs()
    {
        $this->setNextMiddlewareResponse(function () {
            $target = 'https://accounts.example.com/foo';
            
            return $this->response_factory->redirect()
                                          ->absoluteRedirect($target);
        });
        
        $request = $this->frontendRequest('GET', '/foo')
                        ->withHeader('referer', 'https://evil.com');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response, 'https://accounts.example.com/foo');
    }
    
    /** @test */
    public function all_protection_can_be_bypassed_if_using_the_away_method()
    {
        $this->setNextMiddlewareResponse(function () {
            $target = 'https://external-site.com';
            
            return $this->response_factory->redirect()->away($target);
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertRedirect('https://external-site.com');
    }
    
    private function newMiddleware($whitelist = []) :OpenRedirectProtection
    {
        $route = new Route(['GET'], '/redirect/exit', [RedirectController::class, 'exit']);
        $route->name('redirect.protection');
        $this->routes->add($route);
        $this->routes->addToUrlMatcher();
        
        return new OpenRedirectProtection('https://example.com', $whitelist);
    }
    
    private function assertForbiddenRedirect(TestResponse $response, string $intended)
    {
        $intended = urlencode($intended);
        
        $this->assertStringStartsWith('/redirect/exit', $response->getHeaderLine('Location'));
        $this->assertStringContainsString(
            '&intended_redirect='.$intended,
            $response->getHeaderLine('Location')
        );
        $this->assertStringContainsString(
            '?expires='.Carbon::now()->addSeconds(10)->getTimestamp(),
            $response->getHeaderLine('Location')
        );
    }
    
}

