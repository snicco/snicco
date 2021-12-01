<?php

declare(strict_types=1);

namespace Snicco\Testing;

use Closure;
use RuntimeException;
use Snicco\Http\Delegate;
use Snicco\View\ViewEngine;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Contracts\Middleware;
use Snicco\Routing\UrlGenerator;
use Snicco\Http\ResponseFactory;
use Snicco\Http\StatelessRedirector;
use Snicco\Contracts\RouteUrlGenerator;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Testing\TestDoubles\TestMagicLink;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Snicco\Testing\Assertable\MiddlewareTestResponse;

/**
 * Use this test to unit test your middlewares
 */
abstract class MiddlewareTestCase extends \PHPUnit\Framework\TestCase
{
    
    use CreatePsrRequests;
    
    protected ResponseFactory $response_factory;
    private UrlGenerator      $url;
    private Request           $request;
    private Closure           $next_middleware_response;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->response_factory = new ResponseFactory(
            $this->viewEngine(),
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            new StatelessRedirector($this->url = $this->urlGenerator(), $this->psrResponseFactory())
        );
        
        $this->next_middleware_response = fn(Response $response) => $response;
        $GLOBALS['test']['_next_middleware_called'] = false;
    }
    
    abstract protected function psrResponseFactory() :ResponseFactoryInterface;
    
    abstract protected function psrServerRequestFactory() :ServerRequestFactoryInterface;
    
    abstract protected function psrUriFactory() :UriFactoryInterface;
    
    abstract protected function psrStreamFactory() :StreamFactoryInterface;
    
    abstract protected function routeUrlGenerator() :RouteUrlGenerator;
    
    abstract protected function viewEngine() :ViewEngine;
    
    /**
     * Overwrite this function if you want to specify a custom response that should be returned by
     * the next middleware.
     */
    protected function setNextMiddlewareResponse(Closure $closure)
    {
        $this->next_middleware_response = $closure;
    }
    
    protected function runMiddleware(Middleware $middleware, Request $request) :Assertable\MiddlewareTestResponse
    {
        if (isset($this->request)) {
            unset($this->request);
        }
        
        $middleware->setResponseFactory($this->response_factory);
        $this->url->setRequestResolver(fn() => $request);
        
        /** @var Response $response */
        $response = $middleware->handle($request, $this->getNext());
        
        if (isset($response->received_request)) {
            $this->request = $response->received_request;
            unset($response->received_request);
        }
        
        return $this->transformResponse($response);
    }
    
    protected function urlGenerator() :UrlGenerator
    {
        return new UrlGenerator(
            $this->routeUrlGenerator(),
            new TestMagicLink(),
            false
        );
    }
    
    protected function receivedRequest() :Request
    {
        if ( ! isset($this->request)) {
            throw new RuntimeException('The next middleware was not called.');
        }
        
        return $this->request;
    }
    
    private function getNext() :Delegate
    {
        return new Delegate(
            new TestDoubles\TestDelegate($this->response_factory, $this->next_middleware_response)
        );
    }
    
    private function transformResponse(Response $response)
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

