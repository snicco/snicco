<?php

declare(strict_types=1);


namespace Snicco\Middleware\WPNonce\Tests;

use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Middleware\WPNonce\VerifyWPNonce;
use Snicco\Middleware\WPNonce\WPNonce;

final class VerifyWPNonceTest extends MiddlewareTestCase
{
    /**
     * @test
     */
    public function test_with_valid_nonce_for_same_url(): void
    {
        $middleware = new VerifyWPNonce($wp = new VerifyNonceTestWPApi());

        $request = $this->frontendRequest('/foo', [], 'POST')->withParsedBody([
            VerifyWPNonce::inputKey() => $wp->createNonce('/foo')
        ]);

        $response = $this->runMiddleware($middleware, $request);
        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()->assertOk();
    }

    /**
     * @test
     */
    public function test_with_invalid_nonce_for_same_url_throws_exception(): void
    {
        $middleware = new VerifyWPNonce($wp = new VerifyNonceTestWPApi());

        $request = $this->frontendRequest('/foo', [], 'POST')->withParsedBody([
            VerifyWPNonce::inputKey() => $wp->createNonce('/bar')
        ]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Nonce check failed for request path [/foo]');

        $this->runMiddleware($middleware, $request);
    }

    /**
     * @test
     */
    public function the_nonce_generator_is_added_for_view_responses(): void
    {
        $middleware = new VerifyWPNonce(new VerifyNonceTestWPApi());

        $this->withNextMiddlewareResponse(function (Response $response) {
            return new ViewResponse('foo', $response);
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runMiddleware($middleware, $request);
        $response->assertNextMiddlewareCalled();

        $psr = $response->assertableResponse()->getPsrResponse();
        $this->assertInstanceOf(ViewResponse::class, $psr);

        $data = $psr->viewData();
        $this->assertTrue(isset($data['wp_nonce']));
        $nonce = $data['wp_nonce'];
        $this->assertInstanceOf(WPNonce::class, $nonce);
    }

    /**
     * @test
     */
    public function test_wp_nonce_with_no_arguments(): void
    {
        $middleware = new VerifyWPNonce(new VerifyNonceTestWPApi());

        $this->withNextMiddlewareResponse(function (Response $response) {
            return new ViewResponse('foo', $response);
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runMiddleware($middleware, $request);
        $psr = $response->assertableResponse()->getPsrResponse();
        $this->assertInstanceOf(ViewResponse::class, $psr);

        $data = $psr->viewData();
        $this->assertTrue(isset($data['wp_nonce']));
        $wp_nonce = $data['wp_nonce'];
        $this->assertInstanceOf(WPNonce::class, $wp_nonce);

        $nonce = $wp_nonce();

        $key = VerifyWPNonce::inputKey();

        $this->assertStringStartsWith("<input type='hidden'", $nonce);
        $this->assertStringContainsString($key, $nonce);
        $this->assertStringContainsString("value='nonce./foo'", $nonce);

        $request = $this->frontendRequest('/foo');

        $response = $this->runMiddleware(
            $middleware,
            $request->withMethod('POST')->withParsedBody([
                VerifyWPNonce::inputKey() => 'nonce./foo'
            ])
        );

        $response->assertNextMiddlewareCalled()->assertableResponse()->assertOk();
    }

    /**
     * @test
     */
    public function test_wp_nonce_with_route_name(): void
    {
        $this->withRoutes([
            Route::create('/foo/{param}', Route::DELEGATE, 'foo_route')
        ]);

        $middleware = new VerifyWPNonce(new VerifyNonceTestWPApi());

        $this->withNextMiddlewareResponse(function (Response $response) {
            return new ViewResponse('foo', $response);
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runMiddleware($middleware, $request);
        $psr = $response->assertableResponse()->getPsrResponse();
        $this->assertInstanceOf(ViewResponse::class, $psr);

        $data = $psr->viewData();
        $this->assertTrue(isset($data['wp_nonce']));
        $wp_nonce = $data['wp_nonce'];
        $this->assertInstanceOf(WPNonce::class, $wp_nonce);

        $nonce = $wp_nonce('foo_route', ['param' => 'bar']);

        $key = VerifyWPNonce::inputKey();

        $this->assertStringStartsWith("<input type='hidden'", $nonce);
        $this->assertStringContainsString($key, $nonce);
        $this->assertStringContainsString("value='nonce./foo/bar'", $nonce);

        $request = $this->frontendRequest('/foo/bar');

        $response = $this->runMiddleware(
            $middleware,
            $request->withMethod('POST')->withParsedBody([
                VerifyWPNonce::inputKey() => 'nonce./foo/bar'
            ])
        );

        $response->assertNextMiddlewareCalled()->assertableResponse()->assertOk();
    }

    /**
     * @test
     */
    public function test_wp_nonce_with_argument_that_is_not_a_route_name(): void
    {
        $middleware = new VerifyWPNonce(new VerifyNonceTestWPApi());

        $this->withNextMiddlewareResponse(function (Response $response) {
            return new ViewResponse('foo', $response);
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runMiddleware($middleware, $request);
        $psr = $response->assertableResponse()->getPsrResponse();
        $this->assertInstanceOf(ViewResponse::class, $psr);

        $data = $psr->viewData();
        $this->assertTrue(isset($data['wp_nonce']));
        $wp_nonce = $data['wp_nonce'];
        $this->assertInstanceOf(WPNonce::class, $wp_nonce);

        $nonce = $wp_nonce('foo_route');

        $key = VerifyWPNonce::inputKey();

        $this->assertStringStartsWith("<input type='hidden'", $nonce);
        $this->assertStringContainsString($key, $nonce);
        $this->assertStringContainsString("value='nonce./foo_route'", $nonce);

        $request = $this->frontendRequest('/foo_route');

        $response = $this->runMiddleware(
            $middleware,
            $request->withMethod('POST')->withParsedBody([
                VerifyWPNonce::inputKey() => 'nonce./foo_route'
            ])
        );

        $response->assertNextMiddlewareCalled()->assertableResponse()->assertOk();
    }

    /**
     * @test
     */
    public function test_does_nothing_for_read_request_that_is_not_a_view_response(): void
    {
        $middleware = new VerifyWPNonce(new VerifyNonceTestWPApi());

        $this->withNextMiddlewareResponse(function (Response $response) {
            return $response;
        });

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));
        $response->assertNextMiddlewareCalled()->assertableResponse()->assertOk();
    }
}

class VerifyNonceTestWPApi extends BetterWPAPI
{
    public function verifyNonce(string $nonce, string $action): bool
    {
        return $nonce === $this->createNonce($action);
    }

    public function createNonce(string $form_action): string
    {
        return "nonce.$form_action";
    }
}
