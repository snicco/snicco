<?php

declare(strict_types=1);

namespace Snicco\Testing;

use Closure;
use RuntimeException;
use Snicco\Http\Delegate;
use Snicco\Http\Redirector;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Contracts\Middleware;
use Snicco\Routing\UrlGenerator;
use Snicco\Http\ResponseFactory;
use Tests\concerns\CreatePsrRequests;
use Snicco\Contracts\RouteUrlGenerator;
use Psr\Http\Message\UriFactoryInterface;
use Snicco\Contracts\ViewFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Testing\TestDoubles\TestMagicLink;
use Psr\Http\Message\ResponseFactoryInterface;
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
            $this->viewFactory(),
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            new Redirector($this->url = $this->urlGenerator(), $this->psrResponseFactory())
        );
        
        $this->next_middleware_response = fn(Response $response) => $response;
        $GLOBALS['test']['_next_middleware_called'] = false;
    }
    
    abstract protected function psrResponseFactory() :ResponseFactoryInterface;
    
    abstract protected function psrServerRequestFactory() :ServerRequestFactoryInterface;
    
    abstract protected function psrUriFactory() :UriFactoryInterface;
    
    abstract protected function psrStreamFactory() :StreamFactoryInterface;
    
    abstract protected function routeUrlGenerator() :RouteUrlGenerator;
    
    abstract protected function viewFactory() :ViewFactoryInterface;
    
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
    
    //protected function frontendRequest(string $method = 'GET', $uri = '/') :Request
    //{
    //    $method = strtoupper($method);
    //    $uri = $this->createUri($uri);
    //
    //    $request = new Request(
    //        $this->psrServerRequestFactory()->createServerRequest(
    //            $method,
    //            $uri,
    //            ['REQUEST_METHOD' => $method, 'SCRIPT_NAME' => 'index.php']
    //        )
    //    );
    //
    //    parse_str($request->getUri()->getQuery(), $query);
    //    return $request->withQueryParams($query);
    //}
    
    //protected function adminRequest(string $method, $menu_slug, $parent = 'admin.php') :Request
    //{
    //    $method = strtoupper($method);
    //    $url = $this->adminUrlTo($menu_slug, $parent);
    //    $uri = $this->createUri($url);
    //
    //    $request = new Request(
    //        $this->psrServerRequestFactory()->createServerRequest(
    //            $method,
    //            $uri,
    //            ['REQUEST_METHOD' => $method, 'SCRIPT_NAME' => "wp-admin/$parent"]
    //        )
    //    );
    //
    //    return $request->withQueryParams(['page' => $menu_slug]);
    //}
    
    //protected function adminAjaxRequest(string $method, string $action, array $data = []) :Request
    //{
    //    $method = strtoupper($method);
    //    $uri = $this->createUri($this->ajaxUrl($action));
    //
    //    $request = new Request(
    //        $this->psrServerRequestFactory()->createServerRequest(
    //            $method,
    //            $uri,
    //            ['REQUEST_METHOD' => $method, 'SCRIPT_NAME' => "wp-admin/admin.ajax.php"]
    //        )
    //    );
    //
    //    if ($request->isGet()) {
    //        return $request->withQueryParams(array_merge(['action' => $action], $data));
    //    }
    //
    //    return $request->withParsedBody(array_merge(['action' => $action], $data));
    //}
    
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
    
    //private function createUri($uri) :UriInterface
    //{
    //    if (is_string($uri)) {
    //        if ( ! Str::contains($uri, 'http')) {
    //            $uri = Url::addLeading($uri);
    //        }
    //    }
    //
    //    $uri = $uri instanceof UriInterface
    //        ? $uri
    //        : $this->psrUriFactory()->createUri($uri);
    //
    //    if ( ! $uri->getScheme()) {
    //        $uri = $uri->withScheme('https');
    //    }
    //
    //    if ( ! $uri->getHost()) {
    //        $uri = $uri->withHost(
    //            parse_url(
    //                'https://example.com/',
    //                PHP_URL_HOST
    //            )
    //        );
    //    }
    //
    //    return $uri;
    //}
    
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

