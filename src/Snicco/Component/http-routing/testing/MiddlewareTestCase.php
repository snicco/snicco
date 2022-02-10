<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Testing;

use Closure;
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
use Snicco\Component\HttpRouting\Middleware;
use Snicco\Component\HttpRouting\Http\Psr7\DefaultResponseFactory;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\NextMiddleware;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;

use function call_user_func;

/**
 * @api
 */
abstract class MiddlewareTestCase extends TestCase
{

    use CreatesPsrRequests;

    private Routes $routes;
    private DefaultResponseFactory $response_factory;

    /**
     * @var Closure(Response,Request):Response
     */
    private Closure $next_middleware_response;
    private bool $next_called = false;
    private ?Request $received_request_by_next_middleware = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->next_middleware_response = function (Response $response): Response {
            return $response;
        };
    }

    protected function psrServerRequestFactory(): ServerRequestFactoryInterface
    {
        return new Psr17Factory();
    }

    protected function psrUriFactory(): UriFactoryInterface
    {
        return new Psr17Factory();
    }

    /**
     * @param array<Route> $routes
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

    final protected function runMiddleware(MiddlewareInterface $middleware, Request $request): MiddlewareTestResponse
    {
        $this->next_called = false;
        $this->received_request_by_next_middleware = null;

        $pimple = new Container();
        $url = $this->newUrlGenerator(
            $this->routes ?? new RouteCollection([]),
            UrlGenerationContext::fromRequest($request)
        );
        $response_factory = $this->newResponseFactory($url);
        $this->response_factory = $response_factory;

        if ($middleware instanceof Middleware) {
            if (!$pimple->offsetExists(ResponseFactory::class)) {
                $pimple[ResponseFactory::class] = $response_factory;
            }
            if (!$pimple->offsetExists(Redirector::class)) {
                $pimple[Redirector::class] = $response_factory;
            }
            if (!$pimple->offsetExists(UrlGeneratorInterface::class)) {
                $pimple[UrlGeneratorInterface::class] = $url;
            }
            $middleware->setContainer(new \Pimple\Psr11\Container($pimple));
        }

        /** @var Response $response */
        $response = $middleware->process($request, $this->next());

        unset($this->response_factory);

        return new MiddlewareTestResponse(
            $response,
            $this->next_called
        );
    }

    protected function psrResponseFactory(): ResponseFactoryInterface
    {
        return new Psr17Factory();
    }

    protected function psrStreamFactory(): StreamFactoryInterface
    {
        return new Psr17Factory();
    }

    final protected function getReceivedRequest(): Request
    {
        if (!isset($this->received_request_by_next_middleware)) {
            throw new RuntimeException('The next middleware was not called.');
        }

        return $this->received_request_by_next_middleware;
    }

    final protected function getRedirector(): Redirector
    {
        if (!isset($this->response_factory)) {
            throw new RuntimeException(
                'You can only retrieve the redirector from inside the next_response closure'
            );
        }
        return $this->response_factory;
    }

    final protected function getResponseFactory(): ResponseFactory
    {
        if (!isset($this->response_factory)) {
            throw new RuntimeException(
                'You can only retrieve the response factory from inside the next_response closure'
            );
        }
        return $this->response_factory;
    }

    private function newUrlGenerator(Routes $routes, UrlGenerationContext $context): UrlGeneratorInterface
    {
        return new UrlGenerator(
            $routes,
            $context,
            WPAdminArea::fromDefaults(),
            new RFC3986Encoder()
        );
    }

    private function newResponseFactory(UrlGeneratorInterface $url_generator): DefaultResponseFactory
    {
        return new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $url_generator,
        );
    }

    private function next(): NextMiddleware
    {
        $func = function (Request $request): ResponseInterface {
            $response = call_user_func($this->next_middleware_response, $this->response_factory->make(), $request);
            $this->received_request_by_next_middleware = $request;
            $this->next_called = true;
            return $response;
        };

        return new NextMiddleware($func);
    }

}
