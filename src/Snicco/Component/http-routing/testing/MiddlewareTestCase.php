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
use Snicco\Component\HttpRouting\AbstractMiddleware;
use Snicco\Component\HttpRouting\Http\Psr7\DefaultResponseFactory;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\NextMiddleware;
use Snicco\Component\HttpRouting\Routing\AdminDashboard\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\InternalUrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

/**
 * @api
 */
abstract class MiddlewareTestCase extends TestCase
{

    use CreatesPsrRequests;

    private Routes $routes;
    private ResponseFactoryInterface $response_factory;
    private Request $request;
    private Closure $next_middleware_response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->next_middleware_response = function (Response $response) {
            return $response;
        };
        $GLOBALS['test']['_next_middleware_called'] = false;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['test']['_next_middleware_called']);
        parent::tearDown();
    }

    protected function psrServerRequestFactory(): ServerRequestFactoryInterface
    {
        return new Psr17Factory();
    }

    protected function psrUriFactory(): UriFactoryInterface
    {
        return new Psr17Factory();
    }

    final protected function withRoutes(array $routes)
    {
        $this->routes = new RouteCollection($routes);
    }

    /**
     * Overwrite this function if you want to specify a custom response that should be returned by
     * the next middleware.
     */
    final protected function withNextMiddlewareResponse(Closure $closure)
    {
        $this->next_middleware_response = $closure;
    }

    final protected function runMiddleware(MiddlewareInterface $middleware, Request $request): MiddlewareTestResponse
    {
        if (isset($this->request)) {
            unset($this->request);
        }

        $pimple = new Container();
        $url = $this->newUrlGenerator(
            $this->routes ?? new RouteCollection([]),
            UrlGenerationContext::fromRequest($request)
        );
        $response_factory = $this->newResponseFactory($url);
        $this->response_factory = $response_factory;

        if ($middleware instanceof AbstractMiddleware) {
            if (!$pimple->offsetExists(ResponseFactory::class)) {
                $pimple[ResponseFactory::class] = $response_factory;
            }
            if (!$pimple->offsetExists(Redirector::class)) {
                $pimple[Redirector::class] = $response_factory;
            }
            if (!$pimple->offsetExists(UrlGenerator::class)) {
                $pimple[UrlGenerator::class] = $url;
            }
            $middleware->setContainer(new \Pimple\Psr11\Container($pimple));
        }

        /** @var Response $response */
        $response = $middleware->process($request, $this->getNext());

        if (isset($response->received_request)) {
            $this->request = $response->received_request;
            unset($response->received_request);
        }

        if (isset($this->response_factory)) {
            unset($this->response_factory);
        }

        return $this->transformResponse($response);
    }

    private function newUrlGenerator(Routes $routes, UrlGenerationContext $context): UrlGenerator
    {
        return new InternalUrlGenerator(
            $routes,
            $context,
            WPAdminArea::fromDefaults(),
            new RFC3986Encoder()
        );
    }

    private function newResponseFactory(UrlGenerator $url_generator): ResponseFactory
    {
        return new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $url_generator,
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

    private function getNext(): NextMiddleware
    {
        return new NextMiddleware(
            new TestDelegate($this->response_factory, $this->next_middleware_response)
        );
    }

    private function transformResponse(ResponseInterface $response)
    {
        if (!$response instanceof MiddlewareTestResponse) {
            $response = new MiddlewareTestResponse(
                $response,
                $GLOBALS['test']['_next_middleware_called']
            );
        }

        $GLOBALS['test']['_next_middleware_called'] = false;

        return $response;
    }

    final protected function getReceivedRequest(): Request
    {
        if (!isset($this->request)) {
            throw new RuntimeException('The next middleware was not called.');
        }

        return $this->request;
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

}
