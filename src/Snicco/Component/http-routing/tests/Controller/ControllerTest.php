<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Controller;

use LogicException;
use Pimple\Container;
use RuntimeException;
use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\ResponseUtils;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

/**
 * @internal
 */
final class ControllerTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function test_controllers_have_access_to_response_utils(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $router): void {
            $router->get('r1', '/foo', ResponseUtilsTestController::class);
        });

        $response = $this->runNewPipeline($this->frontendRequest('https://foo.com/foo'));

        $response->assertLocation('https://foo.com/foo');
    }

    /**
     * @test
     */
    public function test_exception_if_current_request_not_set(): void
    {
        $container = new Container();
        $container[ResponseFactory::class] = fn (): ResponseFactory => $this->createResponseFactory();
        $container[UrlGenerator::class] = fn (): UrlGenerator => $this->generator();

        $controller = new ResponseUtilsTestController();
        $controller->setContainer(new \Pimple\Psr11\Container($container));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Current request');

        $controller->responseWith()
            ->refresh();
    }

    /**
     * @test
     */
    public function test_exception_if_url_generator_not_set(): void
    {
        $container = new Container();
        $container[ResponseFactory::class] = fn (): ResponseFactory => $this->createResponseFactory();

        $controller = new ResponseUtilsTestController();
        $controller->setContainer(new \Pimple\Psr11\Container($container));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The UrlGenerator is not bound');

        $controller->responseWith()
            ->refresh();
    }

    /**
     * @test
     */
    public function test_exception_if_response_factory_not_set(): void
    {
        $container = new Container();
        $container[UrlGenerator::class] = fn (): UrlGenerator => $this->generator();

        $controller = new ResponseUtilsTestController();
        $controller->setContainer(new \Pimple\Psr11\Container($container));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The ResponseFactory is not bound');

        $controller->responseWith()
            ->refresh();
    }
}

final class ResponseUtilsTestController extends Controller
{
    public function __invoke()
    {
        return $this->respondWith()
            ->refresh();
    }

    public function responseWith(): ResponseUtils
    {
        return parent::respondWith();
    }
}
