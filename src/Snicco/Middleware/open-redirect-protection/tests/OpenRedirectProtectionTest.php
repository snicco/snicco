<?php

declare(strict_types=1);

namespace Snicco\Middleware\OpenRedirectProtection\Tests;

use InvalidArgumentException;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Middleware\OpenRedirectProtection\OpenRedirectProtection;

class OpenRedirectProtectionTest extends MiddlewareTestCase
{

    /**
     * @test
     */
    public function non_redirect_responses_are_always_allowed(): void
    {
        $request = $this->frontendRequest();

        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()->assertOk();
    }

    /**
     * @test
     */
    public function a_redirect_response_is_allowed_if_its_relative(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->responseUtils()->redirectTo('foo');
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()->assertRedirect('/foo', 302);
    }

    /**
     * @test
     */
    public function a_redirect_response_is_allowed_if_its_absolute_and_to_the_same_host(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->responseFactory()->redirect('https://foo.com/bar');
        });

        $request = $this->frontendRequest('https://foo.com/foo');

        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()->assertRedirect('https://foo.com/bar', 302);
    }

    /**
     * @test
     */
    public function absolute_redirects_to_other_hosts_are_not_allowed(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->responseFactory()->redirect('https://bar.com/foo');
        });

        $request = $this->frontendRequest('https://foo.com/foo');
        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()->assertLocation('/exit?intended_redirect=https://bar.com/foo');
    }

    /**
     * @test
     */
    public function a_network_path_url_is_not_allowed(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->responseFactory()->redirect('//bar.com:80/path/info');
        });

        $request = $this->frontendRequest('https://foo.com/foo');
        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()->assertLocation('/exit?intended_redirect=//bar.com:80/path/info');
    }

    /**
     * @test
     */
    public function hosts_can_be_whitelisted_if_the_referer_is_the_same_site(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->responseFactory()->redirect('https://stripe.com/foo');
        });

        $request = $this->frontendRequest('https://foo.com/foo');
        $response = $this->runMiddleware($this->newMiddleware(['stripe.com']), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()->assertRedirect('https://stripe.com/foo');
    }

    /**
     * @test
     */
    public function a_redirect_response_is_forbidden_if_its_to_a_non_white_listed_host(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->responseFactory()->redirect('https://paypal.com/pay');
        });

        $request = $this->frontendRequest('https://foo.com/foo');

        $response = $this->runMiddleware($this->newMiddleware(['stripe.com']), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()->assertLocation('/exit?intended_redirect=https://paypal.com/pay');
    }

    /**
     * @test
     */
    public function subdomains_can_be_whitelisted_with_regex(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->responseFactory()->redirect(
                'https://payments.stripe.com/foo'
            );
        });

        $request = $this->frontendRequest('/foo');
        $response = $this->runMiddleware($this->newMiddleware(['*.stripe.com']), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()->assertRedirect('https://payments.stripe.com/foo');

        $this->withNextMiddlewareResponse(function () {
            return $this->responseFactory()->redirect(
                'https://accounts.stripe.com/foo'
            );
        });

        $request = $this->frontendRequest('/foo');
        $response = $this->runMiddleware($this->newMiddleware(['*.stripe.com']), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()->assertRedirect('https://accounts.stripe.com/foo');
    }

    /**
     * @test
     */
    public function redirects_to_same_site_subdomains_are_allowed(): void
    {
        $this->withNextMiddlewareResponse(function () {
            $target = 'https://accounts.foo.com/foo';

            return $this->responseFactory()->redirect($target);
        });

        $request = $this->frontendRequest('https://foo.com/foo');

        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()->assertRedirect('https://accounts.foo.com/foo');
    }

    /**
     * @test
     */
    public function all_protection_can_be_bypassed_if_using_the_away_method(): void
    {
        $this->withNextMiddlewareResponse(function () {
            $target = 'https://external-site.com';

            return $this->responseUtils()->externalRedirect($target);
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runMiddleware($this->newMiddleware(), $request);

        $response->assertableResponse()->assertRedirect('https://external-site.com');
    }

    /**
     * @test
     */
    public function the_exit_page_path_can_be_customized(): void
    {
        $this->withRoutes([]);

        $this->withNextMiddlewareResponse(function () {
            return $this->responseFactory()->redirect('https://paypal.com/pay');
        });

        $request = $this->frontendRequest('https://foo.com/foo');

        $response = $this->runMiddleware($this->newMiddleware(['stripe.com'], '/exit_page/'), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()->assertRedirect('/exit_page/?intended_redirect=https://paypal.com/pay');
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_for_an_invalid_host(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid host [bogus].');
        new OpenRedirectProtection('bogus', '/exit');
    }

    /**
     * @param string[] $whitelist
     */
    private function newMiddleware(array $whitelist = [], string $exit_path = '/exit'): OpenRedirectProtection
    {
        return new OpenRedirectProtection('https://foo.com', $exit_path, $whitelist);
    }

}

