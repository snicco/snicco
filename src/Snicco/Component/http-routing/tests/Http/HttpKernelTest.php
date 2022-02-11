<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use LogicException;
use Snicco\Component\HttpRouting\Http\MethodOverride;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

class HttpKernelTest extends HttpRunnerTestCase
{

    /**
     * @test
     */
    public function the_kernel_throws_an_exception_if_a_request_has_no_type_specified(): void
    {
        $psr = $this->psrServerRequestFactory()->createServerRequest('GET', '/foo');
        $request = new Request($psr);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The HttpKernel tried to handle a request without a declared type. This is not allowed.'
        );

        $this->runKernel($request);
    }

    /**
     * @test
     */
    public function a_delegate_response_is_returned_by_default_if_no_route_matches(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator) {
            $configurator->get('r1', '/foo', RoutingTestController::class);
        });
        $test_response = $this->runKernel($this->frontendRequest('/bar'));

        $this->assertInstanceOf(DelegatedResponse::class, $test_response->getPsrResponse());
    }

    /**
     * @test
     */
    public function a_normal_response_will_be_returned_for_matching_routes(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator) {
            $configurator->get('r1', '/foo', RoutingTestController::class);
        });

        $test_response = $this->runKernel($this->frontendRequest('/foo'));

        $this->assertNotInstanceOf(DelegatedResponse::class, $test_response->getPsrResponse());
        $this->assertInstanceOf(Response::class, $test_response->getPsrResponse());

        $this->assertSame(RoutingTestController::static, $test_response->body());
    }

    /**
     * @test
     */
    public function methods_can_be_overwritten(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator) {
            $configurator->put('r1', '/foo', RoutingTestController::class);
        });

        $test_response = $this->runKernel(
            $this->frontendRequest('/foo', [], 'POST')->withHeader(MethodOverride::HEADER, 'PUT')
        );

        $this->assertSame(RoutingTestController::static, $test_response->body());
    }

    /**
     * @test
     */
    public function the_response_is_prepared_and_fixed_for_common_mistakes(): void
    {
        // We only verify that the corresponding middleware gets called

        $this->webRouting(function (WebRoutingConfigurator $configurator) {
            $configurator->get('r1', '/foo', RoutingTestController::class);
        });

        $test_response = $this->runKernel($this->frontendRequest('/foo'));

        $test_response->assertHeader('content-length', (string)strlen(RoutingTestController::static));
    }

    /**
     * @test
     */
    public function content_negotiation_will_be_performed(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator) {
            $configurator->get('r1', '/foo', RoutingTestController::class);
        });

        $test_response = $this->runKernel(
            $this->frontendRequest('/foo')->withHeader(
                'Accept',
                'application/json, text/html;q=0.9'
            )
        );

        $this->assertSame(RoutingTestController::static, $test_response->body());
        $test_response->assertHeader('content-language', 'en');
        $test_response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

}
