<?php

declare(strict_types=1);

namespace Snicco\Testing;

use Closure;
use RuntimeException;
use Snicco\Component\Core\DIContainer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Middleware\Delegate;
use Snicco\Component\HttpRouting\Http\ResponseFactory;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;
use Snicco\Component\HttpRouting\Testing\CreatePsrRequests;
use Snicco\Bundle\Testing\Assertable\MiddlewareTestResponse;
use Snicco\Component\HttpRouting\Http\DefaultResponseFactory;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\AdminDashboard\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;
use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\InternalUrlGenerator;

/**
 * @api
 */
abstract class MiddlewareTestCase extends \PHPUnit\Framework\TestCase
{
    
    use CreatePsrRequests;
    
    private Routes          $routes;
    private ResponseFactory $response_factory;
    private Request         $request;
    private Closure         $next_middleware_response;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->next_middleware_response = function (Response $response) { return $response; };
        $GLOBALS['test']['_next_middleware_called'] = false;
    }
    
    protected function tearDown() :void
    {
        unset($GLOBALS['test']['_next_middleware_called']);
        parent::tearDown();
    }
    
    abstract protected function psrResponseFactory() :Psr17ResponseFactory;
    
    abstract protected function psrServerRequestFactory() :ServerRequestFactoryInterface;
    
    abstract protected function psrUriFactory() :UriFactoryInterface;
    
    abstract protected function psrStreamFactory() :StreamFactoryInterface;
    
    abstract protected function createContainer() :DIContainer;
    
    protected final function withRoutes(array $routes)
    {
        $this->routes = new RouteCollection($routes);
    }
    
    /**
     * Overwrite this function if you want to specify a custom response that should be returned by
     * the next middleware.
     */
    protected function withNextMiddlewareResponse(Closure $closure)
    {
        $this->next_middleware_response = $closure;
    }
    
    final protected function runMiddleware(MiddlewareInterface $middleware, Request $request) :Snicco\Bundle\Testing\Assertable\MiddlewareTestResponse
    {
        if (isset($this->request)) {
            unset($this->request);
        }
        
        if ($middleware instanceof AbstractMiddleware) {
            $container = $this->createContainer();
            $url = $this->newUrlGenerator(
                $this->routes ?? new RouteCollection([]),
                UrlGenerationContext::fromRequest($request)
            );
            $response_factory = $this->newResponseFactory($url);
            $this->response_factory = $response_factory;
            
            if ( ! $container->has(ResponseFactory::class)) {
                $container[ResponseFactory::class] = $response_factory;
            }
            if ( ! $container->has(Redirector::class)) {
                $container[Redirector::class] = $response_factory;
            }
            if ( ! $container->has(UrlGenerator::class)) {
                $container[UrlGenerator::class] = $url;
            }
            $middleware->setContainer($container);
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
    
    final protected function getReceivedRequest() :Request
    {
        if ( ! isset($this->request)) {
            throw new RuntimeException('The next middleware was not called.');
        }
        
        return $this->request;
    }
    
    final protected function getRedirector() :Redirector
    {
        if ( ! isset($this->response_factory)) {
            throw new RuntimeException(
                'You can only retrieve the redirector from inside the next_response closure'
            );
        }
        return $this->response_factory;
    }
    
    final protected function getResponseFactory() :ResponseFactory
    {
        if ( ! isset($this->response_factory)) {
            throw new RuntimeException(
                "You can only retrieve the response factory from inside the next_response closure"
            );
        }
        return $this->response_factory;
    }
    
    private function newResponseFactory(UrlGenerator $url_generator) :ResponseFactory
    {
        return new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $url_generator,
        );
    }
    
    private function newUrlGenerator(Routes $routes, UrlGenerationContext $context) :UrlGenerator
    {
        return new InternalUrlGenerator(
            $routes,
            $context,
            WPAdminArea::fromDefaults(),
            new RFC3986Encoder()
        );
    }
    
    private function getNext() :Delegate
    {
        return new Delegate(
            new Snicco\Bundle\Testing\TestDoubles\TestDelegate(
                $this->response_factory,
                $this->next_middleware_response
            )
        );
    }
    
    private function transformResponse(ResponseInterface $response)
    {
        if ( ! $response instanceof MiddlewareTestResponse) {
            $response = new MiddlewareTestResponse(
                $response,
                $GLOBALS['test']['_next_middleware_called']
            
            );
        }
        
        $GLOBALS['test']['_next_middleware_called'] = false;
        
        return $response;
    }
    
}

