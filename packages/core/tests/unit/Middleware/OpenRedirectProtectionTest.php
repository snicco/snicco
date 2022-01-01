<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Mockery;
use Snicco\Core\Support\WP;
use Snicco\Core\Routing\Route;
use Snicco\Testing\TestResponse;
use Tests\Core\MiddlewareTestCase;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Controllers\RedirectAbstractController;
use Snicco\Core\Middleware\Core\OpenRedirectProtection;
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
            return $this->redirector()->to('foo');
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
            return $this->response_factory->redirect('https://foo.com/bar');
        });
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertRedirect('https://foo.com/bar', 302);
    }
    
    /** @test */
    public function absolute_redirects_to_other_hosts_are_not_allowed()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect('https://bar.com/foo');
        });
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo');
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response, 'https://bar.com/foo');
    }
    
    /** @test */
    public function a_network_path_url_is_not_allowed()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect('//bar.com:80/path/info');
        });
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo');
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response, '//bar.com:80/path/info');
    }
    
    /** @test */
    public function hosts_can_be_whitelisted_if_the_referer_is_the_same_site()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect('https://stripe.com/foo');
        });
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo');
        $response = $this->runMiddleware($this->newMiddleware(['stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertRedirect('https://stripe.com/foo');
    }
    
    /** @test */
    public function a_redirect_response_is_forbidden_if_its_to_a_non_white_listed_host()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect('https://paypal.com/pay');
        });
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(['stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response, 'https://paypal.com/pay');
    }
    
    /** @test */
    public function subdomains_can_be_whitelisted_with_regex()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect(
                'https://payments.stripe.com/foo'
            );
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $response = $this->runMiddleware($this->newMiddleware(['*.stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertRedirect('https://payments.stripe.com/foo');
        
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->redirect(
                'https://accounts.stripe.com/foo'
            );
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $response = $this->runMiddleware($this->newMiddleware(['*.stripe.com']), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertRedirect('https://accounts.stripe.com/foo');
    }
    
    /** @test */
    public function redirects_to_same_site_subdomains_are_allowed()
    {
        $this->setNextMiddlewareResponse(function () {
            $target = 'https://accounts.foo.com/foo';
            
            return $this->response_factory->redirect($target);
        });
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertRedirect('https://accounts.foo.com/foo');
    }
    
    /** @test */
    public function all_protection_can_be_bypassed_if_using_the_away_method()
    {
        $this->setNextMiddlewareResponse(function () {
            $target = 'https://external-site.com';
            
            return $this->redirector()->away($target);
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertRedirect('https://external-site.com');
    }
    
    private function newMiddleware($whitelist = []) :OpenRedirectProtection
    {
        $route = new Route(['GET'], '/redirect/exit', [RedirectAbstractController::class, 'exit']);
        $route->name('redirect.protection');
        $this->routes->add($route);
        $this->routes->addToUrlMatcher();
        
        return new OpenRedirectProtection('https://foo.com', $whitelist);
    }
    
    private function assertForbiddenRedirect(TestResponse $response, string $intended)
    {
        $this->assertStringStartsWith('/redirect/exit', $response->getHeaderLine('Location'));
        $this->assertStringContainsString(
            '?intended_redirect='.$intended,
            $response->getHeaderLine('Location')
        );
    }
    
}

