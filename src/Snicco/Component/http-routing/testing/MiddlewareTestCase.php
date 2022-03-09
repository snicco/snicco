<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Testing;

use Closure;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\ResponseUtils;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\Generator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

use function call_user_func;

abstract class MiddlewareTestCase extends TestCase
{
    use CreatesPsrRequests;

    private Routes $routes;

    private ResponseFactory $response_factory;

    /**
     * @var Closure(Response,Request):Response
     */
    private Closure $next_middleware_response;

    private bool $next_called = false;

    private ?Request $received_request_by_next_middleware = null;

    private ?ResponseUtils $response_utils = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->next_middleware_response = function (Response $response): Response {
            return $response;
        };
        $this->response_factory = $this->newResponseFactory();
    }

    protected function psrServerRequestFactory(): ServerRequestFactoryInterface
    {
        return new Psr17Factory();
    }

    protected function psrUriFactory(): UriFactoryInterface
    {
        return new Psr17Factory();
    }

    protected function psrResponseFactory(): ResponseFactoryInterface
    {
        return new Psr17Factory();
    }

    protected function psrStreamFactory(): StreamFactoryInterface
    {
        return new Psr17Factory();
    }

    /**
     * @param Route[] $routes
     */
    final protected function withRoutes(array $routes): void
    {
        $this->routes = new RouteCollection($routes);
    }

    /**
     * @param Closure(Response,Request):Response $closure
     */
    final protected function withNextMiddlewareResponse(Closure $closure): void
    {
        $this->next_middleware_response = $closure;
    }

    final protected function runMiddleware(MiddlewareInterface $middleware, Request $request): MiddlewareTestResult
    {
        $this->next_called = false;
        $this->received_request_by_next_middleware = null;

        $pimple = new Container();
        $url = $this->newUrlGenerator(
            $this->routes ?? new RouteCollection([]),
            new UrlGenerationContext($request->getUri()->getHost())
        );

        if ($middleware instanceof Middleware) {
            if (! $pimple->offsetExists(ResponseFactory::class)) {
                $pimple[ResponseFactory::class] = $this->response_factory;
            }
            if (! $pimple->offsetExists(UrlGenerator::class)) {
                $pimple[UrlGenerator::class] = $url;
            }
            $middleware->setContainer(new \Pimple\Psr11\Container($pimple));
        }

        $this->response_utils = new ResponseUtils(
            $url,
            $this->response_factory,
            $request
        );

        /** @var Response $response */
        $response = $middleware->process($request, $this->next());

        return new MiddlewareTestResult(
            $response,
            $this->next_called
        );
    }

    final protected function receivedRequest(): Request
    {
        if (! isset($this->received_request_by_next_middleware)) {
            throw new RuntimeException('The next middleware was not called.');
        }

        return $this->received_request_by_next_middleware;
    }

    final protected function responseFactory(): ResponseFactory
    {
        return $this->response_factory;
    }

    final protected function responseUtils(): ResponseUtils
    {
        if (! isset($this->response_utils)) {
            throw new LogicException('response utils can only be accessed during the next middleware response.');
        }

        return $this->response_utils;
    }

    private function newUrlGenerator(Routes $routes, UrlGenerationContext $context): UrlGenerator
    {
        return new Generator(
            $routes,
            $context,
            WPAdminArea::fromDefaults(),
            new RFC3986Encoder()
        );
    }

    private function newResponseFactory(): ResponseFactory
    {
        return new ResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
        );
    }

    private function next(): NextMiddleware
    {
        $func = function (Request $request): ResponseInterface {
            $response = call_user_func(
                $this->next_middleware_response,
                $this->response_factory->createResponse(),
                $request
            );
            $this->received_request_by_next_middleware = $request;
            $this->next_called = true;

            return $response;
        };

        return new NextMiddleware($func);
    }
}
