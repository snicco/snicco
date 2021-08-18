<?php

declare(strict_types=1);

namespace Tests;

use Snicco\Http\Delegate;
use Snicco\Session\Session;
use Tests\stubs\TestRequest;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Snicco\Http\ResponseFactory;
use Snicco\Routing\UrlGenerator;
use Tests\helpers\AssertsResponse;
use Snicco\Routing\RouteCollection;
use Tests\helpers\CreateUrlGenerator;
use Psr\Http\Message\ResponseInterface;
use Tests\helpers\CreateRouteCollection;
use Snicco\Session\Drivers\ArraySessionDriver;

abstract class MiddlewareTestCase extends UnitTest
{
    
    use AssertsResponse;
    use CreateUrlGenerator;
    use CreateRouteCollection;
    
    protected Delegate        $route_action;
    protected TestRequest     $request;
    protected ResponseFactory $response_factory;
    protected UrlGenerator    $generator;
    protected RouteCollection $routes;
    
    protected function runMiddleware(Request $request = null, Delegate $delegate = null) :ResponseInterface
    {
        
        $m = $this->newMiddleware();
        $m->setResponseFactory($this->response_factory);
        
        $r = $request ?? $this->request;
        $d = $delegate ?? $this->route_action;
        
        return $m->handle($r, $d);
        
    }
    
    abstract public function newMiddleware() :Middleware;
    
    protected function newSession(int $lifetime = 10) :Session
    {
        return new Session(new ArraySessionDriver($lifetime));
    }
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->response_factory = $this->createResponseFactory();
        $this->request = TestRequest::from('GET', '/foo');
        
    }
    
}