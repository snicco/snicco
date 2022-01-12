<?php

declare(strict_types=1);

namespace Snicco\Testing;

use Closure;
use RuntimeException;
use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Routing\Route\Routes;
use Snicco\Core\Contracts\Redirector;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Shared\ContainerAdapter;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\UriFactoryInterface;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Http\DefaultResponseFactory;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Snicco\Core\Routing\Route\RouteCollection;
use Snicco\Core\Routing\UrlGenerator\UrlGenerator;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Snicco\Testing\Assertable\MiddlewareTestResponse;
use Snicco\Core\Routing\UrlGenerator\UrlGenerationContext;
use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;

/**
 * This class can be used to unit test custom middleware
 *
 * @api
 */
abstract class MiddlewareTestCase extends \PHPUnit\Framework\TestCase
{
    
    use CreatePsrRequests;
    
    protected ContainerAdapter   $container;
    private ResponseFactory      $response_factory;
    private Routes               $routes;
    private UrlGenerator         $url;
    private Request              $request;
    private Closure              $next_middleware_response;
    private UrlGenerationContext $context;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->app_domain = 'example.com';
        $this->routes = new RouteCollection();
        $this->url = $this->urlGenerator();
        $this->response_factory = $this->newResponseFactory($this->url);
        $this->container = $this->createContainer();
        $this->next_middleware_response = function (Response $response) { return $response; };
        $GLOBALS['test']['_next_middleware_called'] = false;
    }
    
    protected function routes() :Routes
    {
        return $this->routes;
    }
    
    protected function newResponseFactory(UrlGenerator $url_generator = null) :ResponseFactory
    {
        $url_generator = $url_generator ?? $this->url;
        
        return new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $url_generator,
        );
    }
    
    abstract protected function psrResponseFactory() :Psr17ResponseFactory;
    
    abstract protected function psrServerRequestFactory() :ServerRequestFactoryInterface;
    
    abstract protected function psrUriFactory() :UriFactoryInterface;
    
    abstract protected function psrStreamFactory() :StreamFactoryInterface;
    
    abstract protected function urlGenerator(UrlGenerationContext $context = null) :UrlGenerator;
    
    /**
     * Overwrite this function if you want to specify a custom response that should be returned by
     * the next middleware.
     */
    protected function setNextMiddlewareResponse(Closure $closure)
    {
        $this->next_middleware_response = $closure;
    }
    
    protected function runMiddleware(MiddlewareInterface $middleware, Request $request) :Assertable\MiddlewareTestResponse
    {
        if (isset($this->request)) {
            unset($this->request);
        }
        
        if ($middleware instanceof AbstractMiddleware) {
            if ( ! $this->container->has(ResponseFactory::class)) {
                $this->container[ResponseFactory::class] = $this->response_factory;
            }
            if ( ! $this->container->has(Redirector::class)) {
                $this->container[Redirector::class] = $this->response_factory;
            }
            if ( ! $this->container->has(UrlGenerator::class)) {
                $this->container[UrlGenerator::class] = $this->url;
            }
            $middleware->setContainer($this->container);
        }
        
        /** @var Response $response */
        $response = $middleware->process($request, $this->getNext());
        
        if (isset($response->received_request)) {
            $this->request = $response->received_request;
            unset($response->received_request);
        }
        
        return $this->transformResponse($response);
    }
    
    protected function receivedRequest() :Request
    {
        if ( ! isset($this->request)) {
            throw new RuntimeException('The next middleware was not called.');
        }
        
        return $this->request;
    }
    
    protected function redirector() :Redirector
    {
        return $this->response_factory;
    }
    
    abstract protected function createContainer() :ContainerAdapter;
    
    protected function setUrlGenerationContext(UrlGenerationContext $context)
    {
        $this->context = $context;
        $this->url = $this->urlGenerator($context);
        $this->response_factory = $this->newResponseFactory();
    }
    
    protected function urlGenerationContext() :UrlGenerationContext
    {
        return $this->context ??= UrlGenerationContext::forConsole('localhost.com');
    }
    
    private function getNext() :Delegate
    {
        return new Delegate(
            new TestDoubles\TestDelegate($this->response_factory, $this->next_middleware_response)
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

