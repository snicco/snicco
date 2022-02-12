<?php

declare(strict_types=1);

namespace Snicco\Middleware\OpenRedirectProtection\Tests;

use InvalidArgumentException;
use Snicco\Component\HttpRouting\Controller\RedirectController;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Testing\AssertableResponse;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Middleware\OpenRedirectProtection\OpenRedirectProtection;

class OpenRedirectProtectionTest extends MiddlewareTestCase
{

    protected function setUp(): void
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

    /**
     * @test
     */
    public function non_redirect_responses_are_always_allowed(): void
    {
        $request = $this->frontendRequest();

        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertOk();
    }

    /**
     * @test
     */
    public function a_redirect_response_is_allowed_if_its_relative(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getRedirector()->to('foo');
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertRedirect('/foo', 302);
    }

    /**
     * @test
     */
    public function a_redirect_response_is_allowed_if_its_absolute_and_to_the_same_host(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->redirect('https://foo.com/bar');
        });

        $request = $this->frontendRequest('https://foo.com/foo');

        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertRedirect('https://foo.com/bar', 302);
    }

    /**
     * @test
     */
    public function absolute_redirects_to_other_hosts_are_not_allowed(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->redirect('https://bar.com/foo');
        });

        $request = $this->frontendRequest('https://foo.com/foo');
        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response->psr(), 'https://bar.com/foo');
    }

    /**
     * @test
     */
    public function a_network_path_url_is_not_allowed(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->redirect('//bar.com:80/path/info');
        });

        $request = $this->frontendRequest('https://foo.com/foo');
        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response->psr(), '//bar.com:80/path/info');
    }

    /**
     * @test
     */
    public function hosts_can_be_whitelisted_if_the_referer_is_the_same_site(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->redirect('https://stripe.com/foo');
        });

        $request = $this->frontendRequest('https://foo.com/foo');
        $response = $this->runMiddleware($this->newMiddleware(['stripe.com']), $request);

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertRedirect('https://stripe.com/foo');
    }

    /**
     * @test
     */
    public function a_redirect_response_is_forbidden_if_its_to_a_non_white_listed_host(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->redirect('https://paypal.com/pay');
        });

        $request = $this->frontendRequest('https://foo.com/foo');

        $response = $this->runMiddleware($this->newMiddleware(['stripe.com']), $request);

        $response->assertNextMiddlewareCalled();
        $this->assertForbiddenRedirect($response->psr(), 'https://paypal.com/pay');
    }

    /**
     * @test
     */
    public function subdomains_can_be_whitelisted_with_regex(): void
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

    /**
     * @test
     */
    public function redirects_to_same_site_subdomains_are_allowed(): void
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

    /**
     * @test
     */
    public function all_protection_can_be_bypassed_if_using_the_away_method(): void
    {
        $this->withNextMiddlewareResponse(function () {
            $target = 'https://external-site.com';

            return $this->getRedirector()->away($target);
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->psr()->assertRedirect('https://external-site.com');
    }

    /**
     * @test
     */
    public function if_the_route_does_not_exist_the_user_is_redirect_to_the_homepage(): void
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

    /**
     * @test
     */
    public function an_exception_is_thrown_for_an_invalid_host(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid host [bogus].');
        new OpenRedirectProtection('bogus');
    }

    /**
     * @param string[] $whitelist
     */
    private function newMiddleware(array $whitelist = []): OpenRedirectProtection
    {
        return new OpenRedirectProtection('https://foo.com', $whitelist);
    }

    private function assertForbiddenRedirect(AssertableResponse $response, string $intended): void
    {
        $this->assertStringStartsWith(
            '/redirect/exit',
            $response->getPsrResponse()->getHeaderLine('Location')
        );
        $this->assertStringContainsString(
            '?intended_redirect=' . $intended,
            $response->getPsrResponse()->getHeaderLine('Location')
        );
    }

}

